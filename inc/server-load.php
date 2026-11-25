<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Background work yields to visitors.
 *
 * The page warmers, the image queue, the sitemap builder and the top performers refresh all run on the same PHP workers and CPU as the
 * public pages. When the server is already busy they only make it slower (and, on a shared host, push the account over its limits), and
 * nothing is lost by skipping a turn: every one of them runs again at its next turn, and the data they refresh stays valid for hours.
 *
 * soServerBusy() is true when the one-minute load average is above the limit. The limit is SO_BG_MAX_LOAD (an absolute load average)
 * or, when that is not set, one per CPU core; a negative value (-1) means always busy, which pauses the jobs that ask (the page warmers, the
 * image and sitemap workers and the top performers refresh). With no load figure (a host that hides it) it is never busy unless a negative
 * limit is set, so nothing changes there.
 */
function soCpuCores(): int {
    static $n = null;
    if ($n !== null) return $n;
    $cpu = @file_get_contents('/proc/cpuinfo');
    $found = $cpu !== false ? (int) preg_match_all('/^processor\s*:/m', $cpu) : 0;
    return $n = max(1, $found);
}

/** @param float|null $load the one-minute load average (tests); @param int|null $cores CPU cores (tests); @param float|null $limit absolute limit (tests) */
function soServerBusy(?float $load = null, ?int $cores = null, ?float $limit = null): bool {
    if ($limit === null) { $env = getenv('SO_BG_MAX_LOAD'); $limit = $env !== false && is_numeric($env) ? (float) $env : 0.0; }
    if ($limit < 0) return true;   // SO_BG_MAX_LOAD=-1 pauses the jobs that ask (and makes the tests independent of the real load)
    if ($load === null) {
        $l = function_exists('sys_getloadavg') ? @sys_getloadavg() : false;
        if (!is_array($l) || !isset($l[0])) return false;
        $load = (float) $l[0];
    }
    $max = $limit > 0 ? $limit : (float) ($cores ?? soCpuCores());
    return $load > $max;
}

/** The cron scripts: stop quietly (exit 0, so no failure mail) when the server is busy. */
function soCronYieldIfBusy(string $job): void {
    if (soServerBusy()) {
        $l = function_exists('sys_getloadavg') ? @sys_getloadavg() : [0];
        echo $job . ': the server is busy (load ' . round((float) ($l[0] ?? 0), 2) . '), skipping this run; the next run will do the work.' . "\n";
        exit(0);
    }
}

/** A cron that keeps a web-triggered worker's duplicate away: it records that it ran; the worker stands down while the record is fresh. */
function soCronHeartbeat(string $name): void {
    $dir = dirname(__DIR__) . '/cache';
    if (is_dir($dir) && is_writable($dir)) @touch($dir . '/' . $name . '.cron');
}
function soCronRecentlyRan(string $name, int $seconds): bool {
    $f = dirname(__DIR__) . '/cache/' . $name . '.cron';
    clearstatcache(true, $f);   // a worker that lives for a while must not read a remembered time
    return is_file($f) && time() - (int) @filemtime($f) < $seconds;
}

/**
 * One log line for a slow request, or null. $elapsedMs is the PHP run so far, $thresholdMs the limit (0 or less turns the log off), $timing the
 * Server-Timing text (soServerTimingHeader()), $load the one-minute load average or null. Only the path is kept: no query string, no address,
 * no cookie, no user agent. Control characters are removed so a crafted address cannot add lines to the log.
 */
function soSlowRequestLine(float $elapsedMs, string $uri, int $status, string $timing, ?float $load, int $thresholdMs): ?string {
    if ($thresholdMs <= 0 || $elapsedMs < $thresholdMs) return null;
    $path = (string) parse_url($uri, PHP_URL_PATH);
    $path = substr((string) preg_replace('/[^\x20-\x7e]/', '?', $path), 0, 120);
    return 'slow request: ' . ($path !== '' ? $path : '/') . ' status ' . $status . ' took ' . (int) round($elapsedMs) . ' ms'
        . ($load !== null ? ' load ' . round($load, 2) : '') . ' [' . substr((string) preg_replace('/[^\x20-\x7e]/', '?', $timing), 0, 400) . ']';
}

/**
 * Shutdown hook (registered first, so it runs before the background jobs): a page that took SO_SLOW_LOG_MS or more (default 5000, 0 turns it off)
 * leaves one line in the PHP error log with the same breakdown as the Server-Timing header, so a slow period can be explained afterwards from
 * the log alone (docs/resource-use.md). Pages only: command line runs and the background jobs are not logged.
 */
function soSlowRequestLog(): void {
    if (PHP_SAPI === 'cli') return;
    $env = getenv('SO_SLOW_LOG_MS');
    $limit = $env !== false && is_numeric($env) ? (int) $env : 5000;
    $start = (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? 0);
    if ($limit <= 0 || $start <= 0) return;
    $elapsed = (microtime(true) - $start) * 1000;
    if ($elapsed < $limit) return;   // the common case: nothing else is computed
    try {
        $l = function_exists('sys_getloadavg') ? @sys_getloadavg() : false;
        $line = soSlowRequestLine($elapsed, (string) ($_SERVER['REQUEST_URI'] ?? '/'), (int) (http_response_code() ?: 200),
            function_exists('soServerTimingHeader') ? soServerTimingHeader() : '', is_array($l) && isset($l[0]) ? (float) $l[0] : null, $limit);
        if ($line !== null) error_log($line);
    } catch (\Throwable $e) { /* diagnostics only: never turn a slow page into a failed one */ }
}
