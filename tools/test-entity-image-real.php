<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test: getEntityImage() says whether a picture is the entity's own ('real'). Three pages read that key and never got it: the photo tiles
 * of the artists in a category's text (inc/seo-category.php), the event image in structured data and the Cirque du Soleil category hero
 * (inc/genre-pages.php), so each always showed the generic photo or a text list. 'real' is true for a stored row with status ok or manual and an
 * address, false for everything else. Artists get a name that is unique per run and only those rows are removed.
 * Run: php tools/test-entity-image-real.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/seo-category.php';

$fail = 0;
function eimgRealCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$tag = bin2hex(random_bytes(4));
$name = function (string $k) use ($tag): string { return 'Zq Real ' . $tag . ' ' . $k; };
$row = function (string $type, string $who, string $status, string $url): void {
    imageRecordUpsert(['imgkey' => imageEntityKey($type, $who), 'entity_type' => $type, 'entity_name' => $who, 'url' => $url, 'status' => $status, 'source' => 'test',
        'expires_at' => date('Y-m-d H:i:s', time() + 86400)]);
};
$clean = function () use ($tag) {
    $like = 'Zq Real ' . $tag . ' %';
    $st = MYSQLI->prepare("DELETE FROM images WHERE entity_type IN ('artist', 'team') AND entity_name LIKE ?");
    $st->bind_param('s', $like);
    $st->execute();
    $st->close();
};
$clean();
try {
    $row('artist', $name('ok'), 'ok', '/images/zq-real-ok.webp');
    $row('artist', $name('manual'), 'manual', '/images/zq-real-manual.webp');
    $row('artist', $name('miss'), 'fallback', '');
    $row('artist', $name('empty'), 'ok', '');
    $get = function (string $k) use ($name): array { return getEntityImage('artist', $name($k), ['resolve' => false]); };
    eimgRealCheck('a stored picture is real', $get('ok')['real'], true);
    eimgRealCheck('a picture set by hand is real', $get('manual')['real'], true);
    eimgRealCheck('a known miss is not real', $get('miss')['real'], false);
    eimgRealCheck('a row with no address is not real', $get('empty')['real'], false);
    eimgRealCheck('an entity with no row is not real', $get('none')['real'], false);
    eimgRealCheck('a blank name is not real', getEntityImage('artist', '', ['resolve' => false])['real'], false);
    eimgRealCheck('a real picture keeps its address', $get('ok')['url'], '/images/zq-real-ok.webp');

    // The category text: an artist with a stored picture gets a photo tile with its name and picture, the others stay in the text list.
    foreach (['artist', 'team'] as $type) $row($type, $name('Act 1'), 'ok', '/images/zq-real-tile.webp');
    $artists = [];
    for ($i = 1; $i <= 3; $i++) $artists[] = ['id' => 9840000 + $i, 'name' => $name('Act ' . $i), 'n' => 4 - $i, 'first' => $i, 'cat' => ['path' => '.1859.1986.9700002.']];
    $cfg = ['id' => 9700002, 'label' => 'Zq Real', 'long' => 'zq real', 'kind' => 'concerts', 'profile' => null, 'slug' => 'zq-real'];
    $data = ['total' => 10, 'artists' => $artists, 'venues' => [], 'cities' => [], 'min' => 31.0, 'next' => null, 'path' => '.1859.1986.9700002.'];
    $html = (string) (soCategorySeo($cfg, $data)['html'] ?? '');
    eimgRealCheck('the category text shows the stored picture as a tile', substr_count($html, 'zq-real-tile.webp'), 1);
    eimgRealCheck('the tile is inside the photo list', (bool) preg_match('#<ul class="so-cseo__artists"><li><a href="/artist/[^"]+"><img src="[^"]*zq-real-tile\.webp" alt="[^"]*Act 1[^"]*"#', $html), true);
    eimgRealCheck('an artist with no picture stays in the text list', strpos($html, $name('Act 2')) !== false && substr_count($html, '<img') === 1, true);
} finally {
    $clean();
}
echo $fail ? "$fail failure(s)\n" : "entity image real: all passed\n"; exit($fail ? 1 : 0);
