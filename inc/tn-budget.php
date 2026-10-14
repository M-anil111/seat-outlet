<?php
/**
 * Shared budget for live TicketNetwork calls, single-flight on cold keys, and Retry-After handling. Kept apart from functions.php
 * so tools/test-tn-budget.php can load it on its own. Every function takes its file or clock as a parameter where a test needs one.
 *
 * Budget: a token bucket shared by every PHP process (web requests, crons, crawlers). Visitors (web requests) may use the whole
 * bucket and wait up to 1.5 seconds for a token. Background work (CLI crons) may use only the upper half, so a warmer can never
 * use up the calls a visitor needs. The rate is TN_CALLS_PER_SECOND (default 0.75, which is 45 a minute; 0 turns the budget off). Lower it while 429s
 * continue; raise it only when TicketNetwork confirms a higher limit.
 */

/**
 * Each family of live calls has its own circuit, so a throttle on event lists does not stop performer or venue lookups (and the
 * other way round). Calls with no endpoint in hand (crons, the sitemap build) check every family.
 */
const TN_BREAKER_FAMILIES = ['events', 'reference'];

/** The family of an endpoint: event lists, details and search are "events"; everything else is "reference". */
function tnBreakerFamily($endpoint): string {
    $e = trim((string) $endpoint, '/');
    return (strpos($e, 'catalog/v2/events') === 0 || strpos($e, 'events/search') !== false) ? 'events' : 'reference';
}

/**
 * Today's TicketNetwork outcome counts (cache/tn_stats_<date>.json) as per-page shares. "pages" is counted once per visitor page that
 * needed an answer (tnCount), "no_data" once per page that got none, "pages_suspect" once per page that kept a stored list because the
 * live answer came back empty. The alert flag is on when either share passes 20% over at least 50 pages, so a quiet hour with one
 * failure does not page anyone, and a long empty-feed flap is not hidden behind a low no-data rate.
 */
function tnStatsSummary(array $stats): array {
    $pages = (int) ($stats['pages'] ?? 0);
    // Both are counted once per page, so no_data can never exceed pages. A larger value is what a day's file holds when the "pages"
    // counter was added mid-day (deployed after no_data had been counting): clamp, so the share is never over 100% and a stale
    // leftover cannot raise an alert.
    $noData = min($pages, (int) ($stats['no_data'] ?? 0));
    $suspect = min($pages, (int) ($stats['pages_suspect'] ?? 0));
    $rate = $pages > 0 ? $noData / $pages : 0.0;
    $suspectRate = $pages > 0 ? $suspect / $pages : 0.0;
    $liveCalls = (int) ($stats['live_call'] ?? 0);
    $share = fn(int $part, int $whole): float => $whole > 0 ? round(min(1.0, $part / $whole), 3) : 0.0;
    $botPages = min($pages, (int) ($stats['pages_bot'] ?? 0));
    $directPages = min($pages, (int) ($stats['pages_direct'] ?? 0));
    $botCalls = min($liveCalls, (int) ($stats['live_call_bot'] ?? 0));
    $directCalls = min($liveCalls, (int) ($stats['live_call_direct'] ?? 0));
    return ['pages' => $pages, 'noData' => $noData, 'rate' => round($rate, 3), 'suspect' => $suspect, 'suspectRate' => round($suspectRate, 3),
            // per call, not per page: how many live calls the API refused (429) and how many our own budget held back
            'throttled' => (int) ($stats['throttled'] ?? 0), 'overBudget' => (int) ($stats['over_budget'] ?? 0),
            // who asked: crawlers (_bot) and hits on the beta address (_direct), as a share of pages, of the live calls, and of the no-data pages
            'demand' => [
                'botPageShare' => $share($botPages, $pages), 'botCallShare' => $share($botCalls, $liveCalls),
                'botNoDataShare' => $share(min($noData, (int) ($stats['no_data_bot'] ?? 0)), $noData),
                'directPageShare' => $share($directPages, $pages), 'directCallShare' => $share($directCalls, $liveCalls),
                'directNoDataShare' => $share(min($noData, (int) ($stats['no_data_direct'] ?? 0)), $noData),
            ],
            'alert' => $pages >= 50 && ($rate > 0.2 || $suspectRate > 0.2)];
}

/**
 * True when the request came through the live Cloudflare Worker, which fetches every page from the beta origin and sets
 * X-Forwarded-Host to the public host; false for a direct hit on the beta address. seatoutlet.com is not a separate server: both names
 * reach one PHP install, so this header is the only way to tell them apart. $server and $publicHost are for tests.
 */
