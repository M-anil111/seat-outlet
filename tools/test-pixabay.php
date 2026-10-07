<?php
// Offline test of the Pixabay city source (inc/images.php): no network, no database. Run: php tools/test-pixabay.php
define('HOME_URL', 'https://seatoutlet.com');
putenv('PIXABAY_API_KEY=test-key');
$src = file_get_contents(__DIR__ . '/../inc/images.php');
preg_match("/const IMAGE_STATE_NAMES = \[.*?\n\];/s", $src, $m1);
preg_match("/function imageHttpJson\(.*?\n}\n/s", $src, $m2);
preg_match("/function imageSourcePixabay\(.*?\n}\n/s", $src, $m3);
preg_match("/function imageLicenseUrl\(.*?\n}\n/s", $src, $m4);
eval($m1[0] . $m2[0] . $m3[0] . $m4[0]);
function imageHttpGet($url, $h = []) { return [0, '']; }

$fails = 0;
function pxCheck($ok, $msg) { global $fails; if (!$ok) { $fails++; fwrite(STDERR, "FAIL: $msg\n"); } }
function hit($o = []) {
    return array_replace(['id' => 1, 'pageURL' => 'https://pixabay.com/photos/phoenix-arizona-city-1/', 'tags' => 'phoenix, arizona, city, skyline', 'user' => 'Jane Doe',
        'largeImageURL' => 'https://pixabay.com/get/abc_1280.jpg', 'imageWidth' => 2816, 'imageHeight' => 2112], $o);
}
$seen = null;
$GLOBALS['so_image_http_stub'] = function ($url) use (&$seen) { $seen = $url; return ['hits' => [hit()]]; };
$r = imageSourcePixabay('Phoenix, AZ', 'city');
pxCheck(is_array($r) && strpos($r['image_url'], 'https://pixabay.com/get/') === 0, 'a matching photo is returned');
pxCheck(($r['license'] ?? '') === 'Pixabay License' && ($r['attribution'] ?? '') === 'Photo by Jane Doe on Pixabay', 'licence and credit text');
pxCheck(strpos($r['source_url'] ?? '', 'https://pixabay.com/') === 0, 'source link is the Pixabay page');
pxCheck(strpos((string) $seen, 'min_width=1600') !== false && strpos((string) $seen, 'safesearch=true') !== false && strpos((string) $seen, 'orientation=horizontal') !== false, 'asks for large, horizontal, safe photos');
pxCheck(imageLicenseUrl('Pixabay License') === 'https://pixabay.com/service/license-summary/', 'licence link');

foreach ([
    'phoenix island (other place)' => hit(['tags' => 'ship, city, harbor, phoenix island, skyline']),
    'city without state tag'       => hit(['tags' => 'phoenix, city, skyline']),
    'state without city tag'       => hit(['tags' => 'arizona, desert, city']),
    'Paris France for Paris, TX'   => hit(['tags' => 'paris, france, eiffel tower']),
    'portrait'                     => hit(['imageWidth' => 2000, 'imageHeight' => 3000]),
    'small'                        => hit(['imageWidth' => 1200, 'imageHeight' => 800]),
    'no contributor name'          => hit(['user' => '']),
    'image not on pixabay'         => hit(['largeImageURL' => 'https://evil.example/x.jpg']),
    'page not on pixabay'          => hit(['pageURL' => 'https://evil.example/p']),
] as $why => $p) {
    $name = $why === 'Paris France for Paris, TX' ? 'Paris, TX' : 'Phoenix, AZ';
    $GLOBALS['so_image_http_stub'] = fn($url) => ['hits' => [$p]];
    pxCheck(imageSourcePixabay($name, 'city') === null, "rejected: $why");
}
// Paris, TX accepts a Paris, Texas photo.
$GLOBALS['so_image_http_stub'] = fn($url) => ['hits' => [hit(['tags' => 'paris, texas, downtown'])]];
pxCheck(is_array(imageSourcePixabay('Paris, TX', 'city')), 'Paris, TX accepts a photo tagged paris, texas');
// The first usable photo wins even when an unusable one comes first.
$GLOBALS['so_image_http_stub'] = fn($url) => ['hits' => [hit(['tags' => 'phoenix island']), hit(['user' => 'Second'])]];
pxCheck((imageSourcePixabay('Phoenix, AZ', 'city')['attribution'] ?? '') === 'Photo by Second on Pixabay', 'skips a bad hit and takes the next');
// Rate limit and failures.
$GLOBALS['so_image_http_stub'] = fn($url) => 'RATE_LIMITED';
pxCheck(imageSourcePixabay('Phoenix, AZ', 'city') === 'RATE_LIMITED', '429 is passed on so the queue backs off');
$GLOBALS['so_image_http_stub'] = fn($url) => null;
pxCheck(imageSourcePixabay('Phoenix, AZ', 'city') === null, 'an API failure gives no photo');
// Only cities, only with a key and a "City, ST" name.
pxCheck(imageSourcePixabay('Phoenix, AZ', 'venue') === null, 'venues are ignored');
pxCheck(imageSourcePixabay('Phoenix', 'city') === null, 'a name without a state is ignored');
putenv('PIXABAY_API_KEY=');
pxCheck(imageSourcePixabay('Phoenix, AZ', 'city') === null, 'no key, no call');

echo $fails ? "pixabay source: $fails failed\n" : "pixabay source: all passed\n";
exit($fails ? 1 : 0);
