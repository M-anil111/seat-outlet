<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test: the image in an event's structured data (soEventSchemaImage) is the artist's own stored picture when there is one. It checked a
 * 'real' key that getEntityImage() never returns, so every event listed the category stock photo, even for an artist with a licensed picture.
 * A stored picture is a row with status ok or manual and a url; anything else keeps the category photo.
 * Artists get a name that is unique per run and only those rows are removed. Run: php tools/test-event-schema-image.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';

$fail = 0;
function esCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$tag = bin2hex(random_bytes(4));
$name = function (string $k) use ($tag): string { return 'Zq Schemaimg ' . $tag . ' ' . $k; };
$event = function (string $who): array { return ['performers' => [['id' => 1, 'name' => $who]], 'defaultCategory' => ['path' => '.1859.1988.9700001.']]; };
$row = function (string $who, string $status, string $url): void {
    imageRecordUpsert(['imgkey' => imageEntityKey('artist', $who), 'entity_type' => 'artist', 'entity_name' => $who, 'url' => $url, 'status' => $status, 'source' => 'test',
        'expires_at' => date('Y-m-d H:i:s', time() + 86400)]);
};
$clean = function () use ($tag) {
    $like = 'Zq Schemaimg ' . $tag . ' %';
    $st = MYSQLI->prepare("DELETE FROM images WHERE entity_type = 'artist' AND entity_name LIKE ?");
    $st->bind_param('s', $like);
    $st->execute();
    $st->close();
};
$clean();
try {
    $home = rtrim(HOME_URL, '/');
    $stock = soEventSchemaImage($event($name('none')));
    esCheck('an artist with no row gets the category photo', $stock !== '' && strpos($stock, $home . '/') === 0, true);

    $row($name('ok'), 'ok', '/images/zq-schemaimg-ok.webp');
    esCheck('an artist with a stored picture gets that picture', soEventSchemaImage($event($name('ok'))), $home . '/images/zq-schemaimg-ok.webp');

    $row($name('manual'), 'manual', 'https://img.example.test/zq-manual.webp');
    esCheck('a picture set by hand is used, a full address is kept', soEventSchemaImage($event($name('manual'))), 'https://img.example.test/zq-manual.webp');

    $row($name('miss'), 'fallback', '');
    esCheck('a known miss keeps the category photo', soEventSchemaImage($event($name('miss'))), $stock);

    $row($name('empty'), 'ok', '');
    esCheck('a row with no address keeps the category photo', soEventSchemaImage($event($name('empty'))), $stock);

    esCheck('an event with no performer keeps the category photo', soEventSchemaImage(['performers' => [], 'defaultCategory' => ['path' => '.1859.1988.9700001.']]), $stock);
} finally {
    $clean();
}
echo $fail ? "$fail failure(s)\n" : "event schema image: all passed\n"; exit($fail ? 1 : 0);
