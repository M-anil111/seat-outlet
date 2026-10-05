<?php
/**
 * Fetches a short factual summary for each category from Wikipedia's REST API and stores it in inc/category-facts.json.
 * The category pages show the first two sentences with a link back to the article (Wikipedia text is CC BY-SA 4.0, so the
 * attribution is required and is printed with it). No AI involved, no runtime network calls: pages read the JSON file.
 * Re-run occasionally (monthly is plenty): php tools/fetch-category-facts.php
 * Categories without a clean Wikipedia article (Children / Family, Holiday events, Las Vegas) are left out on purpose.
 */
require_once __DIR__ . '/../inc/cli-guard.php';

$topics = [
    1862 => 'Alternative rock', 1863 => 'Ballet', 1864 => 'Baseball', 1865 => 'Basketball', 1867 => 'Boxing',
    1868 => 'Broadway theatre', 2031 => 'Cirque du Soleil', 1871 => 'Classical music', 1872 => 'Stand-up comedy',
    1873 => 'Country music', 1875 => 'Dance', 1877 => 'Music festival', 1879 => 'National Football League',
    1882 => 'Heavy metal music', 1883 => 'Ice hockey', 1885 => 'Jazz', 1890 => 'Latin music', 1969 => 'Major League Baseball',
    1970 => 'Major League Soccer', 1894 => 'Musical theatre', 1971 => 'National Basketball Association',
    1972 => 'National Hockey League', 1896 => 'Off-Broadway', 1898 => 'Opera', 1903 => 'Pop music', 1905 => 'Auto racing',
    1904 => 'Rhythm and blues', 1913 => 'Association football', 1915 => 'Electronic dance music', 1916 => 'Tennis',
];

$out = [];
foreach ($topics as $id => $title) {
    $url = 'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode(str_replace(' ', '_', $title)) . '?redirect=true';
    for ($try = 0; $try < 3; $try++) {   // be polite: pause between calls and back off when asked to slow down
        usleep(600000);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => 'SeatOutletBot/1.0 (https://seatoutlet.com; category facts)']);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 429) break;
        sleep(4 * ($try + 1));
    }
    $j = $code === 200 ? json_decode((string) $body, true) : null;
    if (!$j || ($j['type'] ?? '') !== 'standard' || empty($j['extract'])) { fwrite(STDERR, "skip $id $title (HTTP $code)\n"); continue; }
    $out[$id] = ['title' => $j['title'], 'extract' => $j['extract'], 'url' => $j['content_urls']['desktop']['page'] ?? '', 'fetched' => date('Y-m-d')];
    echo "ok   $id {$j['title']}\n";
}
file_put_contents(__DIR__ . '/../inc/category-facts.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
echo count($out) . " categories written\n";
