<?php
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../functions.php';

// Homepage "Browse by Categories": the subcategories of each root with the
// most tickets on sale right now (the category tree has no salesRank, so
// _metadata.ticketCount is the API's own "what's hot" signal). Replaces a
// hard-coded list. Run on the same schedule as home-top-performers.php.
$categories = [
    'concerts' => getTopSubcategories(TN_CATEGORY_PATH_CONCERTS, 8),
    'sports'   => getTopSubcategories(TN_CATEGORY_PATH_SPORTS, 8),
    'theater'  => getTopSubcategories(TN_CATEGORY_PATH_THEATER, 8),
];

// Never overwrite a good cache with an empty one on an API hiccup.
if (empty($categories['concerts']) && empty($categories['sports']) && empty($categories['theater'])) {
    echo "home categories: API returned nothing, cache left untouched\n";
    exit(1);
}

cache_set('top_categories', $categories);

echo "home categories cache refreshed on " . date('Y-m-d H:i:s');
