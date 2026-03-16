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
}else{
    $teamsFallback = getTeamsByCategoryFallback($slug);
    if(!empty($teamsFallback)) {
        foreach ($teamsFallback as $fteam) {
            $name = $fteam['text']['name'] ?? '';
            $uri  = $fteam['uriComponent'] ?? '';
            $id   = $fteam['id'] ?? null;
    
            if ($name === '' || $uri === '') continue;
    
            $response[] = [
                'id'   => $id,
                'name' => $name,
                'slug' => strtolower($uri),
            ];
        }
    }
}

echo json_encode($response, JSON_UNESCAPED_SLASHES);
exit;