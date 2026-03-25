<?php
require_once '../functions.php';

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
    'festival' => '.1859.1987.'
];

if (!isset($categoryMap[$tab])) {
    echo json_encode([]);
    exit;
}

$rootPath = $categoryMap[$tab];

/* ==============================
   CACHE KEY
============================== */

$loc1Key = strtolower(str_replace(['.', ' '], ['', '_'], $loc1));
$loc2Key = strtolower(str_replace('.', '', $loc2));

if($type == '') {
    $cacheKey = "home_events_{$tab}";
}else{
    $cacheKey = "home_loc_events_{$tab}_{$type}_{$loc1Key}_{$loc2Key}";
}

$cached = cache_get($cacheKey);

if ($cached) {
    header('Cache-Control: public, max-age=86400');
    header('X-Cache: HIT');
    echo json_encode($cached);
    exit;
}

/* ==============================
   FETCH EVENTS FROM TN
============================== */

$events = fetchLocationCategoryEvents($rootPath, $type, $loc1, $loc2, 25);

/* ==============================
   FORMAT RESPONSE
============================== */

$output = [];

if (!empty($events)) {

    foreach ($events as $event) {
        $eventName = $event['text']['name'] ?? '';
        $venueName = $event['venue']['text']['name'] ?? '';
        $evtPerformer = $event['performers'][0]['name'] ?? '';
        $imageUrl = getEventImage($evtPerformer, $event['defaultCategory'], $eventName);

        $eventDateRaw = $event['date']['date'];
		$timestamp    = strtotime($eventDateRaw);
        $time = $event['date']['text']['time'];
        $city  = $event['city']['text']['name'] ?? '';
        $state = $event['stateProvince']['text']['abbr'] ?? '';

        if($imageUrl) {
            $output[] = [
                'id'    => (int)($event['id'] ?? 0),
                'name'  => $eventName,
                'date'  => date('D, d M y', $timestamp) . ', ' . $time,
                'venue' => $venueName,
                'price' => $event['pricingInfo']['lowPrice']['text']['formatted'] ?? '',
                'image' => $imageUrl,
                'loc'   => trim($city . ', ' . $state)
            ];
        }
    }
}

/* ==============================
   SAVE CACHE
============================== */

cache_set($cacheKey, $output);

header('Cache-Control: public, max-age=86400');
header('X-Cache-Status: ' . (isset($cached) && $cached !== false ? 'HIT' : 'MISS'));
echo json_encode($output);
exit;