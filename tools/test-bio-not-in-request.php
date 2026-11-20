<?php
/**
 * CI (needs the migrated database): a performer page never calls another site while the visitor waits. Until 20 Nov 2026 a performer with no
 * stored bio made every page view wait for a Wikipedia request (10 s timeout, nothing stored on failure): /artist/lizzy-mcalpine answered in
 * 13 s with no ticket call. The lookup is now queued for after the response, once per performer per 6 hours.
 * Performer ids 9100901 and up; removed at the end. Run: php tools/test-bio-not-in-request.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
putenv('SO_BIO_FETCH_CLI');   // the test never reaches Wikipedia
require_once __DIR__ . '/../functions.php';

$fail = 0;
function bnCheck(string $name, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$clean = function () {
    MYSQLI->query("DELETE FROM bios WHERE performerId BETWEEN 9100901 AND 9100909");
    foreach (glob(cache_dir() . 'bio_try_91009*') ?: [] as $f) { @unlink($f); }
};
$clean();

// The function reads the stored bio and does nothing else while the visitor waits.
$src = (string) file_get_contents(__DIR__ . '/../functions.php');
preg_match('/function getArtistBio\(.*?\n\}\n/s', $src, $m);
bnCheck('getArtistBio makes no request of its own', [strpos($m[0] ?? '', 'curl') !== false, strpos($m[0] ?? '', 'wikipedia') !== false], [false, false]);
bnCheck('the lookup connects in 2 s and gives up in 4', strpos($src, 'CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 4') !== false, true);
bnCheck('without fastcgi_finish_request the lookup is skipped, never run in front of the visitor', strpos($src, "if (!function_exists('fastcgi_finish_request')) return;") !== false, true);

// A stored bio is returned as is, with no retry marker.
set_bio(9100901, 'Stored bio text');
bnCheck('a stored bio is returned', getArtistBio('Zq Bio Band', 9100901), 'Stored bio text');
bnCheck('and no lookup is queued for it', cache_get('bio_try_9100901', SO_BIO_RETRY_SECONDS), false);

// No stored bio: '' at once, one lookup queued, and not again within 6 hours.
$t0 = microtime(true);
bnCheck('no stored bio: empty string', getArtistBio('Zq Bio Band Two', 9100902), '');
bnCheck('it answers at once (no outbound request)', (microtime(true) - $t0) < 0.5, true);
bnCheck('one lookup is marked as tried', cache_get('bio_try_9100902', SO_BIO_RETRY_SECONDS) !== false, true);
$mark = cache_get('bio_try_9100902', SO_BIO_RETRY_SECONDS);
sleep(1);
getArtistBio('Zq Bio Band Two', 9100902);
bnCheck('a second view does not queue another try inside 6 hours', cache_get('bio_try_9100902', SO_BIO_RETRY_SECONDS), $mark);

// The header can carry the time spent reading the bio.
$GLOBALS['so_timers'] = ['bio' => 123.0, 'Bad Name!' => 5.0];
bnCheck('Server-Timing carries named timers (valid names only)', preg_match('/, bio;dur=120$/', soServerTimingHeader()) === 1, true);

$clean();
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: an artist page never waits for another site\n";
