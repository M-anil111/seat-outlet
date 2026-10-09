<?php
/**
 * Checks that the hosted checkout (TN_CHECKOUT_URL) is reachable: DNS lookup + an HTTPS HEAD request.
 * Every Buy click on an event page hands the buyer to this host, so if it does not resolve, no sale can complete.
 * For tech support to run after any DNS or TicketNetwork change, and from a daily monitor if you have one.
 *
 *   php tools/check-checkout-host.php            exit 0 = ok, 1 = problem
 *
 * Uses the TN_CHECKOUT_URL environment variable (default https://checkout.seatoutlet.com), the same value the site uses.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$url = rtrim(getenv('TN_CHECKOUT_URL') ?: 'https://checkout.seatoutlet.com', '/');
$host = parse_url($url, PHP_URL_HOST);
$scheme = parse_url($url, PHP_URL_SCHEME);
echo "Checkout URL: $url\n";
if (!$host || $scheme !== 'https') { echo "FAIL: TN_CHECKOUT_URL must be a full https:// address.\n"; exit(1); }

$ips = array_merge((array) @gethostbynamel($host) ?: [], array_column((array) @dns_get_record($host, DNS_AAAA) ?: [], 'ipv6'));
if (!$ips) { echo "FAIL: DNS: $host does not resolve. Buyers clicking Buy will see a browser error.\n"; exit(1); }
echo "DNS ok: " . implode(', ', $ips) . "\n";

$ch = curl_init($url . '/');
curl_setopt_array($ch, [CURLOPT_NOBODY => true, CURLOPT_HEADER => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 15, CURLOPT_USERAGENT => 'SeatOutletCheckoutCheck/1.0']);
$out = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$err = curl_error($ch);
if ($out === false) { echo "FAIL: HTTPS HEAD: $err\n"; exit(1); }
echo "HTTPS HEAD: status $code\n";
if ($code >= 500 || $code === 0) { echo "FAIL: the checkout host answered with an error.\n"; exit(1); }
if ($code === 404 || $code === 410) { echo "FAIL: the checkout host answers $code: nothing is served here. Check TN_CHECKOUT_URL and the host's routing; a 404 is not a working checkout.\n"; exit(1); }
echo "OK: the checkout host resolves and answers. (This does not prove a ticket group opens correctly: place a test order after any change.)\n";
exit(0);
