<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }

// Some server configs rewrite pretty URLs (/city/slug -> city.php?slug=slug) and
// lose the visitor's own query string on the way, so ?when= / ?sort= / ?page=
// silently did nothing on those pages (seen on beta). REQUEST_URI still holds the
// original address, so restore any parameter PHP did not receive. Existing keys
// (the rewrite's own slug) always win, so this can never override routing.
if (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '?') !== false) {
    parse_str((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $so_original_query);
    if (is_array($so_original_query) && $so_original_query) {
        $_GET = $_GET + $so_original_query;
        $_REQUEST = $_REQUEST + $so_original_query;
    }
    unset($so_original_query);
}

include 'db/config.php';
include 'inc/constants.php';
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Error monitoring. Only active when SENTRY_DSN is set in the environment -
// see inc/constants.php. Initialized as early as possible so it also catches
// errors during the rest of this file's own setup.
if (SENTRY_DSN !== '') {
    \Sentry\init([
        'dsn' => SENTRY_DSN,
        'environment' => SENTRY_ENVIRONMENT,
        'traces_sample_rate' => 0.2,
    ]);
}

function getS3Client() {
    // Constructing an S3Client resolves config and builds an HTTP handler
    // stack - real work, even though it's not a network call by itself. A
    // single page render can call this a dozen+ times (once per image
    // existence check/upload), so it's worth building once per request.
    static $client = null;
    if ($client !== null) {
        return $client;
    }

    $accountId = AWS_ACCOUNT_ID;
    $accessKey = AWS_ACCESS_KEY;
    $secretKey = AWS_SECRET_KEY;

    $client = new S3Client([
        'version' => 'latest',
        'region'  => 'auto',
        'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
        'credentials' => [
            'key'    => $accessKey,
            'secret' => $secretKey,
        ],
    ]);

    return $client;
}

function downloadImage($url) {
    if (!preg_match('#^https?://#i', (string) $url)) return '';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => imageUserAgent(),
        // Was `die("cURL Error: ...")` on failure, which turned a JSON
        // endpoint into plain text and killed whatever page called it.
        CURLOPT_NOPROGRESS     => false,
        CURLOPT_PROGRESSFUNCTION => function ($ch, $dlTotal, $dlNow) {
            return ($dlTotal > IMAGE_MAX_DOWNLOAD_BYTES || $dlNow > IMAGE_MAX_DOWNLOAD_BYTES) ? 1 : 0;
        },
    ]);
    $data = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_errno($ch);
    curl_close($ch);
    if ($err || $code !== 200 || $data === false || $data === '') {
        return '';
    }
    return $data;
}

// Nearly every page on the site calls this (indirectly, via tnRequest())
// before it can make its real TicketNetwork API call. The `static` local
// only avoids re-fetching within a single request - PHP-FPM/CGI processes
// don't persist that between requests, so this was doing a full OAuth2
// round-trip to key-manager.tn-apis.com on every single page view, even
// though the token it gets back is valid for a full hour (`expires_in`).
// APCu caches it across requests for that same lifetime (minus a safety
// margin), so most page views skip this round-trip entirely. Falls back to
// the exact previous per-request-only behavior when APCu isn't installed.
const TN_ACCESS_TOKEN_CACHE_KEY = 'tn_access_token';
const TN_ACCESS_TOKEN_EXPIRY_BUFFER = 60; // seconds

function tnTokenCacheFile() {
    $dir = getenv('TN_TOKEN_DIR');
    $dir = ($dir !== false && $dir !== '') ? rtrim($dir, '/') : rtrim(sys_get_temp_dir(), '/');
    return $dir . '/seatoutlet_tn_token_' . md5(CONSUMER_KEY . '|' . BASE_URL) . '.json';
}

/** Cached token if it is still inside its lifetime, else ''. */
function tnTokenFromFile() {
    $file = tnTokenCacheFile();
    if (!is_file($file)) return '';
    $stored = json_decode((string) @file_get_contents($file), true);
    if (!empty($stored['token']) && (int) ($stored['expires_at'] ?? 0) > time()) {
        return (string) $stored['token'];
    }
    return '';
}

/**
 * OAuth access token for the catalog API.
 *
 * TicketNetwork invalidates an app's previous token a few seconds after a new
 * one is issued (verified: token A worked right after B was issued, and
 * returned 401 five seconds later). Two processes that each fetch their own
 * token therefore knock each other out. So:
 *   - one token store shared by everything that runs on this host (file in
 *     the temp dir, or TN_TOKEN_DIR - point it at a shared directory when
 *     web and cron use different temp dirs or there are several servers);
 *   - fetching is serialized with a lock and re-checked inside it, so a
 *     burst of requests after expiry issues one token, not one each;
 *   - $rejected lets a caller say "this token got a 401": we use a newer one
 *     if another process already refreshed, otherwise fetch a fresh one.
 */
function getTnAccessToken($rejected = '') {
    static $accessToken = null;

    if ($rejected === '' && $accessToken !== null) {
        return $accessToken;
    }
    if ($rejected !== '' && $accessToken === $rejected) {
        $accessToken = null;
    }

    if ($rejected === '' && function_exists('apcu_fetch')) {
        $cached = apcu_fetch(TN_ACCESS_TOKEN_CACHE_KEY, $found);
        if ($found) {
            $accessToken = $cached;
            return $accessToken;
        }
    }

    $fromFile = tnTokenFromFile();
    if ($fromFile !== '' && $fromFile !== $rejected) {
        return $accessToken = $fromFile;
    }

    $lockFile = tnTokenCacheFile() . '.lock';
    $lock = @fopen($lockFile, 'c');
    $locked = false;
    if ($lock) {
        for ($i = 0; $i < 100; $i++) {          // wait up to ~10s for another process
            if (flock($lock, LOCK_EX | LOCK_NB)) { $locked = true; break; }
            usleep(100000);
        }
    }
    try {
        // Someone else may have refreshed while we waited for the lock.
        $fromFile = tnTokenFromFile();
        if ($fromFile !== '' && $fromFile !== $rejected) {
            return $accessToken = $fromFile;
        }

        $ch = curl_init('https://key-manager.tn-apis.com/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET),
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POSTFIELDS     => http_build_query(['grant_type' => 'client_credentials']),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            \Sentry\captureMessage('TicketNetwork token request failed: ' . $error);
            return '';
        }
        curl_close($ch);

        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            \Sentry\captureMessage('TicketNetwork token error: ' . substr((string) $response, 0, 500));
            return '';
        }

        $accessToken = $data['access_token'];
        $ttl = max(60, (int) ($data['expires_in'] ?? 3600) - TN_ACCESS_TOKEN_EXPIRY_BUFFER);

        if (function_exists('apcu_store')) {
            apcu_store(TN_ACCESS_TOKEN_CACHE_KEY, $accessToken, $ttl);
        }
        $file = tnTokenCacheFile();
        $tmp = $file . '.' . uniqid('tmp_', true);
        if (@file_put_contents($tmp, json_encode(['token' => $accessToken, 'expires_at' => time() + $ttl]), LOCK_EX) !== false) {
            @chmod($tmp, 0600);
            @rename($tmp, $file);
        }
        if ($rejected !== '') {
            \Sentry\captureMessage('TicketNetwork token was rejected (401) and replaced; if this repeats, two processes are using separate token stores - set TN_TOKEN_DIR to a shared directory.', \Sentry\Severity::warning());
        }
        return $accessToken;
    } finally {
        if ($lock) {
            if ($locked) flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

/*
|--------------------------------------------------------------------------
| TicketNetwork request layer
|--------------------------------------------------------------------------
| Every catalog call goes through tnRequest(). GET responses are cached in
| cache/ (file cache; the host has no APCu) for a TTL chosen per endpoint
| by tnDefaultTtl() unless the caller passes one: reference data
| (performers, venues, cities, categories, ...) for 6h, event lists 10 min,
| search 5 min, per-user geo queries 5 min. Pass $ttl = 0 to bypass.
|
| Set the TN_PROFILE env var to a writable file path to get one JSON line
| per HTTP request with every call, cache hit/miss and milliseconds.
*/

const TN_CACHE_SWEEP_AGE = 172800; // 2 days: must exceed TN_STALE_ON_ERROR (24h)

function tnDefaultTtl($endpoint, array $params) {
    $e = trim($endpoint, '/');
    if (!empty($params['geoFilter']))            return 300;
    if (strpos($e, 'events/search') !== false)   return 300;
    if (strpos($e, 'catalog/v2/events') === 0)   return 600;
    if (strpos($e, 'catalog/v2/suggest') === 0)  return 3600;
    return 6 * 3600;
}

function tnProfile($endpoint, $hit, $ms) {
    $file = getenv('TN_PROFILE');
    if ($file === false || $file === '') return;
    if (!isset($GLOBALS['tn_profile'])) {
        $GLOBALS['tn_profile'] = ['uri' => $_SERVER['REQUEST_URI'] ?? ($_SERVER['argv'][0] ?? 'cli'), 'calls' => []];
        register_shutdown_function(function () use ($file) {
            $p = $GLOBALS['tn_profile'];
            $misses = array_filter($p['calls'], fn($c) => !$c['hit']);
            $line = json_encode([
                'uri'   => $p['uri'],
                'calls' => count($p['calls']),
                'hits'  => count($p['calls']) - count($misses),
                'ms'    => (int) array_sum(array_column($p['calls'], 'ms')),
                'live'  => array_values(array_map(fn($c) => $c['endpoint'] . ' ' . $c['ms'] . 'ms', $misses)),
            ]);
            @file_put_contents($file, $line . "\n", FILE_APPEND);
        });
    }
    $GLOBALS['tn_profile']['calls'][] = ['endpoint' => $endpoint, 'hit' => $hit, 'ms' => (int) $ms];
}

/*
| Cache entry, breaker and refresh helpers used by tnRequest()/tnRequestMulti().
*/
function tnCacheKey($endpoint, array $params, $method = 'GET') {
    return 'tn_' . md5(WEBSITE_CONFIG_ID . '|' . $method . '|' . trim($endpoint, '/') . '?' . http_build_query($params));
}

/** @return array{data:array,age:int}|null */
function tnCacheEntry($key) {
    $file = cache_file_path($key);
    if (!is_file($file)) return null;
    $json = @file_get_contents($file);
    $data = $json !== false && $json !== '' ? json_decode($json, true) : null;
    if (!is_array($data)) return null;
    return ['data' => $data, 'age' => max(0, time() - (int) @filemtime($file))];
}

/** How long past its TTL an entry may still be served while a refresh runs. */
function tnGrace($ttl) {
    return (int) min($ttl * 3, 86400);
}

/** Longest a stale entry may stand in for a failed live call. */
const TN_STALE_ON_ERROR = 86400;

function tnBreakerFile() {
    return rtrim(sys_get_temp_dir(), '/') . '/seatoutlet_tn_breaker_' . md5(BASE_URL) . '.txt';
}

/** Seconds the circuit stays open after an outage, and after the API throttles us (quota windows are longer). */
const TN_BREAKER_SECONDS = 20;
const TN_BREAKER_THROTTLE_SECONDS = 60;

/** True while a recent live failure holds the circuit open: skip live calls, serve stale or empty. */
function tnBreakerOpen() {
    $f = tnBreakerFile();
    if (!is_file($f)) return false;
    $hold = (int) @file_get_contents($f);   // the file holds how long this trip lasts (empty = default)
    if ($hold <= 0) $hold = TN_BREAKER_SECONDS;
    return (time() - (int) @filemtime($f)) < $hold;
}

function tnBreakerTrip($why, $seconds = TN_BREAKER_SECONDS) {
    // Never shorten a longer trip that is still running.
    if (tnBreakerOpen() && (int) @file_get_contents(tnBreakerFile()) > $seconds) {
        return;
    }
    @file_put_contents(tnBreakerFile(), (string) (int) $seconds);
    @touch(tnBreakerFile());
    if (function_exists('\Sentry\captureMessage')) {
        \Sentry\captureMessage('TicketNetwork API unavailable, circuit opened for ' . (int) $seconds . 's: ' . $why);
    }
}

/**
 * A page that had to go without API data (outage, throttling, circuit open and
 * nothing stale to show) must not be kept by a CDN: sendPageCacheHeaders()
 * reads this flag and answers no-store.
 */
function tnMarkDegraded() {
    $GLOBALS['tn_degraded'] = true;
}

function tnBuildHandle($endpoint, array $params, $method, $token) {
    $url = BASE_URL . $endpoint . (!empty($params) ? '?' . http_build_query($params) : '');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID,
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_ENCODING       => '',
    ]);
    return $ch;
}

/**
 * Interprets a finished handle.
 * @return array{ok:bool,data:array,cacheable:bool,error:string}
 */
function tnParseResult($ch, $response) {
    if ($response === false || $response === null) {
        return ['ok' => false, 'data' => [], 'cacheable' => false, 'error' => curl_error($ch) ?: 'empty response'];
    }
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $decoded = json_decode((string) $response, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return ['ok' => false, 'data' => [], 'cacheable' => false, 'error' => 'invalid JSON (HTTP ' . $code . ')'];
    }
    if ($code >= 500) {
        return ['ok' => false, 'data' => $decoded, 'cacheable' => false, 'error' => 'HTTP ' . $code];
    }
    // Throttled ("Message throttled out", quota exceeded): a failure to retry later, not an answer.
    // Treating it as data skipped the stale fallback and rendered empty listings as 200 pages.
    if ($code === 429 || (isset($decoded['code']) && preg_match('/^9008\d\d$/', (string) $decoded['code']))) {
        return ['ok' => false, 'data' => $decoded, 'cacheable' => false, 'error' => 'throttled (HTTP ' . $code . ')', 'throttled' => true];
    }
    $cacheable = $code === 200 && !isset($decoded['code']) && !isset($decoded['Message']);
    return ['ok' => true, 'data' => $decoded, 'cacheable' => $cacheable, 'error' => '', 'auth' => $code === 401];
}

/** One live call, no caching. A 401 (rotated token) refreshes the token and retries once. */
function tnFetchLive($endpoint, array $params, $method) {
    $t0 = microtime(true);
    $token = getTnAccessToken();
    $ch = tnBuildHandle($endpoint, $params, $method, $token);
    $result = tnParseResult($ch, curl_exec($ch));
    curl_close($ch);
    if (!empty($result['auth'])) {
        $token = getTnAccessToken($token);
        if ($token !== '') {
            $ch = tnBuildHandle($endpoint, $params, $method, $token);
            $result = tnParseResult($ch, curl_exec($ch));
            curl_close($ch);
        }
    }
    tnProfile($endpoint, false, (microtime(true) - $t0) * 1000);
    return $result;
}

/**
 * Refresh a stale entry after the response has gone out. Only one process
 * refreshes a given key (non-blocking lock); the rest keep serving stale.
 */
