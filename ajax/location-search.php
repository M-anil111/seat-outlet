<?php 

require_once '../functions.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

$response = getLocationSuggestions($q);

$unique = [];
$results = [];

if (!empty($response['results'])) {
    foreach ($response['results'] as $row) {

        $city  = $row['city']['text']['name'] ?? null;
        $state = $row['stateProvince']['text']['abbr'] ?? null;
        $zip   = $row['postalCode'] ?? '';

        if (!$city || !$state) {
            continue;
        }

        // Build unique key
        $key = strtolower($city . '|' . $state . '|' . $zip);

        if (isset($unique[$key])) {
            continue; // skip duplicates
        }

        $unique[$key] = true;

        $results[] = [
            'city'  => $city,
            'state' => $state,
            'zip'   => $zip
        ];
    }
}

echo json_encode(array_values($results));
exit;
