<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * The Content-Security-Policy, kept in one place so it can be checked by tools/check-csp.php (CI) and summarised from the report log.
 *
 * The header is sent by soSendSecurityHeaders() (functions.php): report-only by default, enforced when CSP_ENFORCE=1 is set in
 * inc/env.local.php. Violations are posted to /ajax/csp-report.php and written to the PHP error log as "csp-report-only: ..." lines;
 * tools/csp-report-summary.php turns that log into counts per directive and host.
 *
 * Nothing risky is allowed silently: every wildcard, scheme source and 'unsafe-*' keyword must appear in SO_CSP_EXCEPTIONS with the reason,
 * and tools/check-csp.php fails on one that is in the policy but not documented (or documented but no longer in the policy).
 * 'unsafe-eval' is not in the policy. The Seatics seat map evaluates strings; enforcing this policy on event pages needs that decision
 * first (docs/csp.md).
 */

/** Every directive's sources as they are sent. Keep each list short and name hosts, not wildcards, unless SO_CSP_EXCEPTIONS says why. */
const SO_CSP_POLICY = [
    'default-src' => ["'self'"],
    'script-src'  => ["'self'", "'unsafe-inline'", 'https://tickettransaction.com', 'https://*.seatics.com', 'https://d1s8091zjpj5vh.cloudfront.net', 'https://www.googletagmanager.com', 'https://www.google-analytics.com', 'https://pagead2.googlesyndication.com', 'https://*.googlesyndication.com', 'https://*.doubleclick.net', 'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com', 'https://static.cloudflareinsights.com', 'https://www.google.com/recaptcha/', 'https://www.gstatic.com/recaptcha/', 'https://fundingchoicesmessages.google.com', 'https://*.adtrafficquality.google'],
    'style-src'   => ["'self'", "'unsafe-inline'", 'https://tickettransaction.com', 'https://*.seatics.com', 'https://d1s8091zjpj5vh.cloudfront.net', 'https://fonts.googleapis.com', 'https://cdnjs.cloudflare.com', 'https://cdn.jsdelivr.net'],
    'img-src'     => ["'self'", 'data:', 'blob:', 'https:'],
    'font-src'    => ["'self'", 'data:', 'https://fonts.gstatic.com', 'https://cdnjs.cloudflare.com', 'https://d1s8091zjpj5vh.cloudfront.net'],
    'connect-src' => ["'self'", 'https://tickettransaction.com', 'https://*.tickettransaction.com', 'https://*.seatics.com', 'https://d1s8091zjpj5vh.cloudfront.net', 'https://fundingchoicesmessages.google.com', 'https://*.adtrafficquality.google', 'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://*.googlesyndication.com', 'https://*.doubleclick.net', 'https://cloudflareinsights.com', 'https://www.google.com/recaptcha/'],
    'frame-src'   => ["'self'", 'https://tickettransaction.com', 'https://fundingchoicesmessages.google.com', 'https://*.adtrafficquality.google', 'https://*.googlesyndication.com', 'https://*.doubleclick.net', 'https://www.googletagmanager.com', 'https://www.google.com/recaptcha/', 'https://recaptcha.google.com/recaptcha/'],
    'object-src'  => ["'none'"],
    'base-uri'    => ["'self'"],
];

/**
 * Every wildcard, scheme source and 'unsafe-*' keyword in SO_CSP_POLICY, with why it is there. "directive source" => reason.
 * The check fails on one missing from this list and on one listed here that the policy no longer contains.
 */
const SO_CSP_EXCEPTIONS = [
    "script-src 'unsafe-inline'" => 'Pages carry inline scripts (structured data is not one, but the page frame, the Google tag bootstrap, per-page widgets and the consent banner are). Replacing them with nonces is a separate change; until then this stays.',
    "style-src 'unsafe-inline'" => 'Inline style attributes and per-page style blocks are used throughout the templates.',
    'script-src https://*.seatics.com' => 'The seat map widget loads from several Seatics hosts (mapwidget3, and others it picks at run time).',
    'script-src https://*.googlesyndication.com' => 'AdSense serves its scripts from changing subdomains.',
    'script-src https://*.doubleclick.net' => 'Google ad delivery uses changing subdomains.',
    'script-src https://*.adtrafficquality.google' => 'Google ad traffic quality uses changing subdomains.',
    'style-src https://*.seatics.com' => 'The seat map loads its own style sheets from its hosts.',
    'img-src https:' => 'Photos, team logos, venue pictures and ad creatives come from many hosts (Wikimedia, Pixabay, Unsplash, the ticket API images, AdSense). Images cannot run code; this is the one scheme-wide source and it is limited to https.',
    'connect-src https://*.tickettransaction.com' => 'The hosted checkout and its API use several subdomains of the checkout host.',
    'connect-src https://*.seatics.com' => 'The seat map talks to several Seatics hosts.',
    'connect-src https://*.adtrafficquality.google' => 'Google ad traffic quality uses changing subdomains.',
    'connect-src https://*.google-analytics.com' => 'GA4 sends to regional collection hosts.',
    'connect-src https://*.analytics.google.com' => 'GA4 sends to regional collection hosts.',
    'connect-src https://*.googlesyndication.com' => 'AdSense reporting uses changing subdomains.',
    'connect-src https://*.doubleclick.net' => 'Google ad delivery uses changing subdomains.',
    'frame-src https://*.adtrafficquality.google' => 'Google ad traffic quality frames, served from changing subdomains.',
    'frame-src https://*.googlesyndication.com' => 'AdSense ad frames, served from changing subdomains.',
    'frame-src https://*.doubleclick.net' => 'Google ad frames, served from changing subdomains.',
];

