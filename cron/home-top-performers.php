<?php
require_once '../functions.php';

$concerts = getTopPerformersByCategory(".1859.1986.");
$sports   = getTopPerformersByCategory(".1859.1988.");
$theater  = getTopPerformersByCategory(".1859.1989.");

$performers = [
    'concerts' => $concerts,
    'sports'   => $sports,
    'theater'  => $theater
];

cache_set("top_performers", $performers);

echo "home top performers cache refreshed on " . date('Y-m-d H:i:s');