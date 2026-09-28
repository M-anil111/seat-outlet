<?php
/**
 * Keep the high-traffic listing feeds warm so no visitor pays TicketNetwork's
 * latency: tnRequest() caches these for 10 minutes, this runs every 5.
 *
 *   php cron/warm-listings.php
 */
require_once __DIR__ . '/../functions.php';

$t0 = microtime(true);
$warmed = 0;

// /tickets, /concerts, /sports, /theater, /festival (page 1, sales-rank order)
foreach (['', TN_CATEGORY_PATH_CONCERTS, TN_CATEGORY_PATH_SPORTS, TN_CATEGORY_PATH_THEATER, TN_CATEGORY_PATH_FESTIVAL] as $path) {
    getCategoryListingEvents($path);
    $warmed++;
}

// /cities and the sitemap's city list
getTopCities(60);
$warmed++;

// Top subcategory pages linked from the homepage
$topCategories = cache_get('top_categories', 7 * 86400) ?: [];
foreach ($topCategories as $bucket) {
    foreach (array_slice((array) $bucket, 0, 4) as $cat) {
        if (!empty($cat['id'])) {
            getTnCatEvents((int) $cat['id'], ['perPage' => 20, 'page' => 1, 'includeTotalCount' => 'true']);
            $warmed++;
        }
    }
}

// Top 10 city pages
foreach (array_slice(getTopCities(10), 0, 10) as $city) {
    getTnCityEvents((int) $city['id'], ['perPage' => 20, 'page' => 1, 'includeTotalCount' => 'true']);
    $warmed++;
}

printf("warmed %d listing feeds in %.1fs on %s\n", $warmed, microtime(true) - $t0, date('Y-m-d H:i:s'));
