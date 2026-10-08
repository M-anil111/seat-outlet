<?php
// Offline test (no network, no database) of the contact form's same-origin check: the live site rejected every submission because the
// host PHP sees differs from the public host. Run: php tools/test-contact-origin.php
$src = file_get_contents(__DIR__ . '/../inc/contact.php');
preg_match("/function soContactSameOrigin\(\).*?\n}\n/s", $src, $m);
if (!$m) { fwrite(STDERR, "FAIL: soContactSameOrigin not found\n"); exit(1); }
define('SO_PUBLIC_ORIGIN', 'https://seatoutlet.com');
define('HOME_URL', 'https://beta.seatoutlet.com');
eval($m[0]);

$fails = 0;
function originCheck(string $name, array $server, bool $want): void {
    global $fails;
    $_SERVER = $server;
    $got = soContactSameOrigin();
    echo ($got === $want ? 'ok   ' : 'FAIL ') . $name . "\n";
    if ($got !== $want) $fails++;
}
$proxied = ['HTTP_HOST' => 'origin-server.internal'];   // what PHP sees behind the proxy
originCheck('live Origin, proxied Host', $proxied + ['HTTP_ORIGIN' => 'https://seatoutlet.com'], true);
originCheck('live Referer, proxied Host', $proxied + ['HTTP_REFERER' => 'https://seatoutlet.com/ticket-customer-service'], true);
originCheck('www Origin', $proxied + ['HTTP_ORIGIN' => 'https://www.seatoutlet.com'], true);
originCheck('beta Origin', $proxied + ['HTTP_ORIGIN' => 'https://beta.seatoutlet.com'], true);
originCheck('forwarded host', ['HTTP_HOST' => 'x.internal', 'HTTP_X_FORWARDED_HOST' => 'shop.example.org, other', 'HTTP_ORIGIN' => 'https://shop.example.org'], true);
originCheck('another site', $proxied + ['HTTP_ORIGIN' => 'https://evil.example'], false);
originCheck('look-alike host', $proxied + ['HTTP_ORIGIN' => 'https://seatoutlet.com.evil.example'], false);
originCheck('look-alike prefix', $proxied + ['HTTP_ORIGIN' => 'https://evilseatoutlet.com'], false);
originCheck('foreign Referer, no Origin', $proxied + ['HTTP_REFERER' => 'https://evil.example/page'], false);
originCheck('Origin null, no Referer', $proxied + ['HTTP_ORIGIN' => 'null'], true);
originCheck('no headers at all', $proxied, true);
echo $fails ? "$fails failed\n" : "contact origin: all passed\n";
exit($fails ? 1 : 0);
