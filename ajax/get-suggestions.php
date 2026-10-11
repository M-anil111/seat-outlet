<?php
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');

$q = mb_substr(soQs('q'), 0, 100);

if (!$q) {
    echo json_encode([]);
    exit;
}

try {
    $data = getKeywordSearchResults($q);
    if (!$data && soApiDegraded()) throw new SoTnUnavailable('Suggestions unavailable');

    $artists = $data['performers']['results'] ?? [];
    $venues  = $data['venues']['results'] ?? [];

    $response = [];

    /* ================= ARTISTS ================= */
    if(!empty($artists)) {
        // One batched lookup for all suggested performers (was one request each).
        $artistDetailsById = getTnPerformersByIds(array_column($artists, 'id'));
        foreach ($artists as $artistItem) {
            if (empty($artistItem['id']) || empty($artistItem['name'])) continue;

            $artistDetails = $artistDetailsById[(int) $artistItem['id']] ?? [];
            $defaultCategory = $artistDetails['defaultCategory'] ?? [];

            $subcategory = '';
            $cat = '';
            if (!empty($defaultCategory)) {
                if (($defaultCategory['depth'] ?? 0) == 2) {
                    $subcategory = $defaultCategory['text']['name'] ?? '';
                } else {
                    foreach ($defaultCategory['ancestors'] ?? [] as $ancestor) {
                        if (($ancestor['depth'] ?? 0) == 2) {
                            $subcategory = $ancestor['text']['name'] ?? '';
                            break;
                        }
                    }
                }
            }

            if($subcategory == 'OTHER') {
                if(($defaultCategory['depth'] ?? 0) == 1) {
                    $cat = strtolower((string) ($defaultCategory['text']['name'] ?? ''));
                }
                if(empty($cat)) {
                    if (!empty($defaultCategory['ancestors'])) {
                        foreach ($defaultCategory['ancestors'] as $ancestor) {
                            if(($ancestor['depth'] ?? 0) == 1) {
                                $cat = strtolower((string) ($ancestor['text']['name'] ?? ''));
                            }
                        }
                    }
                }
                $subcategory = $cat;
            }
            $subcat = ucfirst(strtolower($subcategory));

            $response[] = [
                'type' => 'artist',
                'name' => $artistItem['name'],
                'slug' => '/artist/' . soSlug('performer', $artistItem['name'], $artistItem['id']),
                'image' => getCategoryFallbackImage($defaultCategory, $cat),
                'meta' => $subcat,
                'category' => $defaultCategory
            ];
        }
    }

    /* ================= VENUES ================= */
    if(!empty($venues)) {
        foreach ($venues as $venueItem) {
            if (empty($venueItem['id']) || empty($venueItem['name'])) continue;

            $response[] = [
                'type' => 'venue',
                'name' => $venueItem['name'],
                'slug' => '/venue/' . soSlug('venue', $venueItem['name'], $venueItem['id']),
                'image' => "/images/venue-480.webp",
                'meta' => trim((string) ($venueItem['city'] ?? '') . ', ' . (string) ($venueItem['state'] ?? ''), ', ')
                //'category' => $defaultCategory
            ];
        }
    }

    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code($e instanceof SoTnUnavailable ? 503 : 500);
    header('Cache-Control: no-store');
    echo json_encode(['error' => 'Suggestions temporarily unavailable']);
    exit;
}

exit;
