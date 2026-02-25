<?php
require_once '../functions.php';

$slug = $_GET['slug'] ?? '';

$teams = getTeamsByCategory($slug);

if (empty($teams)) {
    echo json_encode([]);
    exit;
}

foreach ($teams as $team) {
    $response[] = [
        'id'         => $team['id'],
        'name'       => $team['text']['name'],
        'slug'       => strtolower($team['uriComponent'])
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
exit;