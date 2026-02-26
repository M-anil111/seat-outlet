<?php
require_once __DIR__ . '/functions.php';

$tabs  = ['concerts','sports','theater','festival'];
$types = ['city','state','country'];

$locations = [
    ['loc1' => 'United States', 'loc2' => ''],
    ['loc1' => 'California', 'loc2' => ''],
    ['loc1' => 'Los Angeles', 'loc2' => 'CA'],
];

foreach ($tabs as $tab) {
    foreach ($types as $type) {
        foreach ($locations as $l) {

            $loc1 = trim($l['loc1']);
            $loc2 = trim($l['loc2']);

            $loc1Key = strtolower(str_replace(' ', '_', $loc1));
            $loc2Key = strtolower(str_replace(' ', '_', $loc2));

            $cacheKey = "home_events_{$tab}_{$type}_{$loc1Key}_{$loc2Key}";

            $categoryMap = [
                'concerts' => '.1859.1986.',
                'sports'   => '.1859.1988.',
                'theater'  => '.1859.1987.',
                'festival' => '.1859.1989.'
            ];

            if (!isset($categoryMap[$tab])) continue;

            $rootPath = $categoryMap[$tab];

            $events = fetchLocationCategoryEvents($rootPath, $type, $loc1, $loc2);

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

            cache_set($cacheKey, $output);
        }
    }
}

echo "home events cache refreshed\n";