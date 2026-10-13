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
 *
 * On a host that is not indexable (SITE_INDEXABLE false: beta, staging) crawlers are refused outright with 403 before any page is built
 * (soBotBlocked), except the user agents named in SO_BOT_ALLOW.
 */

/** Allowed public page requests per minute for one address (0 = no limit). */
function soRlLimit(string $userAgent = ''): int {
    $env = getenv('SO_RL_PER_MIN');
    $base = ($env === false || $env === '') ? 120 : max(0, (int) $env);
    if ($base === 0) return 0;
    return preg_match('/googlebot|bingbot|duckduckbot|applebot|yandexbot|baiduspider|slurp/i', $userAgent) ? $base * 3 : $base;
}

/**
 * True when the host is not meant to be indexed (beta, staging) and the request comes from a crawler that is not on the allow list.
 * Such a host says "Disallow: /" in robots.txt, but SEO crawlers (SemrushBot, ShapBot on 12 and 13 Oct 2026) load its pages anyway, and
 * every uncached page costs a call against the one production TicketNetwork subscription that live visitors need. $allow is
 * SO_BOT_ALLOW: comma-separated user agent fragments (case-insensitive) for the audit tools that may crawl the host.
 */
function soBotBlocked(string $userAgent, bool $indexable, string $allow = ''): bool {
    if ($indexable || $userAgent === '') return false;
    // Named crawlers and the generic words crawlers use, never a bare "bot": a Cubot phone, UptimeRobot or NewRelicPingerBot is not a crawler.
    if (!preg_match('/googlebot|bingbot|yandex|baiduspider|duckduckbot|applebot|slurp|semrush|ahrefs|mj12bot|dotbot|petalbot|bytespider|facebookexternalhit|shapbot|screaming frog|seranking|dataforseo|serpstat|gptbot|ccbot|claudebot|amazonbot|crawler|crawl|spider/i', $userAgent)) return false;
    foreach (array_filter(array_map('trim', explode(',', strtolower($allow)))) as $fragment) {
        if (strpos(strtolower($userAgent), $fragment) !== false) return false;
    }
    return true;
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
    // HEAD counts like GET: a crawler's HEAD builds the whole page and makes the same ticket API calls (ShapBot, Sentry SEAT-OUTLET-Q).
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) return;
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

/**
 * The crawler decision for one request, with no side effects (the tests run it on a made-up $_SERVER): ['status' => 403, 'body' => ...]
 * when this host refuses this crawler, null otherwise. It covers every path except robots.txt, the sitemaps and the favicon (a crawler
 * may read what it may not crawl), so the data endpoints that call the ticket API (/ajax/get-home-feed.php, /ajax/load-events.php)
 * are refused too, not only pages. GET and HEAD only: a crawler's HEAD builds the whole page.
 */
function soCrawlGuardDecision(array $server, bool $indexable, string $allow): ?array {
    if (!in_array($server['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) return null;
    $path = (string) parse_url((string) ($server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if (preg_match('#^/(robots\.txt|sitemap[^/]*\.xml|favicon\.ico)$#', $path)) return null;
    if (!soBotBlocked((string) ($server['HTTP_USER_AGENT'] ?? ''), $indexable, $allow)) return null;
    return ['status' => 403, 'body' => "This host is not open to crawlers (see robots.txt).\n"];
}

/**
 * The bootstrap call. Must run before any register_shutdown_function() in the bootstrap: the refusal ends the request with exit, and a
 * shutdown hook registered before it (the top-performers refresh, the sitemap crawl) would still run and call the ticket API.
 */
function soCrawlGuardPublic(): void {
    if (PHP_SAPI === 'cli') return;
    $refuse = soCrawlGuardDecision($_SERVER, defined('SITE_INDEXABLE') ? (bool) SITE_INDEXABLE : true, (string) getenv('SO_BOT_ALLOW'));
    if ($refuse !== null && !headers_sent()) {
        http_response_code($refuse['status']);
        header('Cache-Control: no-store');
        header('Content-Type: text/plain; charset=UTF-8');
        echo $refuse['body'];
        exit;
    }
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if (soCrawlGuardApplies($path)) soCrawlGuard();
}
