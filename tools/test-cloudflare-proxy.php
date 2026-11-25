<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline test: the site can sit behind the Cloudflare proxy (no Worker) without losing the visitor's address.
 *  - soClientIp() believes CF-Connecting-IP only from Cloudflare's ranges, so forged headers from anywhere else change nothing;
 *  - the contact form and the image lookup rate limit use that address, not the raw connection (a Cloudflare edge address shared by many visitors);
 *  - the Vhost's set_real_ip_from list is exactly the PHP list, and real_ip_header is CF-Connecting-IP;
 *  - the runbook exists and names the cache rule and the rollback.
 * Run: php tools/test-cloudflare-proxy.php
 */
$root = dirname(__DIR__);
require_once $root . '/inc/request-guard.php';
$fail = 0;
function cfpCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}

// soClientIp is memoised per request: run each case in its own process.
$probe = function (array $server) use ($root): string {
    $code = '$_SERVER = ' . var_export($server, true) . '; require ' . var_export($root . '/inc/request-guard.php', true) . '; echo soClientIp();';
    return trim((string) shell_exec('php -d display_errors=0 -r ' . escapeshellarg($code) . ' 2>/dev/null'));
};
cfpCheck('direct visitor: the connection address', $probe(['REMOTE_ADDR' => '198.51.100.7']), '198.51.100.7');
cfpCheck('direct visitor with a forged CF-Connecting-IP: the connection address', $probe(['REMOTE_ADDR' => '198.51.100.7', 'HTTP_CF_CONNECTING_IP' => '203.0.113.9']), '198.51.100.7');
cfpCheck('request from a Cloudflare address: the visitor in CF-Connecting-IP', $probe(['REMOTE_ADDR' => '172.70.1.2', 'HTTP_CF_CONNECTING_IP' => '203.0.113.9']), '203.0.113.9');
cfpCheck('Cloudflare IPv6 edge: the visitor in CF-Connecting-IP', $probe(['REMOTE_ADDR' => '2606:4700::1111', 'HTTP_CF_CONNECTING_IP' => '203.0.113.9']), '203.0.113.9');
cfpCheck('Cloudflare address with a junk header: the edge address', $probe(['REMOTE_ADDR' => '172.70.1.2', 'HTTP_CF_CONNECTING_IP' => 'not-an-ip']), '172.70.1.2');

// Rate limits use the trusted visitor address.
$contact = file_get_contents($root . '/inc/contact.php');
cfpCheck('contact form limit uses soClientIp', strpos($contact, "function soContactClientIp(): string { return function_exists('soClientIp') ? (string) soClientIp()") !== false, true);
$img = file_get_contents($root . '/ajax/resolve-images.php');
cfpCheck('image lookup limit uses soClientIp', strpos($img, "\$ip   = function_exists('soClientIp') ? (string) soClientIp()") !== false, true);

// The Vhost list equals the PHP list.
$vh = (string) file_get_contents($root . '/deploy/nginx/seatoutlet.com.vhost');
preg_match_all('/set_real_ip_from ([0-9a-f:.\/]+);/', $vh, $m);
$vhostRanges = $m[1]; $phpRanges = soTrustedProxyRanges();
sort($vhostRanges); sort($phpRanges);
cfpCheck('Vhost set_real_ip_from list equals soTrustedProxyRanges()', $vhostRanges, $phpRanges);
cfpCheck('Vhost reads CF-Connecting-IP', strpos($vh, 'real_ip_header CF-Connecting-IP;') !== false, true);
cfpCheck('Vhost does not trust X-Forwarded-For', strpos($vh, 'real_ip_header X-Forwarded-For') === false && strpos($vh, 'real_ip_recursive on') === false, true);

// The runbook.
$doc = (string) @file_get_contents($root . '/docs/cloudflare-proxy.md');
foreach (['Full (strict)', 'Eligible for cache', 'Use cache-control header if present', '/admin', '/checkout', '/ajax', 'Rollback', 'DNS only'] as $needle) {
    cfpCheck("runbook mentions: $needle", strpos($doc, $needle) !== false, true);
}

echo $fail ? "$fail failure(s)\n" : "cloudflare proxy: all passed\n"; exit($fail ? 1 : 0);
