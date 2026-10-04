<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Build cache/search_vocab.json: the names inc/smart.php matches typos against.
 * Top performers (by sales rank, with inventory), top venues and top cities.
 * Daily is plenty; the file is valid for 14 days and never blocks a request.
 *
 *   php cron/build-search-vocab.php
 */
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/smart.php';

$t0 = microtime(true);
$specs = [];

// 10 pages x 200 performers, best sellers with tickets on sale first.
// `filter` (not `eventFilter`): eventFilter combined with a sales-rank sort
// returns HTTP 500 after 30s from perPage=50 upward (verified); the plain
// filter answers 200 rows in under a second.
for ($page = 1; $page <= 10; $page++) {
    $specs[] = ['/catalog/v2/performers', [
        'filter'           => '_metadata/hasTickets eq true',
        'sort'             => '-salesRank',
        'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}',
        'perPage'          => 200,
        'page'             => $page,
    ], 6 * 3600];
}
// 3 pages x 200 US venues by sales rank.
for ($page = 1; $page <= 3; $page++) {
    $specs[] = ['/catalog/v2/venues', [
        'filter'           => "country/alphaCode eq 'US'",
        'sort'             => '-salesRank',
        'salesRankOptions' => '{"interval":"day","metric":"ticketVolume"}',
        'perPage'          => 200,
        'page'             => $page,
    ], 6 * 3600];
}

$responses = tnRequestMulti($specs);

$items = [];
$seen = [];
$add = function ($name, $type, $id, $url) use (&$items, &$seen) {
    $k = smartNormalize($name);
    $id = (int) $id;
    if ($k === '' || $id <= 0 || strlen($k) < 3 || isset($seen[$type . $id])) return;
    $seen[$type . $id] = true;
    $items[] = ['n' => $name, 'k' => $k, 't' => $type, 'i' => $id, 'u' => $url];
};

foreach (array_slice($responses, 0, 10) as $res) {
    foreach ($res['results'] ?? [] as $p) {
        $name = $p['text']['name'] ?? '';
        $add($name, 'performer', $p['id'] ?? 0, '/artist/' . soSlug('performer', $name, $p['id'] ?? 0));
    }
}
foreach (array_slice($responses, 10) as $res) {
    foreach ($res['results'] ?? [] as $v) {
        $name = $v['text']['name'] ?? '';
        $add($name, 'venue', $v['id'] ?? 0, '/venue/' . soSlug('venue', $name, $v['id'] ?? 0));
    }
}
foreach (getTopCities(200) as $c) {
    $add($c['name'], 'city', $c['id'], '/city/' . soSlug('city', $c['label'], $c['id']));
}

if (count($items) < 50) {
    fwrite(STDERR, "search vocab: only " . count($items) . " names, keeping the existing file\n");
    exit(1);
}
cache_set('search_vocab', ['built' => time(), 'items' => $items]);
printf("search vocab: %d names (%d performers) in %.1fs on %s\n", count($items), count(array_filter($items, fn($i) => $i['t'] === 'performer')), microtime(true) - $t0, date('Y-m-d H:i:s'));
