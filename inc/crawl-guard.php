<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Crawl guard: one visitor cannot use up the ticket API's request limit.
 *
 * Every page that is not cached asks the ticket API for data, and the API answers 429 when it gets too many requests. A site audit or a
 * scraper that loads tens of thousands of pages in a day (the 4 to 5 Oct 2026 audit loaded 58,000) makes every uncached page for every
 * visitor answer 503. So each address may load SO_RL_PER_MIN public pages a minute; past that it gets "429 Too Many Requests" with
 * Retry-After, nothing is built and the API is not called. Search engine crawlers named in the user agent get three times the allowance
 * (a fake name only buys a fake bot three times the limit, still far below an audit crawl).
 *
 * Counts live in small files under cache/ratelimit (one per address, per minute), so it needs nothing but PHP. Tune with
 * putenv('SO_RL_PER_MIN=120') in inc/env.local.php; 0 turns it off.
 */

/** Allowed public page requests per minute for one address (0 = no limit). */
function soRlLimit(string $userAgent = ''): int {
    $env = getenv('SO_RL_PER_MIN');
    $base = ($env === false || $env === '') ? 120 : max(0, (int) $env);
    if ($base === 0) return 0;
    return preg_match('/googlebot|bingbot|duckduckbot|applebot|yandexbot|baiduspider|slurp/i', $userAgent) ? $base * 3 : $base;
}

/** The visitor's address as the Cloudflare Worker or Cloudflare passes it on, '' when it cannot be told. */
function soRlClientIp(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $k) {
        $v = trim((string) ($_SERVER[$k] ?? ''));
        if ($v !== '' && filter_var($v, FILTER_VALIDATE_IP)) return $v;
    }
    return '';
}

/**
 * Count this request. Returns the number of requests this address has made in the current minute (including this one), or 0 when the
 * address is exempt or the counter cannot be written (never block a visitor because of a disk problem). $dir and $now are for tests.
 */
function soRlCount(string $ip, ?string $dir = null, ?int $now = null): int {
    if ($ip === '' || in_array($ip, ['127.0.0.1', '::1'], true)) return 0;
    $now = $now ?? time();
    $dir = $dir ?? ((defined('HOME_PATH') ? HOME_PATH : dirname(__DIR__)) . '/cache/ratelimit');
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) return 0;
    $minute = intdiv($now, 60);
    $file = $dir . '/' . md5($ip) . '.rl';
    $fh = @fopen($file, 'c+');
    if (!$fh) return 0;
    $n = 0;
    if (flock($fh, LOCK_EX)) {
        $raw = trim((string) stream_get_contents($fh));
        [$m, $c] = array_pad(explode(' ', $raw), 2, '0');
        $n = ((int) $m === $minute ? (int) $c : 0) + 1;
        ftruncate($fh, 0); rewind($fh); fwrite($fh, $minute . ' ' . $n);
        flock($fh, LOCK_UN);
    }
    fclose($fh);
    // Housekeeping, about once in 200 requests: remove counters older than ten minutes.
    if (mt_rand(1, 200) === 1) {
        foreach (@glob($dir . '/*.rl') ?: [] as $f) { if ($now - (int) @filemtime($f) > 600) @unlink($f); }
    }
    return $n;
}

/** Call at the start of a public page. Sends 429 and stops when the address is over its allowance. */
function soCrawlGuard(): void {
    if (PHP_SAPI === 'cli' || headers_sent()) return;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    $limit = soRlLimit((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($limit === 0) return;
    $n = soRlCount(soRlClientIp());
    if ($n > $limit) {
        http_response_code(429);
        header('Retry-After: ' . (60 - (time() % 60)));
        header('Cache-Control: no-store');
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Too many requests. Please slow down and try again in a minute.\n";
        exit;
    }
}

/** True for a public page path that should be counted (not data endpoints, checkout, admin, cron, sitemaps or robots). */
function soCrawlGuardApplies(string $path): bool {
    return !preg_match('#^/(ajax|admin|cron|tools|checkout|newsletter|unsubscribe|order-confirmation|thank-you|cdn-cgi)(/|\.php|$)#', $path)
        && !preg_match('#^/(robots\.txt|sitemap[^/]*\.xml|favicon\.ico)$#', $path);
}

/** The bootstrap call: guards public page requests only. */
function soCrawlGuardPublic(): void {
    if (PHP_SAPI === 'cli') return;
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if (soCrawlGuardApplies($path)) soCrawlGuard();
}