/** The policy header value. $enforce adds frame-ancestors (it is ignored in a report-only policy). */
function soCspPolicy(bool $enforce = false): string {
    $parts = [];
    foreach (SO_CSP_POLICY as $dir => $sources) $parts[] = $dir . ' ' . implode(' ', $sources);
    if ($enforce) $parts[] = "frame-ancestors 'self'";
    $parts[] = 'report-uri /ajax/csp-report.php';
    return implode('; ', $parts);
}

/** A header value (or any policy string) as [directive => [sources]]. */
function soCspParse(string $policy): array {
    $out = [];
    foreach (explode(';', $policy) as $d) {
        $t = preg_split('/\s+/', trim($d), -1, PREG_SPLIT_NO_EMPTY);
        if (!$t) continue;
        $name = strtolower(array_shift($t));
        if (!isset($out[$name])) $out[$name] = $t;
    }
    return $out;
}

/** Sources in a parsed policy that are risky: a wildcard host, a bare scheme, a bare "*", or an 'unsafe-*' keyword. Returns ["directive source", ...]. */
function soCspRiskySources(array $parsed): array {
    $risky = [];
    foreach ($parsed as $dir => $sources) {
        foreach ($sources as $s) {
            $low = strtolower($s);
            $isScheme = (bool) preg_match('/^[a-z][a-z0-9+.-]*:$/', $low) && $low !== 'data:' && $low !== 'blob:';
            // data: and blob: are scheme sources too, but only img-src and font-src may carry them without a note (they cannot run script there)
            $isRiskyData = in_array($low, ['data:', 'blob:'], true) && !in_array($dir, ['img-src', 'font-src', 'media-src'], true);
            if ($low === '*' || strpos($low, '*') !== false || $isScheme || $isRiskyData || strpos($low, "'unsafe-") === 0) $risky[] = $dir . ' ' . $s;
        }
    }
    return $risky;
}

/** Does a URL's host fall under a source list ('self' is the site's own host, passed in)? Hosts match exactly or by a leading "*." wildcard. */
function soCspUrlAllowed(string $url, array $sources, string $selfHost = ''): bool {
    $p = parse_url($url);
    $host = strtolower((string) ($p['host'] ?? ''));
    $scheme = strtolower((string) ($p['scheme'] ?? ''));
    if ($host === '') return false;
    foreach ($sources as $s) {
        $low = strtolower($s);
        if ($low === "'self'") { if ($selfHost !== '' && $host === strtolower($selfHost)) return true; continue; }
        if (preg_match('/^[a-z][a-z0-9+.-]*:$/', $low)) { if ($low === $scheme . ':') return true; continue; }
        if (!preg_match('#^(?:([a-z]+)://)?([^/:]+)#', $low, $m)) continue;
        if ($m[1] !== '' && $m[1] !== $scheme) continue;
        $h = $m[2];
        if ($h === $host) return true;
        if (strpos($h, '*.') === 0 && substr($host, -strlen($h) + 1) === substr($h, 1) && $host !== substr($h, 2)) return true;
    }
    return false;
}

/**
 * Summarises "csp-report-only: <directive> blocked=<uri> page=<url>" log lines: one row per directive and blocked host with its count and
 * the pages it was seen on (at most 3 shown). Returns ['rows' => [[count, directive, host, pages[]]], 'total' => n], most frequent first.
 */
function soCspSummarize(iterable $lines): array {
    $n = []; $pages = []; $total = 0;
    foreach ($lines as $line) {
        if (!preg_match('/csp-report-only: (\S+) blocked=(\S*) page=(\S*)/', (string) $line, $m)) continue;
        $uri = $m[2];
        $host = $uri === '' ? '(inline or eval)' : (preg_match('#^[a-z][a-z0-9+.-]*://([^/]+)#i', $uri, $h) ? strtolower($h[1]) : $uri);
        $k = $m[1] . "\t" . $host;
        $n[$k] = ($n[$k] ?? 0) + 1;
        $path = (string) (parse_url($m[3], PHP_URL_PATH) ?? '');
        if ($path !== '' && count($pages[$k] ?? []) < 3 && !in_array($path, $pages[$k] ?? [], true)) $pages[$k][] = $path;
        $total++;
    }
    arsort($n);
    $rows = [];
    foreach ($n as $k => $c) { [$dir, $host] = explode("\t", $k, 2); $rows[] = [$c, $dir, $host, $pages[$k] ?? []]; }
    return ['rows' => $rows, 'total' => $total];
}
