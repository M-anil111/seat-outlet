<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Price-drop emails for people who asked "alert me if the price drops" on an event page (lead_interests.alert_kind = 'price').
 *
 *   php cron/send-price-alerts.php --dry-run          show what would be sent, send nothing, change nothing
 *   php cron/send-price-alerts.php                    send (default: at most 60 emails per run, 1 second apart)
 *   php cron/send-price-alerts.php --limit=100 --pause-ms=500 --max-events=150
 *
 * Rules: the event's current lowest listed price must be at least 10% below the baseline (the price the visitor saw, then the
 * price last mailed); one email per lead per run; the same interest is not mailed again for 3 days; unsubscribed leads are never
 * mailed; events that are over or have no tickets are skipped; an interest is marked only after the mail server accepted the
 * message, and its baseline then moves to the price that was mailed. Schedule: every 2 to 4 hours (see docs/launch-checklist.md).
 */
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/leads-core.php';
require_once __DIR__ . '/../inc/lead-mail.php';

$opts = getopt('', ['dry-run', 'limit::', 'pause-ms::', 'max-events::', 'help']);
if (isset($opts['help'])) {
    echo "Usage: php cron/send-price-alerts.php [--dry-run] [--limit=60] [--pause-ms=1000] [--max-events=100]\n";
    exit(0);
}
$dry = isset($opts['dry-run']);
$limit = max(1, min(500, (int) ($opts['limit'] ?? 60)));
$pauseMs = max(0, min(10000, (int) ($opts['pause-ms'] ?? 1000)));
$maxEvents = max(1, min(400, (int) ($opts['max-events'] ?? 100)));
const SO_PRICE_DROP = 0.90;   // alert at 10% below the baseline

$db = MYSQLI;
$rows = [];
try {
    $res = $db->query("SELECT li.id AS interest_row, li.interest_id, li.interest_name, li.baseline_price, l.id AS lead_id, l.email, l.fname, l.lname
        FROM lead_interests li JOIN leads l ON l.id = li.lead_id
        WHERE li.interest_type = 'event' AND li.alert_kind = 'price' AND li.baseline_price > 0 AND li.interest_id > 0 AND l.unsubscribed_at IS NULL
          AND (li.notified_at IS NULL OR li.notified_at < NOW() - INTERVAL 3 DAY)
        ORDER BY li.id LIMIT 2000");
} catch (\mysqli_sql_exception $e) {
    fwrite(STDERR, "Price alerts need migration 0039 (php db/migrate.php): " . $e->getMessage() . "\n");
    exit(1);
}
while ($res && ($r = $res->fetch_assoc())) $rows[] = $r;

// Current lowest price per distinct event (cached by tnRequest), bounded per run.
$now = [];
$ids = array_slice(array_values(array_unique(array_map(function ($r) { return (int) $r['interest_id']; }, $rows))), 0, $maxEvents);
foreach ($ids as $eid) {
    $e = getTnEventById($eid);
    if (!is_array($e) || tnEntityMissing($e) || tnEntityUnavailable($e) || empty($e['text']['name'])) continue;
    $date = (string) ($e['date']['date'] ?? '');
    if ($date !== '' && strtotime($date) !== false && strtotime($date) < strtotime('today')) continue;     // over
    if (empty($e['_metadata']['hasTickets'])) continue;
    $low = $e['pricingInfo']['lowPrice']['value'] ?? null;
    if ($low === null || (float) $low <= 0) continue;
    $place = trim(($e['city']['text']['name'] ?? '') . (isset($e['stateProvince']['text']['abbr']) ? ', ' . $e['stateProvince']['text']['abbr'] : ''), ', ');
    $now[$eid] = [
        'low' => (float) $low,
        'name' => (string) $e['text']['name'],
        'date' => trim(($e['date']['text']['date'] ?? '') . (isset($e['date']['text']['time']) ? ' at ' . $e['date']['text']['time'] : '')),
        'venue' => (string) ($e['venue']['text']['name'] ?? ''),
        'place' => $place,
        'url' => HOME_URL . '/event/' . createSlug((string) $e['text']['name'], $eid),
    ];
}

$byLead = [];
foreach ($rows as $r) {
    $eid = (int) $r['interest_id'];
    if (!isset($now[$eid])) continue;
    if ($now[$eid]['low'] > (float) $r['baseline_price'] * SO_PRICE_DROP) continue;
    if (count($byLead[(int) $r['lead_id']]['items'] ?? []) >= 5) continue;
    $byLead[(int) $r['lead_id']]['lead'] = $r;
    $byLead[(int) $r['lead_id']]['items'][] = $r;
}

$sent = 0; $failed = 0;
foreach ($byLead as $leadId => $entry) {
    if ($sent >= $limit) break;
    $r = $entry['lead'];
    $items = [];
    foreach ($entry['items'] as $it) {
        $n = $now[(int) $it['interest_id']];
        $items[] = $n + ['was' => '$' . number_format((float) $it['baseline_price'], 0), 'now' => '$' . number_format($n['low'], 0)];
    }
    if ($dry) {
        echo 'DRY RUN would email ' . $r['email'] . ': ' . implode('; ', array_map(function ($i) { return $i['name'] . ' ' . $i['was'] . ' -> ' . $i['now']; }, $items)) . "\n";
        $sent++;
        continue;
    }
    $token = soLeadEnsureToken($leadId);
    if ($token === '') { $failed++; continue; }
    $lead = ['email' => $r['email'], 'fname' => $r['fname'], 'lname' => $r['lname'], 'token' => $token];
    [$subject, $html, $text] = soLeadPriceAlertMessage($lead, $items);
    if (soLeadSendMail($lead, $subject, $html, $text, soLeadUnsubscribeUrl($token))) {
        $stmt = $db->prepare('UPDATE lead_interests SET notified_at = NOW(), baseline_price = ? WHERE id = ?');
        foreach ($entry['items'] as $it) {
            $price = round($now[(int) $it['interest_id']]['low'], 2);
            $row = (int) $it['interest_row'];
            $stmt->bind_param('di', $price, $row);
            $stmt->execute();
        }
        $stmt->close();
        $sent++;
    } else {
        $failed++;
    }
    if ($pauseMs > 0) usleep($pauseMs * 1000);
}

echo ($dry ? 'Dry run: ' : '') . 'watched ' . count($rows) . ' interests on ' . count($ids) . " events, emails " . ($dry ? 'planned ' : 'sent ') . $sent . ", failed $failed\n";
