<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Performer alert emails for people who asked for them (leads with a performer interest, see inc/leads.php).
 *
 *   php cron/send-alerts.php --dry-run          show what would be sent, send nothing, change nothing
 *   php cron/send-alerts.php                    send (default: at most 40 emails per run, 1 second apart)
 *   php cron/send-alerts.php --limit=100 --pause-ms=500 --max-performers=60
 *
 * Rules: a lead gets at most one alert per performer per 7 days (lead_interests.notified_at), one email per run that lists
 * up to 3 performers with up to 5 upcoming events that have tickets (name, date, venue, city, from-price, link), every email
 * has an unsubscribe link, unsubscribed leads are never mailed, and an interest is marked as notified only after the email was
 * accepted by the mail server. Schedule: once a day (see CONTRIBUTING.md).
 */
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/leads-core.php';
require_once __DIR__ . '/../inc/lead-mail.php';

$opts = getopt('', ['dry-run', 'limit::', 'pause-ms::', 'max-performers::', 'help']);
if (isset($opts['help'])) {
    echo "Usage: php cron/send-alerts.php [--dry-run] [--limit=40] [--pause-ms=1000] [--max-performers=40]\n";
    exit(0);
}
$dry = isset($opts['dry-run']);
$limit = max(1, min(500, (int) ($opts['limit'] ?? 40)));
$pauseMs = max(0, min(10000, (int) ($opts['pause-ms'] ?? 1000)));
$maxPerformers = max(1, min(200, (int) ($opts['max-performers'] ?? 40)));
$perPerformer = 5;
$perMail = 3;
$minDays = 7;

$db = MYSQLI;
$rows = [];
$res = $db->query("SELECT li.id AS interest_row, li.interest_id, li.interest_name, l.id AS lead_id, l.email, l.fname, l.lname
    FROM lead_interests li JOIN leads l ON l.id = li.lead_id
    WHERE li.interest_type = 'performer' AND li.interest_id > 0 AND l.unsubscribed_at IS NULL
      AND (li.notified_at IS NULL OR li.notified_at < NOW() - INTERVAL $minDays DAY)
    ORDER BY (li.notified_at IS NULL) DESC, li.notified_at, li.id LIMIT 1000");
while ($res && ($r = $res->fetch_assoc())) $rows[] = $r;

// Fetch each performer's upcoming events once (bounded), keep only those with tickets.
$eventsFor = [];
$performers = [];
foreach ($rows as $r) { $performers[(int) $r['interest_id']] = true; }
$performers = array_slice(array_keys($performers), 0, $maxPerformers);
foreach ($performers as $pid) {
    [, $resp] = getPerformerPageEvents($pid, 20);
    $list = [];
    foreach ($resp['results'] ?? [] as $e) {
        if (empty($e['_metadata']['hasTickets']) || !empty($e['isTest'])) continue;
        $low = $e['pricingInfo']['lowPrice']['value'] ?? null;
        $place = trim(($e['city']['text']['name'] ?? '') . (isset($e['stateProvince']['text']['abbr']) ? ', ' . $e['stateProvince']['text']['abbr'] : ''), ', ');
        $list[] = [
            'name' => (string) ($e['text']['name'] ?? ''),
            'date' => trim(($e['date']['text']['date'] ?? '') . (isset($e['date']['text']['time']) ? ' at ' . $e['date']['text']['time'] : '')),
            'venue' => (string) ($e['venue']['text']['name'] ?? ''),
            'place' => $place,
            'from' => ($low !== null && (float) $low > 0) ? '$' . number_format((float) $low, 0) : '',
            'url' => HOME_URL . '/event/' . createSlug((string) ($e['text']['name'] ?? ''), (int) ($e['id'] ?? 0)),
        ];
        if (count($list) >= $perPerformer) break;
    }
    $eventsFor[$pid] = $list;
}

// One email per lead, up to $perMail performers in it.
$byLead = [];
foreach ($rows as $r) {
    $pid = (int) $r['interest_id'];
    if (empty($eventsFor[$pid])) continue;
    $byLead[(int) $r['lead_id']]['lead'] = $r;
    if (count($byLead[(int) $r['lead_id']]['items'] ?? []) < $perMail) $byLead[(int) $r['lead_id']]['items'][] = $r;
}

$sent = 0; $failed = 0; $skipped = count($rows) - array_sum(array_map(function ($l) { return count($l['items']); }, $byLead));
foreach ($byLead as $leadId => $entry) {
    if ($sent >= $limit) break;
    $r = $entry['lead'];
    $groups = [];
    foreach ($entry['items'] as $item) {
        $pid = (int) $item['interest_id'];
        $groups[] = ['name' => (string) $item['interest_name'], 'url' => HOME_URL . '/artist/' . createSlug((string) $item['interest_name'], $pid), 'events' => $eventsFor[$pid]];
    }
    if ($dry) {
        echo 'DRY RUN would email ' . $r['email'] . ': ' . implode(', ', array_map(function ($g) { return $g['name'] . ' (' . count($g['events']) . ' events)'; }, $groups)) . "\n";
        $sent++;
        continue;
    }
    $token = soLeadEnsureToken($leadId);
    if ($token === '') { $failed++; continue; }
    $lead = ['email' => $r['email'], 'fname' => $r['fname'], 'lname' => $r['lname'], 'token' => $token];
    [$subject, $html, $text] = soLeadAlertMessage($lead, $groups);
    if (soLeadSendMail($lead, $subject, $html, $text, soLeadUnsubscribeUrl($token))) {
        $ids = array_map(function ($i) { return (int) $i['interest_row']; }, $entry['items']);
        $db->query('UPDATE lead_interests SET notified_at = NOW() WHERE id IN (' . implode(',', $ids) . ')');
        $db->query('UPDATE leads SET notified_at = NOW() WHERE id = ' . (int) $leadId);
        $sent++;
    } else {
        $failed++;
    }
    if ($pauseMs > 0) usleep($pauseMs * 1000);
}

echo ($dry ? 'Dry run: ' : '') . "candidates " . count($rows) . ", performers checked " . count($performers) . ", emails " . ($dry ? 'planned ' : 'sent ') . $sent . ", failed $failed, interests without current events $skipped\n";
