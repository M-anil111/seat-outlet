<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * The home page "Top performers" cards: the five best-selling performers per category (concerts, sports, theater) and, for each
 * performer, the lowest listed ticket price on a coming event ("From $53" on the card is the lowest of the five, nothing estimated).
 * Built by cron/home-top-performers.php, and also by itself in the background after a page request when the cache is older than
 * 6 hours (soTopPerformersMaybeRun), so no cron is needed. Stored in cache key "top_performers", read by ajax/get-top-performers.php.
 */
function soTopPerformersBuild(): array {
    $cats = ['concerts' => '.1859.1986.', 'sports' => '.1859.1988.', 'theater' => '.1859.1989.'];
    $out = ['concerts' => [], 'sports' => [], 'theater' => [], 'from' => []];
    $reqs = []; $map = [];
    foreach ($cats as $key => $path) {
        $list = getTopPerformersByCategory($path);
        $out[$key] = $list;
        foreach ($list as $i => $p) {
            if (empty($p['id'])) continue;
            $map[count($reqs)] = [$key, $i];
            $reqs[] = ['/catalog/v2/events/', [
                'filter' => 'date/date ge ' . date('Y-m-d') . ' and date/date le ' . date('Y-m-d', strtotime('+1 year')) . ' and _metadata/hasTickets eq true',
                'performerFilter' => 'id eq ' . (int) $p['id'],
                'sort' => 'pricingInfo/lowPrice/value',
                'perPage' => 1,
            ], 900];
        }
    }
    if ($reqs) {
        foreach (tnRequestMulti($reqs) as $n => $resp) {
            [$key, $i] = $map[$n] ?? [null, null];
            if ($key === null) continue;
            $price = $resp['results'][0]['pricingInfo']['lowPrice']['value'] ?? null;
            if ($price !== null && (float) $price > 0) $out[$key][$i]['from'] = (float) $price;
        }
    }
    foreach ($cats as $key => $_) {
        $prices = array_filter(array_column($out[$key], 'from'));
        if ($prices) $out['from'][$key] = (int) round(min($prices));
    }
    $out['builtAt'] = time();
    return $out;
}

/** Rebuild into the cache; keeps the old cache when nothing came back (API down). */
function soTopPerformersRefresh(): bool {
    $data = soTopPerformersBuild();
    if (!$data['concerts'] && !$data['sports'] && !$data['theater']) return false;
    cache_set('top_performers', $data);
    return true;
}

/** Shutdown hook for public page requests: refresh after the page has been sent, at most every 10 minutes, when the cache is old. */
function soTopPerformersMaybeRun(): void {
    if (PHP_SAPI === 'cli' || !function_exists('fastcgi_finish_request') || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    $dir = dirname(__DIR__) . '/cache';
    if (!is_dir($dir) || !is_writable($dir)) return;
    $cur = cache_get('top_performers', 30 * 86400);
    if (is_array($cur) && isset($cur['from']) && time() - (int) ($cur['builtAt'] ?? 0) < 6 * 3600) return;
    $stamp = $dir . '/top_performers.stamp';
    if (is_file($stamp) && time() - (int) @filemtime($stamp) < 600) return;
    @touch($stamp);
    try {
        ignore_user_abort(true);
        @set_time_limit(60);
        fastcgi_finish_request();
        soTopPerformersRefresh();
    } catch (Throwable $e) { error_log('top performers refresh: ' . $e->getMessage()); }
}