function soViaLiveProxy(?array $server = null, ?string $publicHost = null): bool {
    $server = $server ?? $_SERVER;
    $publicHost = $publicHost ?? (defined('SO_PUBLIC_ORIGIN') ? (string) (parse_url(SO_PUBLIC_ORIGIN, PHP_URL_HOST) ?: rtrim((string) SO_PUBLIC_ORIGIN, '/')) : 'seatoutlet.com');
    // X-SO-Public-Host is the Worker's own header; X-Forwarded-Host is kept as a fallback, but a proxy layer in front of PHP can rewrite it
    // to the beta host (13 Oct 2026: /ajax/health.php reported viaProxy false through seatoutlet.com), which is why the Worker sends both.
    $host = strtolower(trim(explode(',', (string) ($server['HTTP_X_SO_PUBLIC_HOST'] ?? '') ?: (string) ($server['HTTP_X_FORWARDED_HOST'] ?? ''))[0]));
    $host = preg_replace('/:\d+$/', '', $host);
    return $host !== '' && $publicHost !== '' && ($host === strtolower($publicHost) || $host === 'www.' . strtolower($publicHost));
}

/** A crawler or script rather than a person, for the demand counters only (never to refuse anyone), so the pattern is broad but names crawlers rather than matching a bare "bot" (UptimeRobot, a Cubot phone). */
function soClientIsCrawler(?string $userAgent = null): bool {
    $ua = $userAgent ?? (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    return $ua !== '' && preg_match('/\bbot\b|googlebot|bingbot|yandex|baiduspider|duckduckbot|applebot|slurp|semrush|ahrefs|mj12|dotbot|shapbot|petalbot|bytespider|facebookexternalhit|crawl|spider|scrapy|python-requests|go-http-client|wget|curl\//i', $ua) === 1;
}

/**
 * The counters one TicketNetwork outcome bumps in the day's stats file. The outcome itself; "pages" once per page (the first
 * outcome of a page request); "pages_suspect" once per page that kept a stored list; and, for a crawler (_bot) or a hit on the beta
 * address instead of the live Worker (_direct), the same outcome and page again under that suffix, so the health page can say who the
 * demand and the no-data pages are. $firstOfPage and $firstSuspect are decided by the caller (once per request).
 */
function tnCountKeys(string $kind, bool $firstOfPage, bool $firstSuspect, bool $crawler, bool $direct): array {
    $keys = [$kind];
    if ($firstOfPage) $keys[] = 'pages';
    if ($firstSuspect) $keys[] = 'pages_suspect';
    $split = ['live_call', 'no_data', 'over_budget', 'throttled', 'stale_on_error'];
    foreach ([['_bot', $crawler], ['_direct', $direct]] as [$suffix, $on]) {
        if (!$on) continue;
        if (in_array($kind, $split, true)) $keys[] = $kind . $suffix;
        if ($firstOfPage) $keys[] = 'pages' . $suffix;
    }
    return $keys;
}

/** Live calls per second the whole site may make to TicketNetwork. */
function tnCallsPerSecond(): float {
    $v = getenv('TN_CALLS_PER_SECOND');
    // Default 0.75 (45 a minute). TicketNetwork support confirmed on 14 Oct 2026 that the Catalog API limit is 50 requests a minute
    // (code 900804, the subscription tier). Measured on 13 Oct 2026 (ten minutes each): at 1 a second, about 460 calls got through
    // (46 a minute) and 2 were refused; at 2, 37 were refused with the same number getting through. A default of 1 is 60 a minute plus
    // a burst of two, above the limit; 0.75 leaves headroom for the burst and for a window that is not aligned with ours. Only
    // TicketNetwork can raise the tier; a higher rate here just turns budget holds into 429s.
    return ($v === false || $v === '' || !is_numeric($v)) ? 0.75 : max(0.0, (float) $v);
}

/**
 * A caller that may only use the spare part of the budget: a cron (CLI), and a page request the snapshot warmer makes to the site
 * itself (it sends X-SO-Warm with its own user agent, so its visits are not mistaken for visitors and cannot drain the bucket ahead
 * of them). Both are required: a background caller is also left out of the visitor counts on the health page, and a bot must not be
 * able to take its failures out of those counts by sending one header.
 */
function tnBackgroundCaller(): bool {
    return PHP_SAPI === 'cli' || (!empty($_SERVER['HTTP_X_SO_WARM']) && ($_SERVER['HTTP_USER_AGENT'] ?? '') === 'SeatOutletSnapshotWarmer/1.0');
}

/**
 * Takes one token if the bucket allows it. Background callers need 1 + half the burst, so they leave the rest for visitors.
 * Fails open when the bucket file cannot be locked: a counter fault must never block a visitor.
 */
function tnBucketTake(bool $background, float $rate, string $file, ?float $now = null): bool {
    if ($rate <= 0) return true;
    $now = $now ?? microtime(true);
    $burst = max(2.0, $rate * 2);
    $fh = @fopen($file . '.lock', 'c');
    if (!$fh) return true;
    if (!flock($fh, LOCK_EX)) {   // no lock: do not block and do not count (fail open, as above)
        fclose($fh);
        return true;
    }
    $state = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    $tokens = is_array($state) ? (float) ($state['tokens'] ?? $burst) : $burst;
    $last = is_array($state) ? (float) ($state['t'] ?? $now) : $now;
    $tokens = min($burst, $tokens + max(0.0, $now - $last) * $rate);
    $need = $background ? 1 + $burst / 2 : 1.0;
    $ok = $tokens >= $need;
    if ($ok) $tokens -= 1;
    @file_put_contents($file, json_encode(['tokens' => $tokens, 't' => $now]), LOCK_EX);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $ok;
}

/** A visitor waits up to $maxWait seconds for a token; a background caller never waits. */
function tnBucketWait(bool $background, float $rate, string $file, float $maxWait = 1.5): bool {
    if ($background) return tnBucketTake(true, $rate, $file);
    $deadline = microtime(true) + $maxWait;
    do {
        if (tnBucketTake(false, $rate, $file)) return true;
        // Sleep no longer than the time left, so the wait never runs past $maxWait by a whole polling step.
        $left = $deadline - microtime(true);
        if ($left <= 0) break;
        usleep((int) (min(0.15, $left) * 1000000));
    } while (microtime(true) < $deadline);
    return false;
}

/** The numeric seconds of a "Retry-After:" response header line, or null (HTTP-date forms are not used by TicketNetwork's throttle). */
function tnRetryAfterFromHeader(string $line): ?int {
    return preg_match('/^retry-after:\s*(\d+)\s*$/i', trim($line), $m) ? (int) $m[1] : null;
}

/** How long the breaker stays open after a throttle: the server's Retry-After (5 to 300 s), else the default. */
function tnThrottleWait(?int $retryAfter, int $default = 60): int {
    return $retryAfter === null ? $default : max(5, min(300, $retryAfter));
}

/**
 * Single-flight on a cold key. The first request opens a lock file and fetches; a second request gets false and waits for the
 * first one's answer. Returns the handle (leader), false (follower), or null when the lock file cannot be opened (no coordination).
 */
function tnFlightOpen(string $path) {
    $fh = @fopen($path, 'c');
    if (!$fh) return null;
    if (flock($fh, LOCK_EX | LOCK_NB)) return $fh;
    fclose($fh);
    return false;
}

function tnFlightClose($fh): void {
    if (is_resource($fh)) {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

/**
 * A follower waits for the leader's stored answer ($cached returns it or false). It stops early when the leader has released the
 * lock without storing anything (the fetch failed), and gives up after $maxWait seconds. Returns the answer or false.
 */
function tnFlightWait(string $path, callable $cached, float $maxWait = 3.0) {
    $deadline = microtime(true) + $maxWait;
    do {
        $hit = $cached();
        if ($hit !== false) return $hit;
        $fh = @fopen($path, 'c');
        if (!$fh) return false;
        $free = flock($fh, LOCK_EX | LOCK_NB);
        if ($free) flock($fh, LOCK_UN);
        fclose($fh);
        if ($free) {   // the leader is done: its answer may have been stored just before it let go
            $hit = $cached();
            return $hit !== false ? $hit : false;
        }
        usleep(100000);
    } while (microtime(true) < $deadline);
    return false;
}

/**
 * The device scope this process asks for. TicketNetwork's guide: a new token revokes the previous token with the same scopes, so
 * web requests and cron jobs must not share one token (each one's refresh would log the other out). Web and cron each get their
 * own device token; set TN_DEVICE_ID per server when more than one web host shares the application.
 */
function tnDeviceScope() {
    $id = getenv('TN_DEVICE_ID');
    $id = ($id === false || $id === '') ? '' : preg_replace('/[^a-z0-9_-]/i', '', $id) . '_';
    // The role is part of the scope, so web and cron keep separate tokens even when they share one configured id.
    return 'device_' . $id . (PHP_SAPI === 'cli' ? 'cli' : 'web');
}
