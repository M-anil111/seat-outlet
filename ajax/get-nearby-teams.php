<?php
// require_once '../functions.php';

// $slug = $_GET['slug'] ?? '';

// $teams = getTeamsByCategory($slug);

// if (empty($teams)) {
//     echo json_encode([]);
//     exit;
// }

// foreach ($teams as $team) {
//     $response[] = [
//         'id'         => $team['id'],
//         'name'       => $team['text']['name'],
//         'slug'       => strtolower($team['uriComponent'])
//     ];
// }

// header('Content-Type: application/json');
// echo json_encode($response);
// exit;


require_once '../functions.php';

header('Content-Type: application/json; charset=utf-8');

$slug = $_GET['slug'] ?? '';
$slug = strtoupper(trim((string)$slug));

if ($slug === '') {
    echo json_encode([]);
    exit;
}

$teams = getTeamsByCategory($slug);

if (empty($teams)) {
    echo json_encode([]);
    exit;
}

$response = [];

foreach ($teams as $team) {
    $name = $team['text']['name'] ?? '';
    $uri  = $team['uriComponent'] ?? '';
    $id   = $team['id'] ?? null;

    if ($name === '' || $uri === '') continue;

    $response[] = [
        'id'   => $id,
        'name' => $name,
        'slug' => strtolower($uri),
    ];
}

echo json_encode($response, JSON_UNESCAPED_SLASHES);
exit;