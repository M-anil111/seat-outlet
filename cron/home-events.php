<?php
require_once '../functions.php';

$tabs = ['concerts', 'sports', 'theater', 'festival'];

$categoryMap = [
    'concerts' => '.1859.1986.',
    'sports'   => '.1859.1988.',
    'theater'  => '.1859.1987.',
    'festival' => '.1859.1989.'
];

foreach ($tabs as $tab) {
    if (!isset($categoryMap[$tab])) {
        continue;
    }

    $rootPath = $categoryMap[$tab];
    $events = fetchLocationCategoryEvents($rootPath, '', '', '', 8);

    $output = [];
    if (!empty($events) && is_array($events)) {

        foreach ($events as $event) {

            $venueName = $event['venue']['text']['name'] ?? '';
            $performer = $event['performers'][0]['name'] ?? '';
            $imageUrl  = getEventImage($performer, $venueName);

            $city  = $event['city']['text']['name'] ?? '';
            $state = $event['stateProvince']['text']['abbr'] ?? '';

            $output[] = [
                'id'    => (int)($event['id'] ?? 0),
                'name'  => $event['text']['name'] ?? '',
                'date'  => $event['date']['date'] ?? '',
                'venue' => $venueName,
                'price' => $event['pricingInfo']['lowPrice']['text']['formatted'] ?? '',
                'image' => $imageUrl,
                'loc'   => trim($city . ', ' . $state, ', ')
            ];
        }
    }

    $cacheKey = "home_events_{$tab}";
    cache_set($cacheKey, $output);    

}

echo "home events cache refreshed on " . date('Y-m-d H:i:s');