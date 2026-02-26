<?php
require_once '../functions.php';

$lat = 33.6973;
$lng = -117.9087;

$data = [
    'cities'    => getNearbyCities($lat, $lng)
];

header('Content-Type: application/json');
echo json_encode($data);
exit;