<?php
require_once '../functions.php';

$lat = 33.6973;
$lng = -117.9087;

$sportsCategories   = getAllSportsNestedCategories();
$nearbySportsTeams  = getOneEventPerSportNearby($sportsCategories, $lat, $lng);

$result = [];

foreach ($nearbySportsTeams as $sportName => $teamData) {

    $result[] = [
        'sport' => $sportName,
        'name'  => $teamData[0] ?? '',
        'url'   => $teamData[1] ?? ''
    ];
}

header('Content-Type: application/json');
echo json_encode($result);
exit;