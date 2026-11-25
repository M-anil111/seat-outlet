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

// Rate limits use the trusted visitor address: the contact form's per-visitor key is built from soContactClientIp(), run through the real functions.
$contactHash = function (array $server) use ($root): string {
    $code = '$_SERVER = ' . var_export($server, true) . '; require ' . var_export($root . '/inc/request-guard.php', true) . '; require ' . var_export($root . '/inc/contact.php', true) . '; echo soContactIpHash();';
    return trim((string) shell_exec('php -d display_errors=0 -r ' . escapeshellarg($code) . ' 2>/dev/null'));
};
$hDirect   = $contactHash(['REMOTE_ADDR' => '203.0.113.9']);
$hViaCf    = $contactHash(['REMOTE_ADDR' => '172.70.1.2', 'HTTP_CF_CONNECTING_IP' => '203.0.113.9']);
$hOtherCf  = $contactHash(['REMOTE_ADDR' => '172.70.1.2', 'HTTP_CF_CONNECTING_IP' => '198.51.100.20']);
$hForged   = $contactHash(['REMOTE_ADDR' => '198.51.100.7', 'HTTP_CF_CONNECTING_IP' => '203.0.113.9']);
cfpCheck('contact key is a hash', strlen($hDirect), 64);
cfpCheck('contact key: a visitor behind Cloudflare gets the same key as the same address direct', $hViaCf, $hDirect);
cfpCheck('contact key: two visitors behind the same Cloudflare edge get different keys', $hViaCf !== $hOtherCf, true);
cfpCheck('contact key: a forged CF-Connecting-IP from a non-Cloudflare peer does not change the key', $hForged !== $hDirect, true);
$contact = file_get_contents($root . '/inc/contact.php');
cfpCheck('soContactHandle builds its rate-limit key with soContactIpHash()', strpos($contact, '$ipHash = soContactIpHash();') !== false, true);
$img = file_get_contents($root . '/ajax/resolve-images.php');
cfpCheck('image lookup limit uses soClientIp', strpos($img, "\$ip   = function_exists('soClientIp') ? (string) soClientIp()") !== false, true);

// The Vhost lists equal Cloudflare's built-in ranges in PHP (the optional SO_TRUSTED_PROXIES extension is left out of this comparison), in both server blocks.
putenv('SO_TRUSTED_PROXIES');
$vh = (string) file_get_contents($root . '/deploy/nginx/seatoutlet.com.vhost');
$phpRanges = soTrustedProxyRanges(); sort($phpRanges);
$blocks = array_values(array_filter(preg_split('/^server \{/m', $vh, -1, PREG_SPLIT_NO_EMPTY), fn($b) => strpos($b, 'listen ') !== false));   // the file's opening comment is not a server block
cfpCheck('Vhost has the port 80 and port 443 server blocks', count($blocks), 2);
foreach ($blocks as $i => $blk) {
    preg_match_all('/set_real_ip_from ([0-9a-f:.\/]+);/', $blk, $m);
    $r = $m[1]; sort($r);
    cfpCheck('Vhost server block ' . ($i + 1) . ' set_real_ip_from list equals the built-in ranges', $r, $phpRanges);
    cfpCheck('Vhost server block ' . ($i + 1) . ' reads CF-Connecting-IP', strpos($blk, 'real_ip_header CF-Connecting-IP;') !== false, true);
}
cfpCheck('Vhost does not trust X-Forwarded-For', strpos($vh, 'real_ip_header X-Forwarded-For') === false && strpos($vh, 'real_ip_recursive on') === false, true);

// The runbook.
$doc = (string) @file_get_contents($root . '/docs/cloudflare-proxy.md');
foreach (['Full (strict)', 'Eligible for cache', 'Use cache-control header if present', '/admin', '/checkout', '/ajax/tn-order-webhook.php', 'Rollback', 'DNS only'] as $needle) {
    cfpCheck("runbook mentions: $needle", strpos($doc, $needle) !== false, true);
}

echo $fail ? "$fail failure(s)\n" : "cloudflare proxy: all passed\n"; exit($fail ? 1 : 0);
