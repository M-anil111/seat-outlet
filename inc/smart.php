<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/*
|--------------------------------------------------------------------------
| Smart search: typo tolerance and recovery
|--------------------------------------------------------------------------
| TicketNetwork's search and suggest endpoints are prefix/exact matchers.
| Verified against the sandbox: "adelle" and "carot top" return 0 events and
| no suggestions (only "lakres" happened to work). A visitor who mistypes a
| performer name hits a dead end, which for a site whose goal is ticket sales
| is a lost sale.
|
| cron/build-search-vocab.php builds a vocabulary of the names people
| actually search for (top performers by sales rank, top venues, top cities)
| into cache/search_vocab.json. smartDidYouMean() then finds the closest
| names by edit distance / phonetic match, ranked by popularity. Nothing here
| calls the API at request time.
*/

function smartNormalize($s) {
    $s = (string) $s;
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false && $t !== '') $s = $t;
    }
    $s = strtolower($s);
    $s = preg_replace('/&/', ' and ', $s);
    $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}

/** @return array<int,array{n:string,k:string,t:string,i:int,u:string}> ordered by popularity */
function smartVocab() {
    static $vocab = null;
    if ($vocab !== null) return $vocab;
    $data = cache_get('search_vocab', 14 * 86400);
    $vocab = is_array($data) && !empty($data['items']) ? $data['items'] : [];
    return $vocab;
}

/** Edit distance where swapping two adjacent letters ("lakres" -> "lakers") costs 1. */
function smartDistance($a, $b) {
    $la = strlen($a); $lb = strlen($b);
    if ($la === 0) return $lb;
    if ($lb === 0) return $la;
    $prev2 = [];
    $prev = range(0, $lb);
    for ($i = 1; $i <= $la; $i++) {
        $cur = [$i];
        for ($j = 1; $j <= $lb; $j++) {
            $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
            $v = min($prev[$j] + 1, $cur[$j - 1] + 1, $prev[$j - 1] + $cost);
            if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                $v = min($v, $prev2[$j - 2] + 1);
            }
            $cur[] = $v;
        }
        $prev2 = $prev;
        $prev = $cur;
    }
    return $prev[$lb];
}

/**
 * Closest known names to a (probably mistyped) query.
 * A single-word query is also compared with each word of a name, so
 * "lakres" finds "Los Angeles Lakers".
 * @return array<int,array{name:string,type:string,url:string,distance:int}>
 */
function smartDidYouMean($query, $limit = 3) {
    $q = smartNormalize($query);
    $len = strlen($q);
    if ($len < 4) return [];
    $maxDist = $len <= 5 ? 1 : ($len <= 9 ? 2 : 3);
    $singleWord = strpos($q, ' ') === false;

    $found = [];
    foreach (smartVocab() as $rank => $item) {
        $k = $item['k'];
        if ($k === $q) return [];                    // the query is already a known name
        $best = null;
        if (abs(strlen($k) - $len) <= $maxDist) {
            $best = smartDistance($q, $k);
        }
        if ($singleWord && strpos($k, ' ') !== false) {
            foreach (explode(' ', $k) as $tok) {
                if (strlen($tok) >= 4 && abs(strlen($tok) - $len) <= $maxDist) {
                    $dt = smartDistance($q, $tok);
                    // A word match is a weaker signal than a whole-name match.
                    if ($best === null || $dt + 0.5 < $best) $best = $dt + 0.5;
                }
            }
        }
        if ($best !== null && $best <= $maxDist) {
            $found[] = ['d' => $best, 'r' => $rank, 'item' => $item];
        }
    }
    usort($found, fn($a, $b) => [$a['d'], $a['r']] <=> [$b['d'], $b['r']]);

    $out = [];
    $seen = [];
    foreach ($found as $f) {
        $it = $f['item'];
        $key = $it['t'] . $it['i'];
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $out[] = [
            'name'     => $it['n'],
            'type'     => $it['t'],
            'url'      => $it['u'],
            'distance' => (int) ceil($f['d']),
        ];
        if (count($out) >= $limit) break;
    }
    return $out;
}

/**
 * Confident automatic correction ("Showing results for Adele"): only when the
 * closest name is unique and one edit away, so a wrong guess is unlikely.
 */
function smartAutoCorrection($query) {
    $q = smartNormalize($query);
    if (strlen($q) < 5) return null;
    $sugg = smartDidYouMean($query, 2);
    if (!$sugg || $sugg[0]['distance'] > 1 || $sugg[0]['type'] !== 'performer') return null;
    if (isset($sugg[1]) && $sugg[1]['distance'] <= 1) return null;   // ambiguous
    return $sugg[0]['name'];
}
