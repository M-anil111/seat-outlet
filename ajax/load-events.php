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

foreach ($events as $event) {
    $evtPerformers = $event['performers'];
    $names = array_map(function ($performer) {
        return $performer['name'] ?? null;
    }, $evtPerformers);
    $dataPerformers = implode('|', array_filter($names));
    echo '
    <div class="d-flex align-items-center justify-content-between performer-event-item">
        <div class="date-box text-center me-3">
            <div class="month">'.strtoupper(date('M', strtotime($event['date']['date']))).'</div>
            <div class="day">'.date('d', strtotime($event['date']['date'])).'</div>
        </div>
        <div class="flex-grow-1 w-50">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold day-weeks">'.date('D', strtotime($event['date']['date'])).'</span>
                <span class="dot">·</span>
                <span class="time-clock">'.$event['date']['text']['time'].'</span>
                <i class="bi bi-info-circle text-muted icon-i" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight" data-id="'.$event['id'].'" data-date="'.date('D, M d', strtotime($event['date']['date'])).'" data-venue="'.$event['venue']['text']['name'].'" data-location="'.$event['city']['text']['name'].', '.$event['stateProvince']['text']['abbr'].'" data-title="'.$event['text']['name'].'" data-performers="'.$dataPerformers.'"></i>
            </div>
            <div class="fw-semibold">
                '.$event['city']['text']['name'].', '.$event['stateProvince']['text']['abbr'].' · '.$event['venue']['text']['name'].'
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
