<?php

require_once '../functions.php';

header('Content-Type: text/html; charset=UTF-8');

// Raw inputs
$city      = trim($_GET['city']  ?? '');
$state     = trim($_GET['state'] ?? '');
$zip       = trim($_GET['zip']   ?? '');
$cpid      = trim($_GET['cpid']  ?? '');
$lat       = trim($_GET['lat']   ?? '');
$lng       = trim($_GET['lng']   ?? '');
$startDate = trim($_GET['startDate'] ?? '');
$endDate   = trim($_GET['endDate']   ?? '');

// Basic sanitization to prevent malformed filters and injection.
$city  = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $city);
$state = strtoupper(preg_replace('/[^A-Z]/', '', $state));
$zip   = preg_replace('/[^0-9]/', '', $zip);

$cpidInt = (int) $cpid;
$latVal  = is_numeric($lat) ? (float) $lat : null;
$lngVal  = is_numeric($lng) ? (float) $lng : null;

$datePattern = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($datePattern, $startDate)) {
    $startDate = '';
}
if (!preg_match($datePattern, $endDate)) {
    $endDate = '';
}

$params = [
    'perPage' => 20,
    'page'    => 1
];

if ($cpidInt > 0) {
    $params['performerFilter'] = 'id eq ' . $cpidInt;
}

if ($latVal !== null && $lngVal !== null) {
    $params['geoFilter'] = sprintf('nearby(%F, %F, 50mi)', $latVal, $lngVal);
} elseif ($zip !== '') {
    $location = getLocationFromInput($zip);
    $latVal = $location['latitude'];
    $lngVal = $location['longitude'];
    $params = [
        'geoFilter'  => sprintf('nearby(%F, %F, 50mi)', $latVal, $lngVal),
        'rollup'  => 'zip',
        'perPage' => 1
    ];    
} elseif ($city !== '' && $state !== '') {
    $params['filter'] = "city/text/name eq '$city' and stateProvince/text/abbr eq '$state'";
} elseif ($startDate !== '' && $endDate !== '') {
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
    $locationData = getLocationFromInput(['lat' => $latVal, 'lng' => $lngVal]);
    echo 'no|'.$locationData['city'].'|'.$locationData['state'];
    exit;
}

foreach ($events as $event) {
    $eventDateRaw = $event['date']['date'];
    $timestamp    = strtotime($eventDateRaw);
    $evtPerformers = $event['performers'] ?? [];
    $names = array_map(function ($performer) {
        return $performer['name'] ?? null;
    }, $evtPerformers);
    $dataPerformers = implode('|', array_filter($names));
    $year = date('Y');
    $cy = date('Y', strtotime($timestamp));
    $y = '';
    if($cy > $year) { 
        $y = '<div class="month">'.$cy.'</div>';
    }
    echo '
    <div class="d-flex align-items-center justify-content-between performer-event-item">
        <div class="date-box text-center me-3">
            <div class="month">'.strtoupper(date('M', strtotime($timestamp))).'</div>
            <div class="day">'.date('d', strtotime($timestamp)).'</div>
            '.$y.'
        </div>
        <div class="flex-grow-1 w-50">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold day-weeks">'.date('D', strtotime($timestamp)).'</span>
                <span class="dot">·</span>
                <span class="time-clock">'.$event['date']['text']['time'].'</span>
                <i 
                    class="bi bi-info-circle text-muted icon-i" 
                    data-bs-toggle="offcanvas" 
                    data-bs-target="#offcanvasRight" 
                    aria-controls="offcanvasRight" 
                    data-id="'.$event['id'].'" 
                    data-date="'.date('D, M d', strtotime($timestamp)).'" 
                    data-venue="'.$event['venue']['text']['name'].'" 
                    data-location="'.$event['city']['text']['name'].', '.$event['stateProvince']['text']['abbr'].'" 
                    data-title="'.$event['text']['name'].'" 
                    data-performers="'.$dataPerformers.'"
                ></i>
            </div>
            <div class="fw-semibold location-venue-name">
                <a href="#">'.$event['city']['text']['name'].', '.$event['stateProvince']['text']['abbr'].'</a> · <a href="#">'.$event['venue']['text']['name'].'</a>
            </div>
            <div class="text-muted small">
                '.$event['text']['name'].'
            </div>
        </div>
        <div class="ms-3">
            <a href="/event.php?id='.$event['id'].'" class="btn btn-primary d-flex align-items-center gap-2">
                <span class="d-none d-md-inline">
                    Find Tickets
                </span>
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
    ';
}
