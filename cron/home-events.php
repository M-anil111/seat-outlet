<?php
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../functions.php';

$tabs = ['concerts', 'sports', 'theatre', 'festival'];

$categoryMap = [
    'concerts' => '.1859.1986.',
    'sports'   => '.1859.1988.',
    'theatre'  => '.1859.1989.',
    'festival' => '.1859.1986.1877.'
];

foreach ($tabs as $tab) {
    if (!isset($categoryMap[$tab])) {
        continue;
    }

    $rootPath = $categoryMap[$tab];
    if($tab == 'festival') {
        $events = fetchLocationCategoryEvents($rootPath);
    }else{
        $events = fetchGroupedEvents($rootPath);
    }
    
    $output = [];
    if (!empty($events) && is_array($events)) {
        foreach ($events as $event) {
            $eventName = $event['text']['name'] ?? '';
            $venueName = $event['venue']['text']['name'] ?? '';
            $evtPerformer = $event['performers'][0]['name'] ?? '';
            $eventDateRaw = $event['date']['date'];
            $timestamp    = strtotime($eventDateRaw);
            $time = $event['date']['text']['time'];
            $city  = $event['city']['text']['name'] ?? '';
            $state = $event['stateProvince']['text']['abbr'] ?? '';
    
            $output[] = [
                'id'              => (int)($event['id'] ?? 0),
                'name'            => $eventName,
                'date'            => $timestamp ? date('D, d M y', $timestamp) . ', ' . $time : '',
                'venue'           => $venueName,
                'price'           => $event['pricingInfo']['lowPrice']['text']['formatted'] ?? '',
                'loc'             => trim($city . ', ' . $state),
                'performer'       => $evtPerformer,
                'tab'             => $tab,
                'defaultCategory' => $event['defaultCategory'] ?? [],
                'placeholder'     => getCategoryFallbackImage($event['defaultCategory'] ?? [], $tab),
                'edate'           => $timestamp ? $timestamp : '',
            ];
        }
    }

    $cacheKey = "home_events_{$tab}";
    cache_set($cacheKey, $output);    

}

echo "home events cache refreshed on " . date('Y-m-d H:i:s');