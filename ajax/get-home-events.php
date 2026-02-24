<?php
require_once '../functions.php';

$type = $_GET['type'] ?? '';

switch ($type) {
    case 'concert':
        $rootPath = ".1859.1986.";
        break;
    case 'sports':
        $rootPath = ".1859.1988.";
        break;
    case 'theater':
        $rootPath = ".1859.1987.";
        break;
    case 'festival':
        $rootPath = ".1859.1989.";
        break;
    default:
        echo json_encode([]);
        exit;
}

$events = getMostPopularEvents($rootPath);

$result = [];

foreach ($events as $event) {

    $venueName = $event['venue']['text']['name'] ?? '';
    $imageUrl  = getWikipediaVenueImage($venueName);

    $ext = pathinfo($imageUrl, PATHINFO_EXTENSION);

    if (empty($imageUrl) || in_array(strtolower($ext), ['pdf', 'tif'])) {
        $imageUrl = HOME_URL . '/assets/event-concert.jpg';
    }

    $result[] = [
        'id'    => $event['id'] ?? 0,
        'name'  => $event['text']['name'] ?? '',
        'date'  => $event['date']['date'] ?? '',
        'venue' => $venueName,
        'price' => $event['pricingInfo']['lowPrice']['text']['formatted'] ?? '',
        'image' => $imageUrl,
        'loc'   => $event['city']['text']['name'] . ', ' . $event['stateProvince']['text']['abbr']
    ];
}

header('Content-Type: application/json');
echo json_encode($result);
exit;