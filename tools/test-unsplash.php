<?php
// Offline test of the Unsplash city source (inc/images.php): no network, no database. Run: php tools/test-unsplash.php
define('HOME_URL', 'https://seatoutlet.com'); define('IMAGE_TEST', true);
putenv('UNSPLASH_ACCESS_KEY=test-key');
$src = file_get_contents(__DIR__ . '/../inc/images.php');
// Load only the pieces under test (images.php needs the whole app for the rest).
foreach (['IMAGE_HTTP_TIMEOUT' => 6] as $k => $v) { if (!defined($k)) define($k, $v); }
preg_match("/const IMAGE_STATE_NAMES = \[.*?\n\];/s", $src, $m1);
preg_match("/const IMAGE_UNSPLASH_UTM = .*?;/", $src, $m2);
preg_match("/function imageUnsplashGet\(.*?\n}\n/s", $src, $m3);
preg_match("/function imageSourceUnsplash\(.*?\n}\n/s", $src, $m4);
preg_match("/function imageUnsplashTrackDownload\(.*?\n}\n/s", $src, $m5);
preg_match("/function imageUserAgent\(\) \{.*?\n}\n/s", $src, $m6);
preg_match("/function renderImageCredit\(.*?\n}\n/s", $src, $m7);
preg_match("/function imageLicenseUrl\(.*?\n}\n/s", $src, $m8);
eval($m1[0] . $m2[0] . $m3[0] . $m4[0] . $m5[0] . $m6[0] . $m7[0] . $m8[0]);

$fails = 0;
function check($ok, $msg) { global $fails; if (!$ok) { $fails++; fwrite(STDERR, "FAIL: $msg\n"); } }
function photo($o = []) {
    return array_replace_recursive(['width' => 4000, 'height' => 2500, 'description' => 'Phoenix skyline at dusk', 'alt_description' => 'city', 'location' => ['name' => 'Phoenix, Arizona, United States', 'city' => 'Phoenix', 'country' => 'United States'],
        'tags' => [['title' => 'skyline']], 'urls' => ['raw' => 'https://images.unsplash.com/photo-1?ixid=abc'], 'user' => ['name' => 'Jane Doe', 'username' => 'jane_doe'],
        'links' => ['html' => 'https://unsplash.com/photos/abc', 'download_location' => 'https://api.unsplash.com/photos/abc/download?ixid=abc']], $o);
}
$GLOBALS['so_unsplash_stub'] = fn($url) => [200, ['results' => [photo()]], 40];
$r = imageSourceUnsplash('Phoenix, AZ', 'city');
check(is_array($r) && !empty($r['hotlink']), 'a matching photo is returned as a hotlink');
check(strpos($r['image_url'] ?? '', 'https://images.unsplash.com/') === 0 && strpos($r['image_url'], 'w=480') !== false, 'the address stays on images.unsplash.com and is sized');
check(($r['attribution'] ?? '') === 'Jane Doe|jane_doe', 'photographer name and username kept for the credit');
check(strpos($r['source_url'] ?? '', 'utm_source=seatoutlet') !== false, 'photo link carries utm_source');

// A photo that names another city, a foreign Paris, a small or portrait photo: all rejected.
foreach ([
    'wrong city'      => photo(['description' => 'Chicago skyline', 'alt_description' => '', 'location' => ['name' => 'Chicago', 'city' => 'Chicago'], 'tags' => []]),
    'small'           => photo(['width' => 900, 'height' => 600]),
    'portrait'        => photo(['width' => 2000, 'height' => 3000]),
    'missing credit'  => photo(['user' => ['username' => '']]),
] as $why => $p) {
    $GLOBALS['so_unsplash_stub'] = fn($url) => [200, ['results' => [$p]], 40];
    check(imageSourceUnsplash('Phoenix, AZ', 'city') === null, "rejected: $why");
}
$GLOBALS['so_unsplash_stub'] = fn($url) => [200, ['results' => [photo(['description' => 'Paris Eiffel tower', 'location' => ['name' => 'Paris, France', 'city' => 'Paris', 'country' => 'France'], 'tags' => []])]], 40];
check(imageSourceUnsplash('Paris, TX', 'city') === null, 'Paris, TX is not answered with Paris, France');

$GLOBALS['so_unsplash_stub'] = fn($url) => [429, null, 0];
check(imageSourceUnsplash('Phoenix, AZ', 'city') === 'RATE_LIMITED', 'a 429 answers RATE_LIMITED');
check(imageSourceUnsplash('Phoenix, AZ', 'venue') === null, 'only cities');
putenv('UNSPLASH_ACCESS_KEY='); check(imageSourceUnsplash('Phoenix, AZ', 'city') === null, 'no key, no call'); putenv('UNSPLASH_ACCESS_KEY=test-key');

// Credit text: photographer and Unsplash both linked with utm_source.
ob_start(); renderImageCredit(['url' => 'https://images.unsplash.com/x', 'source' => 'unsplash', 'credit' => 'Jane Doe|jane_doe', 'license' => 'Unsplash License']); $html = ob_get_clean();
check(strpos($html, 'Photo by <a href="https://unsplash.com/@jane_doe?utm_source=seatoutlet&amp;utm_medium=referral"') !== false, 'credit links the photographer');
check(strpos($html, '>Unsplash</a>') !== false && strpos($html, 'unsplash.com/?utm_source=seatoutlet') !== false, 'credit links Unsplash');
check(imageLicenseUrl('Unsplash License') === 'https://unsplash.com/license', 'licence link');

$tracked = [];
$GLOBALS['so_unsplash_stub'] = function ($url) use (&$tracked) { $tracked[] = $url; return [200, [], 40]; };
imageUnsplashTrackDownload('https://api.unsplash.com/photos/abc/download?ixid=abc'); imageUnsplashTrackDownload('https://evil.example/x');
check($tracked === ['https://api.unsplash.com/photos/abc/download?ixid=abc'], 'the download notice goes to Unsplash only');

if ($fails) { fwrite(STDERR, "$fails failure(s)\n"); exit(1); }
echo "unsplash source: all passed\n";
