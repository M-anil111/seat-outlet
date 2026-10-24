<?php
/**
 * Where a removed event page goes. event_redirects remembers, for every event we have seen, its last performer, category, canonical
 * slug, name and date, so that when TicketNetwork drops the event its old URL redirects to the performer's page, then to the category hub,
 * and only an event we never saw answers 404. Rows come from three places, so an event nobody opened is covered too:
 *   'visit'   the event page itself (soEventRemember)
 *   'catalog' the events listed on a performer page (getPerformerPageEvents)
 *   'bulk'    the bulk importer (cron/tn-bulk-import.php), once TN_BULK_ENABLED=1
 * The bulk importer leaves the slug empty (registering a slug per catalog event would write ~100k rows); a visit or a performer page fills it.
 * A refresh never blanks what an earlier row knew: an empty performer or category leaves the stored value alone.
 */

/** The values event_redirects keeps for one catalog event, or null when it has no usable id. */
function soEventRedirectRow(array $event, bool $withSlug = true): ?array {
    $id = (int) ($event['id'] ?? 0);
    if ($id <= 0 || $id > 2000000000) return null;
    $name = (string) ($event['text']['name'] ?? $event['name'] ?? '');
    $date = (string) ($event['date']['date'] ?? '');
    $ts = preg_match('/^\d{4}-\d{2}-\d{2}/', $date) ? strtotime(substr($date, 0, 10) . ' 00:00:00') : false;
    $slug = '';
    if ($name !== '' && $withSlug) { try { $slug = soEventSlug($event); } catch (\Throwable $e) { $slug = ''; } }
    return [
        'id'        => $id,
        'perf_id'   => (int) ($event['performers'][0]['id'] ?? 0),
        'perf_name' => mb_substr((string) ($event['performers'][0]['name'] ?? ''), 0, 255),
        'path'      => mb_substr((string) ($event['defaultCategory']['path'] ?? ''), 0, 80),
        'slug'      => mb_substr($slug, 0, 255),
        'name'      => mb_substr($name, 0, 255),
        'date'      => $ts ? date('Y-m-d H:i:s', $ts) : null,
    ];
}

/** Stores or refreshes the rows for these events in one statement per 200. Returns how many events were offered; never throws. */
function soEventRememberMany(array $events, string $source = 'visit'): int {
    if (!defined('MYSQLI') || !(MYSQLI instanceof mysqli)) return 0;
    $source = in_array($source, ['visit', 'catalog', 'bulk'], true) ? $source : 'visit';
    $rows = [];
    foreach ($events as $e) {
        $r = is_array($e) ? soEventRedirectRow($e, $source !== 'bulk') : null;   // bulk: no slug, soEventSlug() registers one per event and the catalog holds ~100k
        if ($r) $rows[$r['id']] = $r;
    }
    $rows = array_values($rows);
    $sql = 'ON DUPLICATE KEY UPDATE'
        . ' performer_name = IF(VALUES(performer_id) > 0, VALUES(performer_name), performer_name),'
        . ' performer_id = IF(VALUES(performer_id) > 0, VALUES(performer_id), performer_id),'
        . ' category_path = IF(VALUES(category_path) <> \'\', VALUES(category_path), category_path),'
        . ' event_slug = COALESCE(NULLIF(VALUES(event_slug), \'\'), event_slug),'
        . ' event_name = COALESCE(NULLIF(VALUES(event_name), \'\'), event_name),'
        . ' event_date = COALESCE(VALUES(event_date), event_date),'
        . ' updated_at = NOW()';
    try {
        foreach (array_chunk($rows, 200) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '(?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'));
            $stmt = MYSQLI->prepare('INSERT INTO event_redirects (event_id, performer_id, performer_name, category_path, event_slug, event_name, event_date, source, created_at, updated_at) VALUES ' . $ph . ' ' . $sql);
            if (!$stmt) return 0;
            $vals = []; 
            foreach ($chunk as $r) array_push($vals, $r['id'], $r['perf_id'], $r['perf_name'], $r['path'], $r['slug'], $r['name'], $r['date'], $source);
            $stmt->bind_param(str_repeat('iissssss', count($chunk)), ...$vals);
            $stmt->execute();
            $stmt->close();
        }
    } catch (\Throwable $e) {
        return 0;   // a missing table or a database hiccup must never break a page or an import
    }
    return count($rows);
}

/** What is known about an event that is gone: ['performer_id', 'performer_name', 'category_path'] from event_redirects, else from the bulk copy; null when neither has it. */
function soEventRedirectLookup(int $eventId): ?array {
    if ($eventId <= 0) return null;
    $row = soDbRetry(function ($db) use ($eventId) {
        $stmt = $db->prepare('SELECT performer_id, performer_name, category_path FROM event_redirects WHERE event_id = ?');
        if (!$stmt) return null;
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $r ?: null;
    });
    if ($row && ((int) $row['performer_id'] > 0 || (string) $row['category_path'] !== '')) return $row;
    // Not remembered (or remembered without a performer): the bulk copy may know it.
    $bulk = soDbRetry(function ($db) use ($eventId) {
        $stmt = $db->prepare('SELECT performers, category_path FROM tn_bulk_events WHERE event_id = ?');
        if (!$stmt) return null;
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $r ?: null;
    });
    if (!$bulk) return $row ?: null;
    $p = json_decode((string) $bulk['performers'], true);
    $p = is_array($p) && is_array($p[0] ?? null) ? $p[0] : [];
    return ['performer_id' => (int) ($p['id'] ?? 0), 'performer_name' => (string) ($p['name'] ?? ''), 'category_path' => (string) $bulk['category_path']];
}

/** The page a removed event sends visitors to, from what is known: performer page, else category hub, else '/buy-tickets-online' (the "nothing known" value: callers answer 404). Never an /event/ URL, so no loop. */
function soEventRedirectDestination(int $perfId, string $perfName, string $path): string {
    if ($perfId > 0 && $perfName !== '') return '/artist/' . soSlug('performer', $perfName, $perfId);
    if (strpos($path, '.1988.') !== false) return '/game-day-tickets';
    if (strpos($path, '.1989.') !== false) return '/buy-broadway-tickets';
    if (strpos($path, '.1877.') !== false || strpos($path, '.2065.') !== false) return '/upcoming-music-festivals';
    if (strpos($path, '.1986.') !== false) return '/concert-tickets-for-sale';
    return '/buy-tickets-online';
}
