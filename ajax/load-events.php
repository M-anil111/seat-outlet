<?php

require_once '../functions.php';

$city  = $_GET['city']  ?? '';
$state = $_GET['state'] ?? '';
$zip   = $_GET['zip']   ?? '';
$cpid = trim($_GET['cpid'] ?? '');
$lat  = trim($_GET['lat'] ?? '');
$lng  = trim($_GET['lng'] ?? '');

$startDate  = $_GET['startDate'] ?? '';
$endDate  = $_GET['endDate'] ?? '';

$params = [
    'perPage' => 20,
    'page' => 1
];

if ($cpid !== '') {
    $params['performerFilter'] = "id eq $cpid";
}

if ($lat !== '' && $lng !== '') {

    $params['geoFilter'] = "nearby($lat, $lng, 50mi)";

} elseif ($zip !== '') {
    $params['filter'] = "postalCode eq '$zip'";
} elseif ($city !== '' && $state !== '') {
    $params['filter'] = "city/text/name eq '$city' and stateProvince/text/abbr eq '$state'";
} elseif (!empty($startDate) && !empty($endDate)) {
    $params['filter'] = "date/date ge $startDate and date/date le $endDate";
}

try {
    $response = getTnEvents($params);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<div class="error">Failed to load events</div>';
    exit;
}

$events = $response['results'] ?? [];

if (empty($events)) {
    echo 'no';
    exit;
}

$daynames = DAY_NAMES;

foreach ($events as $event) {
    $class = 'so-cta-tickets';
    $pricinginfo = '';
    if(!empty($event['pricingInfo'])) { 
        $class = '';
        $pricinginfo .= '<span>Price From</span><strong>'.$event['pricingInfo']['lowPrice']['text']['formatted'].'</strong>';
    }
    echo '
    <div class="performer-event-item">
        <div class="row">
            <div class="performer-event-item-info col-sm-9">
                <h3>'.$event['text']['name'].'</h3>
                <ul class="row ps-0">
                    <li class="col-sm-5">
                        <span>Venue</span>
                        '.$event['venue']['text']['name'].'
                        <span class="location">'.$event['city']['text']['name'].', '.$event['stateProvince']['text']['abbr'].'</span>
                    </li>
                    <li class="col-sm-4">
                        <span>'.$daynames[$event['date']['weekday']-1].'</span>
                        '.date('F. jS, Y', strtotime($event['date']['date'])).'
                    </li>
                    <li class="col-sm-3">
                        <span>Time</span>
                        '.$event['date']['text']['time'].'
                    </li>
                </ul>
            </div>
            <div class="performer-event-item-price col-sm-3">
                '.$pricinginfo.'
                <a href="/event.php?id='.$event['id'].'" class="'.$class.'">Get Tickets</a>
            </div>
        </div>
    </div>
    ';
}
