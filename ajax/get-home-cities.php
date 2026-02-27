<?php
require_once '../functions.php';

$location = getUserLocationFromCookie();
if(!empty($location)) {
    $lat = $location['latitude'];
    $lng = $location['longitude'];
    $data = [
        'cities'    => getNearbyCities($lat, $lng)
    ];
}else{
    $cities = [
        ['name' => 'New York',      'state' => 'NY', 'country' => 'US'],
        ['name' => 'Los Angeles',   'state' => 'CA', 'country' => 'US'],
        ['name' => 'Chicago',       'state' => 'IL', 'country' => 'US'],
        ['name' => 'Houston',       'state' => 'TX', 'country' => 'US'],
        ['name' => 'Phoenix',       'state' => 'AZ', 'country' => 'US'],
        ['name' => 'Philadelphia',  'state' => 'PA', 'country' => 'US'],
        ['name' => 'San Antonio',   'state' => 'TX', 'country' => 'US'],
        ['name' => 'San Diego',     'state' => 'CA', 'country' => 'US'],
        ['name' => 'Dallas',        'state' => 'TX', 'country' => 'US'],
        ['name' => 'Jacksonville',  'state' => 'FL', 'country' => 'US'],
        ['name' => 'Fort Worth',    'state' => 'TX', 'country' => 'US'],
        ['name' => 'San Jose',      'state' => 'CA', 'country' => 'US'],
        ['name' => 'Austin',        'state' => 'TX', 'country' => 'US'],
        ['name' => 'Charlotte',     'state' => 'NC', 'country' => 'US'],
        ['name' => 'Columbus',      'state' => 'OH', 'country' => 'US'],
        ['name' => 'Indianapolis',  'state' => 'IN', 'country' => 'US']
    ];
    $data = [
        'cities'    => $cities
    ];
}



header('Content-Type: application/json');
echo json_encode($data);
exit;