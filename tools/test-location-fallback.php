<?php
// Offline source checks: a location lookup that fails must not invent a city, and "no location" must show nationwide events, never an
// empty or hidden list. Run: php tools/test-location-fallback.php
$root = dirname(__DIR__);
$fails = 0;
function locCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }
$read = function (string $f) use ($root) { return (string) file_get_contents($root . '/' . $f); };

$ip = $read('ajax/get_ip_details.php');
locCheck('PHP lookup has no Austin fallback', stripos($ip, 'austin') === false);
locCheck('PHP lookup says "unknown" and is not cached when it fails', strpos($ip, "'source' => 'unknown'") !== false && strpos($ip, 'no-store') !== false);
locCheck('PHP lookup labels a real answer without naming a provider it may not have used', strpos($ip, "'source' => 'ip'") !== false && strpos($ip, 'maxmind') === false);

$w = $read('deploy/cloudflare-worker/seatoutlet-blog-proxy.js');
locCheck('Worker has no Austin location fallback', strpos($w, 'DEFAULT_LOCATION') === false && strpos($w, 'city: "Austin"') === false);
preg_match('/async function locationResponse\(.*?\n}\n/s', $w, $lr);
locCheck('Worker answers "unknown" and does not cache it (checked inside locationResponse)', !empty($lr[0]) && strpos($lr[0], 'UNKNOWN_LOCATION') !== false && preg_match('/known\s*\?\s*"private, max-age=300"\s*:\s*"no-store"/', $lr[0]) === 1);
preg_match('/function setCookie\(name, value\)\{.*?\n  \}/s', $w, $sc);
locCheck('Worker location cookies last a day', !empty($sc[0]) && strpos($sc[0], '86400') !== false);

$feed = $read('ajax/get-home-feed.php');
locCheck('home feed without a location goes nationwide, not scope none', strpos($feed, 'if (!$hasGeo) { $nationwide = true; }') !== false);

$home = $read('js/home.js');
locCheck('"Clear location" does not look the address up again', preg_match('/locationClearBtn.*?loadNationalEvents\(\);/s', $home) === 1 && strpos(substr($home, strpos($home, 'locationClearBtn.addEventListener')), 'get_ip_details') === false);

$main = $read('js/main.js');
locCheck('location cookies last a day, not 30', strpos($main, "so_(lat|lng|label)") !== false && strpos($main, '86400') !== false);

$near = $read('js/near-you.js');
locCheck('near-you section loads nationwide when no location is known', strpos($near, 'startNationwide') !== false);

echo $fails ? "$fails failed\n" : "location fallback: all passed\n";
exit($fails ? 1 : 0);
