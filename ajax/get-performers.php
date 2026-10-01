<?php

require_once '../functions.php';

header('Content-Type: application/json; charset=UTF-8');

$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = isset($_GET['perPage']) ? max(1, min(100, (int) $_GET['perPage'])) : 24;
$letter  = isset($_GET['letter']) ? strtoupper(trim($_GET['letter'])) : 'ALL';
if ($letter !== 'ALL' && !preg_match('/^[A-Z]$/', $letter)) {
    $letter = 'ALL';
}

try {
    $params = [
        'page'              => $page,
        'perPage'           => $perPage,
        'includeTotalCount' => 'true',
        'sort'              => 'text/name',
    ];
    if ($letter !== 'ALL') {
        $params['filter'] = "startswith(text/name,'" . tnEscapeFilterValue($letter) . "')";
    }

    $response = getTnPerformers($params);

    $totalCount = (int) ($response['totalCount'] ?? 0);
    $totalPages = $perPage > 0 ? (int) ceil($totalCount / $perPage) : 0;
    $results    = $response['results'] ?? [];
    $hasMore    = ($page < $totalPages);

    $performers = [];
    foreach ($results as $performer) {
        $name = $performer['text']['name'] ?? '';
        $uriComponent = $performer['uriComponent'] ?? '';
        if ($name === '' || $uriComponent === '') {
            continue;
        }
        $defaultCategory = $performer['defaultCategory'] ?? [];
        $image = getPerformerImage($name, $defaultCategory);
        if (!$image) {
            $image = getCategoryFallbackImage($defaultCategory, 'performer');
        }

        $performers[] = [
            'name'         => $name,
            'uriComponent' => rawurlencode($uriComponent),
            'genre'        => getPerformerGenreLabel($defaultCategory),
            'image'        => $image,
        ];
    }

    echo json_encode([
        'performers'  => $performers,
        'totalCount'  => $totalCount,
        'currentPage' => $page,
        'nextPage'    => $hasMore ? ($page + 1) : null,
        'hasMore'     => $hasMore,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'performers'  => [],
        'totalCount'  => 0,
        'currentPage' => $page,
        'nextPage'    => null,
        'hasMore'     => false,
        'error'       => 'Failed to load performers',
    ]);
}
