<?php

require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=UTF-8');

header('Cache-Control: public, max-age=600');   // the A-Z performer lists change slowly; the API answer is cached server-side too
$page    = soQsInt('page', 1, 1, 500);
$perPage = soQsInt('perPage', 24, 1, 100);
$letter  = strtoupper(soQs('letter', 'ALL'));
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
            'slug'         => soSlug('performer', (string) $name, (int) ($performer['id'] ?? 0)),
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
    if (!($e instanceof SoTnUnavailable)) \Sentry\captureException($e);   // an API outage was already reported once by the circuit breaker
    http_response_code(500);
    header('Cache-Control: no-store');
    echo json_encode([
        'performers'  => [],
        'totalCount'  => 0,
        'currentPage' => $page,
        'nextPage'    => null,
        'hasMore'     => false,
        'error'       => 'Failed to load performers',
    ]);
}
