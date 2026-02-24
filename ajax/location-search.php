<?php 

require_once '../functions.php';

$q = trim($_GET['q'] ?? '');

// Basic validation / normalization
if ($q === '' || mb_strlen($q) > 50) {
    echo json_encode([]);
    exit;
}

// Restrict allowed characters to avoid malformed filters.
$q = preg_replace('/[^a-zA-Z0-9\s,\-]/u', '', $q);

try {
    $response = getLocationSuggestions($q);
    $searchType = $response['searchType'];
} catch (Throwable $e) {
    echo json_encode([]);
    exit;
}

$unique  = [];
$results = [];

if (!empty($response['results'])) {
    foreach ($response['results'] as $row) {

        $city  = $row['city']['text']['name'] ?? null;
        $state = $row['stateProvince']['text']['abbr'] ?? null;

        $location = getLocationFromInput(['city' => $city, 'state' => $state]);
        $zip   = $location['zip'] ?? '';

        if (!$city || !$state) {
            continue;
        }

        // Build unique key
        $key = strtolower($city . '|' . $state . '|' . $zip);

        if (isset($unique[$key])) {
            continue; // skip duplicates
        }

        $unique[$key] = true;
        if($searchType == 'zip') {
            $results[] = [
                'city'  => $city,
                'state' => $state,
                'zip'   => $zip,
            ];
        }else{
            $results[] = [
                'city'  => $city,
                'state' => $state,
                'zip'   => '',
            ];
        }
    }
}

echo json_encode(array_values($results));
exit;
