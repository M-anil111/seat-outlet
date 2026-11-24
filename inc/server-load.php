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
 * or, when that is not set, one per CPU core. With no load figure (a host that hides it) it is never busy, so nothing changes there.
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
    if ($load === null) {
        $l = function_exists('sys_getloadavg') ? @sys_getloadavg() : false;
        if (!is_array($l) || !isset($l[0])) return false;
        $load = (float) $l[0];
    }
    if ($limit === null) { $env = getenv('SO_BG_MAX_LOAD'); $limit = $env !== false && is_numeric($env) ? (float) $env : 0.0; }
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
