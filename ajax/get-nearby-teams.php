<?php
require_once '../functions.php';

header('Content-Type: application/json; charset=utf-8');

$slug = $_GET['slug'] ?? '';
$slug = strtoupper(trim((string)$slug));

if ($slug === '') {
    echo json_encode([]);
    exit;
}

$teams = getTeamsByCategory($slug);

$response = [];

if(!empty($teams)) {
    $i = 0;
    foreach ($teams as $team) {
        if($i > 8) continue;
        $name = $team['text']['name'] ?? '';
        $uri  = $team['uriComponent'] ?? '';
        $id   = $team['id'] ?? null;
        $logo = getTeamImage($name);

        if ($name === '' || $uri === '') continue;
        
        if(!empty($logo)) {
            $i++;
            $response[] = [
                'id'   => $id,
                'name' => $name,
                'slug' => strtolower($uri),
                'logo' => $logo
            ];
        }
    }
}

echo json_encode($response);
exit;