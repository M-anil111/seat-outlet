<?php
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

/* ==============================
   VALIDATE INPUT
============================== */

$tab  = $_GET['tab']  ?? '';
$type = $_GET['type'] ?? '';
$loc1 = $_GET['loc1'] ?? '';
$loc2 = $_GET['loc2'] ?? '';

/* ==============================
   MAP TAB TO TN CATEGORY PATH
============================== */

$categoryMap = [
    'concerts' => '.1859.1986.',
    'sports'   => '.1859.1988.',
    'theatre'  => '.1859.1989.',
    'festival' => '.1859.1986.1877.'
];

if (!isset($categoryMap[$tab])) {
    echo json_encode([]);
    exit;
}

$rootPath = $categoryMap[$tab];

/* ==============================
   FETCH EVENTS FROM TN
============================== */
if($tab == 'festival') {
    $events = fetchLocationCategoryEvents($rootPath, $type, $loc1, $loc2);
}else{
    $events = fetchGroupedEvents($rootPath, $type, $loc1, $loc2);
}

/* ==============================
   FORMAT RESPONSE
============================== */

$output = [];

if (!empty($events)) {

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

if (empty($output) && $type !== '') {
    $fallbackKey = "home_events_{$tab}";
    $fallbackCache = cache_get($fallbackKey);

    if ($fallbackCache !== false) {
        header('Cache-Control: public, max-age=86400');
        header('X-Cache-Fallback: HIT');
        echo json_encode($fallbackCache);
        exit;
    }
}

header('Cache-Control: public, max-age=86400');
header('X-Cache: HIT');
echo json_encode($output);
exit;