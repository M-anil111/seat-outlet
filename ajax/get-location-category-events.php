<?php
require_once '../functions.php';

header('Content-Type: application/json');

/* ==============================
   VALIDATE INPUT
============================== */

$tab  = isset($_GET['tab']) ? $_GET['tab'] : '';
$type  = isset($_GET['type']) ? $_GET['type'] : '';
$loc1  = isset($_GET['loc1']) ? $_GET['loc1'] : '';
$loc2  = isset($_GET['loc2']) ? $_GET['loc2'] : '';

/* ==============================
   MAP TAB TO TN CATEGORY PATH
============================== */

$categoryMap = [
    'concerts' => '.1859.1986.',
    'sports'   => '.1859.1988.',
    'theater'  => '.1859.1987.',
    'festival' => '.1859.1989.'
];

if (!isset($categoryMap[$tab])) {
    echo json_encode([]);
    exit;
}

$rootPath = $categoryMap[$tab];

/* ==============================
   FETCH EVENTS FROM TN
============================== */
$events = fetchLocationCategoryEvents($rootPath, $type, $loc1, $loc2);

/* ==============================
   FORMAT RESPONSE
============================== */

$output = [];

foreach ($events as $event) {

    $venueName = $event['venue']['text']['name'] ?? '';
    $imageUrl  = getWikipediaVenueImage($venueName);

    $ext = pathinfo($imageUrl, PATHINFO_EXTENSION);

    if (empty($imageUrl) || in_array(strtolower($ext), ['pdf', 'tif'])) {
        $imageUrl = HOME_URL . '/assets/event-concert.jpg';
    }

    $output[] = [
        'id'    => (int)($event['id'] ?? 0),
        'name'  => $event['text']['name'] ?? '',
        'date'  => $event['date']['date'] ?? '',
        'venue' => $venueName,
        'price' => $event['pricingInfo']['lowPrice']['text']['formatted'] ?? '',
        'image' => $imageUrl,
        'loc'   => $event['city']['text']['name'] . ', ' . $event['stateProvince']['text']['abbr']
    ];
}

//echo json_encode([$rootPath, $tab, $type, $loc1, $loc2]);
echo json_encode($output);
exit;