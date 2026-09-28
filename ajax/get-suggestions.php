<?php
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$q = trim($q);

if (!$q) {
    echo json_encode([]);
    exit;
}

$data = getKeywordSearchResults($q);

$artists = $data['performers']['results'] ?? [];
$venues  = $data['venues']['results'] ?? [];

$response = [];

/* ================= ARTISTS ================= */
if(!empty($artists)) {
    foreach ($artists as $artistItem) {

        $artistDetails = getTnPerformerById($artistItem['id']);
        $defaultCategory = $artistDetails['defaultCategory'] ?? [];

        $subcategory = '';
        $cat = '';
        if (!empty($defaultCategory)) {
            if ($defaultCategory['depth'] == 2) {
                $subcategory = $defaultCategory['text']['name'];
            } else {
                foreach ($defaultCategory['ancestors'] ?? [] as $ancestor) {
                    if ($ancestor['depth'] == 2) {
                        $subcategory = $ancestor['text']['name'];
                        break;
                    }
                }
            }
        }

        if($subcategory == 'OTHER') {
            if($defaultCategory['depth'] == 1) {
                $cat = strtolower($defaultCategory['text']['name']);
            }
            if(empty($cat)) {
                if (!empty($defaultCategory['ancestors'])) {                    
                    foreach ($defaultCategory['ancestors'] as $ancestor) {
                        if($ancestor['depth'] == 1) {
                            $cat = strtolower($ancestor['text']['name']);
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
            'slug' => '/artist/' . createSlug($artistItem['name'], $artistItem['id']),
            'image' => getCategoryFallbackImage($defaultCategory, $cat),
            'meta' => $subcat,
            'category' => $defaultCategory
        ];
    }
}

/* ================= VENUES ================= */
if(!empty($venues)) {
    foreach ($venues as $venueItem) {

        $response[] = [
            'type' => 'venue',
            'name' => $venueItem['name'],
            'slug' => '/venue/' . createSlug($venueItem['name'], $venueItem['id']),
            'image' => "/images/venue.webp",
            'meta' => $venueItem['city'] . ', ' . $venueItem['state']
            //'category' => $defaultCategory
        ];
    }
}

echo json_encode($response);
exit;