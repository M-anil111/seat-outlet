<?php
require_once '../functions.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 3) {
  echo json_encode([]);
  exit;
}

$qLike = "%{$q}%";
$country = 'US';

$stmt = $mysqli->prepare("
    SELECT 
        TRIM(city) AS city,
        TRIM(short_state) AS short_state,
        AVG(latitude) AS latitude,
        AVG(longitude) AS longitude
    FROM postal_codes_worldwide
    WHERE city LIKE ?
      AND country = ?
      AND city IS NOT NULL
      AND city <> ''
    GROUP BY city, short_state
    ORDER BY city ASC
    LIMIT 20
");

$stmt->bind_param("ss", $qLike, $country);
$stmt->execute();

$result = $stmt->get_result();

$out = [];
$seen = [];

while ($row = $result->fetch_assoc()) {

    $city  = trim($row['city']);
    $state = trim($row['short_state'] ?? '');

    // Unique key should match display format
    $cityKey = strtolower($city . '_' . $state);

    if (isset($seen[$cityKey])) {
        continue;
    }

    $seen[$cityKey] = true;

    $label = $city . ($state ? ', ' . $state : '');

    $out[] = [
        'city'    => $city,
        'state'   => $state,
        'lat'     => (float)$row['latitude'],
        'lng'     => (float)$row['longitude'],
        'label'   => $label
    ];
}

echo json_encode($out);
exit;