function tnScheduleRefresh($endpoint, array $params, $method, $key) {
    static $queued = [];
    static $registered = false;
    if (isset($queued[$key])) return;
    $queued[$key] = [$endpoint, $params, $method];
    if ($registered) return;
    $registered = true;
    register_shutdown_function(function () use (&$queued) {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        foreach ($queued as $key => [$endpoint, $params, $method]) {
            if (tnBreakerOpen()) break;
            $lock = @fopen(cache_file_path($key) . '.lock', 'c');
            if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { if ($lock) fclose($lock); continue; }
            $r = tnFetchLive($endpoint, $params, $method);
            if ($r['ok'] && $r['cacheable']) {
                cache_set($key, $r['data']);
            } elseif (!$r['ok']) {
                tnBreakerTrip($endpoint . ' ' . $r['error'], !empty($r['throttled']) ? TN_BREAKER_THROTTLE_SECONDS : TN_BREAKER_SECONDS);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink(cache_file_path($key) . '.lock');
        }
    });
}

function tnRequest($endpoint, $params = [], $method = 'GET', $ttl = null) {
    $params = is_array($params) ? $params : [];
    $ttl = $ttl === null ? tnDefaultTtl($endpoint, $params) : (int) $ttl;
    $key = null;
    $entry = null;

    if ($method === 'GET' && $ttl > 0) {
        $key = tnCacheKey($endpoint, $params, $method);
        $entry = tnCacheEntry($key);
        if ($entry !== null) {
            if ($entry['age'] <= $ttl) {
                tnProfile($endpoint, true, 0);
                return $entry['data'];
            }
            // Stale but inside the grace window: answer now, refresh after
            // the response is sent, so the visitor after a TTL expiry does
            // not pay the API latency (and a herd of them doesn't stampede it).
            if ($entry['age'] <= $ttl + tnGrace($ttl)) {
                tnProfile($endpoint, true, 0);
                if (!tnBreakerOpen()) tnScheduleRefresh($endpoint, $params, $method, $key);
                return $entry['data'];
            }
        }
    }

    // Circuit open: do not queue more 20s timeouts behind a dead API.
    if (tnBreakerOpen()) {
        if ($entry !== null && $entry['age'] <= TN_STALE_ON_ERROR) return $entry['data'];
        tnMarkDegraded();
        return [];
    }

    $r = tnFetchLive($endpoint, $params, $method);

    if (!$r['ok']) {
        tnBreakerTrip($endpoint . ' ' . $r['error'], !empty($r['throttled']) ? TN_BREAKER_THROTTLE_SECONDS : TN_BREAKER_SECONDS);
        \Sentry\captureMessage('TicketNetwork API error (' . $endpoint . '): ' . $r['error']);
        // Stale data beats a blank page during an outage.
        if ($entry !== null && $entry['age'] <= TN_STALE_ON_ERROR) return $entry['data'];
        tnMarkDegraded();
        return [];
    }

    if ($key !== null && $r['cacheable']) {
        cache_set($key, $r['data']);
        if (mt_rand(1, 200) === 1) {
            tnCacheSweep();
        }
    }
    return $r['data'];
}

/**
 * Fetch several independent requests in parallel and fill the cache.
 * $requests: list of [endpoint, params] or [endpoint, params, ttl].
 * Returns the responses in the same order. Entries already cached (fresh or
 * inside their grace window) are not re-fetched, so this is safe to call
 * speculatively at the top of a page: the normal tnRequest() calls further
 * down then hit the cache.
 */
function tnRequestMulti(array $requests) {
    $out = [];
    $todo = [];
    foreach ($requests as $i => $req) {
        $endpoint = $req[0];
        $params = is_array($req[1] ?? null) ? $req[1] : [];
        $ttl = isset($req[2]) ? (int) $req[2] : tnDefaultTtl($endpoint, $params);
        $key = tnCacheKey($endpoint, $params, 'GET');
        $entry = $ttl > 0 ? tnCacheEntry($key) : null;
        if ($entry !== null && $entry['age'] <= $ttl + tnGrace($ttl)) {
            tnProfile($endpoint, true, 0);
            $out[$i] = $entry['data'];
            continue;
        }
        $out[$i] = ($entry !== null && $entry['age'] <= TN_STALE_ON_ERROR) ? $entry['data'] : [];
        $todo[$i] = [$endpoint, $params, $ttl, $key];
    }
    if (!$todo) {
        return $out;
    }
    if (tnBreakerOpen()) {
        foreach ($todo as $i => $_) { if (empty($out[$i])) tnMarkDegraded(); }
        return $out;
    }

    $token = getTnAccessToken();
    $mh = curl_multi_init();
    $handles = [];
    foreach ($todo as $i => [$endpoint, $params]) {
        $handles[$i] = tnBuildHandle($endpoint, $params, 'GET', $token);
        curl_multi_add_handle($mh, $handles[$i]);
    }
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 0.5);
    } while ($running > 0);

    foreach ($handles as $i => $ch) {
        [$endpoint, $callParams, $ttl, $key] = $todo[$i];
        $r = tnParseResult($ch, curl_multi_getcontent($ch));
        tnProfile($endpoint, false, curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);
        if (!empty($r['auth'])) {
            // Rotated token: refresh (once, shared) and retry this request.
            $r = tnFetchLive($endpoint, $callParams, 'GET');
        }
        if ($r['ok']) {
            $out[$i] = $r['data'];
            if ($ttl > 0 && $r['cacheable']) cache_set($key, $r['data']);
        } else {
            tnBreakerTrip($endpoint . ' ' . $r['error'], !empty($r['throttled']) ? TN_BREAKER_THROTTLE_SECONDS : TN_BREAKER_SECONDS);
            if (empty($out[$i])) tnMarkDegraded();
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

/** Delete stale tn_*.json response files so cache/ doesn't grow unbounded. */
function tnCacheSweep() {
    $cutoff = time() - TN_CACHE_SWEEP_AGE;
    foreach (glob(cache_dir() . 'tn_*.json') ?: [] as $file) {
        if (@filemtime($file) < $cutoff) {
            @unlink($file);
        }
    }
}

/** Kept for existing callers; tnRequest() itself now caches. */
function tnRequestCached($endpoint, array $params = [], $ttl = 600) {
    return tnRequest($endpoint, $params, 'GET', $ttl);
}


/** [endpoint, params] for a performer's events, so callers can prefetch the exact request. */
function performerEventsSpec($performerId = 0, $params = []) {
    if ($performerId > 0) {
        $params['performerFilter'] = 'id eq ' . (int) $performerId;
    }
    return ['/catalog/v2/events/', $params];
}

function getTnPerformerEvents($performerId = 0, $params = []) {
    [$endpoint, $params] = performerEventsSpec($performerId, $params);
    return tnRequest($endpoint, $params);
}

// The /catalog/v2/suggest endpoint (used for header search autocomplete)
// only returns id+name for performers, no category data - so
// ajax/get-suggestions.php calls this once per suggested performer to get
// enough to render each result, meaning one autocomplete keystroke can
// trigger several of these in a row. A performer's category assignment is
// effectively static, so it's cached the same optional-APCu way as
// everything else here rather than re-fetched live every time. Every
// existing caller (performer.php, artist-*.php, etc.) benefits from this
// too, not just the autocomplete path.

/**
 * Page 1 of a performer's upcoming events, date order, cached 10 minutes.
 * Returns [params, response]; params are what the More Events button gets
 * so page 2+ continue the same set. No country filter: a performer page
 * lists that performer's own dates wherever they are (the old US-only
 * filter left international acts with an empty page).
 */
function performerPageEventsSpec($performerId, $perPage = 20) {
    return ['/catalog/v2/events', [
        'filter'            => 'date/date ge ' . date('Y-m-d') . ' and date/date le ' . date('Y-m-d', strtotime('+3 years')),   // TicketNetwork parks date-TBA events decades out
        'performerFilter'   => 'id eq ' . (int) $performerId,
        'sort'              => 'date/date',
        'perPage'           => (int) $perPage,
        'page'              => 1,
        'includeTotalCount' => 'true',
    ]];
}

function getPerformerPageEvents($performerId, $perPage = 20) {
    [$endpoint, $params] = performerPageEventsSpec($performerId, $perPage);
    return [$params, tnRequest($endpoint, $params, 'GET', 600)];
}

/** Cheapest "From" price and range across a set of listed events. */
function performerPriceSnapshot(array $events) {
    $min = null; $max = null; $priced = 0; $tickets = 0;
    foreach ($events as $event) {
        $low = $event['pricingInfo']['lowPrice']['value'] ?? null;
        if ($low !== null && (float) $low > 0) {
            $priced++;
            $min = $min === null ? (float) $low : min($min, (float) $low);
            $max = $max === null ? (float) $low : max($max, (float) $low);
        }
        $tickets += (int) ($event['_metadata']['ticketCount'] ?? 0);
    }
    return [
        'from'    => $min !== null ? '$' . number_format($min, 0) : '',
        'to'      => $max !== null ? '$' . number_format($max, 0) : '',
        'priced'  => $priced,
        'tickets' => $tickets,
    ];
}

/** BreadcrumbList + one Event node per listed event, for the performer page. */
function buildPerformerPageJsonLd(string $artistName, int $performerId, array $events, array $breadcrumbs, string $imageUrl = '') {
    $nodes = [];
    $crumbs = [];
    foreach ($breadcrumbs as $i => $crumb) {
        $crumbs[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb['label'] ?? '', 'item' => $crumb['url'] ?? HOME_URL];
    }
    $crumbs[] = ['@type' => 'ListItem', 'position' => count($crumbs) + 1, 'name' => $artistName . ' Tickets', 'item' => HOME_URL . '/artist/' . createSlug($artistName, $performerId)];
    $nodes[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $crumbs];

    foreach (array_slice($events, 0, 20) as $event) {
        $startDate = $event['date']['datetime'] ?? $event['date']['date'] ?? '';
        if ($startDate === '') continue;
        $price = $event['pricingInfo']['lowPrice']['value'] ?? null;
        $node = [
            '@type'       => 'Event',
            'name'        => $event['text']['name'] ?? $artistName,
            'startDate'   => $startDate,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'url'         => HOME_URL . '/event/' . createSlug($event['text']['name'] ?? '', $event['id'] ?? 0),
            'location'    => [
                '@type'   => 'Place',
                'name'    => $event['venue']['text']['name'] ?? '',
                'address' => [
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $event['city']['text']['name'] ?? '',
                    'addressRegion'   => $event['stateProvince']['text']['abbr'] ?? '',
                    'addressCountry'  => $event['country']['alphaCode'] ?? 'US',
                ],
            ],
            'performer'   => buildEventPerformerSchema($event),
        ];
        if ($imageUrl !== '') $node['image'] = $imageUrl;
        if ($price !== null && (float) $price > 0) {
            $node['offers'] = [
                '@type'         => 'Offer',
                'url'           => $node['url'],
                'price'         => (string) $price,
                'priceCurrency' => 'USD',
                'availability'  => !empty($event['_metadata']['hasTickets']) ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'validFrom'     => date('Y-m-d'),
            ];
        }
        $nodes[] = $node;
    }
    return $nodes;
}

function getTnPerformerById($performerId) {
    $performerId = (int) $performerId;
    $cacheKey = 'tn_performer:' . $performerId;

    if (function_exists('apcu_fetch')) {
        $cached = apcu_fetch($cacheKey, $found);
        if ($found) {
            return $cached;
        }
    }

    // File cache (6h) so hosts without APCu don't refetch on every view.
    $result = tnRequestCached('/catalog/v2/performers/' . $performerId, [], 6 * 3600);

    if (function_exists('apcu_store')) {
        apcu_store($cacheKey, $result, 3600);
    }

    return $result;
}

/**
 * Several performers in one call (`filter=id in (...)`, verified), instead of
 * one request per id. Each result also seeds the single-performer cache so a
 * later getTnPerformerById() for the same id is a hit.
 * @return array<int,array> performers keyed by id
 */
function getTnPerformersByIds(array $ids) {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    $out = [];
    $missing = [];
    foreach ($ids as $id) {
        $entry = tnCacheEntry(tnCacheKey('/catalog/v2/performers/' . $id, [], 'GET'));
        if ($entry !== null && $entry['age'] <= 6 * 3600) { $out[$id] = $entry['data']; }
        else { $missing[] = $id; }
    }
    if ($missing) {
        $res = tnRequest('/catalog/v2/performers', [
            'filter'  => 'id in (' . implode(',', $missing) . ')',
            'perPage' => count($missing),
        ]);
        foreach ($res['results'] ?? [] as $perf) {
            $pid = (int) ($perf['id'] ?? 0);
            if ($pid <= 0) continue;
            $out[$pid] = $perf;
            cache_set(tnCacheKey('/catalog/v2/performers/' . $pid, [], 'GET'), $perf);
        }
    }
    return $out;
}

/**
 * One page of the performer catalog (performers.php and
 * ajax/get-performers.php). Only performers with tickets on sale, so the
 * A-Z list has no dead clicks. Uses `filter`, not `eventFilter`, for the
 * same reason as cron/build-search-vocab.php. tnRequest() answers [] when
 * the API is down with nothing cached; this throws instead so callers can
 * tell "API down" apart from "no performers for this letter".
 */
function getTnPerformers(array $params = []) {
    $filter = '_metadata/hasTickets eq true';
    if (!empty($params['filter'])) {
        $filter .= ' and ' . $params['filter'];
    }
    $params['filter'] = $filter;

    $data = tnRequest('/catalog/v2/performers', $params, 'GET', 6 * 3600);
    if (!is_array($data) || !array_key_exists('results', $data)) {
        throw new RuntimeException('TicketNetwork performers request failed');
    }
    return $data;
}

/** Card image for a performer list: serve-only, never blocks the page on a lookup. */
function getPerformerImage($name, $defaultCategory) {
    return getArtistImage($name, $defaultCategory ?: [], false);
}

/** Display label for a performer's default category, e.g. "Rock / Pop". */
function getPerformerGenreLabel($defaultCategory) {
    $name = trim((string) ($defaultCategory['text']['name'] ?? ''));
    return $name === '' ? '' : ucwords(strtolower($name));
}


/**
 * Where an event page that is over (or no longer in the catalog) should send the visitor and search engines:
 * the main performer's page when we know it, otherwise the matching category hub, otherwise all events.
 * $event is the catalog record when it still exists, or null when only the remembered row can be used.
 */
function soEventRedirectTarget($event, int $eventId): string {
    $perfId = (int) ($event['performers'][0]['id'] ?? 0);
    $perfName = (string) ($event['performers'][0]['name'] ?? '');
    $path = (string) ($event['defaultCategory']['path'] ?? '');
    if ($perfId <= 0 && $eventId > 0) {
        $stmt = MYSQLI->prepare('SELECT performer_id, performer_name, category_path FROM event_redirects WHERE event_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $eventId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) { $perfId = (int) $row['performer_id']; $perfName = (string) $row['performer_name']; $path = (string) $row['category_path']; }
        }
    }
    if ($perfId > 0 && $perfName !== '') return '/artist/' . createSlug($perfName, $perfId);
    if (strpos($path, '.1988.') !== false) return '/game-day-tickets';
    if (strpos($path, '.1989.') !== false) return '/buy-broadway-tickets';
    if (strpos($path, '.1986.') !== false) return '/concert-tickets-for-sale';
    return '/buy-tickets-online';
}

/** Remembers the performer and category of a live event page (one insert the first time, nothing afterwards). */
function soEventRemember(array $event): void {
    $id = (int) ($event['id'] ?? 0);
    if ($id <= 0) return;
    $perfId = (int) ($event['performers'][0]['id'] ?? 0);
    $perfName = (string) ($event['performers'][0]['name'] ?? '');
    $path = (string) ($event['defaultCategory']['path'] ?? '');
    try {
        $stmt = MYSQLI->prepare('INSERT IGNORE INTO event_redirects (event_id, performer_id, performer_name, category_path, created_at) VALUES (?, ?, ?, ?, NOW())');
        if (!$stmt) return;
        $stmt->bind_param('iiss', $id, $perfId, $perfName, $path);
        $stmt->execute();
        $stmt->close();
    } catch (\Throwable $e) {
        // A missing table or a database hiccup must never break an event page.
    }
}

/** True once the event has started more than 6 hours ago (the catalog time carries the venue's UTC offset). */
function soEventIsOver(array $event): bool {
    $when = (string) ($event['date']['datetimeOffset'] ?? '');
    $ts = $when !== '' ? strtotime($when) : false;
    if (!$ts) return false;
    return $ts + 6 * 3600 < time();
}

function getTnEventById($eventId) {
    $endpoint = "/catalog/v2/events/" . (int) $eventId;
    return tnRequest($endpoint);
}

function stripCategoryRootPath($path) {
    static $rootPaths = ['.1859.1986.', '.1859.1988.', '.1859.1989.'];
    return str_replace('.', '', str_replace($rootPaths, '', $path));
}

function buildCategoryBreadcrumb($defaultCategory) {

    $breadcrumb = [
        [
            'label' => 'Home',
            'url'   => HOME_URL
        ]
    ];

    if($defaultCategory['depth'] == 1) {
        $breadcrumb[] = [
            'label' => ucwords(strtolower($defaultCategory['text']['name'])),
            'url'   => '/' . sanitize_title($defaultCategory['text']['name'])
        ];
    }
    if (!empty($defaultCategory['ancestors'])) {
        $depth1 = [];
        $depth2 = [];
        foreach ($defaultCategory['ancestors'] as $ancestor) {
            if ($ancestor['depth'] == 1) {
                $depth1[] = [
                    'label' => ucwords(strtolower($ancestor['text']['name'])),
                    'url'   => '/' . sanitize_title($ancestor['text']['name'])
                ];
            } elseif ($ancestor['depth'] == 2) {
                $depth2[] = [
                    'label' => ucwords(strtolower($ancestor['text']['name'])),
                    'url'   => '/category/' . sanitize_title($ancestor['text']['name']) . '-' . stripCategoryRootPath($ancestor['path'])
                ];
            }
        }
        $breadcrumb = array_merge($breadcrumb, $depth1, $depth2);
    }
    if(count($breadcrumb) < 3) {
        $breadcrumb[] = [
            'label' => ucwords(strtolower($defaultCategory['text']['name'])),
            'url'   => '/category/' . sanitize_title($defaultCategory['text']['name']) . '-' . stripCategoryRootPath($defaultCategory['path'])
        ];
    }

    return $breadcrumb;
}

/** [endpoint, params, ttl] for the related-performers query (also used to prefetch/warm). */
function relatedPerformersSpec($categoryPath, $limit = 20) {
    $categoryPathEsc = tnEscapeFilterValue($categoryPath);
    return ['/catalog/v2/performers', [
        'filter' => "defaultCategory/path eq '$categoryPathEsc'",
        'eventFilter' => '_metadata/hasTickets eq true',
        'sort'   => '-salesRank',
        'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}',
        'page'   => 1,
        'perPage'=> $limit
    ], 6 * 3600];
}

function getRelatedPerformers($categoryPath, $currentPerformerId, $limit = 20) {
    [$endpoint, $params, $ttl] = relatedPerformersSpec($categoryPath, $limit);
    $data = tnRequest($endpoint, $params, 'GET', $ttl);

    $related = array_filter($data['results'] ?? [], function ($p) use ($currentPerformerId) {
        return $p['id'] != $currentPerformerId;
    });

    return array_slice($related, 0, $limit);
}

function getTnEvents($params = []) {
    return tnRequest('/catalog/v2/events', $params);
}

function tnEscapeFilterValue($value = '') {
    return str_replace("'", "''", $value);
}

function sanitize_title($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

function curlGet($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'SeatOutlet/1.0 (' . HOME_URL . ')'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function resizeAndConvertToWebP($imageContent, $maxWidth = 500, $quality = 80) {

    $source = imagecreatefromstring($imageContent);
    if (!$source) return false;

    $width  = imagesx($source);
    $height = imagesy($source);

    if ($width <= $maxWidth) {
        $newWidth  = $width;
        $newHeight = $height;
    } else {
        $ratio = $height / $width;
        $newWidth  = $maxWidth;
        $newHeight = $maxWidth * $ratio;
    }

    $resized = imagecreatetruecolor($newWidth, $newHeight);

    imagecopyresampled(
        $resized,
        $source,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $width, $height
    );

    ob_start();
    imagewebp($resized, null, $quality);
    $webpData = ob_get_clean();

    imagedestroy($source);
    imagedestroy($resized);

    return $webpData;
}

function s3ExistsCacheKey($key) {
    return 's3_object_exists:' . $key;
}

// Called once per performer/venue image rendered on a page (city/state
// listing pages and "Fans Also Viewed" grids can render a dozen or more),
// on every single page load, even for images that were verified to exist
// moments ago. That was a real network round-trip to R2 per image, every
// time - cached here the same optional-APCu way as page_rules.
function s3ObjectExists($key) {
    if (function_exists('apcu_fetch')) {
        $cached = apcu_fetch(s3ExistsCacheKey($key), $found);
        if ($found) {
            return $cached;
        }
    }

    try {
        $client = getS3Client();

        $client->headObject([
            'Bucket' => AWS_BUCKET_NAME,
            'Key'    => $key
        ]);

        $exists = true;

    } catch (\Throwable $e) {
        // AwsException (missing object) or a credentials/config problem:
        // either way the object is not usable, and a page must never fatal
        // because storage is unreachable or unconfigured.
        $exists = false;
    }

    if (function_exists('apcu_store')) {
        // Once uploaded, an image essentially never disappears - cache a hit
        // for a full day. Cache a miss for only a minute so an image
        // processAndStoreImage() is about to upload (it calls this right
        // beforehand) is reflected on the very next request, not stuck
        // behind a long TTL.
        apcu_store(s3ExistsCacheKey($key), $exists, $exists ? 86400 : 60);
    }

    return $exists;
}

function uploadImageToS3($imageContent, $key, $contentType) {

    $client = getS3Client();

    $client->putObject([
        'Bucket' => AWS_BUCKET_NAME,
        'Key'    => $key,
        'Body'   => $imageContent,
        'ContentType' => $contentType,
        'CacheControl' => 'public, max-age=31536000'
    ]);
}

function getS3PublicUrl($key) {
    return AWS_CDN_URL . $key;
}

function getArtistBio($artistName, $performerId) {

    $bio = get_bio($performerId);

    if(!empty($bio)) {
        return $bio['bio'];
    }

    $artistName = trim(preg_replace('/\s*\(.*?\)|\s*feat\.?.*/i', '', $artistName));

    $url = 'https://en.wikipedia.org/w/api.php'
         . '?action=query'
         . '&prop=extracts'
         . '&exintro=1'
         . '&explaintext=1'
         . '&redirects=1'
         . '&titles=' . urlencode($artistName)
         . '&format=json';

    $response = curlGet($url);
    if (!$response) return '';

    $data = json_decode($response, true);
    if (empty($data['query']['pages'])) return '';

    $page = reset($data['query']['pages']);
    // A title with no Wikipedia article comes back without an 'extract' key.
    // Store an empty string (not NULL) so the miss is cached even if the
    // column is NOT NULL, and never let a cache write take the page down.
    $extract = trim((string) ($page['extract'] ?? ''));
    try {
        set_bio($performerId, $extract);
    } catch (\Throwable $e) {
        error_log('Bio cache write failed (' . $performerId . '): ' . $e->getMessage());
    }
    return $extract;
}

function set_bio($performerId, $bio, $mysqli = MYSQLI) {

    $stmt = $mysqli->prepare("
        INSERT INTO bios (performerId, bio)
        VALUES (?, ?)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ds", $performerId, $bio);

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}

function get_bio($performerId, $mysqli = MYSQLI) {

    $stmt = $mysqli->prepare("
        SELECT bio FROM bios WHERE performerId = ? LIMIT 1
    ");

    $stmt->bind_param("d", $performerId);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $result ?? null;
}

function getFaqs($type = null, $mysqli = MYSQLI) {

    $faqs = [];

    if ($type) {
        $stmt = $mysqli->prepare("SELECT * FROM faq WHERE type LIKE CONCAT('%', ?, '%')");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query("SELECT * FROM faq");
    }

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $faqs[] = $row;
        }
    }

    return $faqs;
}


function getNearbyVenues($lt, $lg, $limit = 20) {

    $radius = '50mi';
    
    if (empty($lt) && empty($lg)) {
        return [];
    }
    
    $params = [
        'geoFilter' => "nearby($lt, $lg, $radius)",
        'perPage'   => $limit,
        'sort' => '-salesRank',
        'salesRankOptions' => '{"interval":"week","metric":"ticketVolume"}'
    ];    

    $data = tnRequest('/catalog/v2/venues/', $params);
    
    return $data['results'] ?? [];
}

function getTopVenues($limit = 20) {

    $params = [ 
        'filter' => "country/alphaCode eq 'US'", 
        'sort' => '-salesRank', 
        'salesRankOptions' => '{"interval":"day","metric":"ticketVolume"}', 
        'perPage' => $limit 
    ];

    $data = tnRequest('/catalog/v2/venues/', $params);

    return $data['results'] ?? [];
}

/**
 * Real, live top cities for cities.php's category grids - built from
 * getTopVenues() (already in production use, see cron/home-venues.php)
 * rather than a fresh, unverified endpoint: each venue result embeds its
 * city's id/name/state, so top venues by sales rank gives real top cities
 * for free, just deduplicated by city id.
 */
function getTopCities($limit = 60) {
    $cacheKey = 'top_cities';
    $cached = cache_get($cacheKey, 12 * 3600);
    if (is_array($cached) && count($cached) >= min($limit, 10)) {
        return array_slice($cached, 0, $limit);
    }

    // /catalog/v2/cities supports the same -salesRank sort as performers and
    // venues and carries _metadata.eventCount/ticketCount (verified live), so
    // "top cities" is the API's own answer rather than a derivation from the
    // top-venues list (which put one-venue towns next to New York).
    $data = tnRequest('/catalog/v2/cities', [
        'filter'           => "country/alphaCode eq 'US' and _metadata/hasTickets eq true",
        'sort'             => '-salesRank',
        'salesRankOptions' => '{"interval":"week","metric":"ticketVolume"}',
        'perPage'          => min(200, $limit * 2),
    ]);

    $cities = [];
    foreach ($data['results'] ?? [] as $city) {
        $cityId = (int) ($city['id'] ?? 0);
        $cityName = trim((string) ($city['text']['name'] ?? ''));
        // TicketNetwork files virtual / at-home events under a placeholder
        // "Your Home" city; it is not a place a buyer would browse.
        if ($cityId <= 0 || $cityName === '' || strcasecmp($cityName, 'Your Home') === 0) continue;
        if ((int) ($city['_metadata']['eventCount'] ?? 0) < 3) continue;
        $stateAbbr = $city['stateProvince']['text']['abbr'] ?? '';
        $cities[] = [
            'id'         => $cityId,
            'name'       => $cityName,
            'state'      => $stateAbbr,
            'stateId'    => (int) ($city['stateProvince']['id'] ?? 0),
            'label'      => trim($cityName . ', ' . $stateAbbr, ', '),
            'eventCount' => (int) ($city['_metadata']['eventCount'] ?? 0),
        ];
        if (count($cities) >= $limit) break;
    }

    if (empty($cities)) {
        // API hiccup: derive from top venues as before rather than render nothing.
        $seen = [];
        foreach (getTopVenues($limit * 3) as $venue) {
            $cityId = $venue['city']['id'] ?? null;
            $cityName = $venue['city']['text']['name'] ?? '';
            if (empty($cityId) || $cityName === '' || isset($seen[$cityId])) continue;
            $seen[$cityId] = true;
            $stateAbbr = $venue['stateProvince']['text']['abbr'] ?? '';
            $cities[] = ['id' => $cityId, 'name' => $cityName, 'state' => $stateAbbr, 'stateId' => (int) ($venue['stateProvince']['id'] ?? 0), 'label' => trim($cityName . ', ' . $stateAbbr, ', '), 'eventCount' => 0];
            if (count($cities) >= $limit) break;
        }
        return $cities;
    }

    cache_set($cacheKey, $cities);
    return $cities;
}


function getKeywordSearchSuggestions($q) {

    $q = trim($q);

    if (!$q) return [];

    // tnRequest() caches /suggest for an hour (stale-while-revalidate). The
    // keywords DB table this used to read and write first never expired, so
    // a one-off empty answer for "adelle" was served forever and every
    // keystroke cost a DB query.
    $data = tnRequest('/catalog/v2/suggest', [
        'q' => $q,
        'performersRequested' => 5,
        'venuesRequested' => 5,
        'citiesRequested' => 5
    ]);

    if (!$data) return [];

    // TicketNetwork's suggest is a prefix matcher: "adelle" finds nothing, and
    // "carot top" only unrelated venues. No performer match on a real-looking
    // word is the signal to offer close names.
    if (($data['performers']['totalResultCount'] ?? 0) === 0) {
        require_once __DIR__ . '/inc/smart.php';
        $dym = smartDidYouMean($q, 4);
        if ($dym) { $data['didYouMean'] = $dym; }
    }
    return $data;
}

function getKeywordSearchResults($q) {

    $q = trim($q);

    if (!$q) return [];

    $params = [
        'q' => $q,
        'performersRequested' => 5,
        'venuesRequested' => 5
    ];

    $data = tnRequest('/catalog/v2/suggest', $params);

    if (!$data) return [];

    return $data;
}

function tnCurlRequest($url) {
    // Legacy signature (full URL). Delegates to tnRequest() so it shares the
    // cache, timeouts, error handling and profiling.
    $parts = parse_url($url);
    $params = [];
    parse_str($parts['query'] ?? '', $params);
    return tnRequest($parts['path'] ?? '', $params);
}

function getEmptySuggestionResponse() {
    return [
        'performers' => [
            'totalResultCount' => 0,
            'results' => []
        ],
        'venues' => [
            'totalResultCount' => 0,
            'results' => []
        ],
        'cities' => [
            'totalResultCount' => 0,
            'results' => []
        ]
    ];
}

function getHeaderSearchEvents($params = []) {

    $data = tnRequest('/catalog/v2/events/search', $params);

    return $data ?? [];
}

function getLoadMoreEvents($params = []) {

    $data = tnRequest('/catalog/v2/events', $params);

    return $data ?? [];
}



function fetchLocationCategoryEvents($rootPath, $type = '', $loc1 = '', $loc2 = '') {
    $today = date('Y-m-d');
    // Featured feeds only: TicketNetwork parks date-TBA events decades out
    // (2070+, time "TBA"); they can carry inventory, so hasTickets alone
    // doesn't exclude them, and a homepage card for "Feb 2072" reads as a
    // bug. Full listings are not windowed.
    $horizon = date('Y-m-d', strtotime('+2 years'));
    $rootPath = tnEscapeFilterValue($rootPath);

    $params = [
        'filter' => "date/date ge $today and date/date le $horizon and _metadata/hasTickets eq true and contains(defaultCategory/path,'$rootPath')",
        'perPage' => 6,
        'sort' => '-salesRank', 
        'salesRankOptions' => '{"interval":"day","metric":"ticketVolume"}', 
    ];

    if ($type === 'll') {
        $lat = floatval($loc1);
        $lng = floatval($loc2);
        $params['geoFilter'] = sprintf('nearby(%F,%F,50mi)', $lat, $lng);
    }

    $data = tnRequest('/catalog/v2/events/', $params);

    return $data['results'] ?? [];
}

$paths = [
    '.1859.1986.' => [".1859.1986.1903.",".1859.1986.1862.",".1859.1986.1872.",".1859.1986.1873.",".1859.1986.1906.",".1859.1986.1885.",".1859.1986.1871.",".1859.1986.1882."],
    '.1859.1988.' => [".1859.1988.1910.",".1859.1988.1883.",".1859.1988.1867.",".1859.1988.1880.",".1859.1988.1864.",".1859.1988.1897.",".1859.1988.1874.",".1859.1988.1881."],
    '.1859.1989.' => [".1859.1989.1894.",".1859.1989.2060.",".1859.1989.1887.",".1859.1989.1868.",".1859.1989.1869.",".1859.1989.1896.",".1859.1989.1863.",".1859.1989.1898."],
];

function fetchGroupedEvents($rootPath, $type = '', $loc1 = '', $loc2 = '') {
    global $paths;

    if (empty($paths[$rootPath])) {
        return [];
    }

    $subcategories = $paths[$rootPath];    
    $today = date('Y-m-d');
    // Featured feeds only: TicketNetwork parks date-TBA events decades out
    // (2070+, time "TBA"); they can carry inventory, so hasTickets alone
    // doesn't exclude them, and a homepage card for "Feb 2072" reads as a
    // bug. Full listings are not windowed.
    $horizon = date('Y-m-d', strtotime('+2 years'));

    $params = [
        'filter' => "date/date ge $today and date/date le $horizon and _metadata/hasTickets eq true and contains(defaultCategory/path,'$rootPath')",
        'sort' => '-salesRank', 
        'salesRankOptions' => '{"interval":"day","metric":"ticketVolume"}', 
        'perPage' => 150
    ];

    if ($type === 'll') {
        $params['geoFilter'] = sprintf('nearby(%F,%F,50mi)', $loc1, $loc2);
    }

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

    $data = tnCurlRequest($url);    
    $events = $data['results'] ?? [];

    $grouped = [];
    $usedEventIds = [];
    foreach ($events as $event) {
        $eventPath = $event['defaultCategory']['path'] ?? '';
        if (!$eventPath) continue;
        foreach ($subcategories as $subPath) {
            if (isset($grouped[$subPath])) continue;
            if (strpos($eventPath, $subPath) !== false) {
                $grouped[$subPath] = $event;
                $usedEventIds[$event['id']] = true;
                break;
            }
        }
        if (count($grouped) === count($subcategories)) break;
    }
    
    if (count($grouped) < 6) {
        foreach ($events as $event) {
            if (isset($usedEventIds[$event['id']])) continue;
            $grouped[] = $event;
            $usedEventIds[$event['id']] = true;
            if (count($grouped) >= 6) break;
        }
    }

    return array_slice(array_values($grouped), 0, 6);
}

function normalizeKey($v) {
    if (is_numeric($v)) {
        $v = number_format((float)$v, 2, '.', '');
    }

    $v = strtolower((string)($v ?? ''));
    $v = str_replace(['.', ' '], ['', '_'], $v);

    return $v;
}

function getTeamsByCategory($categorySlug, $limit = 50) {

    $categorySlug = strtoupper(trim($categorySlug));
    if ($categorySlug === '') return [];

    $categoryPaths = [
        'NFL' => '.1859.1988.1879.1959.',
        'NBA' => '.1859.1988.1865.1971.',
        'MLB' => '.1859.1988.1864.1969.',
        'NHL' => '.1859.1988.1883.1972.',
        'MLS' => '.1859.1988.1913.1970.',
    ];

    if (!isset($categoryPaths[$categorySlug])) {
        return [];
    }

    $categoryPath = $categoryPaths[$categorySlug];

    $params = [
        'categoryFilter' => "path eq '$categoryPath'",
        'perPage' => $limit
    ];

    $data = tnRequest('/catalog/v2/performers', $params);
    $results = $data['results'] ?? [];

    return $results;
}


function cache_dir() {
    $dir = __DIR__ . '/cache/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function cache_file_path($key) {
    $safeKey = preg_replace('/[^a-z0-9_\-]/i', '_', (string)$key);
    return cache_dir() . $safeKey . '.json';
}

function cache_get($key, $ttl = 86400) {
    $file = cache_file_path($key);

    if (!is_file($file)) return false;
    if ($ttl > 0 && (time() - filemtime($file)) > $ttl) return false;

    $json = file_get_contents($file);
    if ($json === false || $json === '') return false;

    $data = json_decode($json, true);
    return is_array($data) ? $data : false;
}

function cache_set($key, $data) {
    $file = cache_file_path($key);

    $tmp = $file . '.' . uniqid('tmp_', true);
    $json = json_encode($data, JSON_UNESCAPED_SLASHES);

    if ($json === false) return false;

    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return rename($tmp, $file);
}

function renderSkeletonCardsEvents($count = 8) {
    for ($i = 0; $i < $count; $i++) {
        echo '
        <a href="javascript:void(0)" class="team-link skeleton-link">
            <article class="event-card skeleton-card">
                <div class="event-card__img skeleton-img"></div>
                <div class="event-card__body">
                    <div class="skeleton-line skeleton-title"></div>
                    <div class="skeleton-meta">
                        <span class="skeleton-line skeleton-date"></span>
                        <span class="dot"></span>
                        <span class="skeleton-line skeleton-venue"></span>
                    </div>
                    <div class="skeleton-line skeleton-price"></div>
                </div>
            </article>
        </a>';
    }
}

function generateTeamSkeleton($count = 6) {

    $html = '<div class="team-slider">';

    for ($i = 0; $i < $count; $i++) {

        $html .= '
        <a href="javascript:void(0)" class="team-link skeleton-link">
            <div class="team-card team-card-skeleton">
                <span class="skeleton-line skeleton-team-name"></span>
            </div>
        </a>
        ';
    }

    return $html . '</div>';
}

function buildVenueSkeleton($count = 8) {

    $html = '';

    for ($i = 0; $i < $count; $i++) {

        $html .= '
            <div class="venue-card-skeleton">
                <div class="skeleton-img shimmer"></div>
                <div class="venue-content text-center p-3">
                    <div class="skeleton-line skeleton-title shimmer"></div>
                    <div class="skeleton-line skeleton-location shimmer"></div>
                </div>
            </div>
        ';
    }

    return $html;
}

/*
|--------------------------------------------------------------------------
| Category listing feeds (/tickets, /concerts, /sports, /theater, /festival)
|--------------------------------------------------------------------------
| These five pages used to have five copy-pasted cURL blocks with NO sort
| parameter, so TicketNetwork returned events in its default (date) order
| and page 1 of "Concerts" was simply whatever happened to be on today.
| They now share one implementation that asks the API for its own top
| sellers (sort=-salesRank; the descending form is the real "top", the
| ascending form returns the salesRank=100 baseline alphabetically) and
| restricts the feed to events that actually have inventory. Without the
| hasTickets filter the sales-rank sort surfaces placeholder "TBD" events
| with zero tickets (dated 2070+ in the sandbox) that nobody can buy.
|
| categoryListingParams() is public because each page also hands the same
| params to the browser (data-params on the "More Events" button) so
| ajax/load-more-events.php pages through the *same* ordered result set;
| sort/salesRankOptions are on that endpoint's allow-list.
*/

const TN_CATEGORY_PATH_CONCERTS = '.1859.1986.';
const TN_CATEGORY_PATH_SPORTS   = '.1859.1988.';
const TN_CATEGORY_PATH_THEATER  = '.1859.1989.';
const TN_CATEGORY_PATH_FESTIVAL = '.1859.1986.1877.';

/** [from, to] (Y-m-d) for a "when" quick filter, or null for "any time". */
function listingDateRange($when) {
    $today = new DateTimeImmutable('today');
    switch ($when) {
        case 'today':
            return [$today->format('Y-m-d'), $today->format('Y-m-d')];
        case 'weekend':
            $dow = (int) $today->format('N');            // 1=Mon .. 7=Sun
            if ($dow >= 5) {                             // Fri/Sat/Sun: this weekend, from today
                $from = $today;
            } else {
                $from = $today->modify('next friday');
            }
            $to = $from->modify('sunday this week');
            if ($to < $from) $to = $from->modify('next sunday');
            return [$from->format('Y-m-d'), $to->format('Y-m-d')];
        case 'week':
            return [$today->format('Y-m-d'), $today->modify('+7 days')->format('Y-m-d')];
        case 'month':
            return [$today->format('Y-m-d'), $today->modify('+30 days')->format('Y-m-d')];
    }
    return null;
}

const LISTING_WHEN = ['today' => 'Today', 'weekend' => 'This weekend', 'week' => 'Next 7 days', 'month' => 'Next 30 days'];
const LISTING_SORT = ['popular' => 'Best sellers', 'soonest' => 'Soonest', 'price' => 'Lowest price'];

/** Sort params for a listing sort key (verified sort keys). */
function listingSortParams($sort) {
    if ($sort === 'soonest') {
        return ['sort' => 'date/date'];
    }
    if ($sort === 'price') {
        // Verified: the API sorts on pricingInfo/lowPrice/value (ascending = cheapest first).
        return ['sort' => 'pricingInfo/lowPrice/value'];
    }
    return ['sort' => '-salesRank', 'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}'];
}

/** Listing query for any OData location/category fragment, with when/sort applied. */
function locationListingParams($fragment, $perPage = 20, $page = 1, $when = '', $sort = 'popular') {
    $range = listingDateRange($when);
    $from = $range ? $range[0] : date('Y-m-d');
    $filter = $fragment . " and date/date ge $from" . ($range ? " and date/date le {$range[1]}" : '') . ' and _metadata/hasTickets eq true';
    return ['filter' => $filter] + listingSortParams($sort) + [
        'perPage'           => (int) $perPage,
        'page'              => (int) $page,
        'includeTotalCount' => 'true',
    ];
}

function categoryListingParams($categoryPath = '', $perPage = 20, $page = 1, $when = '', $sort = 'popular') {
    $fragment = $categoryPath !== ''
        ? "startswith(defaultCategory/path, '" . tnEscapeFilterValue($categoryPath) . "')"
        : "country/alphaCode eq 'US'";
    return locationListingParams($fragment, $perPage, $page, $when, $sort);
}

function getCategoryListingEvents($categoryPath = '', $perPage = 20, $page = 1, $when = '', $sort = 'popular') {
    return tnRequest('/catalog/v2/events/', categoryListingParams($categoryPath, $perPage, $page, $when, $sort));
}

/**
 * "When" quick filters + sort links for a listing page. Plain links (works
 * without JS, each state has a URL); filtered variants are noindex,follow and
 * canonical to the base page (see listingRequestState()).
 */
function listingRequestState($defaultSort = 'popular') {
    $when = isset($_GET['when']) && isset(LISTING_WHEN[$_GET['when']]) ? $_GET['when'] : '';
    $sort = isset($_GET['sort']) && isset(LISTING_SORT[$_GET['sort']]) ? $_GET['sort'] : $defaultSort;
    return [$when, $sort, ($when !== '' || $sort !== $defaultSort)];
}

/**
 * "Weekend 1 / Weekend 2" groups for a single multi-day festival (or other multi-day event) at one site.
 * Returns [] unless the events are all at one venue, belong to a festival category (or are flagged multi-day), and fall
 * into at least two separate runs of days (gap of more than 3 days between runs). Touring artists never qualify.
 */
function soWeekendGroups(array $events): array {
    if (count($events) < 2) return [];
    $venues = []; $festival = false; $multi = false; $dates = [];
    foreach ($events as $ev) {
        $venues[(string) ($ev['venue']['id'] ?? '0')] = true;
        $path = (string) ($ev['defaultCategory']['path'] ?? '');
        if (strpos($path, '.1877.') !== false || strpos($path, '.2065.') !== false) $festival = true;
        if (!empty($ev['isMultiDayEvent'])) $multi = true;
        $d = (string) ($ev['date']['date'] ?? '');
        if ($d === '' || !strtotime($d)) return [];
        $dates[(int) ($ev['id'] ?? 0)] = $d;
    }
    if (count($venues) !== 1 || (!$festival && !$multi)) return [];
    asort($dates);
    $groups = []; $prev = null; $g = -1;
    foreach ($dates as $id => $d) {
        if ($prev === null || (strtotime($d) - strtotime($prev)) / 86400 > 3) { $g++; $groups[$g] = ['ids' => [], 'from' => $d, 'to' => $d]; }
        $groups[$g]['ids'][] = $id; $groups[$g]['to'] = $d; $prev = $d;
    }
    if (count($groups) < 2 || count($groups) > 6) return [];
    foreach ($groups as $i => &$grp) {
        $grp['label'] = 'Weekend ' . ($i + 1);
        $a = date('M d', strtotime($grp['from'])); $b = date('M d', strtotime($grp['to']));
        $grp['range'] = $a === $b ? $a : $a . ' - ' . $b;
    }
    unset($grp);
    return $groups;
}

/** Listing hubs that get the "Explore ... near you" block: base path => [feed category, plural noun]. */
const SO_EXPLORE_HUBS = [
    '/buy-tickets-online'        => ['all', 'events', '/images/crowd-at-concert-or-event.webp'],
    '/concert-tickets-for-sale'  => ['concerts', 'concerts', '/images/event-concert.jpg'],
    '/game-day-tickets'          => ['sports', 'games', '/images/event-basketball.jpg'],
    '/buy-broadway-tickets'      => ['theatre', 'shows', '/images/loews-theatre.webp'],
    '/upcoming-music-festivals'  => ['festival', 'festivals', '/images/festival-1.webp'],
];

/**
 * Category tabs, location and date chips, and the "near you" grid (filled by js/near-you.js from the visitor's own
 * location; it stays hidden until a location is known), then the heading of the full national list below.
 */
function renderExploreBar($basePath, array $opts = []) {
    if (isset($opts['catId'])) {
        // A single category page: its own TicketNetwork category id and name.
        $cat = 'all'; $noun = (string) ($opts['noun'] ?? 'events'); $heroImg = '/images/crowd-at-concert-or-event.webp';
        $catId = (int) $opts['catId'];
    } elseif (isset(SO_EXPLORE_HUBS[$basePath])) {
        [$cat, $noun, $heroImg] = SO_EXPLORE_HUBS[$basePath];
        $catId = 0;
    } else {
        return;
    }
    $tabs = ['/buy-tickets-online' => 'All events', '/game-day-tickets' => 'Sports', '/concert-tickets-for-sale' => 'Concerts', '/buy-broadway-tickets' => 'Theater', '/upcoming-music-festivals' => 'Festivals'];
    ?>
    <div class="so-explore" data-so-explore data-hero="<?php echo htmlspecialchars($heroImg, ENT_QUOTES, 'UTF-8'); ?>" data-when="<?php echo htmlspecialchars((string) ($opts['when'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-catid="<?php echo (int) $catId; ?>" data-cat="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>" data-noun="<?php echo htmlspecialchars($noun, ENT_QUOTES, 'UTF-8'); ?>">
        <nav class="so-cattabs" aria-label="Event categories">
            <?php foreach ($tabs as $href => $label) { ?>
                <a href="<?php echo $href; ?>" <?php echo $href === $basePath ? 'class="active" aria-current="page"' : ''; ?>><?php echo $label; ?></a>
            <?php } ?>
        </nav>
        <div class="so-chips">
            <div class="so-chip-wrap">
                <button type="button" class="so-chip so-chip--on" data-so-loc aria-haspopup="dialog" aria-expanded="false">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    <span data-so-loc-label>Finding your location...</span>
                    <svg class="so-chip__caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="so-pop" data-so-loc-pop role="dialog" aria-label="Change location" hidden>
                    <label class="so-pop__label" for="soNearInput">Change location</label>
                    <input id="soNearInput" class="so-pop__input" type="text" placeholder="City or ZIP, for example Austin, TX" autocomplete="off">
                    <button type="button" class="so-pop__row" data-so-loc-here>Use my current location</button>
                </div>
            </div>
        </div>
        <section class="so-near" data-so-near hidden aria-live="polite">
            <h2 class="so-near__title" data-so-near-title>Explore <?php echo htmlspecialchars($noun, ENT_QUOTES, 'UTF-8'); ?> near you</h2>
            <div class="so-near__grid" data-so-near-grid></div>
            <button type="button" class="so-near__more" data-so-near-more hidden>See more</button>
        </section>
        <h2 class="so-allhead">All <?php echo htmlspecialchars($noun, ENT_QUOTES, 'UTF-8'); ?> in the USA</h2>
    </div>
    <?php
}

function renderListingFilters($basePath, $when, $sort, $total, $defaultSort = 'popular', array $explore = []) {
    $url = function ($w, $s) use ($basePath, $defaultSort) {
        $q = array_filter(['when' => $w, 'sort' => $s === $defaultSort ? '' : $s]);
        return htmlspecialchars($basePath . ($q ? '?' . http_build_query($q) : ''), ENT_QUOTES, 'UTF-8');
    };
    renderExploreBar($basePath, $explore + ['when' => $when]);
    $whenLabel = $when !== '' ? LISTING_WHEN[$when] : 'All dates';
    $sortLabel = LISTING_SORT[$sort] ?? 'Best sellers';
    ?>
    <div class="so-filterbar" role="group" aria-label="Filter and sort events">
        <details class="so-dd">
            <summary class="so-chip<?php echo $when !== '' ? ' so-chip--on' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                <span><?php echo htmlspecialchars($whenLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <svg class="so-chip__caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="so-dd__menu">
                <a class="so-pop__row<?php echo $when === '' ? ' is-active' : ''; ?>" href="<?php echo $url('', $sort); ?>"<?php echo $when === '' ? ' aria-current="true"' : ''; ?>>All dates</a>
                <?php foreach (LISTING_WHEN as $key => $label) { ?>
                    <a class="so-pop__row<?php echo $when === $key ? ' is-active' : ''; ?>" href="<?php echo $url($key, $sort); ?>"<?php echo $when === $key ? ' aria-current="true"' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php } ?>
            </div>
        </details>
        <details class="so-dd">
            <summary class="so-chip<?php echo $sort !== $defaultSort ? ' so-chip--on' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h10M4 12h7M4 17h4M17 5v14m0 0-3-3m3 3 3-3"/></svg>
                <span><?php echo htmlspecialchars($sortLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <svg class="so-chip__caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="so-dd__menu">
                <?php foreach (LISTING_SORT as $key => $label) { ?>
                    <a class="so-pop__row<?php echo $sort === $key ? ' is-active' : ''; ?>" href="<?php echo $url($when, $key); ?>"<?php echo $sort === $key ? ' aria-current="true"' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php } ?>
            </div>
        </details>
        <?php if ((int) $total === 0 && $when !== '') { ?>
            <p class="listing-filter-empty">No events match <strong><?php echo htmlspecialchars(strtolower(LISTING_WHEN[$when]), ENT_QUOTES, 'UTF-8'); ?></strong>. <a href="<?php echo $url('', $sort); ?>">Show all dates</a>.</p>
        <?php } ?>
    </div>
    <?php
}

function getAllEvents() {
    return getCategoryListingEvents('');
}

function getSportsCatEvents() {
    return getCategoryListingEvents(TN_CATEGORY_PATH_SPORTS);
}

function getConcertsCatEvents() {
    return getCategoryListingEvents(TN_CATEGORY_PATH_CONCERTS);
}

function getTheaterCatEvents() {
    return getCategoryListingEvents(TN_CATEGORY_PATH_THEATER);
}

function getFestivalCatEvents() {
    return getCategoryListingEvents(TN_CATEGORY_PATH_FESTIVAL);
}

/*
|--------------------------------------------------------------------------
| Listing price / deal helpers
|--------------------------------------------------------------------------
| The events list endpoint already returns pricingInfo (lowPrice /
| averagePrice / highPrice, each with .value and .text.formatted) and
| _metadata.ticketCount for every event, so "From $X" costs no extra API
| call. Until now only the homepage cards used it; every other listing row
| showed a bare "Find Tickets" button with no price, which is the single
| biggest piece of information a buyer wants before clicking.
|
| "Deal" is data-driven, not a promo code: an event is flagged when its
| cheapest listing is at most 60% of the average listing price for that
| event, i.e. there is genuinely cheap inventory relative to the rest of
| the map. Nothing here promises a discount the checkout can't apply.
*/

const EVENT_DEAL_RATIO = 0.6;

function eventFromPrice(array $event) {
    $formatted = $event['pricingInfo']['lowPrice']['text']['formatted'] ?? '';
    $value     = $event['pricingInfo']['lowPrice']['value'] ?? null;
    if ($formatted === '' || $value === null || (float) $value <= 0) {
        return '';
    }
    return (string) $formatted;
}

function eventDealInfo(array $event) {
    $low  = $event['pricingInfo']['lowPrice']['value'] ?? null;
    $avg  = $event['pricingInfo']['averagePrice']['value'] ?? null;
    $from = eventFromPrice($event);
    $isDeal = $from !== ''
        && $avg !== null && (float) $avg > 0
        && (float) $low <= (float) $avg * EVENT_DEAL_RATIO;
    return [
        'from'    => $from,
        'avg'     => $event['pricingInfo']['averagePrice']['text']['formatted'] ?? '',
        'is_deal' => $isDeal,
        'tickets' => (int) ($event['_metadata']['ticketCount'] ?? 0),
    ];
}

function renderEventPriceTag(array $event) {
    $deal = eventDealInfo($event);
    if ($deal['from'] === '') {
        // Nothing on sale for this date right now: say so instead of leaving a blank next to the button.
        echo '<div class="event-price-tag event-price-tag--none">No tickets listed yet</div>';
        return;
    }
    echo '<div class="event-price-tag">';
    if ($deal['is_deal']) {
        echo '<span class="event-deal-badge">Deal</span> ';
    }
    echo 'From <strong>' . htmlspecialchars($deal['from'], ENT_QUOTES, 'UTF-8') . '</strong>';
    // Factual inventory signal, not manufactured urgency: the number of tickets
    // TicketNetwork currently lists for the event, shown only when it is small.
    if ($deal['tickets'] > 0 && $deal['tickets'] <= 20) {
        echo '<span class="event-low-inv">Only ' . (int) $deal['tickets'] . ' listed</span>';
    }
    echo '</div>';
}

/*
|--------------------------------------------------------------------------
| Top subcategories (homepage "Browse by Categories")
|--------------------------------------------------------------------------
| The category tree has no salesRank, but every category carries
| _metadata.ticketCount / eventCount, so "top" = the children of a root
| with the most inventory on sale right now. Replaces a hard-coded list
| that had Reggae, Religious and 50s/60s Era as the concert picks.
| Refreshed by cron/home-categories.php into cache/top_categories.json.
*/

function getTopSubcategories($rootPath, $limit = 8) {
    $data = tnRequest('/catalog/v2/categories', [
        'filter'  => "parentCategory/path eq '" . tnEscapeFilterValue($rootPath) . "'",
        'perPage' => 100,
    ]);
    $rows = [];
    foreach ($data['results'] ?? [] as $cat) {
        $name = $cat['text']['name'] ?? '';
        $path = $cat['path'] ?? '';
        if ($name === '' || $path === '' || strtoupper($name) === 'OTHER') {
            continue;
        }
        $segments = array_values(array_filter(explode('.', $path)));
        $id = (int) end($segments);
        if ($id <= 0) {
            continue;
        }
        $rows[] = [
            'id'          => $id,
            'name'        => ucwords(strtolower($name)),
            'slug'        => createSlug($name, $id),
            'ticketCount' => (int) ($cat['_metadata']['ticketCount'] ?? 0),
            'eventCount'  => (int) ($cat['_metadata']['eventCount'] ?? 0),
        ];
    }
    usort($rows, function ($a, $b) {
        return [$b['ticketCount'], $b['eventCount']] <=> [$a['ticketCount'], $a['eventCount']];
    });
    return array_slice($rows, 0, $limit);
}

function searchPostalCodes($text, $country = 'US', $limit = 20) {

    if (!$text) {
        return [];
    }

    $text = trim($text);

    if (preg_match('/^[0-9]+$/', $text)) {
        $filter = "code eq '{$text}' and country/alphaCode eq '{$country}'";
    } else {
        $filter = "startswith(city/text/name,'{$text}') and country/alphaCode eq '{$country}'";
    }

    $params = [
        'filter'  => $filter,
        'perPage' => $limit,
        'sort'    => 'city'
    ];

    $data = tnRequest('/catalog/v2/postalCodes/', $params);

    return $data;
}

function fixImageOrientation($imageContent) {

    $tmp = tempnam(sys_get_temp_dir(), 'img_');
    file_put_contents($tmp, $imageContent);

    $image = imagecreatefromstring($imageContent);
    if (!$image) return $imageContent;

    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);

        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
        }
    }

    ob_start();
    imagejpeg($image, null, 90);
    $fixed = ob_get_clean();

    imagedestroy($image);
    unlink($tmp);

    return $fixed;
}



function getStoredImageUrl($name, $type) {

    if (!$name) return '';

    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
    $clean = trim($slug, '-');
    $key  = "{$type}/{$clean}.webp";

    if (s3ObjectExists($key)) {
        return getS3PublicUrl($key);
    }

    return '';
}

function processAndStoreImage($imageUrl, $name, $type) {

    if (!$imageUrl || !$name) return '';

    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
    $clean = trim($slug, '-');
    $key  = "{$type}/{$clean}.webp";

    if (s3ObjectExists($key)) {
        return getS3PublicUrl($key);
    }

    $imageContent = downloadImage($imageUrl);
    if (!$imageContent) return '';

    $imageContent = fixImageOrientation($imageContent);

    $webpImage = resizeAndConvertToWebP($imageContent, 1200, 82);
    if (!$webpImage) return '';

    try {
        uploadImageToS3($webpImage, $key, 'image/webp');
    } catch (Throwable $e) {
        // Storage outage must not surface as a fatal from an image lookup;
        // the caller stores a short-lived fallback and retries later.
        \Sentry\captureMessage('Image upload to storage failed (' . $key . '): ' . $e->getMessage());
        return '';
    }

    if (function_exists('apcu_store')) {
        apcu_store(s3ExistsCacheKey($key), true, 86400);
    }

    return getS3PublicUrl($key);
}

/*
|--------------------------------------------------------------------------
| Entity image facade
|--------------------------------------------------------------------------
| Thin wrappers kept for the existing call sites; the implementation lives
| in inc/images.php (per-type source chain, license capture, cron queue).
| $resolve=false is for lists (related performers, cards): serve what is
| stored or the fallback and queue the lookup, never block the page.
*/

function getArtistImage($artist, $defaultCategory, $resolve = true) {
    if (!$artist) return '';
    $type = imageEntityTypeForPerformer($defaultCategory ?: []);
    return getEntityImage($type, $artist, ['category' => $defaultCategory ?: [], 'resolve' => $resolve])['url'];
}

function getTeamImage($team, $cat = '', $subcat = '', $resolve = true) {
    if (!$team) return '';
    return getEntityImage('team', $team, ['tab' => 'sports', 'resolve' => $resolve])['url'];
}

function getVenueImage($venue, $resolve = true) {
    if (!$venue) return '';
    return getEntityImage('venue', $venue, ['resolve' => $resolve])['url'];
}

function getEventImage($artist, $defaultCategory, $event, $tab) {
    // Event names ("X vs. Y", "Tour 2027 - Night 2") never have an image of
    // their own; the performer does. Serve-only: cards must not block.
    if ($artist) {
        return getArtistImage($artist, $defaultCategory, false);
    }
    return getCategoryFallbackImage($defaultCategory ?: [], $tab);
}

$venueKeywords = [
    'venue','stadium','arena','theater','theatre','hall',
    'center','centre','field','park','grounds','dome','club',
    'lounge','bar','auditorium','amphitheater','amphitheatre',
    'pavilion','coliseum','colosseum','ballpark'
];

function getCategoryFallbackImage($defaultCategory, $tab = '') {

    $subcategory = '';

    if (!empty($defaultCategory)) {
        if (($defaultCategory['depth'] ?? 0) == 2) {
            $subcategory = $defaultCategory['text']['name'];
        } else {
            foreach ($defaultCategory['ancestors'] ?? [] as $ancestor) {
                if ($ancestor['depth'] == 2) {
                    $subcategory = $ancestor['text']['name'];
                    break;
                }
            }
        }
    }

    $slug = str_replace([' ', '/', '(', ')', '-', '&'], '', $subcategory);

    if(($slug == 'OTHER' || empty($slug)) && !empty($tab)) {
        $slug = $tab;
    }

    return AWS_CDN_URL . 'categories/' . strtolower($slug) . '.webp';
}




function convertToFloat($value) {

    if (substr($value, 0, 1) === '-') {
        $value = substr($value, 1);
        $parts = explode('-', $value, 2);
        return '-' . $parts[0] . '.' . $parts[1];
    }

    $parts = explode('-', $value, 2);
    return $parts[0] . '.' . $parts[1];
}


/**
 * schema.org Offer for an event, or null when there is no real price.
 * The homepage and listing schemas used to default a missing price to "50"/"0"
 * and wrote prices like "$10" with the currency symbol, which is invalid and
 * claims prices the page does not show. $price may be 7350, "7350.00" or "$1,250".
 */
function seoOffer($url, $price) {
    if ($price === null || $price === '') return null;
    $num = (float) str_replace([',', '$', ' '], '', (string) $price);
    if ($num <= 0) return null;
    return [
        "@type"         => "Offer",
        "url"           => $url,
        "price"         => number_format($num, 2, '.', ''),
        "priceCurrency" => "USD",
        "availability"  => "https://schema.org/InStock",
    ];
}

/** Trim a <title> to ~60 characters at a word boundary, keeping the brand suffix when it fits. */
function seoClampTitle($title, $max = 62) {
    $title = trim(preg_replace('/\s+/', ' ', (string) $title));
    if (mb_strlen($title) <= $max) return $title;
    $brand = ' | Seat Outlet';
    $base = $title;
    if (mb_substr($title, -mb_strlen($brand)) === $brand) {
        $base = mb_substr($title, 0, -mb_strlen($brand));
        if (mb_strlen($base) + mb_strlen($brand) <= $max) return $title;
    } else {
        $brand = '';
    }
    $room = $max - mb_strlen($brand);
    if (mb_strlen($base) > $room) {
        $cut = mb_substr($base, 0, $room - 1);
        $sp = mb_strrpos($cut, ' ');
        $base = rtrim(($sp !== false && $sp > $room * 0.6) ? mb_substr($cut, 0, $sp) : $cut, " ,:;-\u{2013}") . "\u{2026}";
    }
    return $base . $brand;
}

/** Trim a meta description to ~155 characters at a word boundary. */
function seoClampDescription($desc, $max = 158) {
    $desc = trim(preg_replace('/\s+/', ' ', (string) $desc));
    if (mb_strlen($desc) <= $max) return $desc;
    $cut = mb_substr($desc, 0, $max - 1);
    $sp = mb_strrpos($cut, ' ');
    $cut = ($sp !== false && $sp > $max * 0.6) ? mb_substr($cut, 0, $sp) : $cut;
    return rtrim($cut, " ,:;-\u{2013}") . "\u{2026}";
}

/**
 * True when a TicketNetwork "get one" call found nothing. A missing id does not return an
 * empty body: the API answers {"Message":"The requested resource was not found."}, which is
 * non-empty and used to pass every `empty($x)` check, so invalid URLs rendered as 200 pages.
 */
function tnEntityMissing($r) {
    if (empty($r) || !is_array($r)) return true;
    return isset($r['Message']) && !isset($r['id']) && !isset($r['alphaCode']);
}

/**
 * True only when the API answered "not found" for this id. Anything else that
 * lacks the entity (empty body, timeout, circuit open, throttling, 5xx) is a
 * failure of ours or the API's, not proof the page is gone.
 */
function tnEntityDefinitelyMissing($r) {
    return is_array($r) && isset($r['Message']) && !isset($r['id']) && !isset($r['alphaCode'])
        && stripos((string) $r['Message'], 'not found') !== false;
}

/** The entity is missing because the API failed, not because it does not exist. */
function tnEntityUnavailable($r) {
    if (empty($r) || !is_array($r)) return true;
    return !isset($r['id']) && !isset($r['alphaCode']) && !tnEntityDefinitelyMissing($r);
}

/** Friendly "not found" content with ways back to inventory (no header/footer). */
function notFoundBlockHtml($what) {
    $w = htmlspecialchars((string) $what, ENT_QUOTES, 'UTF-8');
    return '<div class="container py-5 text-center"><h1 class="fs-3 fw-bold mb-2">' . $w . ' not found</h1>'
        . '<p class="text-muted mb-4">We could not find that page. It may have moved, or the event may have already taken place.</p>'
        . '<div class="d-flex flex-wrap justify-content-center gap-2">'
        . '<a class="btn btn-primary" href="/buy-tickets-online">Browse all events</a>'
        . '<a class="so-linkchip" href="/concert-tickets-for-sale">Concerts</a>'
        . '<a class="so-linkchip" href="/game-day-tickets">Sports</a>'
        . '<a class="so-linkchip" href="/buy-broadway-tickets">Theater</a>'
        . '<a class="so-linkchip" href="/city-events">Cities</a>'
        . '</div></div>';
}

/** "Try again in a moment" content for when the ticket feed failed (no header/footer). */
function unavailableBlockHtml($what) {
    $w = htmlspecialchars((string) $what, ENT_QUOTES, 'UTF-8');
    return '<div class="container py-5 text-center"><h1 class="fs-3 fw-bold mb-2">' . $w . ' temporarily unavailable</h1>'
        . '<p class="text-muted mb-4">Our ticket feed did not answer just now. Please try again in a few seconds.</p>'
        . '<div class="d-flex flex-wrap justify-content-center gap-2">'
        . '<a class="btn btn-primary" href="">Try again</a>'
        . '<a class="so-linkchip" href="/buy-tickets-online">Browse all events</a>'
        . '<a class="so-linkchip" href="/concert-tickets-for-sale">Concerts</a>'
        . '<a class="so-linkchip" href="/game-day-tickets">Sports</a>'
        . '<a class="so-linkchip" href="/buy-broadway-tickets">Theater</a>'
        . '</div></div>';
}

/** Whole "temporarily unavailable" page: HTTP 503 + Retry-After, noindex, never cached. */
function renderUnavailablePage($what) {
    http_response_code(503);
    header('Retry-After: 30');
    $pageRobots = 'noindex, follow';
    $pageMetaTitle = $what . ' temporarily unavailable | Seat Outlet';
    $pageMetaDescription = 'This page is temporarily unavailable. Please try again in a moment.';
    include 'header.php';
    echo unavailableBlockHtml($what);
    include 'footer.php';
    exit;
}

/**
 * Whole "not found" page: HTTP 404, noindex, branded. Call before any output.
 * Pass the API response that came back empty: if it was an API failure rather
 * than a real "not found", answer 503 (retry) so a throttled or down API can
 * never tell search engines that live pages are gone.
 */
function renderNotFoundPage($what, $apiResponse = null) {
    if ($apiResponse !== null && tnEntityUnavailable($apiResponse)) {
        renderUnavailablePage($what);
    }
    http_response_code(404);
    $pageRobots = 'noindex, follow';
    $pageMetaTitle = $what . ' not found | Seat Outlet';
    $pageMetaDescription = 'The page you were looking for could not be found. Browse concerts, sports, theater and festival tickets on Seat Outlet.';
    include 'header.php';
    echo notFoundBlockHtml($what);
    include 'footer.php';
    exit;
}

/**
 * URL of a front-end asset, preferring its minified build.
 *
 * `soAsset('js/main.js')` returns /js/main.min.js?v=<mtime> when
 * js/main.min.js exists (made by tools/build-assets.sh; CI fails when the
 * minified file is out of date), otherwise the readable source. css/style.css
 * is built together with css/skeleton.css into css/style.min.css.
 */
function soAsset($rel) {
    $rel = ltrim((string) $rel, '/');
    $min = preg_replace('/\.(css|js)$/', '.min.$1', $rel);
    $file = __DIR__ . '/' . $min;
    if ($min !== $rel && is_file($file)) {
        return rtrim(HOME_URL, '/') . '/' . $min . '?v=' . filemtime($file);
    }
    $file = __DIR__ . '/' . $rel;
    return rtrim(HOME_URL, '/') . '/' . $rel . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/**
 * Baseline response headers for every HTML page (called from header.php
 * before any output). No CSP yet: the pages load Bootstrap, Slick, Seatics,
 * Google Maps and fonts from several CDNs, so a strict policy needs its own
 * pass with reporting first.
 */
function sendSecurityHeaders() {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), payment=(), geolocation=(self)');
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}


/**
 * Cache-Control for public pages, so a CDN can serve the HTML instead of PHP.
 *
 * Public pages are identical for every visitor: no cookies or sessions are read, nothing is personalised
 * on the server (saved location, recently viewed and recent searches are applied by JavaScript in the
 * browser). So a shared cache may keep a page briefly:
 *   - browsers always revalidate (max-age=0); only shared caches (CDN) keep it, for s-maxage seconds;
 *   - stale-while-revalidate lets the CDN answer instantly while it refreshes in the background;
 *   - 404 pages are cached for a minute; checkout, confirmation, thank-you, admin and anything that is not
 *     a plain GET are never stored.
 * Takes effect only where a CDN cache rule honours origin headers (Cloudflare: "Cache Everything" with
 * "Respect origin TTL"); on its own this changes nothing for visitors. Tune with HTML_EDGE_CACHE_SECONDS
 * (default 120, 0 = send nothing). Prices and inventory shown in cached HTML can be that many seconds old;
 * the hosted checkout always re-prices. Pages that must never be cached can set $pageNoCache = true first.
 */
function sendPageCacheHeaders() {
    if (headers_sent()) return;
    $path = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $never = ['/checkout', '/order-confirmation', '/thank-you'];
    if (in_array($path, $never, true) || strpos($path, '/admin') === 0 || !empty($GLOBALS['pageNoCache'])
        || !in_array($method, ['GET', 'HEAD'], true)) {
        header('Cache-Control: private, no-store');
        return;
    }
    $env = getenv('HTML_EDGE_CACHE_SECONDS');
    $ttl = ($env === false || $env === '') ? 120 : max(0, (int) $env);
    if ($ttl === 0) return;
    $status = http_response_code() ?: 200;
    if (!empty($GLOBALS['tn_degraded'])) {
        header('Cache-Control: no-store');   // built without API data: never let a CDN keep it
    } elseif ($status === 404 || $status === 200) {
        header($status === 404
            ? 'Cache-Control: public, max-age=0, s-maxage=60'
            : 'Cache-Control: public, max-age=0, s-maxage=' . $ttl . ', stale-while-revalidate=' . ($ttl * 5) . ', stale-if-error=3600');
        // Listing data is fetched while the page body renders, after this header is chosen.
        // Hold the output so the header can be corrected at the end if an API call failed.
        ob_start('soDegradedGuard');
    } else {
        header('Cache-Control: no-store');
    }
}

/**
 * The page's focus keyword, shown in the strip above the header (the page's one <h1>) and again at the
 * bottom of the footer. Order: a keyword the page sets itself ($pageFocusKeyword), the admin's focus keyword
 * for this URL (page_rules), then for entity pages the "<name> Tickets" part of the meta title, then a default.
 */
/**
 * Prints the page's search-focused copy block (inc/seo-copy/<key>.php) just above the footer: a short guide, images, links
 * and an FAQ written around the page's focus keyword. A page with no file for its key prints nothing.
 * The copy files are plain HTML (headings, paragraphs, figures, <details> FAQ); $year is available to them.
 */
function soSeoCopy($key) {
    $file = __DIR__ . '/inc/seo-copy/' . preg_replace('/[^a-z0-9-]/', '', (string) $key) . '.php';
    if (!is_file($file)) { return; }
    $year = date('Y');
    echo "\n<section class=\"so-seo-copy\" data-so-copy=\"" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "\"><div class=\"container\"><div class=\"so-seo-copy__inner\">\n";
    include $file;
    echo "\n</div></div></section>\n";
}

/** Headline form of a focus keyword for the strip: "how to buy tickets online" -> "How to Buy Tickets Online". */
function soKeywordLabel($kw) {
    $small = ['a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'of', 'on', 'or', 'the', 'to', 'vs'];
    $caps  = ['bbb', 'faq', 'faqs', 'nfl', 'nba', 'mlb', 'nhl', 'mls', 'edm', 'usa'];
    $words = preg_split('/\s+/', trim((string) $kw));
    foreach ($words as $i => $w) {
        $lw = mb_strtolower($w);
        if (in_array($lw, $caps, true)) { $words[$i] = mb_strtoupper($w); continue; }
        if ($i > 0 && in_array($lw, $small, true)) { $words[$i] = $lw; continue; }
        $words[$i] = $w === $lw ? mb_strtoupper(mb_substr($w, 0, 1)) . mb_substr($w, 1) : $w;   // keep names already capitalised
    }
    return implode(' ', $words);
}

/**
 * The SEO plan for a URL path (inc/seo-keywords.php): focus keyword, title, description. Returns null for a path
 * that has no entry. The title keeps the brand out (header.php appends it) and "{Y}" is the current year.
 */
function soSeoPlan($path = null) {
    static $plan = null;
    if ($plan === null) { $plan = (require __DIR__ . '/inc/seo-keywords.php')['plan']; }
    if ($path === null) { return $plan; }
    $path = rtrim((string) $path, '/') ?: '/';
    if (!isset($plan[$path])) { return null; }
    [$kw, $title, $desc, $vol, $kd, $scored] = $plan[$path];
    return [
        'keyword' => $kw,
        'title' => $title !== null ? str_replace('{Y}', date('Y'), $title) : null,
        'description' => $desc,
        'volume' => $vol, 'difficulty' => $kd, 'scored' => $scored,
    ];
}

function soFocusKeyword() {
    static $kw = null;
    if ($kw !== null) return $kw;
    $clean = function ($v) {
        $v = trim(preg_replace('/\s+/', ' ', strip_tags((string) $v)));
        if (function_exists('mb_substr') && mb_strlen($v) > 70) $v = rtrim(mb_substr($v, 0, 70), " ,.;:-|");
        return $v;
    };
    $rule = $GLOBALS['pageRule'] ?? null;
    foreach ([$GLOBALS['pageFocusKeyword'] ?? '', is_array($rule) ? ($rule['focus_keyword'] ?? '') : ''] as $cand) {
        $cand = $clean($cand);
        if ($cand !== '') return $kw = $cand;
    }
    $planned = soSeoPlan(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if ($planned !== null && $planned['keyword'] !== '') { return $kw = $clean($planned['keyword']); }
    $title = (string) ($GLOBALS['pageMetaTitle'] ?? '');
    if (preg_match('/^(.{2,60}?\bTickets)\b/u', $title, $m)) {
        $cand = preg_replace('/^Events (?:in|at) /i', '', $m[1]);                  // "Events in Nevada Tickets" -> "Nevada Tickets"
        $cand = preg_replace('/,\s*[A-Z]{2}(\s+Tickets)$/', '$1', $cand);          // "Las Vegas, NV Tickets" -> "Las Vegas Tickets"
        $cand = $clean($cand);
        if ($cand !== '' && strlen($cand) <= 42 && !preg_match('/^(Search|Buy|Tickets)\b/i', $cand)) {   // generic or long page titles keep the default
            return $kw = $cand;
        }
    }
    return $kw = 'Buy Concert Tickets';
}

/**
 * Output filter for the page body. The keyword strip is the page's one <h1>; any other <h1> a page template prints
 * becomes an <h2 class="h1 ..."> so it keeps its look (css/style.css styles ".h1" like "h1") and the page has a
 * single H1. Turn off with KEYWORD_H1=0 in the environment.
 */
function soSingleH1($html) {
    $stripSeen = false; $demoted = false;
    return preg_replace_callback('#<(/?)h1\b([^>]*)>#i', function ($m) use (&$stripSeen, &$demoted) {
        if ($m[1] === '/') {
            if ($demoted) { $demoted = false; return '</h2>'; }
            return $m[0];
        }
        if (!$stripSeen && strpos($m[2], 'so-keyword-h1') !== false) { $stripSeen = true; return $m[0]; }
        $demoted = true;
        $attrs = $m[2];
        if (preg_match('/\bclass\s*=\s*(["\'])(.*?)\1/i', $attrs)) {
            $attrs = preg_replace('/\bclass\s*=\s*(["\'])(.*?)\1/i', 'class=$1$2 h1$1', $attrs, 1);
        } else {
            $attrs .= ' class="h1"';
        }
        return '<h2' . $attrs . '>';
    }, $html);
}

/**
 * Old URL shapes that serve the same page twice: /performers.php (and every other /name.php), and the
 * /event.php?id=123 and /event?id=123 forms. Send visitors and crawlers to the one real address with a permanent
 * redirect; before this they got a duplicate page titled "Performers.php" or "Event.php".
 */
function soRedirectLegacyUrl() {
    if (PHP_SAPI === 'cli' || headers_sent()) return;
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) return;
    $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) parse_url($uri, PHP_URL_PATH);
    $qs   = (string) parse_url($uri, PHP_URL_QUERY);
    $to   = null;
    if (($path === '/event.php' || $path === '/event') && isset($_GET['id']) && ctype_digit((string) $_GET['id']) && (int) $_GET['id'] > 0) {
        $ev = getTnEventById((int) $_GET['id']);
        if (!tnEntityMissing($ev) && !empty($ev['text']['name'])) {
            $to = '/event/' . createSlug($ev['text']['name'], (int) $_GET['id']);
            $qs = '';   // the id is now in the path
        } elseif (tnEntityDefinitelyMissing($ev)) {
            http_response_code(404);   // an id that does not exist is a real 404, not a 200 page that says "not found"
        }
    } elseif (preg_match('#^/([a-z0-9-]+)\.php$#', $path, $m) && is_file(__DIR__ . '/' . $m[1] . '.php')
              && !in_array($m[1], ['event', 'functions', 'header', 'footer', 'robots', 'sitemap'], true)) {
        $to = $m[1] === 'index' ? '/' : '/' . $m[1];
    }
    if ($to === null) return;
    http_response_code(301);
    header('Location: ' . $to . ($qs !== '' ? '?' . $qs : ''));
    header('Cache-Control: public, max-age=3600');
    exit;
}

/** Output-buffer callback: a page that rendered without API data is never CDN-cached. */
function soDegradedGuard($buffer) {
    if (!empty($GLOBALS['tn_degraded']) && !headers_sent()) {
        header('Cache-Control: no-store');
    }
    return $buffer;
}

function getTnCityEvents($cityId = 0, $params = []) {

    $today = date('Y-m-d');
    if ($cityId > 0) {
        $params['filter'] = "city/id eq $cityId and date/date ge $today";
    }

    return tnRequest('/catalog/v2/events/', $params);
}

/**
 * Total number of events matching $params. The old count functions asked
 * for perPage=500 and returned the size of that one page, but the API
 * caps a page at 200, so every listing that had more than 200 upcoming
 * events (Broadway alone has 500+) reported 200 and its "More Events"
 * button stopped 10 pages in. includeTotalCount gives the real figure
 * from a 1-row request.
 */
function tnCountEvents(array $params) {
    $params['page']    = 1;
    $params['perPage'] = 1;
    $params['includeTotalCount'] = 'true';
    $json = tnRequest('/catalog/v2/events/', $params);
    return (int) ($json['totalCount'] ?? $json['count'] ?? 0);
}

function getTnCityEventsCount($cityId = 0, $params = []) {
    
    $today = date('Y-m-d');
    if ($cityId > 0) {
        $params['filter'] = "city/id eq $cityId and date/date ge $today";
    }

    return tnCountEvents($params);
}

function getTnVenueEvents($venueId = 0, $params = []) {

    $today = date('Y-m-d');
    if ($venueId > 0) {
        $params['filter'] = "venue/id eq $venueId and date/date ge $today";
    }

    return tnRequest('/catalog/v2/events/', $params);
}

function getTnVenueEventsCount($venueId = 0, $params = []) {
    
    $today = date('Y-m-d');
    if ($venueId > 0) {
        $params['filter'] = "venue/id eq $venueId and date/date ge $today";
    }

    return tnCountEvents($params);
}

/**
 * state.php/country.php's own event fetchers - same shape as
 * getTnCityEvents()/getTnVenueEvents() above, for the two location
 * dimensions that didn't have a plain (all-categories) events page yet.
 */
function getTnStateEvents($stateId = 0, $params = []) {

    $today = date('Y-m-d');
    if ($stateId > 0) {
        $params['filter'] = "stateProvince/id eq $stateId and date/date ge $today";
    }

    return tnRequest('/catalog/v2/events/', $params);
}

function getTnStateEventsCount($stateId = 0, $params = []) {

    $today = date('Y-m-d');
    if ($stateId > 0) {
        $params['filter'] = "stateProvince/id eq $stateId and date/date ge $today";
    }

    return tnCountEvents($params);
}

function getTnCountryEvents($countryCode = '', $params = []) {

    $today = date('Y-m-d');
    if ($countryCode !== '') {
        $params['filter'] = "country/alphaCode eq '" . tnEscapeFilterValue($countryCode) . "' and date/date ge $today";
    }

    return tnRequest('/catalog/v2/events/', $params);
}

function getTnCountryEventsCount($countryCode = '', $params = []) {

    $today = date('Y-m-d');
    if ($countryCode !== '') {
        $params['filter'] = "country/alphaCode eq '" . tnEscapeFilterValue($countryCode) . "' and date/date ge $today";
    }

    return tnCountEvents($params);
}

function getTopFestivalPerformers() {

    $params = [
        'q' => 'festival',
        'performersRequested' => 8,
        'venuesRequested' => 0,
        'citiesRequested' => 0
    ];

    $url = BASE_URL . '/catalog/v2/suggest?' . http_build_query($params);

    $data = tnCurlRequest($url);

    return $data['performers']['results'] ?? [];
}

function createSlug($name, $id) {
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9\s-]/', '', $name);
    $name = preg_replace('/\s+/', '-', $name);
    $name = preg_replace('/-+/', '-', $name);
    $slug = $name . '-' . $id;
    return $slug;
}

function getTnCityById($cityId) {
    $endpoint = "/catalog/v2/cities/" . (int) $cityId;
    return tnRequest($endpoint);
}

function getTnVenueById($venueId) {
    $endpoint = "/catalog/v2/venues/" . (int) $venueId;
    return tnRequest($endpoint);
}

function getTnCatEvents($catId = 0, $params = []) {

    $today = date('Y-m-d');
    if ($catId > 0) {
        $params['filter'] = "contains(defaultCategory/path, '.$catId.') and date/date ge $today";
    }

    return tnRequest('/catalog/v2/events/', $params);
}

function getTnCatEventsCount($catId = 0, $params = []) {
    
    $today = date('Y-m-d');
    if ($catId > 0) {
        $params['filter'] = "contains(defaultCategory/path, '.$catId.') and date/date ge $today";
    }

    return tnCountEvents($params);
}

function getTnCatById($catId) {
    $params['filter'] = "contains(path, '." . (int) $catId . ".') and depth eq 2";
    $params['perPage'] = 1;
    return tnRequest("/catalog/v2/categories/", $params);
}




function set_image($imageCacheKey, $imageUrl, $mysqli = MYSQLI) {

    if (empty($imageCacheKey) || empty($imageUrl)) {
        return false;
    }

    $stmt = $mysqli->prepare("
        INSERT INTO images (imgkey, url)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE url = VALUES(url)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $imageCacheKey, $imageUrl);

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}

function get_image($imageCacheKey, $mysqli = MYSQLI) {

    $stmt = $mysqli->prepare("
        SELECT url FROM images WHERE imgkey = ? LIMIT 1
    ");

    $stmt->bind_param("s", $imageCacheKey);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $result['url'] ?? null;
}

function set_keyword($keyword, $results, $mysqli = MYSQLI) {

    if (empty($keyword) || empty($results)) {
        return false;
    }

    $stmt = $mysqli->prepare("
        INSERT INTO keywords (keyword, results)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE results = VALUES(results)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $keyword, $results);

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}

function get_keyword($keyword, $mysqli = MYSQLI) {

    $stmt = $mysqli->prepare("
        SELECT results FROM keywords WHERE keyword = ? LIMIT 1
    ");

    $stmt->bind_param("s", $keyword);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $result['results'] ?? null;
}

$concertsKeywords = [
    "50s-60s-era" => ['classic','oldies','retro','vintage music'],
    "alternative" => ['alternative','indie','rock band'],
    "bluegrass" => ['bluegrass','acoustic','folk band'],
    "classical" => ['classical','orchestra','composer','symphony'],
    "comedy" => ['comedian','stand-up','comedy'],
    "country-folk" => ['country','folk','singer'],
    "festival-tour" => ['festival','music festival','tour'],
    "hard-rock-metal" => ['rock','metal','heavy metal','band'],
    "holiday" => ['holiday show','christmas music'],
    "jazz-blues" => ['jazz','blues','musician'],
    "las-vegas-shows" => ['las vegas show','residency','live show'],
    "latin" => ['latin music','reggaeton','latin artist'],
    "new-age" => ['new age','instrumental','ambient'],
    "other" => ['music','artist'],
    "pop-rock" => ['pop','rock','band','artist','musician'],
    "rnb-soul" => ['rnb','soul','singer'],
    "rap-hip-hop" => ['rap','hip hop','rapper'],
    "reggae-reggaeton" => ['reggae','reggaeton'],
    "religious" => ['gospel','christian','worship'],
    "techno-electronic" => ['dj','edm','electronic'],
    "world" => ['world music','international artist'],
    "performance-series" => ['live performance','series'],
    "children-family" => ['kids show','family show']
];

$sportsKeywords = [
    "baseball" => ['baseball','mlb','pitcher','batter','team'],
    "basketball" => ['basketball','nba','player','dunk'],
    "boxing" => ['boxing','boxer','fight','ring'],
    "cricket" => ['cricket','batsman','bowler','innings'],
    "football" => ['football','nfl','quarterback','touchdown'],
    "golf" => ['golf','golfer','pga','tournament'],
    "gymnastics" => ['gymnastics','gymnast','olympic'],
    "hockey" => ['hockey','nhl','ice hockey','goalie'],
    "lacrosse" => ['lacrosse','team','match'],
    "olympics" => ['olympics','olympic athlete'],
    "other" => ['sports','athlete'],
    "racing" => ['racing','nascar','formula','driver'],
    "rodeo" => ['rodeo','bull riding','cowboy'],
    "rugby" => ['rugby','team','match'],
    "skating" => ['skating','figure skating','ice skating'],
    "soccer" => ['soccer','football','fifa','goal'],
    "tennis" => ['tennis','player','grand slam'],
    "volleyball" => ['volleyball','team','match'],
    "wrestling" => ['wrestling','wwe','wrestler'],
    "mixed-martial-arts" => ['mma','ufc','fighter','fight'],
    "softball" => ['softball','team','pitcher']
];

$theaterKeywords = [
    "ballet" => ['ballet','dance','performance','company'],
    "broadway" => ['broadway','theatre','musical','stage'],
    "children-family" => ['kids show','family show','children theatre'],
    "dance" => ['dance','dance performance','choreography'],
    "las-vegas" => ['las vegas show','residency','live show'],
    "musical-play" => ['musical','play','theatre','stage show'],
    "off-broadway" => ['off broadway','theatre','play'],
    "opera" => ['opera','opera singer','classical performance'],
    "other" => ['theatre','performance'],
    "cirque-du-soleil" => ['cirque','acrobatics','circus','performance'],
    "west-end" => ['west end','london theatre','stage'],
    "festival" => ['theatre festival','performance festival']
];

$festivalKeywords = [
    "festival-tour" => ['festival','music festival','tour'],
];

function kgSlugify(string $value) {
    $value = strtolower(trim($value));
    $value = preg_replace('/&/', 'and', $value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim($value, '-');
}

function getKgKeywordsByCategory($category, $subcategory) {

    global $concertsKeywords, $sportsKeywords, $theaterKeywords, $festivalKeywords;

    $category = strtolower(trim($category));
    $slug = kgSlugify($subcategory);

    if ($category === 'concerts') {
        return $concertsKeywords[$slug] ?? [];
    }

    if ($category === 'sports') {
        return $sportsKeywords[$slug] ?? [];
    }

    if ($category === 'theater') {
        return $theaterKeywords[$slug] ?? [];
    }

    if ($category === 'festival') {
        return $theaterKeywords[$slug] ?? [];
    }

    return [];
}




function getTopPerformersByCategory($categoryPath) {

    // eventFilter keeps the list to performers who actually have inventory
    // on sale: without it the sales-rank sort surfaces performers with no
    // upcoming events at all (verified live), which is a dead click for a
    // buyer. Sort was 'salesRank' (ascending = the salesRank=100 baseline in
    // alphabetical order: "3 Doors Down, 50 Cent, A Perfect Circle").
    $params = [
        'categoryFilter' => "contains(path,'" . tnEscapeFilterValue($categoryPath) . "')",
        'eventFilter' => '_metadata/hasTickets eq true',
        'sort'   => '-salesRank',
        'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}',
        'perPage'=> 5
    ];
    
    $data = tnRequest('/catalog/v2/performers', $params);

    $performers = [];

    if (!empty($data['results'])) {
        foreach ($data['results'] as $item) {
            $performers[] = [
                'id'   => $item['id'] ?? '',
                'name' => $item['text']['name'] ?? '',
                'slug' => strtolower($item['uriComponent']) ?? ''
            ];
        }
    }

    return $performers;
}

/*
|--------------------------------------------------------------------------
| Page rules (light SEO / redirect / schema admin table)
|--------------------------------------------------------------------------
| CRUD for the page_rules table used by the admin panel (see
| db/migrations/0002_page_rules.sql for the schema). resolvePageRule(),
| below, is what header.php calls to actually apply a rule on the
| front end.
*/

function normalizePagePath($path) {
    $path = parse_url((string) $path, PHP_URL_PATH) ?: '/';
    if ($path !== '/' && substr($path, -1) === '/') {
        $path = rtrim($path, '/');
    }
    return $path === '' ? '/' : $path;
}

function listPageRules($search = '', $mysqli = MYSQLI) {
    if ($search !== '') {
        $stmt = $mysqli->prepare(
            'SELECT * FROM page_rules WHERE url_path LIKE CONCAT(\'%\', ?, \'%\') ORDER BY updated_at DESC'
        );
        $stmt->bind_param('s', $search);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query('SELECT * FROM page_rules ORDER BY updated_at DESC');
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    if (isset($stmt)) {
        $stmt->close();
    }
    return $rows;
}

function getPageRuleById($id, $mysqli = MYSQLI) {
    $stmt = $mysqli->prepare('SELECT * FROM page_rules WHERE ID = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// Every front-end page load calls getPageRuleByPath() once (via
// resolvePageRule()), and for the overwhelming majority of URLs today
// there's no row to find - that's still a full round-trip to MySQL on
// every single request, for nothing. APCu (common on shared/cPanel PHP
// hosting, but not guaranteed - see functions.php's other optional
// integrations for the same pattern) caches both hits and the "no rule
// here" result for a short TTL, so repeat requests for the same URL within
// that window skip the database entirely. Falls back to a plain query with
// zero behavior change when APCu isn't available.
const PAGE_RULE_CACHE_TTL = 60;

function pageRuleCacheKey($urlPath) {
    return 'seatoutlet_page_rule:' . $urlPath;
}

function pageRuleCacheGet($urlPath, &$found) {
    $found = false;
    if (!function_exists('apcu_fetch')) {
        return null;
    }
    $value = apcu_fetch(pageRuleCacheKey($urlPath), $success);
    $found = $success;
    return $success ? $value : null;
}

function pageRuleCacheSet($urlPath, $value) {
    if (function_exists('apcu_store')) {
        apcu_store(pageRuleCacheKey($urlPath), $value, PAGE_RULE_CACHE_TTL);
    }
}

function pageRuleCacheForget($urlPath) {
    if (function_exists('apcu_delete')) {
        apcu_delete(pageRuleCacheKey($urlPath));
    }
}

function getPageRuleByPath($urlPath, $mysqli = MYSQLI) {
    $urlPath = normalizePagePath($urlPath);

    $cached = pageRuleCacheGet($urlPath, $found);
    if ($found) {
        return $cached;
    }

    // A missing page_rules table (migrations not yet applied after a deploy)
    // must not take every page down: fall back to the per-page-type defaults
    // and don't cache the miss, so rules take effect as soon as it exists.
    try {
        $stmt = $mysqli->prepare('SELECT * FROM page_rules WHERE url_path = ? AND is_active = 1 LIMIT 1');
        $stmt->bind_param('s', $urlPath);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('page_rules lookup failed (run php db/migrate.php?): ' . $e->getMessage());
        return null;
    }
    $rule = $row ?: null;

    pageRuleCacheSet($urlPath, $rule);

    return $rule;
}

function savePageRule(array $data, $mysqli = MYSQLI) {
    $id              = (int) ($data['id'] ?? 0);
    $urlPath         = normalizePagePath($data['url_path'] ?? '');
    $focusKeyword    = trim((string) ($data['focus_keyword'] ?? '')) ?: null;
    $metaTitle       = trim((string) ($data['meta_title'] ?? '')) ?: null;
    $metaDescription = trim((string) ($data['meta_description'] ?? '')) ?: null;
    $canonicalUrl    = trim((string) ($data['canonical_url'] ?? '')) ?: null;
    $robots          = trim((string) ($data['robots'] ?? '')) ?: null;
    $schemaJson      = trim((string) ($data['schema_json'] ?? '')) ?: null;
    $redirectTo      = trim((string) ($data['redirect_to'] ?? '')) ?: null;
    $redirectCode    = !empty($data['redirect_code']) ? (int) $data['redirect_code'] : null;
    $isActive        = !empty($data['is_active']) ? 1 : 0;

    if ($urlPath === '' || $urlPath === '/' && empty($data['url_path'])) {
        throw new InvalidArgumentException('A URL path is required.');
    }
    if ($schemaJson !== null) {
        json_decode($schemaJson);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Schema JSON is not valid JSON: ' . json_last_error_msg());
        }
    }
    if ($redirectCode !== null && !in_array($redirectCode, [301, 302], true)) {
        throw new InvalidArgumentException('Redirect code must be 301 or 302.');
    }

    // Editing an existing rule can change its url_path - capture the old one
    // now so its stale cache entry gets invalidated too, not just the new path's.
    $oldUrlPath = null;
    if ($id > 0) {
        $existing = getPageRuleById($id, $mysqli);
        $oldUrlPath = $existing['url_path'] ?? null;
    }

    if ($id > 0) {
        $stmt = $mysqli->prepare(
            'UPDATE page_rules SET url_path = ?, focus_keyword = ?, meta_title = ?, meta_description = ?, canonical_url = ?,
             robots = ?, schema_json = ?, redirect_to = ?, redirect_code = ?, is_active = ?, updated_at = NOW()
             WHERE ID = ?'
        );
        $stmt->bind_param(
            'ssssssssiii',
            $urlPath, $focusKeyword, $metaTitle, $metaDescription, $canonicalUrl, $robots, $schemaJson,
            $redirectTo, $redirectCode, $isActive, $id
        );
    } else {
        $stmt = $mysqli->prepare(
            'INSERT INTO page_rules
             (url_path, focus_keyword, meta_title, meta_description, canonical_url, robots, schema_json,
              redirect_to, redirect_code, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->bind_param(
            'ssssssssii',
            $urlPath, $focusKeyword, $metaTitle, $metaDescription, $canonicalUrl, $robots, $schemaJson,
            $redirectTo, $redirectCode, $isActive
        );
    }
    $ok = $stmt->execute();
    $error = $stmt->error;
    $stmt->close();
    if (!$ok) {
        throw new RuntimeException($error ?: 'Could not save this page rule.');
    }

    pageRuleCacheForget($urlPath);
    if ($oldUrlPath !== null && $oldUrlPath !== $urlPath) {
        pageRuleCacheForget($oldUrlPath);
    }

    return true;
}

function deletePageRule($id, $mysqli = MYSQLI) {
    $existing = getPageRuleById($id, $mysqli);

    $stmt = $mysqli->prepare('DELETE FROM page_rules WHERE ID = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    if ($existing !== null) {
        pageRuleCacheForget($existing['url_path']);
    }

    return true;
}

/**
 * Front-end entry point for page_rules: looks up the rule for the current
 * request path and, if it's a redirect, sends it and exits immediately -
 * callers must invoke this before any HTML output. Otherwise returns the
 * rule (or null) for header.php to use as SEO metadata overrides.
 */
function resolvePageRule($mysqli = MYSQLI) {
    $path = normalizePagePath($_SERVER['REQUEST_URI'] ?? '/');
    $rule = getPageRuleByPath($path, $mysqli);
    if ($rule && !empty($rule['redirect_to'])) {
        $code = (int) ($rule['redirect_code'] ?: 301);
        header('Location: ' . $rule['redirect_to'], true, $code);
        exit;
    }
    return $rule;
}

/**
 * Page Content Management (page_content_blocks table -
 * db/migrations/0006_page_content_blocks.sql). Lets an admin edit specific
 * copy blocks on static pages without a code deploy. A page opts in by
 * wrapping a piece of copy in getContentBlock($pagePath, $blockKey,
 * $defaultHtml) - with no matching row (or an empty content column) it
 * just returns $defaultHtml unchanged, so nothing on the site changes
 * until an admin actually edits that block. Same APCu-cache-with-fallback
 * pattern as page_rules above, since a page with editable blocks calls
 * this once per block on every front-end render.
 */
const CONTENT_BLOCK_CACHE_TTL = 60;

function contentBlockCacheKey($pagePath, $blockKey) {
    return 'seatoutlet_content_block:' . $pagePath . ':' . $blockKey;
}

function getContentBlock($pagePath, $blockKey, $defaultHtml, $mysqli = MYSQLI) {
    $pagePath = normalizePagePath($pagePath);
    $cacheKey = contentBlockCacheKey($pagePath, $blockKey);

    if (function_exists('apcu_fetch')) {
        $cached = apcu_fetch($cacheKey, $success);
        if ($success) {
            return $cached !== null && $cached !== '' ? $cached : $defaultHtml;
        }
    }

    // Same fallback as getPageRuleByPath(): no table yet means default copy.
    try {
        $stmt = $mysqli->prepare('SELECT content FROM page_content_blocks WHERE page_path = ? AND block_key = ? LIMIT 1');
        $stmt->bind_param('ss', $pagePath, $blockKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('page_content_blocks lookup failed (run php db/migrate.php?): ' . $e->getMessage());
        return $defaultHtml;
    }

    $content = $row['content'] ?? null;

    if (function_exists('apcu_store')) {
        apcu_store($cacheKey, $content, CONTENT_BLOCK_CACHE_TTL);
    }

    return $content !== null && $content !== '' ? $content : $defaultHtml;
}

function contentBlockCacheForget($pagePath, $blockKey) {
    if (function_exists('apcu_delete')) {
        apcu_delete(contentBlockCacheKey(normalizePagePath($pagePath), $blockKey));
    }
}

/**
 * The pages/blocks wired up to be editable today. Purely a UI convenience
 * for the admin list screen (so there's something to browse/edit before
 * any row exists yet) - getContentBlock() itself works for any page_path/
 * block_key a developer chooses, registered here or not.
 */
function getContentBlockRegistry() {
    return [
        ['page_path' => '/about-us', 'block_key' => 'hero-story', 'label' => 'About Us — Hero intro (2 paragraphs)'],
        ['page_path' => '/about-us', 'block_key' => 'about-body', 'label' => 'About Us — "Built for Fans" body (4 paragraphs)'],
        ['page_path' => '/why-us', 'block_key' => 'founding-story', 'label' => 'Why Us — Founding story headline'],
        ['page_path' => '/what-we-do', 'block_key' => 'hero-sub', 'label' => 'What We Do — Hero subtitle'],
        ['page_path' => '/guarantee', 'block_key' => 'hero-subtitle', 'label' => 'Guarantee — Hero subtitle'],
        ['page_path' => '/buyer-protection', 'block_key' => 'trust-safety-intro', 'label' => 'Buyer Protection — Trust & Safety intro'],
    ];
}

function listPageContentBlocks($search = '', $mysqli = MYSQLI) {
    $rows = [];
    if ($search !== '') {
        $stmt = $mysqli->prepare(
            'SELECT * FROM page_content_blocks WHERE page_path LIKE CONCAT(\'%\', ?, \'%\') OR block_key LIKE CONCAT(\'%\', ?, \'%\') OR label LIKE CONCAT(\'%\', ?, \'%\') ORDER BY page_path, block_key'
        );
        $stmt->bind_param('sss', $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query('SELECT * FROM page_content_blocks ORDER BY page_path, block_key');
    }
    while ($row = $result->fetch_assoc()) {
        $rows[$row['page_path'] . '|' . $row['block_key']] = $row;
    }
    if (isset($stmt)) {
        $stmt->close();
    }

    // Merge in registered-but-never-edited blocks so the admin list shows
    // every known editable area, not just the ones already customized.
    foreach (getContentBlockRegistry() as $entry) {
        $key = $entry['page_path'] . '|' . $entry['block_key'];
        if (!isset($rows[$key])) {
            $rows[$key] = [
                'ID' => null,
                'page_path' => $entry['page_path'],
                'block_key' => $entry['block_key'],
                'label' => $entry['label'],
                'content' => null,
                'updated_at' => null,
            ];
        } else {
            // A registered block's label is the canonical one, even if a
            // freeform row (from before it was registered) saved a
            // different label.
            $rows[$key]['label'] = $entry['label'];
        }
    }

    if ($search !== '') {
        $needle = mb_strtolower($search);
        $rows = array_filter($rows, function ($row) use ($needle) {
            return str_contains(mb_strtolower($row['page_path']), $needle)
                || str_contains(mb_strtolower($row['block_key']), $needle)
                || str_contains(mb_strtolower($row['label']), $needle);
        });
    }

    $rows = array_values($rows);
    usort($rows, fn($a, $b) => [$a['page_path'], $a['block_key']] <=> [$b['page_path'], $b['block_key']]);
    return $rows;
}

function getPageContentBlockById($id, $mysqli = MYSQLI) {
    $stmt = $mysqli->prepare('SELECT * FROM page_content_blocks WHERE ID = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function getPageContentBlockByPathKey($pagePath, $blockKey, $mysqli = MYSQLI) {
    $pagePath = normalizePagePath($pagePath);
    $stmt = $mysqli->prepare('SELECT * FROM page_content_blocks WHERE page_path = ? AND block_key = ? LIMIT 1');
    $stmt->bind_param('ss', $pagePath, $blockKey);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function savePageContentBlock(array $data, $mysqli = MYSQLI) {
    $id        = (int) ($data['id'] ?? 0);
    $pagePath  = normalizePagePath($data['page_path'] ?? '');
    $blockKey  = trim((string) ($data['block_key'] ?? ''));
    $label     = trim((string) ($data['label'] ?? ''));
    $content   = (string) ($data['content'] ?? '');

    if ($pagePath === '' || $pagePath === '/' && empty($data['page_path'])) {
        throw new InvalidArgumentException('A page path is required.');
    }
    if ($blockKey === '') {
        throw new InvalidArgumentException('A block key is required.');
    }
    if ($label === '') {
        throw new InvalidArgumentException('A label is required.');
    }

    $oldPagePath = null;
    $oldBlockKey = null;
    if ($id > 0) {
        $existing = getPageContentBlockById($id, $mysqli);
        $oldPagePath = $existing['page_path'] ?? null;
        $oldBlockKey = $existing['block_key'] ?? null;
    }

    if ($id > 0) {
        $stmt = $mysqli->prepare(
            'UPDATE page_content_blocks SET page_path = ?, block_key = ?, label = ?, content = ?, updated_at = NOW()
             WHERE ID = ?'
        );
        $stmt->bind_param('ssssi', $pagePath, $blockKey, $label, $content, $id);
    } else {
        // A registered block (or a re-save of one that was deleted) may
        // already have a row from before - upsert instead of erroring on
        // the unique key.
        $stmt = $mysqli->prepare(
            'INSERT INTO page_content_blocks (page_path, block_key, label, content, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE label = VALUES(label), content = VALUES(content), updated_at = NOW()'
        );
        $stmt->bind_param('ssss', $pagePath, $blockKey, $label, $content);
    }
    $ok = $stmt->execute();
    $error = $stmt->error;
    $stmt->close();
    if (!$ok) {
        throw new RuntimeException($error ?: 'Could not save this content block.');
    }

    contentBlockCacheForget($pagePath, $blockKey);
    if ($oldPagePath !== null && ($oldPagePath !== $pagePath || $oldBlockKey !== $blockKey)) {
        contentBlockCacheForget($oldPagePath, $oldBlockKey);
    }

    return true;
}

function deletePageContentBlock($id, $mysqli = MYSQLI) {
    $existing = getPageContentBlockById($id, $mysqli);

    $stmt = $mysqli->prepare('DELETE FROM page_content_blocks WHERE ID = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    if ($existing !== null) {
        contentBlockCacheForget($existing['page_path'], $existing['block_key']);
    }

    return true;
}

/**
 * Blog (blog_posts table - db/migrations/0004_blog_posts.sql). Same CRUD
 * shape as page_rules above: list/get/save/delete, admin-facing, no caching
 * layer (unlike page_rules this isn't hit on every single front-end page
 * load, just blog.php/blog-post.php, so it doesn't need one).
 */
function blogGenerateUniqueSlug($title, ?int $excludeId = null, $mysqli = MYSQLI) {
    $base = sanitize_title($title);
    if ($base === '') {
        $base = 'post';
    }
    $slug = $base;
    $suffix = 2;
    while (blogSlugExists($slug, $excludeId, $mysqli)) {
        $slug = $base . '-' . $suffix;
        $suffix++;
    }
    return $slug;
}

function blogSlugExists($slug, ?int $excludeId, $mysqli = MYSQLI) {
    if ($excludeId !== null) {
        $stmt = $mysqli->prepare('SELECT ID FROM blog_posts WHERE slug = ? AND ID != ? LIMIT 1');
        $stmt->bind_param('si', $slug, $excludeId);
    } else {
        $stmt = $mysqli->prepare('SELECT ID FROM blog_posts WHERE slug = ? LIMIT 1');
        $stmt->bind_param('s', $slug);
    }
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc() !== null;
    $stmt->close();
    return $found;
}

function listBlogPosts($search = '', $mysqli = MYSQLI) {
    if ($search !== '') {
        $stmt = $mysqli->prepare(
            'SELECT * FROM blog_posts WHERE title LIKE CONCAT(\'%\', ?, \'%\') ORDER BY updated_at DESC'
        );
        $stmt->bind_param('s', $search);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query('SELECT * FROM blog_posts ORDER BY updated_at DESC');
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    if (isset($stmt)) {
        $stmt->close();
    }
    return $rows;
}

function getBlogPostById($id, $mysqli = MYSQLI) {
    $stmt = $mysqli->prepare('SELECT * FROM blog_posts WHERE ID = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function getBlogPostBySlug($slug, bool $onlyPublished = true, $mysqli = MYSQLI) {
    $sql = 'SELECT * FROM blog_posts WHERE slug = ?';
    if ($onlyPublished) {
        $sql .= ' AND status = \'published\' AND published_at IS NOT NULL AND published_at <= NOW()';
    }
    $sql .= ' LIMIT 1';
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function countPublishedBlogPosts($mysqli = MYSQLI) {
    $result = $mysqli->query(
        'SELECT COUNT(*) AS c FROM blog_posts WHERE status = \'published\' AND published_at IS NOT NULL AND published_at <= NOW()'
    );
    $row = $result->fetch_assoc();
    return (int) ($row['c'] ?? 0);
}

function listPublishedBlogPosts(int $page = 1, int $perPage = 10, $mysqli = MYSQLI) {
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;
    $stmt = $mysqli->prepare(
        'SELECT * FROM blog_posts WHERE status = \'published\' AND published_at IS NOT NULL AND published_at <= NOW()
         ORDER BY published_at DESC LIMIT ? OFFSET ?'
    );
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function saveBlogPost(array $data, $mysqli = MYSQLI) {
    $id              = (int) ($data['id'] ?? 0);
    $title           = trim((string) ($data['title'] ?? ''));
    $focusKeyword    = trim((string) ($data['focus_keyword'] ?? '')) ?: null;
    $excerpt         = trim((string) ($data['excerpt'] ?? '')) ?: null;
    $content         = (string) ($data['content'] ?? '');
    $featuredImage   = trim((string) ($data['featured_image'] ?? '')) ?: null;
    $authorName      = trim((string) ($data['author_name'] ?? '')) ?: null;
    $metaTitle       = trim((string) ($data['meta_title'] ?? '')) ?: null;
    $metaDescription = trim((string) ($data['meta_description'] ?? '')) ?: null;
    $status          = ($data['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $slugInput       = trim((string) ($data['slug'] ?? ''));

    if ($title === '') {
        throw new InvalidArgumentException('A title is required.');
    }
    if (trim(strip_tags($content)) === '') {
        throw new InvalidArgumentException('Post content is required.');
    }

    $existing = $id > 0 ? getBlogPostById($id, $mysqli) : null;

    if ($slugInput !== '') {
        $slug = sanitize_title($slugInput);
        if ($slug !== '' && blogSlugExists($slug, $id > 0 ? $id : null, $mysqli)) {
            throw new InvalidArgumentException('That slug is already used by another post.');
        }
        if ($slug === '') {
            $slug = blogGenerateUniqueSlug($title, $id > 0 ? $id : null, $mysqli);
        }
    } else {
        $slug = $existing['slug'] ?? blogGenerateUniqueSlug($title, $id > 0 ? $id : null, $mysqli);
    }

    // First time a post is marked published, stamp published_at now (unless
    // one was already set, e.g. re-saving an already-published post, or
    // explicitly backdating/scheduling via the admin form).
    $publishedAt = $existing['published_at'] ?? null;
    if (!empty($data['published_at'])) {
        $parsed = strtotime((string) $data['published_at']);
        $publishedAt = $parsed ? date('Y-m-d H:i:s', $parsed) : $publishedAt;
    } elseif ($status === 'published' && empty($publishedAt)) {
        $publishedAt = date('Y-m-d H:i:s');
    }

    if ($id > 0) {
        $stmt = $mysqli->prepare(
            'UPDATE blog_posts SET title = ?, focus_keyword = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?,
             author_name = ?, meta_title = ?, meta_description = ?, status = ?, published_at = ?, updated_at = NOW()
             WHERE ID = ?'
        );
        $stmt->bind_param(
            'sssssssssssi',
            $title, $focusKeyword, $slug, $excerpt, $content, $featuredImage,
            $authorName, $metaTitle, $metaDescription, $status, $publishedAt, $id
        );
    } else {
        $stmt = $mysqli->prepare(
            'INSERT INTO blog_posts
             (title, focus_keyword, slug, excerpt, content, featured_image, author_name, meta_title, meta_description, status, published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->bind_param(
            'sssssssssss',
            $title, $focusKeyword, $slug, $excerpt, $content, $featuredImage,
            $authorName, $metaTitle, $metaDescription, $status, $publishedAt
        );
    }
    $ok = $stmt->execute();
    $error = $stmt->error;
    $stmt->close();
    if (!$ok) {
        throw new RuntimeException($error ?: 'Could not save this post.');
    }

    return true;
}

function deleteBlogPost($id, $mysqli = MYSQLI) {
    $stmt = $mysqli->prepare('DELETE FROM blog_posts WHERE ID = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return true;
}

/*
|--------------------------------------------------------------------------
| Reusable schema.org JSON-LD builders
|--------------------------------------------------------------------------
| These exist because inc/seo.php, inc/seo-event.php and inc/seo-tickets.php
| were each hand-writing a JSON-LD <script> tag, with the same fabricated
| Organization reviews/AggregateRating copy-pasted into all three, a
| trailing comma in two of them that made the whole block invalid JSON
| (verified: json_decode() on that exact fragment returns NULL with a
| syntax error), and several schema nodes wrapped in their own nested
| {"@context":...,"@graph":[...]} sub-document instead of being flat nodes
| in the outer graph (also verified: that decodes as valid JSON, but the
| resulting @graph element is a PHP/JSON array with no @type, which
| Google's structured data parser can't resolve to any schema.org type).
|
| outputJsonLdGraph() builds the whole @graph as one real PHP array and
| lets json_encode() produce the <script> tag's contents in a single call,
| so there is no hand-written JSON left to typo a comma into.
*/

/**
 * Event.performer for schema.org accepts either one entity or an array of
 * them. A single-artist concert has one real performer, but a sports
 * matchup event ("Austin FC vs Inter Milan") has two - the previous code
 * (`$event['performers'][0]['name'] ?? $event['text']['name']`) only ever
 * listed the first team, silently dropping the opponent from structured
 * data. Uses every entry in $event['performers'] when there's more than
 * one, falling back to the event's own name only when TicketNetwork
 * didn't supply any performer entities at all.
 */
function buildEventPerformerSchema(array $event) {
    $performers = $event['performers'] ?? [];
    if (empty($performers)) {
        return [
            "@type" => "PerformingGroup",
            "name" => $event['text']['name'] ?? '',
        ];
    }
    if (count($performers) === 1) {
        return [
            "@type" => "PerformingGroup",
            "name" => $performers[0]['name'] ?? ($event['text']['name'] ?? ''),
        ];
    }
    return array_map(fn($p) => [
        "@type" => "PerformingGroup",
        "name" => $p['name'] ?? '',
    ], $performers);
}

function buildOrganizationSchema() {
    // Deliberately no "review" or "aggregateRating" here. The Organization
    // schema in every seo include file previously had six fabricated named
    // reviews and a hardcoded 4.8-star/1200-review AggregateRating - not
    // backed by any reviews table or collection flow anywhere in this app.
    // Google's structured data policies prohibit fake ratings/reviews and
    // enforce it with a manual action that can strip rich results
    // sitewide. Add this back once there's a real reviews data source to
    // pull from - never with placeholder numbers.
    return [
        "@type" => "Organization",
        "@id" => HOME_URL . "/#organization",
        "name" => "Seat Outlet",
        "url" => HOME_URL . "/",
        "logo" => [
            "@type" => "ImageObject",
            "@id" => HOME_URL . "/#logo",
            "url" => HOME_URL . "/images/seatoutlet-logo.webp"
        ],
        "image" => HOME_URL . "/images/seatoutlet-logo.webp",
        "description" => "Verified ticket marketplace network to buy concert, sports, theater, and live event tickets online.",
        "sameAs" => [
            "https://www.facebook.com/profile.php?id=61588886945534",
            "https://www.instagram.com/seatoutlet/",
            "https://www.youtube.com/@SeatOutlet",
            "https://linktr.ee/seatoutlet"
        ]
    ];
}

function buildWebsiteSchema() {
    return [
        "@type" => "WebSite",
        "@id" => HOME_URL . "/#website",
        "url" => HOME_URL . "/",
        "name" => "Seat Outlet",
        "publisher" => [
            "@id" => HOME_URL . "/#organization"
        ]
    ];
}

/**
 * @param array $breadcrumbs Same shape buildCategoryBreadcrumb() returns:
 *                           [['label' => ..., 'url' => ...], ...]
 * @param string|null $currentLabel The current (non-linked) page/item name,
 *                                  appended as the last, unlinked crumb -
 *                                  matches how the visible breadcrumb <nav>
 *                                  on these pages renders it.
 */
function buildBreadcrumbListSchema(array $breadcrumbs, ?string $currentLabel = null) {
    $items = [];
    $position = 1;

    foreach ($breadcrumbs as $crumb) {
        if (empty($crumb['label'])) continue;
        $items[] = [
            "@type" => "ListItem",
            "position" => $position++,
            "name" => $crumb['label'],
            "item" => !empty($crumb['url']) ? $crumb['url'] : null,
        ];
    }

    if ($currentLabel !== null && $currentLabel !== '') {
        $items[] = [
            "@type" => "ListItem",
            "position" => $position++,
            "name" => $currentLabel,
        ];
    }

    return [
        "@type" => "BreadcrumbList",
        "itemListElement" => $items,
    ];
}

/**
 * @param array $faqs Each element ['question' => ..., 'answer' => ...],
 *                     already through any placeholder substitution (e.g.
 *                     [artist_name]) so this matches the visible accordion
 *                     content exactly - Google requires FAQPage schema to
 *                     match what's actually shown on the page.
 */
function buildFaqPageSchema(array $faqs) {
    $items = [];
    foreach ($faqs as $faq) {
        if (empty($faq['question']) || empty($faq['answer'])) continue;
        $items[] = [
            "@type" => "Question",
            "name" => strip_tags($faq['question']),
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => strip_tags($faq['answer']),
            ],
        ];
    }

    if (empty($items)) return null;

    return [
        "@type" => "FAQPage",
        "mainEntity" => $items,
    ];
}

/**
 * @param array $post A blog_posts row (see db/migrations/0004_blog_posts.sql)
 *                     and $url its real, canonical public URL.
 */
function buildArticleSchema(array $post, string $url) {
    $node = [
        "@type" => "Article",
        "@id" => $url . '#article',
        "mainEntityOfPage" => ["@id" => $url . '#webpage'],
        "headline" => $post['title'] ?? '',
        "description" => $post['meta_description'] ?? ($post['excerpt'] ?? ''),
        "datePublished" => !empty($post['published_at']) ? date('c', strtotime($post['published_at'])) : null,
        "dateModified" => !empty($post['updated_at']) ? date('c', strtotime($post['updated_at'])) : null,
        "publisher" => ["@id" => HOME_URL . '/#organization'],
    ];
    if (!empty($post['author_name'])) {
        $node['author'] = ["@type" => "Person", "name" => $post['author_name']];
    }
    if (!empty($post['featured_image'])) {
        $node['image'] = $post['featured_image'];
    }
    return $node;
}

/**
 * Renders one JSON-LD <script> tag from a flat array of schema.org nodes.
 * Filters out any null entries (e.g. buildFaqPageSchema() returning null
 * when there are no FAQs) so callers don't need to guard every call site.
 */
function outputJsonLdGraph(array $nodes) {
    $nodes = array_values(array_filter($nodes));
    if (empty($nodes)) return;

    echo '<script type="application/ld+json">' . "\n";
    echo json_encode([
        "@context" => "https://schema.org",
        "@graph" => $nodes,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);   // compact: pretty-printing doubled the size (51 KB on the homepage)
    echo "\n" . '</script>' . "\n";
}

/*
|--------------------------------------------------------------------------
| Location-filtered category & performer pages (artist-*, concert-*,
| concerts-*, event-*, events-*, festivals-*, sports-*, theater-*, theatre-*)
|--------------------------------------------------------------------------
| These 23 pages were previously broken: every one called getTnPerformerById()
| regardless of what its own filename promised, ignored the location entirely,
| and rendered literal "{city}"/"{state}" placeholder text plus a hardcoded
| "Aaron Lewis" leftover from whatever performer the template was built
| against. This section is the real data layer: parsing a location slug per
| dimension (country is the odd one out - TicketNetwork identifies countries
| by two-letter alpha code, not a numeric ID, confirmed against the live
| sandbox API: /catalog/v2/countries/1 -> 404, /catalog/v2/countries/US ->
| 200), building the matching OData filter fragment, fetching a display name,
| and combining that with either a performer filter (artist-*) or a category
| path filter (everything else) - both confirmed to combine correctly with a
| location filter via a live sandbox request.
*/

/**
 * The "name-name-123" slug pattern (trailing numeric ID) used for
 * performers, events, venues, cities etc. throughout this app - was
 * duplicated inline (explode('-') + end()) in every page that needed it.
 */
function extractTrailingId(string $slug): int {
    $parts = explode('-', trim($slug, '/'));
    return (int) end($parts);
}

const LOCATION_CATEGORY_PATHS = [
    'concert'   => '.1859.1986.',
    'concerts'  => '.1859.1986.',
    'sports'    => '.1859.1988.',
    'theater'   => '.1859.1989.',
    'theatre'   => '.1859.1989.',
    'festivals' => '.1859.1986.1877.',
    'events'    => null, // no category constraint - every category
];

/**
 * Extracts the trailing identifier from a location slug. Every dimension
 * except country uses a numeric TicketNetwork ID (e.g. "austin-tx-247");
 * country slugs end in a two-letter alpha code instead (e.g.
 * "united-states-us" - confirmed against the live API's own uriComponent
 * for the US: "United-States-of-America-US").
 *
 * @return int|string|null int for city/state/venue, a 2-letter string for
 *                          country, or null if the slug doesn't match.
 */
function parseLocationSlug(string $dimension, string $slug) {
    $slug = trim($slug, '/');
    $parts = explode('-', $slug);
    if (empty($parts)) return null;

    if ($dimension === 'country') {
        $code = strtoupper(end($parts));
        return preg_match('/^[A-Z]{2}$/', $code) ? $code : null;
    }

    $id = (int) end($parts);
    return $id > 0 ? $id : null;
}

/**
 * @return string|null The OData filter fragment for this dimension/value,
 *                      or null if the dimension is unrecognized.
 */
function getLocationFilterFragment(string $dimension, $locationValue): ?string {
    switch ($dimension) {
        case 'city':    return 'city/id eq ' . (int) $locationValue;
        case 'state':   return 'stateProvince/id eq ' . (int) $locationValue;
        case 'venue':   return 'venue/id eq ' . (int) $locationValue;
        case 'country': return "country/alphaCode eq '" . tnEscapeFilterValue((string) $locationValue) . "'";
        default:        return null;
    }
}

/**
 * Fetches a human-readable name (+ region, for breadcrumbs/headings) for a
 * location value. Every dimension has its own TicketNetwork resource except
 * country, whose display name comes back on the same countries/{code}
 * lookup used for the filter.
 */
function getLocationDisplayInfo(string $dimension, $locationValue): ?array {
    switch ($dimension) {
        case 'city':
            $city = getTnCityById((int) $locationValue);
            if (tnEntityMissing($city)) return null;
            return [
                'name' => $city['text']['name'] ?? '',
                'region' => $city['stateProvince']['text']['abbr'] ?? '',
                'label' => trim(($city['text']['name'] ?? '') . ', ' . ($city['stateProvince']['text']['abbr'] ?? ''), ', '),
            ];
        case 'state':
            $state = getTnStateById((int) $locationValue);
            if (tnEntityMissing($state)) return null;
            return [
                'name' => $state['text']['name'] ?? '',
                'region' => $state['text']['abbr'] ?? '',
                'label' => $state['text']['name'] ?? '',
            ];
        case 'venue':
            $venue = getTnVenueById((int) $locationValue);
            if (tnEntityMissing($venue)) return null;
            return [
                'name' => $venue['text']['name'] ?? '',
                'region' => trim(($venue['city']['text']['name'] ?? '') . ', ' . ($venue['stateProvince']['text']['abbr'] ?? ''), ', '),
                'label' => $venue['text']['name'] ?? '',
            ];
        case 'country':
            $country = getTnCountryByCode((string) $locationValue);
            if (tnEntityMissing($country) || ($country['text']['name'] ?? 'n/a') === 'n/a') return null;
            return [
                'name' => $country['text']['name'] ?? '',
                'region' => $country['alphaCode'] ?? '',
                'label' => $country['text']['name'] ?? '',
            ];
        default:
            return null;
    }
}

function getTnStateById($stateId) {
    $endpoint = '/catalog/v2/stateProvinces/' . (int) $stateId;
    return tnRequest($endpoint);
}

function getTnCountryByCode($alphaCode) {
    $alphaCode = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $alphaCode));
    if ($alphaCode === '') return null;
    $endpoint = '/catalog/v2/countries/' . $alphaCode;
    return tnRequest($endpoint);
}

/**
 * Track A (artist-city/state/country/venue): one performer's events,
 * additionally filtered to one location. Reuses getTnPerformerEvents()
 * exactly as it already works elsewhere - performerFilter and a location
 * `filter` combine in one request (confirmed live against the sandbox API).
 */
/** Params for a performer's events at one location (also used to prefetch). */
function performerLocationParams(string $dimension, $locationValue, array $params = []) {
    $locationFilter = getLocationFilterFragment($dimension, $locationValue);
    if ($locationFilter === null) return null;
    $params['filter'] = $locationFilter . ' and date/date ge ' . date('Y-m-d');
    return $params;
}

function getPerformerEventsByLocation(int $performerId, string $dimension, $locationValue, array $params = []) {
    $params = performerLocationParams($dimension, $locationValue, $params);
    if ($params === null) return ['results' => [], 'totalCount' => 0];
    return getTnPerformerEvents($performerId, $params);
}

/**
 * Track B (concert-venue, theater-state, sports-city, etc.): every event in
 * a category, filtered to one location - no fixed performer. $categoryKey
 * is one of LOCATION_CATEGORY_PATHS's keys ('events' has no category
 * constraint at all, matching how events-city/events-state work today).
 */
function getCategoryEventsByLocation(string $categoryKey, string $dimension, $locationValue, array $params = []) {
    $locationFilter = getLocationFilterFragment($dimension, $locationValue);
    if ($locationFilter === null) return ['results' => [], 'totalCount' => 0];

    $today = date('Y-m-d');
    $categoryPath = LOCATION_CATEGORY_PATHS[$categoryKey] ?? null;

    $filterParts = [$locationFilter, "date/date ge $today"];
    if ($categoryPath !== null) {
        $filterParts[] = "startswith(defaultCategory/path, '" . tnEscapeFilterValue($categoryPath) . "')";
    }

    $params['filter'] = implode(' and ', $filterParts);
    $params['includeTotalCount'] = $params['includeTotalCount'] ?? 'true';

    return tnRequest('/catalog/v2/events/', $params);
}

/**
 * Maps an event's own defaultCategory/path (real field, already used
 * elsewhere - see cron/home-events.php, ajax/get-location-category-events.php)
 * to the matching category-city location page prefix, for linking an event
 * page back to "more events like this in this city" - see event.php.
 * Checks festivals before concerts since festivals' path is a sub-path of
 * concerts' (".1859.1986.1877." starts with ".1859.1986."), same nesting
 * getCategoryEventsByLocation() already accounts for.
 */
function getCategoryCityLinkPrefix($categoryPath): string {
    $categoryPath = (string) $categoryPath;
    if (strpos($categoryPath, LOCATION_CATEGORY_PATHS['festivals']) === 0) return 'festivals-city';
    if (strpos($categoryPath, LOCATION_CATEGORY_PATHS['concerts']) === 0) return 'concerts-city';
    if (strpos($categoryPath, LOCATION_CATEGORY_PATHS['sports']) === 0) return 'sports-city';
    if (strpos($categoryPath, LOCATION_CATEGORY_PATHS['theater']) === 0) return 'theater-city';
    return 'event-city';
}

/**
 * "Taylor Swift tickets" reads fine, but the exact same generic wording
 * ("tickets") gets duller (not wrong, just generic) for other performer
 * types, and site copy asking for "game" for sports/"show" for theater
 * needed a way to know which is which for a given performer, not just for
 * the category-location pages. Reuses the same category-path detection
 * as getCategoryCityLinkPrefix() (festivals checked before concerts since
 * festivals nests under concerts' path).
 */
function getPerformerNounForPath($categoryPath): array {
    $categoryPath = (string) $categoryPath;
    if (strpos($categoryPath, LOCATION_CATEGORY_PATHS['festivals']) === 0) {
        return ['noun' => 'festival', 'nounCap' => 'Festival'];
    }
    if (strpos($categoryPath, LOCATION_CATEGORY_PATHS['concerts']) === 0) {
        return ['noun' => 'concert', 'nounCap' => 'Concert'];
    }
    if (strpos($categoryPath, LOCATION_CATEGORY_PATHS['sports']) === 0) {
        return ['noun' => 'game', 'nounCap' => 'Game'];
    }
    if (strpos($categoryPath, LOCATION_CATEGORY_PATHS['theater']) === 0) {
        return ['noun' => 'show', 'nounCap' => 'Show'];
    }
    return ['noun' => 'event', 'nounCap' => 'Event'];
}

/**
 * Real internal links from a category page (concerts.php, sports.php,
 * theater.php, festival.php) into the matching category-city location
 * pages, built from that page's own already-fetched $events - same
 * pattern as performer.php's "Tickets by City" block, generalized so it
 * isn't copy-pasted four times. Echoes the HTML directly (call site is a
 * plain top-level page, not a function with output buffering set up).
 */
/*
|--------------------------------------------------------------------------
| Internal links between the location pages
|--------------------------------------------------------------------------
| The 24 category/performer x location pages are excluded from the sitemap
| (see sitemap.php) so they are only discoverable through links. These two
| helpers put those links on every page that has the data for them.
*/

const LOCATION_CATEGORY_PAGES = [
    'city'    => ['plain' => 'city',    'pages' => ['event-city' => 'All events', 'concerts-city' => 'Concerts', 'sports-city' => 'Sports', 'theater-city' => 'Theater', 'festivals-city' => 'Festivals']],
    'state'   => ['plain' => 'state',   'pages' => ['events-state' => 'All events', 'concerts-state' => 'Concerts', 'sports-state' => 'Sports', 'theater-state' => 'Theater', 'festivals-state' => 'Festivals']],
    'country' => ['plain' => 'country', 'pages' => ['concert-country' => 'Concerts', 'theater-country' => 'Theater', 'festivals-country' => 'Festivals']],
    'venue'   => ['plain' => 'venue',   'pages' => ['concert-venue' => 'Concerts', 'theater-venue' => 'Theater', 'festivals-venue' => 'Festivals']],
];

/**
 * "Browse {location} by category" pills. $currentPrefix (e.g. 'concerts-city')
 * is rendered as the plain location page link instead of a self-link.
 */
function renderLocationCategoryLinks(string $dimension, $locationValue, string $locationLabel, string $currentPrefix = ''): void {
    $conf = LOCATION_CATEGORY_PAGES[$dimension] ?? null;
    if (!$conf || $locationLabel === '' || $locationValue === null || $locationValue === '') return;
    $slug = createSlug($locationLabel, $locationValue);
    $links = [];
    if ($currentPrefix !== '') {
        $links[] = ['href' => '/' . $conf['plain'] . '/' . $slug, 'text' => 'All events in ' . $locationLabel];
    }
    foreach ($conf['pages'] as $prefix => $label) {
        if ($prefix === $currentPrefix) continue;
        if ($currentPrefix === '' && $label === 'All events') continue; // plain page already is "all events"
        $links[] = ['href' => '/' . $prefix . '/' . $slug, 'text' => $label . ' in ' . $locationLabel];
    }
    if (!$links) return;
    ?>
    <div class="tab-section content-section-detail" id="browse-<?php echo htmlspecialchars($dimension, ENT_QUOTES, 'UTF-8'); ?>">
        <h2 class="so-heading fw-bold fs-4 mb-4 text-black">More Tickets in <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="so-linkchips">
            <?php foreach ($links as $l) { ?>
                <a href="<?php echo htmlspecialchars($l['href'], ENT_QUOTES, 'UTF-8'); ?>" class="so-linkchip"><?php echo htmlspecialchars($l['text'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php } ?>
        </div>
    </div>
    <?php
}

/**
 * "{Performer} tickets by city / venue / state" pills built from the
 * performer's own upcoming events, linking into artist-city / artist-venue /
 * artist-state. Used by performer.php and the artist location renderer.
 */
function renderPerformerLocationLinks(string $artistName, int $performerId, array $events, string $skipDimension = ''): void {
    $performerSlug = createSlug($artistName, $performerId);
    $dims = [
        'city'  => ['prefix' => 'artist-city',  'heading' => 'by City'],
        'venue' => ['prefix' => 'artist-venue', 'heading' => 'by Venue'],
        'state' => ['prefix' => 'artist-state', 'heading' => 'by State'],
    ];
    foreach ($dims as $dim => $conf) {
        if ($dim === $skipDimension) continue;
        $items = []; $seen = [];
        foreach ($events as $event) {
            switch ($dim) {
                case 'city':
                    $id = $event['city']['id'] ?? null;
                    $label = trim(($event['city']['text']['name'] ?? '') . ', ' . ($event['stateProvince']['text']['abbr'] ?? ''), ', ');
                    break;
                case 'venue':
                    $id = $event['venue']['id'] ?? null;
                    $label = (string) ($event['venue']['text']['name'] ?? '');
                    break;
                default:
                    $id = $event['stateProvince']['id'] ?? null;
                    $label = (string) ($event['stateProvince']['text']['name'] ?? '');
            }
            if (empty($id) || $label === '' || $label === ',' || isset($seen[$id])) continue;
            $seen[$id] = true;
            $items[] = ['id' => $id, 'label' => $label];
            if (count($items) >= 8) break;
        }
        if (!$items) continue;
        ?>
        <div class="tab-section content-section-detail" id="performer-<?php echo $dim; ?>">
            <h2 class="so-heading fw-bold fs-4 mb-4 text-black"><?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> Tickets <?php echo $conf['heading']; ?></h2>
            <div class="so-linkchips">
                <?php foreach ($items as $it) { ?>
                    <a href="/<?php echo $conf['prefix']; ?>/<?php echo htmlspecialchars($performerSlug, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars(createSlug($it['label'], $it['id']), ENT_QUOTES, 'UTF-8'); ?>" class="so-linkchip">
                        <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> <?php echo $dim === 'venue' ? 'at' : 'in'; ?> <?php echo htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php } ?>
            </div>
        </div>
        <?php
    }
}

/**
 * Every date a performer has, for the "where they play" boxes. The page already holds the first 20 dates; only an
 * artist with more than that costs one extra request (up to 200 dates, cached 10 minutes like the page's own list).
 */
function performerWhereEvents(int $performerId, array $events, int $totalCount): array {
    if ($totalCount <= count($events)) {
        return $events;
    }
    [$endpoint, $params] = performerPageEventsSpec($performerId, 200);
    $all = tnRequest($endpoint, $params, 'GET', 600);
    $rows = $all['results'] ?? [];
    return count($rows) > count($events) ? $rows : $events;
}

/** Cities, venues and states a performer plays, with the number of dates in each (most dates first). */
function performerWhereGroups(array $events): array {
    $groups = ['city' => [], 'venue' => [], 'state' => []];
    foreach ($events as $event) {
        $abbr = $event['stateProvince']['text']['abbr'] ?? '';
        $items = [
            'city'  => [$event['city']['id'] ?? null, trim(($event['city']['text']['name'] ?? '') . ', ' . $abbr, ', ')],
            'venue' => [$event['venue']['id'] ?? null, (string) ($event['venue']['text']['name'] ?? '')],
            'state' => [$event['stateProvince']['id'] ?? null, (string) ($event['stateProvince']['text']['name'] ?? '')],
        ];
        foreach ($items as $dim => [$id, $label]) {
            if (empty($id) || $label === '' || $label === ',') continue;
            if (!isset($groups[$dim][$id])) { $groups[$dim][$id] = ['id' => $id, 'label' => $label, 'count' => 0]; }
            $groups[$dim][$id]['count']++;
        }
    }
    foreach ($groups as $dim => $rows) {
        $rows = array_values($rows);
        usort($rows, fn($a, $b) => [$b['count'], $a['label']] <=> [$a['count'], $b['label']]);
        $groups[$dim] = array_slice($rows, 0, 60);
    }
    return $groups;
}

/** "Where <artist> is playing": segmented control (Cities, Venues, States) over one grouped list, built from the real dates. */
function renderPerformerWhere(string $artistName, int $performerId, array $events, int $totalCount): void {
    $groups = performerWhereGroups(performerWhereEvents($performerId, $events, $totalCount));
    if (!$groups['city'] && !$groups['venue'] && !$groups['state']) return;
    $slug = createSlug($artistName, $performerId);
    $dims = [
        'city'  => ['label' => 'Cities', 'prefix' => 'artist-city',  'word' => 'in'],
        'venue' => ['label' => 'Venues', 'prefix' => 'artist-venue', 'word' => 'at'],
        'state' => ['label' => 'States', 'prefix' => 'artist-state', 'word' => 'in'],
    ];
    $visible = 6;
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $first = null;
    ?>
    <div class="tab-section content-section-detail so-where" id="where">
        <h2 class="so-heading fw-bold fs-4 mb-1 text-black">Where <?php echo $e($artistName); ?> is playing</h2>
        <p class="so-where__lead">Pick a place to see every <?php echo $e($artistName); ?> date there.</p>
        <div class="so-seg" role="tablist" aria-label="Browse by place">
            <?php foreach ($dims as $dim => $conf) { if (!$groups[$dim]) continue; if ($first === null) $first = $dim; ?>
                <button type="button" class="so-seg__btn" role="tab" id="so-seg-<?php echo $dim; ?>" aria-controls="so-where-<?php echo $dim; ?>" aria-selected="<?php echo $first === $dim ? 'true' : 'false'; ?>" data-so-seg="<?php echo $dim; ?>"><?php echo $conf['label']; ?> <span><?php echo count($groups[$dim]); ?></span></button>
            <?php } ?>
        </div>
        <?php foreach ($dims as $dim => $conf) { $rows = $groups[$dim]; if (!$rows) continue; ?>
            <div class="so-where__panel" id="so-where-<?php echo $dim; ?>" role="tabpanel" aria-labelledby="so-seg-<?php echo $dim; ?>"<?php echo $first === $dim ? '' : ' hidden'; ?>>
                <ul class="so-list">
                    <?php foreach ($rows as $i => $it) { ?>
                        <li class="so-list__item<?php echo $i >= $visible ? ' so-list__item--extra' : ''; ?>"<?php echo $i >= $visible ? ' hidden' : ''; ?>>
                            <a class="so-list__link" href="/<?php echo $conf['prefix']; ?>/<?php echo $e($slug); ?>/<?php echo $e(createSlug($it['label'], $it['id'])); ?>" title="<?php echo $e($artistName . ' ' . $conf['word'] . ' ' . $it['label']); ?>">
                                <span class="so-list__name"><?php echo $e($it['label']); ?></span>
                                <span class="so-list__meta"><?php echo (int) $it['count']; ?> <?php echo $it['count'] === 1 ? 'date' : 'dates'; ?></span>
                                <i class="bi bi-chevron-right so-list__chev" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
                <?php if (count($rows) > $visible) { ?>
                    <button type="button" class="so-list__more" data-so-more>Show all <?php echo count($rows); ?></button>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    <script>
    (function () {
        var box = document.getElementById('where');
        if (!box) return;
        box.querySelectorAll('[data-so-seg]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                box.querySelectorAll('[data-so-seg]').forEach(function (b) { b.setAttribute('aria-selected', b === btn ? 'true' : 'false'); });
                box.querySelectorAll('.so-where__panel').forEach(function (p) { p.hidden = p.id !== 'so-where-' + btn.dataset.soSeg; });
            });
        });
        box.querySelectorAll('[data-so-more]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.parentNode.querySelectorAll('.so-list__item--extra').forEach(function (li) { li.hidden = false; });
                btn.remove();
            });
        });
    })();
    </script>
    <?php
}

/** Artist biography: first lines, then "Read more" (the full text stays in the page for search engines and no-JS visitors). */
function renderBioBlock($bio): void {
    $bio = (string) $bio;
    if (trim($bio) === '') return;
    $long = mb_strlen(strip_tags($bio)) > 420;
    ?>
    <div class="so-bio<?php echo $long ? ' so-bio--long' : ''; ?>" data-so-bio>
        <p class="so-bio__text"><?php echo $bio; ?></p>
        <?php if ($long) { ?>
            <button type="button" class="so-bio__toggle" hidden aria-expanded="false">Read more</button>
            <script>
            (function () {
                var box = document.currentScript.parentNode, btn = box.querySelector('.so-bio__toggle');
                if (!btn) return;
                box.classList.add('so-bio--ready'); btn.hidden = false;
                btn.addEventListener('click', function () {
                    var open = box.classList.toggle('so-bio--open');
                    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    btn.textContent = open ? 'Show less' : 'Read more';
                });
            })();
            </script>
        <?php } ?>
    </div>
    <?php
}

/** Two-letter initials for a name tile ("Paloma Morphy" -> "PM"). */
function soInitials($name): string {
    $words = preg_split('/[^\p{L}\p{N}]+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    $out = '';
    foreach (array_slice($words, 0, 2) as $w) { $out .= mb_strtoupper(mb_substr($w, 0, 1)); }
    return $out !== '' ? $out : '?';
}

/**
 * "Fans also love": other performers from the same category, each with its own name and picture. A performer whose
 * picture is not stored yet gets a clean initials tile (not the shared stock photo) and is queued for the image job;
 * the page asks ajax/resolve-images.php for the real picture once the cards are on screen.
 */
function renderRelatedPerformersGrid(array $related, int $limit = 8): void {
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $n = 0;
    ?>
    <div class="so-related" data-so-related>
        <?php foreach ($related as $rel) {
            if ($n >= $limit) break;
            $name = trim((string) ($rel['text']['name'] ?? ''));
            $uri  = strtolower((string) ($rel['uriComponent'] ?? ''));
            if ($name === '' || $uri === '') continue;
            $n++;
            $cat  = $rel['defaultCategory'] ?? [];
            $type = imageEntityTypeForPerformer($cat);
            $img  = getEntityImage($type, $name, ['category' => $cat, 'resolve' => false]);
            $real = in_array($img['status'], ['ok', 'manual'], true) && $img['url'] !== '';
            $hue  = hexdec(substr(md5($name), 0, 4)) % 360;
            ?>
            <a class="so-related__card" href="/artist/<?php echo $e($uri); ?>" data-name="<?php echo $e($name); ?>" data-type="<?php echo $e($type); ?>"<?php echo $real ? '' : ' data-pending="1"'; ?>>
                <span class="so-related__media" style="--so-hue:<?php echo (int) $hue; ?>">
                    <?php if ($real) { ?>
                        <img src="<?php echo $e($img['url']); ?>" alt="<?php echo $e($name); ?>" loading="lazy" width="400" height="300">
                    <?php } else { ?>
                        <span class="so-related__initials" aria-hidden="true"><?php echo $e(soInitials($name)); ?></span>
                    <?php } ?>
                </span>
                <span class="so-related__name"><?php echo $e($name); ?></span>
                <span class="so-related__cta">View tickets</span>
            </a>
        <?php } ?>
    </div>
    <script>
    (function () {
        var grid = document.querySelector('[data-so-related]');
        if (!grid || !('IntersectionObserver' in window)) return;
        var pending = [].slice.call(grid.querySelectorAll('[data-pending]'));
        if (!pending.length) return;
        var io = new IntersectionObserver(function (entries) {
            if (!entries.some(function (en) { return en.isIntersecting; })) return;
            io.disconnect();
            fetch('/ajax/resolve-images.php', { method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: pending.map(function (c) { return { name: c.dataset.name, type: c.dataset.type }; }) }) })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    (data.images || []).forEach(function (url, i) {
                        if (!url) return;
                        var media = pending[i].querySelector('.so-related__media');
                        var img = new Image(); img.alt = pending[i].dataset.name; img.width = 400; img.height = 300;
                        img.onload = function () { media.innerHTML = ''; media.appendChild(img); };
                        img.src = url;
                    });
                }).catch(function () {});
        }, { rootMargin: '200px' });
        io.observe(grid);
    })();
    </script>
    <?php
}

function renderCategoryCityLinksBlock(array $events, string $urlPrefix, string $categoryLabel): void {
    $cities = [];
    $seenCityIds = [];
    foreach ($events as $event) {
        $cityId = $event['city']['id'] ?? null;
        $cityName = $event['city']['text']['name'] ?? '';
        if (empty($cityId) || $cityName === '' || isset($seenCityIds[$cityId])) {
            continue;
        }
        $seenCityIds[$cityId] = true;
        $cities[] = [
            'id' => $cityId,
            'label' => trim($cityName . ', ' . ($event['stateProvince']['text']['abbr'] ?? ''), ', '),
        ];
        if (count($cities) >= 12) {
            break;
        }
    }

    if (empty($cities)) {
        return;
    }
    ?>
    <div class="tab-section content-section-detail" id="cities">
        <h2 class="so-heading fw-bold fs-4 mb-4 text-black"><?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?> Tickets by City</h2>
        <div class="so-linkchips">
            <?php foreach ($cities as $city) { ?>
                <a href="/<?php echo htmlspecialchars($urlPrefix, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars(createSlug($city['label'], $city['id']), ENT_QUOTES, 'UTF-8'); ?>" class="so-linkchip">
                    <?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?> in <?php echo htmlspecialchars($city['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php } ?>
        </div>
    </div>
    <?php
}

/**
 * Track A page renderer (artist-city.php, artist-state.php,
 * artist-country.php, artist-venue.php): one performer's events, filtered to
 * a single location dimension. All four files are thin wrappers around this
 * function - the URL contract (confirmed with the site owner) is two path
 * segments, performer slug then location slug, rewritten server-side into
 * $_GET['slug'] and $_GET['loc'].
 */
function renderArtistLocationPage(string $dimension, string $urlPrefix): void {
    $page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = 20;

    $performerId   = extractTrailingId($_GET['slug'] ?? '');
    $locationValue = parseLocationSlug($dimension, $_GET['loc'] ?? '');

    if ($performerId <= 0 || $locationValue === null) {
        include 'header.php';
        echo '<div class="container py-5"><p>Invalid performer or location.</p></div>';
        include 'footer.php';
        return;
    }

    // Independent requests go out together; the normal calls below then hit
    // the cache (cold page: 4 sequential ~200ms calls -> 1 parallel round).
    $prefetch = [
        ['/catalog/v2/performers/' . $performerId, []],
        performerEventsSpec($performerId, performerLocationParams($dimension, $locationValue, [
            'page' => $page, 'perPage' => $perPage, 'includeTotalCount' => 'true',
        ]) ?? []),
        performerEventsSpec($performerId, ['filter' => 'date/date ge ' . date('Y-m-d'), 'perPage' => 100, 'sort' => 'date/date']),
    ];
    tnRequestMulti($prefetch);

    $performer = getTnPerformerById($performerId);
    $location  = getLocationDisplayInfo($dimension, $locationValue);

    if (empty($performer) || empty($performer['defaultCategory']) || empty($location)) {
        include 'header.php';
        echo '<div class="container py-5"><p>Performer or location not found.</p></div>';
        include 'footer.php';
        return;
    }

    $artistName    = $performer['text']['name'];
    $locationLabel = $location['label'];

    $eventsResponse = getPerformerEventsByLocation($performerId, $dimension, $locationValue, [
        'page'    => $page,
        'perPage' => $perPage,
        'includeTotalCount' => 'true',
    ]);

    $total_count = $eventsResponse['totalCount'] ?? 0;
    $total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;
    $events      = $eventsResponse['results'] ?? [];
    $count       = $eventsResponse['count'] ?? count($events);
    // Unfiltered upcoming events feed the "by city / venue / state" links,
    // so a Taylor-Swift-in-Austin page links to her other cities too.
    $allPerformerEvents = getTnPerformerEvents($performerId, ['filter' => 'date/date ge ' . date('Y-m-d'), 'perPage' => 100, 'sort' => 'date/date'])['results'] ?? [];
    $percent     = $total_count > 0 ? ($perPage / $total_count) * 100 : 0;
    $year        = date('Y');

    $sep = '<span class="separator"><strong> / </strong></span>';
    $breadcrumbs = buildCategoryBreadcrumb($performer['defaultCategory']);
    $categoryLabel = end($breadcrumbs)['label'] ?? '';

    $relatedPerformers = getRelatedPerformers($performer['defaultCategory']['path'], $performerId);
    $performer_bio   = getArtistBio($artistName, $performerId);
    $performer_image = getArtistImage($artistName, $performer['defaultCategory']);

    // Same generic "tickets" wording read fine for a music artist ("Taylor
    // Swift tickets") but flat for other performer types - a sports team's
    // page should talk about games, a theater production about a show. All
    // performer types share the exact same TicketNetwork "performer"
    // entity and this same renderer, so the noun is derived from the
    // performer's own real category path rather than hardcoded.
    $noun = getPerformerNounForPath($performer['defaultCategory']['path'] ?? '');

    $faqsRaw = getFaqs('performer');
    $faqs = array_map(function ($faq) use ($artistName, $locationLabel, $noun) {
        $tokens = ['[artist_name]', '[location]', '[event_noun]'];
        $values = [$artistName, $locationLabel, $noun['noun']];
        return [
            'question' => str_replace($tokens, $values, $faq['question']),
            'answer'   => str_replace($tokens, $values, $faq['answer']),
        ];
    }, $faqsRaw);

    // --- SEO: computed before including header.php so the <head> can use real data ---
    $pageFocusKeyword    = "$artistName Tickets in " . preg_replace('/,\s*[A-Z]{2}$/', '', (string) $locationLabel);
    $pageMetaTitle       = "$artistName {$noun['nounCap']} Tickets in $locationLabel | Seat Outlet";
    $pageMetaDescription = "Buy verified $artistName {$noun['noun']} tickets in $locationLabel. Compare prices across sellers and find upcoming $artistName {$noun['noun']}s near you on Seat Outlet.";
    $pageCanonicalUrl    = HOME_URL . '/' . $urlPrefix . '/' . createSlug($artistName, $performerId) . '/' . createSlug($locationLabel, $locationValue);
    $pageJsonLdNodes = array_values(array_filter([
        buildBreadcrumbListSchema(array_map(fn($c) => ['label' => $c['label'], 'url' => null], $breadcrumbs), "$artistName in $locationLabel"),
        buildFaqPageSchema($faqs),
    ]));

    include 'header.php';
    ?>

    <section class="section-featured-header text-sm-center text-md-start">
        <div class="container-fluid min-vh-50 d-flex align-items-center justify-content-center text-white all-sports-events"
            style="background-image: url('<?php echo HOME_URL; ?>/images/event-so.webp'); background-size: cover; background-position: center; background-repeat: no-repeat;">
            <div class="container mx-xl-5 mx-lg-5 mx-md-3">
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="section-content">
                            <nav class="breadcrumb justify-content-sm-center justify-content-md-start">
                                <?php foreach ($breadcrumbs as $index => $item) { ?>
                                    <?php if ($index > 0) { ?>
                                        <?php echo $sep; ?>
                                    <?php } ?>
                                    <a href="<?php echo htmlspecialchars($item['url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                <?php } ?>
                                <?php echo $sep; ?>
                                <span class="current">
                                    <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </nav>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row align-items-center text-center text-md-start">
                            <div class="col-md-3">
                                <div class="img-artist">
                                    <img src="<?php echo $performer_image; ?>" alt="<?php echo htmlspecialchars("$artistName $noun[nounCap] tickets in $locationLabel", ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid rounded artist-img" />
                                </div>
                            </div>
                            <div class="col-md-9 text-white">
                                <div class="artist-heading text-center text-md-start text-lg-start text-xl-start text-xxl-start">
                                    <div class="artist-category">
                                        <a href="<?php echo htmlspecialchars(sanitize_title($categoryLabel), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </div>
                                    <h1 class="artist-title">
                                        <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> Tickets in <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </h1>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="so-tabs sticky-tabs">
        <div class="artist-tabs tabs-wrapper">
            <ul class="nav nav-tabs artist-tabs-nav" id="artistTabs">
                <li class="nav-item">
                    <button class="nav-link active" type="button" data-target="default" onclick="scrollToElement('default')">
                        <?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" type="button" data-target="about" onclick="scrollToElement('about')">About</button>
                </li>
                <?php if (!empty($faqs)) { ?>
                <li class="nav-item">
                    <button class="nav-link" type="button" data-target="faqs" onclick="scrollToElement('faqs')">FAQs</button>
                </li>
                <?php } ?>
            </ul>
            <span class="active-underline"></span>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="tab-section section-performer-content" id="default">
                <div class="row mt-3 gap-5 gap-md-2 gap-lg-4 gap-xl-5 gap-xxl-5">
                    <div class="col-sm-12 col-md-8 left-bar">
                        <div class="mb-3 mb-md-4 mb-lg-4">
                            <div class="d-flex justify-content-between align-items-center results-header">
                                <div class="results-title">
                                    <span class="active-indicator"></span>
                                    <h2>
                                        <?php echo htmlspecialchars(strtoupper($artistName), ENT_QUOTES, 'UTF-8'); ?> TICKETS IN <?php echo htmlspecialchars(strtoupper($locationLabel), ENT_QUOTES, 'UTF-8'); ?> <span class="dot">·</span>
                                        <span class="count" id="results_count">
                                            <?php echo (int) $count; ?>
                                            <?php echo $count > 1 ? 'RESULTS' : 'RESULT'; ?>
                                        </span>
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="list-category-bg pb-3">
                            <?php if (!empty($events)) { ?>
                                <div id="eventsSection" class="section-artist-content event-row-all">
                                    <?php foreach ($events as $event) {
                                        $eventDateRaw = $event['date']['date'] ?? '';
                                        $timestamp    = $eventDateRaw ? strtotime($eventDateRaw) : false;
                                        $eventSlug    = createSlug($event['text']['name'] ?? '', $event['id'] ?? 0);
                                        $evtCityLabel = trim(($event['city']['text']['name'] ?? '') . ', ' . ($event['stateProvince']['text']['abbr'] ?? ''), ', ');
                                        $evtCitySlug  = !empty($event['city']['id']) ? createSlug($evtCityLabel, $event['city']['id']) : null;
                                        $evtVenueSlug = !empty($event['venue']['id']) ? createSlug($event['venue']['text']['name'] ?? '', $event['venue']['id']) : null;
                                    ?>
                                        <div class="d-flex align-items-center justify-content-between performer-event-item">
                                            <div class="date-box text-center me-3">
                                                <div class="month"><?php echo $timestamp ? htmlspecialchars(strtoupper(date('M', $timestamp)), ENT_QUOTES, 'UTF-8') : ''; ?></div>
                                                <div class="day"><?php echo $timestamp ? htmlspecialchars(date('d', $timestamp), ENT_QUOTES, 'UTF-8') : ''; ?></div>
                                                <?php if ($timestamp && date('Y', $timestamp) > $year) { ?>
                                                    <div class="month"><?php echo htmlspecialchars(date('Y', $timestamp), ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php } ?>
                                            </div>
                                            <div class="flex-grow-1 w-50">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-semibold day-weeks"><?php echo $timestamp ? htmlspecialchars(date('D', $timestamp), ENT_QUOTES, 'UTF-8') : ''; ?></span>
                                                    <span class="dot">·</span>
                                                    <span class="time-clock"><?php echo htmlspecialchars($event['date']['text']['time'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                                </div>
                                                <div class="ev-venue"><a href="<?php echo $evtVenueSlug ? '/venue/' . htmlspecialchars($evtVenueSlug, ENT_QUOTES, 'UTF-8') : '#'; ?>"><?php echo htmlspecialchars($event['venue']['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></div>
<div class="ev-place"><a href="<?php echo $evtCitySlug ? '/city/' . htmlspecialchars($evtCitySlug, ENT_QUOTES, 'UTF-8') : '#'; ?>"><?php echo htmlspecialchars($event['city']['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars($event['stateProvince']['text']['abbr'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></div>
<div class="ev-name"><?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                            <div class="ms-3">
                                                <?php renderEventPriceTag($event); ?>
                                                <a href="/event/<?php echo htmlspecialchars($eventSlug, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary d-flex align-items-center gap-2" aria-label="Find tickets for <?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                    <span class="d-none d-md-inline">Buy Tickets</span>
                                                    <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <?php if ($total_pages > 1) { ?>
                                    <div class="load-more-wrapper text-center mt-5">
                                        <div class="load-progress mx-auto mb-3">
                                            <div class="small mb-2">
                                                Loaded <strong id="loadedCount"><?php echo $count; ?></strong> out of <strong id="totalCount"><?php echo $total_count; ?></strong> events
                                            </div>
                                            <div class="progress progress-thin">
                                                <div class="progress-bar" id="progressBar" style="width: <?php echo $percent; ?>%;"></div>
                                            </div>
                                        </div>
                                        <button
                                            class="btn more-events-btn d-inline-flex align-items-center gap-2"
                                            id="loadMoreBtn"
                                            data-total="<?php echo (int) $total_count; ?>"
                                            data-page="2"
                                            data-performer="<?php echo (int) $performerId; ?>"
                                            data-perpage="<?php echo (int) $perPage; ?>">
                                            <span class="btn-text">More Events</span>
                                            <span class="spinner-border spinner-border-sm d-none" id="btnSpinner"></span>
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <h3 style="padding: 20px; font-size: 1.25rem; font-weight: 400;">
                                    No <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> tickets found in <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8'); ?> right now.
                                </h3>
                            <?php } ?>
                        </div>
                    <div id="secondary" class="sidebar col-sm-12 col-md-4">
                        <div class="sticky-top sidebar-inner">
                            <div class="guarantee-card d-flex align-items-center justify-content-between" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                <div class="guarantee">
                                    <strong>Shop Tickets Worry Free</strong><br>
                                    <span>With Our 100% Guarantee</span>
                                </div>
                                <div class="guarantee-icon"><i class="bi bi-shield-check"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-section content-section-detail" id="about">
                <div class="row">
                    <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
                        <div class="so-about-text me-md-3">
                            <h2 class="so-heading mb-3">About <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> in <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                            <?php renderBioBlock($performer_bio); ?>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
                        <div class="so-about mt-3 mt-sm-3 mt-md-0 mt-lg-0 mt-xl-0 mt-xxl-0">
                            <img src="<?php echo $performer_image; ?>" alt="<?php echo htmlspecialchars("About $artistName in $locationLabel", ENT_QUOTES, 'UTF-8'); ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($faqs)) { ?>
                <div class="tab-section content-section-detail" id="faqs">
                    <h2 class="so-heading mb-3">FAQs about <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> Tickets in <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <div class="accordion" id="faqAccordion">
                        <?php foreach ($faqs as $index => $faq) {
                            $collapseId = 'collapse' . $index;
                            $headingId  = 'heading' . $index;
                            $isFirst = ($index === 0);
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                                    <button class="accordion-button <?php echo $isFirst ? '' : 'collapsed'; ?>"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#<?php echo $collapseId; ?>"
                                            aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>"
                                            aria-controls="<?php echo $collapseId; ?>">
                                        <?php echo htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                </h2>
                                <div id="<?php echo $collapseId; ?>"
                                    class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>"
                                    aria-labelledby="<?php echo $headingId; ?>"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?php echo nl2br(htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8')); ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <div class="tab-section content-section-detail" id="more-tickets">
                <h2 class="so-heading fw-bold fs-4 mb-4 text-black">More <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> Tickets</h2>
                <div class="so-linkchips">
                    <a href="/artist/<?php echo htmlspecialchars(createSlug($artistName, $performerId), ENT_QUOTES, 'UTF-8'); ?>" class="so-linkchip">All <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> tickets</a>
                    <a href="/<?php echo htmlspecialchars(LOCATION_CATEGORY_PAGES[$dimension]['plain'] ?? $dimension, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars(createSlug($locationLabel, $locationValue), ENT_QUOTES, 'UTF-8'); ?>" class="so-linkchip">All events in <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            </div>
            <?php $whereEvents = $allPerformerEvents ?? $events; renderPerformerWhere($artistName, (int) $performerId, $whereEvents, count($whereEvents)); ?>
            <?php if (!empty($relatedPerformers)) { ?>
                <div class="tab-section content-section-detail" id="fans">
                    <h2 class="so-heading fw-bold fs-4 mb-3 text-black"><?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> Fans Also Love</h2>
                    <?php renderRelatedPerformersGrid($relatedPerformers, 8); ?>
                </div>
            <?php } ?>
        </div>
    </section>

    <?php
    include 'footer.php';
}

/**
 * Track B page renderer (concert-venue.php, sports-city.php,
 * theater-state.php, festivals-country.php, event-city.php, etc. - 20 files
 * total): every event in a category, filtered to a single location - no
 * fixed performer. Modeled on concerts.php's real, working listing pattern
 * (dynamic /city, /venue, /event links; no placeholder tokens), extended
 * with a location filter and the same SEO head/FAQ treatment as the Track A
 * artist pages. URL contract: one path segment, the location slug,
 * rewritten server-side into $_GET['slug'].
 */
function renderCategoryLocationPage(string $categoryKey, string $categoryLabel, string $dimension, string $urlPrefix): void {
    $page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = 20;

    $locationValue = parseLocationSlug($dimension, $_GET['slug'] ?? '');

    if ($locationValue === null) {
        include 'header.php';
        echo '<div class="container py-5"><p>Invalid location.</p></div>';
        include 'footer.php';
        return;
    }

    $location = getLocationDisplayInfo($dimension, $locationValue);

    if (empty($location)) {
        include 'header.php';
        echo '<div class="container py-5"><p>Location not found.</p></div>';
        include 'footer.php';
        return;
    }

    $locationLabel = $location['label'];

    $eventsResponse = getCategoryEventsByLocation($categoryKey, $dimension, $locationValue, [
        'page'    => $page,
        'perPage' => $perPage,
        'includeTotalCount' => 'true',
    ]);

    $total_count = $eventsResponse['totalCount'] ?? 0;
    $total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;
    $events      = $eventsResponse['results'] ?? [];
    $count       = $eventsResponse['count'] ?? count($events);
    $percent     = $total_count > 0 ? ($perPage / $total_count) * 100 : 0;
    $year        = date('Y');

    $sep = '<span class="separator"><strong> / </strong></span>';
    $breadcrumbs = [
        ['label' => 'Home', 'url' => HOME_URL],
        ['label' => $categoryLabel, 'url' => HOME_URL . '/' . sanitize_title($categoryLabel)],
    ];

    $faqsRaw = getFaqs($categoryKey);
    $faqs = array_map(function ($faq) use ($categoryLabel, $locationLabel) {
        return [
            'question' => str_replace(['[category]', '[location]'], [$categoryLabel, $locationLabel], $faq['question']),
            'answer'   => str_replace(['[category]', '[location]'], [$categoryLabel, $locationLabel], $faq['answer']),
        ];
    }, $faqsRaw);

    // --- SEO: computed before including header.php so the <head> can use real data ---
    $pageFocusKeyword    = "$categoryLabel Tickets in " . preg_replace('/,\s*[A-Z]{2}$/', '', (string) $locationLabel);
    $pageMetaTitle       = "Buy $categoryLabel Tickets in $locationLabel | Seat Outlet";
    $pageMetaDescription = "Buy $categoryLabel tickets in $locationLabel. Compare prices across sellers, browse upcoming events, and find great seats on Seat Outlet.";
    $pageCanonicalUrl    = HOME_URL . '/' . $urlPrefix . '/' . createSlug($locationLabel, $locationValue);
    $pageJsonLdNodes = array_values(array_filter([
        buildBreadcrumbListSchema($breadcrumbs, "$categoryLabel in $locationLabel"),
        buildFaqPageSchema($faqs),
    ]));

    include 'header.php';
    ?>

    <section class="section-featured-header text-sm-center text-md-start">
        <div class="container-fluid min-vh-50 d-flex align-items-center justify-content-center text-white all-sports-events"
            style="background-image: url('<?php echo HOME_URL; ?>/images/event-so.webp'); background-size: cover; background-position: center; background-repeat: no-repeat;">
            <div class="container mx-xl-5 mx-lg-5 mx-md-3">
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="section-content">
                            <nav class="breadcrumb justify-content-sm-center justify-content-md-start">
                                <?php foreach ($breadcrumbs as $item) { ?>
                                    <a href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                    <?php echo $sep; ?>
                                <?php } ?>
                                <span class="current">
                                    <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </nav>
                        </div>
                    </div>
                    <div class="col-12">
                        <h1 class="artist-title text-white">
                            <?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?> Tickets in <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="tab-section section-performer-content" id="default">
                <div class="row mt-3 gap-5 gap-md-2 gap-lg-4 gap-xl-5 gap-xxl-5">
                    <div class="col-sm-12 col-md-8 left-bar">
                        <div class="mb-3 mb-md-4 mb-lg-4">
                            <div class="d-flex justify-content-between align-items-center results-header">
                                <div class="results-title">
                                    <span class="active-indicator"></span>
                                    <h2>
                                        <?php echo htmlspecialchars(strtoupper($categoryLabel), ENT_QUOTES, 'UTF-8'); ?> TICKETS IN <?php echo htmlspecialchars(strtoupper($locationLabel), ENT_QUOTES, 'UTF-8'); ?> <span class="dot">·</span>
                                        <span class="count" id="results_count">
                                            <?php echo (int) $total_count; ?>
                                            <?php echo $total_count > 1 ? 'RESULTS' : 'RESULT'; ?>
                                        </span>
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="list-category-bg pb-3">
                            <?php if (!empty($events)) { ?>
                                <div id="eventsSection" class="section-artist-content event-row-all">
                                    <?php foreach ($events as $event) {
                                        $eventDateRaw = $event['date']['date'] ?? '';
                                        $timestamp    = $eventDateRaw ? strtotime($eventDateRaw) : false;
                                        $eventSlug    = createSlug($event['text']['name'] ?? '', $event['id'] ?? 0);
                                        $eventCityLabel = trim(($event['city']['text']['name'] ?? '') . ', ' . ($event['stateProvince']['text']['abbr'] ?? ''), ', ');
                                        $eventCitySlug  = createSlug($eventCityLabel, $event['city']['id'] ?? 0);
                                        $eventVenueSlug = createSlug($event['venue']['text']['name'] ?? '', $event['venue']['id'] ?? 0);
                                    ?>
                                        <div class="d-flex align-items-center justify-content-between performer-event-item">
                                            <div class="date-box text-center me-3">
                                                <div class="month"><?php echo $timestamp ? htmlspecialchars(strtoupper(date('M', $timestamp)), ENT_QUOTES, 'UTF-8') : ''; ?></div>
                                                <div class="day"><?php echo $timestamp ? htmlspecialchars(date('d', $timestamp), ENT_QUOTES, 'UTF-8') : ''; ?></div>
                                                <?php if ($timestamp && date('Y', $timestamp) > $year) { ?>
                                                    <div class="month"><?php echo htmlspecialchars(date('Y', $timestamp), ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php } ?>
                                            </div>
                                            <div class="flex-grow-1 w-50">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-semibold day-weeks"><?php echo $timestamp ? htmlspecialchars(date('D', $timestamp), ENT_QUOTES, 'UTF-8') : ''; ?></span>
                                                    <span class="dot">·</span>
                                                    <span class="time-clock"><?php echo htmlspecialchars($event['date']['text']['time'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                                </div>
                                                <div class="ev-venue"><a href="/venue/<?php echo htmlspecialchars($eventVenueSlug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($event['venue']['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></div>
<div class="ev-place"><a href="/city/<?php echo htmlspecialchars($eventCitySlug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($eventCityLabel, ENT_QUOTES, 'UTF-8'); ?></a></div>
                                                <div class="ev-name">
                                                    <a href="/event/<?php echo htmlspecialchars($eventSlug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
                                                </div>
                                            </div>
                                            <div class="ms-3">
                                                <?php renderEventPriceTag($event); ?>
                                                <a href="/event/<?php echo htmlspecialchars($eventSlug, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary d-flex align-items-center gap-2" aria-label="Find tickets for <?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                    <span class="d-none d-md-inline">Buy Tickets</span>
                                                    <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <?php if ($total_pages > 1) { ?>
                                    <div class="load-more-wrapper text-center mt-5">
                                        <div class="load-progress mx-auto mb-3">
                                            <div class="small mb-2">
                                                Loaded <strong id="loadedCount"><?php echo $count; ?></strong> out of <strong id="totalCount"><?php echo $total_count; ?></strong> events
                                            </div>
                                            <div class="progress progress-thin">
                                                <div class="progress-bar" id="progressBar" style="width: <?php echo $percent; ?>%;"></div>
                                            </div>
                                        </div>
                                        <button
                                            class="btn more-events-btn d-inline-flex align-items-center gap-2"
                                            id="loadMoreBtn"
                                            data-total="<?php echo (int) $total_count; ?>"
                                            data-page="2"
                                            data-perpage="<?php echo (int) $perPage; ?>">
                                            <span class="btn-text">More Events</span>
                                            <span class="spinner-border spinner-border-sm d-none" id="btnSpinner"></span>
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <h3 style="padding: 20px; font-size: 1.25rem; font-weight: 400;">
                                    No <?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?> tickets found in <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8'); ?> right now.
                                </h3>
                            <?php } ?>
                        </div>
                        <?php renderLocationCategoryLinks($dimension, $locationValue, $locationLabel, $urlPrefix); ?>
                    <div id="secondary" class="sidebar col-sm-12 col-md-4">
                        <div class="sticky-top sidebar-inner">
                            <div class="guarantee-card d-flex align-items-center justify-content-between" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                <div class="guarantee">
                                    <strong>Shop Tickets Worry Free</strong><br>
                                    <span>With Our 100% Guarantee</span>
                                </div>
                                <div class="guarantee-icon"><i class="bi bi-shield-check"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($faqs)) { ?>
                <div class="tab-section content-section-detail" id="faqs">
                    <h2 class="so-heading mb-3">FAQs about <?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?> Tickets in <?php echo htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <div class="accordion" id="faqAccordion">
                        <?php foreach ($faqs as $index => $faq) {
                            $collapseId = 'collapse' . $index;
                            $headingId  = 'heading' . $index;
                            $isFirst = ($index === 0);
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                                    <button class="accordion-button <?php echo $isFirst ? '' : 'collapsed'; ?>"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#<?php echo $collapseId; ?>"
                                            aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>"
                                            aria-controls="<?php echo $collapseId; ?>">
                                        <?php echo htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                </h2>
                                <div id="<?php echo $collapseId; ?>"
                                    class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>"
                                    aria-labelledby="<?php echo $headingId; ?>"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?php echo nl2br(htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8')); ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>

    <?php
    include 'footer.php';
}


/**
 * Focus Keyword SEO Score (page_rules.focus_keyword / blog_posts.focus_keyword
 * - db/migrations/0007_seo_focus_keyword.sql). Modeled directly on Rank
 * Math's own open-source scoring engine (github.com/rankmath/seo-by-rank-math,
 * assets/admin/src/analyzer/analysis/*.js) - same checks, same point values
 * for the ones below, not a from-scratch invention. Two checks from Rank
 * Math's set are deliberately left out: titleSentiment (needs a full
 * sentiment-word lexicon this project doesn't have) and contentHasTOC
 * (detects specific WordPress table-of-contents plugins, not applicable
 * here). Everything else - keyword in title/description/URL/content/
 * subheadings/image-alt, keyword density, content length, internal/external
 * links, paragraph length, title length/number/power-words - is
 * implemented the same way Rank Math checks it. The 18 checks below sum to
 * 98 raw points; the displayed score is that normalized to /100 so it reads
 * the same way Rank Math's does.
 */
const SEO_SCORE_MAX_RAW = 98;

const SEO_POWER_WORDS = [
    'free', 'ultimate', 'essential', 'secret', 'secrets', 'proven', 'guide', 'guaranteed',
    'best', 'top', 'exclusive', 'new', 'amazing', 'easy', 'instant', 'save', 'discover',
    'unlock', 'boost', 'powerful', 'complete', 'definitive', 'trusted', 'verified',
    'limited', 'now', 'simple', 'quick', 'effortless', 'insider', 'expert', 'official',
    'genuine', 'authentic', 'winning', 'incredible', 'unbeatable', 'unforgettable',
];

/**
 * Self-fetches a page's own rendered HTML over HTTP (not by reading the
 * .php source) so the score reflects what a browser/crawler actually sees -
 * the same page after header.php/footer.php/functions.php have all run,
 * with real TicketNetwork data where applicable, exactly like Rank Math
 * analyzes the rendered post rather than the raw editor markup.
 */
function fetchRenderedPageHtml($pagePath) {
    $pagePath = normalizePagePath($pagePath);
    $url = rtrim(HOME_URL, '/') . $pagePath;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['User-Agent: SeatOutletSeoScorer/1.0'],
    ]);
    $html = curl_exec($ch);
    $ok = $html !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);

    return $ok ? $html : null;
}

/**
 * Parses a page's rendered HTML into the same signals Rank Math's Paper/
 * Researcher classes extract from a post: title, meta description, body
 * text with the shared header/nav/footer/script/style chrome stripped out
 * (header.php/footer.php wrap every page in <header>/<nav>/<footer> tags,
 * so this reliably isolates the page's own content), subheading text,
 * image alt attributes, and outbound/internal links.
 */
function extractSeoContentSignals($html) {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    $title = '';
    $titleNodes = $xpath->query('//title');
    if ($titleNodes->length > 0) {
        $title = trim($titleNodes->item(0)->textContent);
    }

    $metaDescription = '';
    $descNodes = $xpath->query('//meta[@name="description"]/@content');
    if ($descNodes->length > 0) {
        $metaDescription = trim($descNodes->item(0)->textContent);
    }

    // Remove the shared chrome so word count/keyword/link checks only see
    // this page's own content, not the nav menu, footer link list, ticker
    // topbar, or the shared "Our 100% Guarantee"/"Event information"
    // modal-and-offcanvas templates every page includes - all identical on
    // every page, so left in place they'd make every page's content look
    // the same to the scorer. .header-top-section (header.php) wraps the
    // topbar+ticker AND the <header> tag together, so tag-only removal
    // missed the topbar/ticker text sitting next to <header> inside it.
    foreach (['header', 'nav', 'footer', 'script', 'style', 'noscript'] as $tag) {
        foreach ($xpath->query("//{$tag}") as $node) {
            $node->parentNode->removeChild($node);
        }
    }
    $chromeSelectors = [
        "//div[contains(concat(' ', normalize-space(@class), ' '), ' header-top-section ')]",
        "//*[@id='mobileMenu']",
        "//*[@id='staticBackdrop']",
        "//*[@id='offcanvasRight']",
    ];
    foreach ($chromeSelectors as $selector) {
        foreach ($xpath->query($selector) as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    $bodyNodes = $xpath->query('//body');
    $bodyText = $bodyNodes->length > 0 ? preg_replace('/\s+/', ' ', trim($bodyNodes->item(0)->textContent)) : '';

    $headings = [];
    foreach (['h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
        foreach ($xpath->query("//{$tag}") as $node) {
            $headings[] = trim($node->textContent);
        }
    }

    $imageAlts = [];
    $imageCount = 0;
    foreach ($xpath->query('//img') as $node) {
        $imageCount++;
        $alt = trim($node->getAttribute('alt'));
        if ($alt !== '') {
            $imageAlts[] = $alt;
        }
    }

    $videoCount = $xpath->query('//video')->length + $xpath->query('//iframe')->length;

    $paragraphs = [];
    foreach ($xpath->query('//p') as $node) {
        $text = trim($node->textContent);
        if ($text !== '') {
            $paragraphs[] = str_word_count($text);
        }
    }

    $host = parse_url(HOME_URL, PHP_URL_HOST) ?: '';
    $internalLinks = 0;
    $externalLinks = 0;
    $externalDofollow = 0;
    foreach ($xpath->query('//a[@href]') as $node) {
        $href = trim($node->getAttribute('href'));
        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
            continue;
        }
        $linkHost = parse_url($href, PHP_URL_HOST);
        $isExternal = $linkHost !== null && $linkHost !== '' && strcasecmp($linkHost, $host) !== 0;
        if ($isExternal) {
            $externalLinks++;
            $rel = strtolower($node->getAttribute('rel'));
            if (!str_contains($rel, 'nofollow')) {
                $externalDofollow++;
            }
        } else {
            $internalLinks++;
        }
    }

    return [
        'title' => $title,
        'meta_description' => $metaDescription,
        'body_text' => $bodyText,
        'headings' => $headings,
        'image_alts' => $imageAlts,
        'image_count' => $imageCount,
        'video_count' => $videoCount,
        'paragraph_word_counts' => $paragraphs,
        'internal_links' => $internalLinks,
        'external_links' => $externalLinks,
        'external_dofollow_links' => $externalDofollow,
    ];
}

/**
 * The scoring engine itself - runs the 18 checks against a set of already-
 * extracted signals (from either extractSeoContentSignals() for a static
 * page, or directly from a blog_posts row - see scoreBlogPost()) plus a
 * focus keyword and the page's URL path. Returns the same shape either way
 * so the admin UI doesn't need to know which source produced it.
 */
function computeSeoScore(array $signals, $focusKeyword, $urlPath) {
    $focusKeyword = trim((string) $focusKeyword);
    $checks = [];
    $earn = function ($key, $label, $weight, $passed, $passMsg, $failMsg) use (&$checks) {
        $checks[] = [
            'key' => $key,
            'label' => $label,
            'weight' => $weight,
            'earned' => $passed ? $weight : 0,
            'status' => $passed ? 'pass' : 'fail',
            'message' => $passed ? $passMsg : $failMsg,
        ];
    };
    $na = function ($key, $label, $weight, $msg) use (&$checks) {
        $checks[] = [
            'key' => $key, 'label' => $label, 'weight' => $weight,
            'earned' => 0, 'status' => 'na', 'message' => $msg,
        ];
    };

    $title = $signals['title'] ?? '';
    $description = $signals['meta_description'] ?? '';
    $bodyText = $signals['body_text'] ?? '';
    $bodyTextLower = mb_strtolower($bodyText);
    $titleLower = mb_strtolower($title);
    $descLower = mb_strtolower($description);
    $keywordLower = mb_strtolower($focusKeyword);

    if ($focusKeyword === '') {
        $na('no_keyword', 'Focus Keyword', 0, 'Set a Focus Keyword to run the on-page checks below.');
        return [
            'raw' => 0, 'max_raw' => SEO_SCORE_MAX_RAW, 'score' => 0, 'checks' => $checks,
        ];
    }

    // --- Title ---
    $earn('keyword_in_title', 'Focus Keyword in the SEO Title', 36,
        $title !== '' && str_contains($titleLower, $keywordLower),
        'The Focus Keyword appears in the SEO title.',
        'Add the Focus Keyword to the SEO title.');

    $earn('title_starts_with_keyword', 'Focus Keyword near the beginning of the Title', 3,
        $titleLower !== '' && str_starts_with(ltrim($titleLower), $keywordLower),
        'The Focus Keyword is used at the beginning of the SEO title.',
        'Use the Focus Keyword near the beginning of the SEO title.');

    $earn('title_has_number', 'Title contains a number', 1,
        (bool) preg_match('/\d/', $title),
        'The SEO title contains a number, which can improve click-through rate.',
        'Consider adding a number to the title to improve click-through rate.');

    $powerWordPattern = '/\b(' . implode('|', array_map('preg_quote', SEO_POWER_WORDS)) . ')\b/i';
    $earn('title_has_power_word', 'Title contains a power word', 1,
        (bool) preg_match($powerWordPattern, $title),
        'The SEO title contains a power word.',
        'Consider adding a power word (e.g. "best", "guide", "proven") to the title.');

    // --- Meta description ---
    $earn('keyword_in_description', 'Focus Keyword in the Meta Description', 2,
        $description !== '' && str_contains($descLower, $keywordLower),
        'The Focus Keyword appears in the meta description.',
        'Add the Focus Keyword to the meta description.');

    // --- URL ---
    $slugForCheck = mb_strtolower(str_replace(['-', '_'], ' ', $urlPath));
    $earn('keyword_in_url', 'Focus Keyword in the URL', 5,
        str_contains($slugForCheck, $keywordLower),
        'The Focus Keyword appears in the URL.',
        'Use the Focus Keyword in the URL/slug.');

    $earn('url_length', 'URL is a reasonable length', 4,
        strlen($urlPath) <= 75,
        'The URL is a reasonable length (75 characters or fewer).',
        'The URL is longer than 75 characters - consider shortening it.');

    // --- Content ---
    $hasBody = $bodyText !== '';
    if (!$hasBody) {
        $na('no_content', 'Content-based checks', 0, 'Could not read this page\'s content (self-fetch failed or the page has no body text) - content checks are skipped.');
    } else {
        $earn('keyword_in_10_percent', 'Focus Keyword in the first 10% of content', 3,
            keywordInFirstPercentOfContent($bodyTextLower, $keywordLower),
            'The Focus Keyword appears early in the content.',
            'Use the Focus Keyword in the first 10% of the content.');

        $earn('keyword_in_content', 'Focus Keyword used in the content', 3,
            str_contains($bodyTextLower, $keywordLower),
            'The Focus Keyword is used in the content.',
            'Use the Focus Keyword in the content.');

        $wordCount = str_word_count($bodyText);
        $keywordOccurrences = $wordCount > 0 ? substr_count($bodyTextLower, $keywordLower) : 0;
        $density = $wordCount > 0 ? round(($keywordOccurrences / $wordCount) * 100, 2) : 0;
        [$densityScore, $densityStatus, $densityMsg] = scoreKeywordDensity($density, $keywordOccurrences);
        $checks[] = [
            'key' => 'keyword_density', 'label' => 'Focus Keyword density', 'weight' => 6,
            'earned' => $densityScore, 'status' => $densityStatus, 'message' => $densityMsg,
        ];

        $hasSubheadingKeyword = false;
        foreach ($signals['headings'] ?? [] as $heading) {
            if (str_contains(mb_strtolower($heading), $keywordLower)) {
                $hasSubheadingKeyword = true;
                break;
            }
        }
        $earn('keyword_in_subheadings', 'Focus Keyword in a subheading (H2-H6)', 3,
            $hasSubheadingKeyword,
            'The Focus Keyword appears in at least one subheading.',
            'Add the Focus Keyword to at least one subheading (H2, H3, etc.).');

        $hasImageAltKeyword = false;
        foreach ($signals['image_alts'] ?? [] as $alt) {
            if (str_contains(mb_strtolower($alt), $keywordLower)) {
                $hasImageAltKeyword = true;
                break;
            }
        }
        $earn('keyword_in_image_alt', 'Focus Keyword in an image alt attribute', 2,
            $hasImageAltKeyword,
            'The Focus Keyword appears in at least one image alt attribute.',
            'Add the Focus Keyword to the alt attribute of at least one image.');

        [$lengthScore, $lengthMsg] = scoreContentLength($wordCount);
        $checks[] = [
            'key' => 'content_length', 'label' => 'Content length', 'weight' => 8,
            'earned' => $lengthScore, 'status' => $lengthScore > 0 ? 'pass' : 'fail', 'message' => $lengthMsg,
        ];

        $internalLinks = (int) ($signals['internal_links'] ?? 0);
        $externalLinks = (int) ($signals['external_links'] ?? 0);
        $externalDofollow = (int) ($signals['external_dofollow_links'] ?? 0);

        $earn('links_internal', 'Has internal links', 5,
            $internalLinks > 0,
            "Found $internalLinks internal link(s).",
            'Add at least one internal link (to another page on this site).');

        $earn('links_external', 'Links to an external resource', 4,
            $externalLinks > 0,
            "Found $externalLinks external link(s).",
            'Link out to at least one relevant external resource.');

        $earn('links_not_all_nofollow', 'Has at least one dofollow external link', 2,
            $externalDofollow > 0,
            'At least one external link is dofollow.',
            'All external links are nofollow - consider making at least one dofollow.');

        $imageCount = (int) ($signals['image_count'] ?? 0);
        $videoCount = (int) ($signals['video_count'] ?? 0);
        $assetScore = min(6, imageCountScore($imageCount) + videoCountScore($videoCount));
        $checks[] = [
            'key' => 'content_has_assets', 'label' => 'Content contains images/video', 'weight' => 6,
            'earned' => $assetScore, 'status' => $assetScore > 0 ? 'pass' : 'fail',
            'message' => $assetScore > 0
                ? "Found $imageCount image(s) and $videoCount video(s)."
                : 'Add at least one image or video to make the content more appealing.',
        ];

        $longestParagraph = 0;
        foreach ($signals['paragraph_word_counts'] ?? [] as $wc) {
            $longestParagraph = max($longestParagraph, $wc);
        }
        $earn('short_paragraphs', 'Paragraphs are a reasonable length', 3,
            $longestParagraph <= 120,
            'No paragraph is longer than 120 words.',
            "The longest paragraph is $longestParagraph words - break it up for readability.");
    }

    // --- Cross-page: keyword reuse (informational, not scored) ---
    $duplicates = findOtherPagesUsingFocusKeyword($focusKeyword, $urlPath);
    if (!empty($duplicates)) {
        $checks[] = [
            'key' => 'keyword_reuse', 'label' => 'Focus Keyword uniqueness', 'weight' => 0,
            'earned' => 0, 'status' => 'warning',
            'message' => 'This keyword is already the focus keyword on: ' . implode(', ', $duplicates)
                . ' - competing pages for the same keyword can hurt both (keyword cannibalization).',
        ];
    }

    $raw = array_sum(array_column($checks, 'earned'));
    return [
        'raw' => $raw,
        'max_raw' => SEO_SCORE_MAX_RAW,
        'score' => (int) round(($raw / SEO_SCORE_MAX_RAW) * 100),
        'checks' => $checks,
    ];
}

function keywordInFirstPercentOfContent($bodyTextLower, $keywordLower) {
    $words = preg_split('/\s+/', trim($bodyTextLower));
    if (count($words) > 400) {
        $words = array_slice($words, 0, (int) floor(count($words) * 0.1));
    }
    return str_contains(implode(' ', $words), $keywordLower);
}

/** Same boundaries as Rank Math's keywordDensity.js: fail <0.5% or >2.5%, fair 0.5-0.75%, good 0.76-1.0%, best otherwise. */
function scoreKeywordDensity($density, $occurrences) {
    if ($density < 0.5) {
        return [0, 'fail', "Keyword density is {$density}% (appears $occurrences time(s)), which is low - aim for around 1%."];
    }
    if ($density > 2.5) {
        return [0, 'fail', "Keyword density is {$density}% (appears $occurrences time(s)), which is high - this can look like keyword stuffing."];
    }
    if ($density >= 0.5 && $density <= 0.75) {
        return [2, 'warning', "Keyword density is {$density}% (appears $occurrences time(s)) - fair, could be a bit higher."];
    }
    if ($density >= 0.76 && $density <= 1.0) {
        return [3, 'pass', "Keyword density is {$density}% (appears $occurrences time(s)) - good."];
    }
    return [6, 'pass', "Keyword density is {$density}% (appears $occurrences time(s)) - best."];
}

/** Same boundaries as Rank Math's lengthContent.js. */
function scoreContentLength($wordCount) {
    if ($wordCount >= 2500) {
        return [8, "Content is $wordCount words long. Good job!"];
    }
    if ($wordCount >= 2000) {
        return [5, "Content is $wordCount words long - solid, 2500+ is ideal."];
    }
    if ($wordCount >= 1500) {
        return [4, "Content is $wordCount words long - decent, consider expanding toward 2500."];
    }
    if ($wordCount >= 1000) {
        return [3, "Content is $wordCount words long - below the recommended 2500."];
    }
    if ($wordCount >= 600) {
        return [2, "Content is $wordCount words long - the minimum recommended is 600."];
    }
    return [0, "Content is $wordCount words long. Consider using at least 600 words."];
}

/** Same score hash as Rank Math's contentHasAssets.js. */
function imageCountScore($count) {
    $map = [0 => 0, 1 => 1, 2 => 2, 3 => 4];
    return $map[$count] ?? 6;
}

function videoCountScore($count) {
    $map = [0 => 0, 1 => 1];
    return $map[$count] ?? 2;
}

/**
 * Cross-page keyword-cannibalization check: does any other page_rules row
 * or blog_posts row already use this same focus keyword?
 */
function findOtherPagesUsingFocusKeyword($focusKeyword, $excludeUrlPath, $mysqli = MYSQLI) {
    $excludeUrlPath = normalizePagePath($excludeUrlPath);
    $matches = [];

    $stmt = $mysqli->prepare('SELECT url_path FROM page_rules WHERE focus_keyword = ? AND url_path != ?');
    $stmt->bind_param('ss', $focusKeyword, $excludeUrlPath);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $matches[] = $row['url_path'];
    }
    $stmt->close();

    // Keywords assigned in the code plan (inc/seo-keywords.php) count too, unless an admin page rule replaced them.
    foreach (soSeoPlan() as $planPath => $planRow) {
        if (strcasecmp($planRow[0], $focusKeyword) === 0 && $planPath !== $excludeUrlPath && !in_array($planPath, $matches, true)) {
            $matches[] = $planPath;
        }
    }

    $stmt = $mysqli->prepare('SELECT slug FROM blog_posts WHERE focus_keyword = ? AND CONCAT(\'/blog/\', slug) != ?');
    $stmt->bind_param('ss', $focusKeyword, $excludeUrlPath);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $matches[] = '/blog/' . $row['slug'];
    }
    $stmt->close();

    return $matches;
}

/** Scores a static page: self-fetches its rendered HTML, then runs computeSeoScore(). */
function scoreStaticPage($urlPath, $focusKeyword) {
    $html = fetchRenderedPageHtml($urlPath);
    if ($html === null) {
        return [
            'raw' => 0, 'max_raw' => SEO_SCORE_MAX_RAW, 'score' => null,
            'checks' => [[
                'key' => 'fetch_failed', 'label' => 'Could not load this page', 'weight' => 0,
                'earned' => 0, 'status' => 'fail',
                'message' => "Couldn't fetch $urlPath (page not reachable at " . HOME_URL . ' - check the path is correct and the site is up).',
            ]],
        ];
    }
    $signals = extractSeoContentSignals($html);
    return computeSeoScore($signals, $focusKeyword, $urlPath);
}

/** Scores a blog post directly from its stored fields - no HTTP fetch needed. */
function scoreBlogPost(array $post) {
    $title = $post['meta_title'] ?: $post['title'];
    $description = $post['meta_description'] ?: $post['excerpt'];
    $content = $post['content'] ?? '';

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $content . '</div>');
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    $headings = [];
    foreach (['h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
        foreach ($xpath->query("//{$tag}") as $node) {
            $headings[] = trim($node->textContent);
        }
    }
    $imageAlts = [];
    $imageCount = 0;
    foreach ($xpath->query('//img') as $node) {
        $imageCount++;
        $alt = trim($node->getAttribute('alt'));
        if ($alt !== '') {
            $imageAlts[] = $alt;
        }
    }
    $videoCount = $xpath->query('//video')->length + $xpath->query('//iframe')->length;
    $paragraphs = [];
    foreach ($xpath->query('//p') as $node) {
        $text = trim($node->textContent);
        if ($text !== '') {
            $paragraphs[] = str_word_count($text);
        }
    }
    $host = parse_url(HOME_URL, PHP_URL_HOST) ?: '';
    $internalLinks = 0;
    $externalLinks = 0;
    $externalDofollow = 0;
    foreach ($xpath->query('//a[@href]') as $node) {
        $href = trim($node->getAttribute('href'));
        if ($href === '' || str_starts_with($href, '#')) {
            continue;
        }
        $linkHost = parse_url($href, PHP_URL_HOST);
        $isExternal = $linkHost !== null && $linkHost !== '' && strcasecmp($linkHost, $host) !== 0;
        if ($isExternal) {
            $externalLinks++;
            if (!str_contains(strtolower($node->getAttribute('rel')), 'nofollow')) {
                $externalDofollow++;
            }
        } else {
            $internalLinks++;
        }
    }
    $bodyText = preg_replace('/\s+/', ' ', trim($doc->textContent));

    $signals = [
        'title' => $title,
        'meta_description' => $description,
        'body_text' => $bodyText,
        'headings' => $headings,
        'image_alts' => $imageAlts,
        'image_count' => $imageCount,
        'video_count' => $videoCount,
        'paragraph_word_counts' => $paragraphs,
        'internal_links' => $internalLinks,
        'external_links' => $externalLinks,
        'external_dofollow_links' => $externalDofollow,
    ];

    return computeSeoScore($signals, $post['focus_keyword'] ?? '', '/blog/' . $post['slug']);
}

/** Rank Math's own red/orange/green score bands, used for the admin UI's badges. */
function seoScoreBadgeClass($score) {
    if ($score === null) {
        return 'bg-secondary-lt';
    }
    if ($score < 50) {
        return 'bg-danger-lt';
    }
    if ($score < 80) {
        return 'bg-orange-lt';
    }
    return 'bg-green-lt';
}

// Entity image layer (performers, teams, venues, festivals, cities).
require_once __DIR__ . '/inc/images.php';
