<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Guard for the image endpoints (ajax/get-image.php, ajax/get-images.php).
 *
 * Both are public and used to queue ANY name a caller sent into the images table, from where cron/resolve-images.php
 * calls Wikidata, Commons, TheSportsDB and Pexels for it. Now a name is queued only when
 *   - it looks like a real name (length, no path or URL characters),
 *   - it is a name the site knows: the search vocabulary (top performers, venues, cities) or an exact match in
 *     TicketNetwork's own suggest results (cached an hour), and
 *   - the caller is under a per-address limit of new names per hour.
 * A name that already has a row is served as before (no insert, no limit). Everything else gets the category fallback.
 */

function soImageNameAcceptable($name) {
    $len = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
    if ($len < 2 || $len > 100) return false;
    if (preg_match('/[\x00-\x1F\x7F<>\\\\{}|^`]/', $name)) return false;
    if (preg_match('#(^|\s)\.{1,2}(/|$)|/\.\.|://|^/#', $name)) return false;
    return preg_match('/\pL|\pN/u', $name) === 1;
}

/** True when $name (normalized) is one of the names TicketNetwork or our search vocabulary knows. */
function soImageNameKnown($name) {
    require_once __DIR__ . '/smart.php';
    $k = smartNormalize($name);
    if ($k === '') return false;
    static $set = null;
    if ($set === null) {
        $set = [];
        foreach (smartVocab() as $item) { $set[$item['k']] = true; }
    }
    if (isset($set[$k])) return true;
    // Not in the vocabulary (it holds the top few thousand names): ask TicketNetwork, exact normalized match only.
    $data = tnRequest('/catalog/v2/suggest', ['q' => $name, 'performersRequested' => 5, 'venuesRequested' => 5]);
    foreach (['performers', 'venues'] as $group) {
        foreach ($data[$group]['results'] ?? [] as $r) {
            if (smartNormalize($r['name'] ?? ($r['text']['name'] ?? '')) === $k) return true;
        }
    }
    return false;
}

/** getEntityImage() for a visitor-supplied name: never queues junk or floods. Same return shape. */
function soGuardedEntityImage($type, $name, array $opts = []) {
    $name = trim((string) $name);
    $fallback = ['url' => imageFallbackUrl($type, $opts['category'] ?? [], $opts['tab'] ?? ''), 'credit' => '', 'license' => '', 'source' => '', 'status' => 'fallback'];
    if (!soImageNameAcceptable($name)) return $fallback;
    if (imageRecordGet(imageEntityKey($type, $name))) {
        return getEntityImage($type, $name, $opts);   // already known: serve only
    }
    if (!soRateHit('img-new', soIpHash(soClientIp()), 60, 3600)) return $fallback;
    if (!soImageNameKnown($name)) return $fallback;
    return getEntityImage($type, $name, $opts);
}
