<?php
/**
 * Shared budget for live TicketNetwork calls, single-flight on cold keys, and Retry-After handling. Kept apart from functions.php
 * so tools/test-tn-budget.php can load it on its own. Every function takes its file or clock as a parameter where a test needs one.
 *
 * Budget: a token bucket shared by every PHP process (web requests, crons, crawlers). Visitors (web requests) may use the whole
 * bucket and wait up to 1.5 seconds for a token. Background work (CLI crons) may use only the upper half, so a warmer can never
 * use up the calls a visitor needs. The rate is TN_CALLS_PER_SECOND (default 1; 0 turns the budget off). Lower it while 429s
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

/** Live calls per second the whole site may make to TicketNetwork. */
function tnCallsPerSecond(): float {
    $v = getenv('TN_CALLS_PER_SECOND');
    return ($v === false || $v === '' || !is_numeric($v)) ? 1.0 : max(0.0, (float) $v);
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
