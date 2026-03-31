<?php 

include 'db/config.php';
include 'constants.php';
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

function getS3Client() {

    $accountId = AWS_ACCOUNT_ID;
    $accessKey = AWS_ACCESS_KEY;
    $secretKey = AWS_SECRET_KEY;
    
    return new S3Client([
        'version' => 'latest',
        'region'  => 'auto',
        'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
        'credentials' => [
            'key'    => $accessKey,
            'secret' => $secretKey,
        ],
    ]);
}

function downloadImage($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');

    $data = curl_exec($ch);

    if (curl_errno($ch)) {
        die("cURL Error: " . curl_error($ch));
    }

    curl_close($ch);
    return $data;
}

function getTnAccessToken() {
    static $accessToken = null;

    if ($accessToken !== null) {
        return $accessToken;
    }

    $basicAuth = base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET);

    $ch = curl_init('https://key-manager.tn-apis.com/oauth2/token');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . $basicAuth,
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'client_credentials',
            // scope is OPTIONAL here since Catalog is already subscribed
        ]),
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Token request error: ' . $error);
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (empty($data['access_token'])) {
        throw new Exception('Token error: ' . $response);
    }

    $accessToken = $data['access_token'];

    return $accessToken;
}

function tnRequest($endpoint, $params = [], $method = 'GET') {
    $accessToken = getTnAccessToken(); 
   
    $url = BASE_URL . $endpoint;

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID,
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL error: ' . $error);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    

    $decoded = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from TicketNetwork API');
    }

    return $decoded;
}

function getTnPerformers($params = []) {
    return tnRequest('/catalog/v2/performers', $params);
}

function getTnPerformerEvents($performerId = 0, $params = []) {

    if ($performerId > 0) {
        $params['performerFilter'] = 'id eq ' . (int) $performerId;
    }

    $today = date('Y-m-d');
    $params['filter'] = "date/date ge $today";

    return tnRequest('/catalog/v2/events/', $params);
}

function getTnPerformerEventsCount($performerId = 0, $params = []) {
    if ($performerId > 0) {
        $params['performerFilter'] = 'id eq ' . (int) $performerId;
    }

    $today = date('Y-m-d');
    $params['filter']  = "date/date ge $today";
    $params['page']    = 1;
    $params['perPage'] = 500;

    $json = tnRequest('/catalog/v2/events/', $params);

    $count = (int) ($json['count'] ?? 0);

    return $count;
}

function getTnPerformerById($performerId) {
    $endpoint = "/catalog/v2/performers/" . (int) $performerId;
    return tnRequest($endpoint);
}

function getTnEventById($eventId) {
    $endpoint = "/catalog/v2/events/" . (int) $eventId;
    return tnRequest($endpoint);
}

function buildCategoryBreadcrumb($defaultCategory) {

    $breadcrumb = [];

    $breadcrumb[] = [
        'label' => 'Home',
        'url'   => '/'
    ];

    if (!empty($defaultCategory['ancestors'])) {
        foreach ($defaultCategory['ancestors'] as $ancestor) {
            $breadcrumb[] = [
                'label' => ucwords(strtolower($ancestor['text']['name'])),
                'url'   => '/category' . $ancestor['path']
            ];
        }
    }

    $breadcrumb[] = [
        'label' => ucwords(strtolower($defaultCategory['text']['name'])),
        'url'   => '/category' . $defaultCategory['path']
    ];

    return $breadcrumb;
}

function getRelatedPerformers($categoryPath, $currentPerformerId, $limit = 20) {
    $categoryPathEsc = tnEscapeFilterValue($categoryPath);
    $params = [
        'filter' => "defaultCategory/path eq '$categoryPathEsc'",
        'sort'   => 'salesRank',
        'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}',
        'page'   => 1,
        'perPage'=> $limit
    ];

    $data = tnRequest('/catalog/v2/performers', $params);

    $related = array_filter($data['results'], function ($p) use ($currentPerformerId) {
        return $p['id'] != $currentPerformerId;
    });

    return array_slice($related, 0, $limit);
}

function getLocationSuggestions($keyword) {
    if(preg_match('/^(?=.*\d)[A-Za-z0-9\- ]{3,10}$/', $keyword)) {
        $location = getLocationFromInput($keyword);
        $latVal = $location['latitude'];
        $lngVal = $location['longitude'];
        $output['searchType'] = 'zip';
        $output['results'][0]['city']['text']['name'] = $location['city'];
        $output['results'][0]['stateProvince']['text']['abbr'] = $location['state'];
        $output['results'][0]['postalCode'] = $location['zip'];        
    }elseif(preg_match('/^[\p{L}\s\'\-\.]{2,}$/u', $keyword)) {
        $keyword      = tnEscapeFilterValue($keyword);
        $stateKeyword = strtoupper(tnEscapeFilterValue($keyword));
        $params = [
            'filter' => "startswith(city/text/name,'$keyword')",
            'rollup'  => 'city',
            'perPage' => 100,
            'page'   => 1
        ];
        $output = tnRequest('/catalog/v2/events', $params);
        $output['searchType'] = 'city';
    }   

    return $output;
}

function getTnEvents($params = [], $flag = 0) {
    $url = BASE_URL . '/catalog/v2/events';

    $defaultParams = [
        'page'    => 1,
        'perPage' => 20,
    ];

    if(!$flag) { 
        $today = date('Y-m-d');
        $params['filter'] = "date/date ge $today";
    }

    $params = array_merge($defaultParams, $params);

    return tnRequest('/catalog/v2/events', $params);
}

function tnEscapeFilterValue(string $value): string {
    return str_replace("'", "''", $value);
}

function sanitize_title($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

function curlGet($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'SeatOutlet/1.0 (https://beta.seatoutlet.com)'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function resizeAndConvertToWebP($imageContent, $maxWidth = 500, $quality = 80) {

    $source = imagecreatefromstring($imageContent);
    if (!$source) return false;

    $width  = imagesx($source);
    $height = imagesy($source);

    if ($width <= $maxWidth) {
        $newWidth  = $width;
        $newHeight = $height;
    } else {
        $ratio = $height / $width;
        $newWidth  = $maxWidth;
        $newHeight = $maxWidth * $ratio;
    }

    $resized = imagecreatetruecolor($newWidth, $newHeight);

    imagecopyresampled(
        $resized,
        $source,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $width, $height
    );

    ob_start();
    imagewebp($resized, null, $quality);
    $webpData = ob_get_clean();

    imagedestroy($source);
    imagedestroy($resized);

    return $webpData;
}

function s3ObjectExists($key) {
    try {
        $client = getS3Client();

        $client->headObject([
            'Bucket' => AWS_BUCKET_NAME,
            'Key'    => $key
        ]);

        return true;

    } catch (\Aws\Exception\AwsException $e) {
        return false;
    }
}

function uploadImageToS3($imageContent, $key, $contentType) {

    $client = getS3Client();

    $client->putObject([
        'Bucket' => AWS_BUCKET_NAME,
        'Key'    => $key,
        'Body'   => $imageContent,
        'ContentType' => $contentType,
        'CacheControl' => 'public, max-age=31536000'
    ]);
}

function getS3PublicUrl($key) {
    return AWS_CDN_URL . $key;
}

function getArtistBio($artistName) {

    $artistName = trim(preg_replace('/\s*\(.*?\)|\s*feat\.?.*/i', '', $artistName));

    $url = 'https://en.wikipedia.org/w/api.php'
         . '?action=query'
         . '&prop=extracts'
         . '&exintro=1'
         . '&explaintext=1'
         . '&redirects=1'
         . '&titles=' . urlencode($artistName)
         . '&format=json';

    $response = curlGet($url);
    if (!$response) return '';

    $data = json_decode($response, true);
    if (empty($data['query']['pages'])) return '';

    $page = reset($data['query']['pages']);
    return trim($page['extract'] ?? '');
}

function getFaqs($mysqli, $type = null) {

    $faqs = [];

    if ($type) {
        $stmt = $mysqli->prepare("SELECT * FROM faq WHERE type LIKE CONCAT('%', ?, '%')");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query("SELECT * FROM faq");
    }

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $faqs[] = $row;
        }
    }

    return $faqs;
}

function getConcertEvents($limit = 12) {

    $accessToken = getTnAccessToken();
    $concertRootPath = ".1859.1986.";

    $params = [
        'filter' => "contains(defaultCategory/path, '$concertRootPath')",
        'perPage' => $limit
    ];

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data['results'] ?? [];
}

function getLocationFromInput($input, $mysqli = MYSQLI) {

    $type = null;
    $zip = $city = $state = $lat = $lng = null;

    if (is_string($input) && preg_match('/^\d{5}$/', $input)) {
        $type = 'zip';
        $zip = trim($input);
    }
    elseif (is_array($input) && isset($input['city'], $input['state'])) {
        $type = 'city';
        $city  = trim($input['city']);
        $state = trim($input['state']);
    }
    elseif (is_array($input) && isset($input['lat'], $input['lng'])) {
        $type = 'latlng';
        $lat = floatval($input['lat']);
        $lng = floatval($input['lng']);
    }
    else {
        return [];
    }

    if ($type === 'zip') {

        $stmt = $mysqli->prepare("SELECT * FROM locations WHERE zip = ? LIMIT 1");
        $stmt->bind_param("s", $zip);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) return $result;
    }

    if ($type === 'city') {

        $stmt = $mysqli->prepare("SELECT * FROM locations WHERE city = ? AND state = ? LIMIT 1");
        $stmt->bind_param("ss", $city, $state);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) return $result;
    }

    if ($type === 'latlng') {

        $stmt = $mysqli->prepare("SELECT * FROM locations WHERE latitude = ? AND longitude = ? LIMIT 1");
        $stmt->bind_param("dd", $lat, $lng);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) return $result;
    }

    $accessToken = getTnAccessToken();
    $params = ['perPage' => 1];

    if ($type === 'zip') {
        $params['filter'] = "code eq '$zip'";
    }

    if ($type === 'city') {
       $params['filter'] = "startswith(city/text/name,'$city')";
    }

    if ($type === 'latlng') {
        $params['geoFilter'] = sprintf('nearby(%F, %F, 50mi)', $lat, $lng);
    }

    $url = BASE_URL . '/catalog/v2/postalCodes/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (empty($data['results'])) return [];

    $loc = $data['results'][0];

    $city      = $loc['city']['text']['name'] ?? '';
    $state     = $loc['stateProvince']['text']['abbr'] ?? '';
    $zip       = $loc['code'] ?? '';
    $country   = $loc['country']['alphaCode'] ?? '';
    $latitude  = $loc['geoCenter']['latitude'] ?? '';
    $longitude = $loc['geoCenter']['longitude'] ?? '';

    $stmt = $mysqli->prepare("
        INSERT IGNORE INTO locations (city, state, country, zip, latitude, longitude)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("ssssss", $city, $state, $country, $zip, $latitude, $longitude);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("SELECT * FROM locations WHERE zip = ? LIMIT 1");
    $stmt->bind_param("s", $zip);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result;
}

function getNearbyVenues($lt, $lg, $limit = 20) {

    $accessToken = getTnAccessToken();
    $radius = '50mi';
    
    if (empty($lt) && empty($lg)) {
        return [];
    }
    
    $params = [
        'geoFilter' => "nearby($lt, $lg, $radius)",
        'perPage'   => $limit
    ];    

    $url = BASE_URL . '/catalog/v2/venues/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    
    return $data['results'] ?? [];
}

function getTopVenues($limit = 20) {

    $accessToken = getTnAccessToken();
    
    $params = [ 
        'filter' => "country/alphaCode eq 'US'", 
        'sort' => 'salesRank', 
        'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}', 
        'perPage' => $limit 
    ];

    $url = BASE_URL . '/catalog/v2/venues/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return $data['results'] ?? [];
}

function getPerformerUriComponent($performerId, $key = 'uriComponent') {

    $data = getTnPerformerById($performerId);

    return $data[$key] ?? '';
}

function getKeywordSearchSuggestions($q) {
    $q = trim($q);

    if (strlen($q) < 2) {
        return [];
    }

    $results = [
        'artists' => [],
        'cities'  => [],
        'venues'  => []
    ];

    /*
    ==========================
    ARTISTS
    ==========================
    */
   
    $artistParams = [
        'filter' => "startswith(text/name,'$q')",
        'sort' => 'salesRank',
        'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}',
        'perPage' => 10
    ];

    $artistUrl = BASE_URL . '/catalog/v2/performers/?' . http_build_query($artistParams);    

    $artistData = tnCurlRequest($artistUrl);

    if (!empty($artistData['results'])) {
        foreach ($artistData['results'] as $artist) {
            $results['artists'][] = [
                'id'   => $artist['id'] ?? '',
                'name' => $artist['text']['name'] ?? '',
                'slug' => $artist['uriComponent'] ?? ''
            ];
        }
    }

    /*
    ==========================
    CITIES
    ==========================
    */
    $cityParams = [
        'filter' => "startswith(text/name,'$q')",
        'sort' => 'salesRank',
        'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}',
        'perPage' => 10
    ];
    
    $cityUrl = BASE_URL . '/catalog/v2/cities/?' . http_build_query($cityParams);

    $cityData = tnCurlRequest($cityUrl);

    if (!empty($cityData['results'])) {
        foreach ($cityData['results'] as $city) {
            $results['cities'][] = [
                'id'   => $city['id'] ?? '',
                'name' => $city['text']['name'] ?? '',
                'state' => $city['stateProvince']['text']['abbr'] ?? '',
                'slug' => $city['uriComponent'] ?? ''
            ];
        }
    }

    /*
    ==========================
    VENUES
    ==========================
    */
    $venueParams = [
        'filter' => "startswith(text/name,'$q')",
        'sort' => 'salesRank',
        'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}',
        'perPage' => 10
    ];

    $venueUrl = BASE_URL . '/catalog/v2/venues/?' . http_build_query($venueParams);

    $venueData = tnCurlRequest($venueUrl);

    if (!empty($venueData['results'])) {
        foreach ($venueData['results'] as $venue) {
            $results['venues'][] = [
                'id'    => $venue['id'] ?? '',
                'name'  => $venue['text']['name'] ?? '',
                'city'  => $venue['city']['text']['name'] ?? '',
                'state' => $venue['stateProvince']['text']['abbr'] ?? '',
                'slug'  => $venue['uriComponent'] ?? ''
            ];
        }
    }

    return $results;
}

function tnCurlRequest($url) {

    $accessToken = getTnAccessToken();

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT        => 20
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function getHeaderSearchEvents($params = []) {

    $accessToken = getTnAccessToken();

    $url = BASE_URL . '/catalog/v2/events/search';

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return $data ?? [];
}

function getMostPopularEvents($catPath = '', $lat = '', $lng = '') {
    
    $accessToken = getTnAccessToken();
    $today = date('Y-m-d');
    if(!empty($lat) && !empty($lng)) {
        $params = [
            'perPage' => 20,
            'page'    => 1,
            'filter'  => "date/date ge '$today' and contains(defaultCategory/path,'$catPath')",
            'geoFilter' => "nearby($lat, $lng, '50mi')",
            'sort'    => 'salesRank'
        ];
    }else{
        $params = [
            'perPage' => 20,
            'page'    => 1,
            'filter'  => "date/date ge " . $today . " and contains(defaultCategory/path,'$catPath')",
            'sort'    => 'salesRank'
        ];
    }
    

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data['results'] ?? [];
}

function tnGetCategoryNearby($rootPath, $lat, $lng, $limit = 12) {

    $accessToken = getTnAccessToken();

    $params = [
        'filter'    => "defaultCategory/path eq '$rootPath'",
        'geoFilter' => sprintf('nearby(%F,%F,50mi)', $lat, $lng),
        'perPage'   => $limit
    ];

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data['results'] ?? [];
}

function fetchLocationCategoryEvents($rootPath, $type, $loc1, $loc2, $limit = 12) {
    $accessToken = getTnAccessToken();
    $today = date('Y-m-d');
    $rootPath = tnEscapeFilterValue($rootPath);
    if($type === 'll') {
        $lat = floatval($loc1);
        $lng = floatval($loc2);
        $params = [
            'filter' => "date/date ge $today and contains(defaultCategory/path,'$rootPath')",
            'geoFilter' => sprintf('nearby(%F,%F,10mi)', $lat, $lng),
            'perPage' => $limit
        ];
    }else{
        $params = [
            'filter' => "date/date ge $today and contains(defaultCategory/path,'$rootPath')",
            'perPage' => $limit
        ];
    }

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data['results'] ?? [];
}

function normalizeKey($v) {
    if (is_numeric($v)) {
        $v = number_format((float)$v, 2, '.', '');
    }

    $v = strtolower((string)($v ?? ''));
    $v = str_replace(['.', ' '], ['', '_'], $v);

    return $v;
}

function getTeamsByCategory($categorySlug, $limit = 50) {

    $categorySlug = strtoupper(trim($categorySlug));
    if ($categorySlug === '') return [];

    $categoryPaths = [
        'NFL' => '.1859.1988.1879.1959.',
        'NBA' => '.1859.1988.1865.1971.',
        'MLB' => '.1859.1988.1864.1969.',
        'NHL' => '.1859.1988.1883.1972.',
        'MLS' => '.1859.1988.1913.1970.',
    ];

    if (!isset($categoryPaths[$categorySlug])) {
        return [];
    }

    $accessToken = getTnAccessToken();
    $categoryPath = $categoryPaths[$categorySlug];

    $params = [
        'categoryFilter' => "path eq '$categoryPath'",
        //'eventFilter' => "country/alphaCode eq 'US'",
        //'sort' => 'salesRank',
        //'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}',
        'perPage' => $limit
    ];

    $url = BASE_URL . '/catalog/v2/performers?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $results = $data['results'] ?? [];

    return $results;
}

function getTeamsByCategoryFallback($categorySlug, $limit = 20) {

    $categorySlug = strtoupper(trim((string)$categorySlug));
    if ($categorySlug === '') return [];

    $categoryPaths = [
        'NFL' => '.1859.1988.1879.1959.',
        'NBA' => '.1859.1988.1865.1971.',
        'MLB' => '.1859.1988.1864.1969.',
        'NHL' => '.1859.1988.1883.1972.',
        'MLS' => '.1859.1988.1913.1970.',
    ];

    if (!isset($categoryPaths[$categorySlug])) {
        return [];
    }

    $accessToken = getTnAccessToken();

    $cacheKey = "teams_{$categorySlug}";
    $params = [
        'categoryFilter' => "path eq '{$categoryPaths[$categorySlug]}'",
        'eventFilter' => "country/alphaCode eq 'US'",
        'sort' => 'salesRank',
        'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}',
        'perPage'   => $limit
    ];       
   
    $cached = cache_get($cacheKey, 86400);
    if ($cached !== false) {
        return $cached;
    }

    $url = BASE_URL . '/catalog/v2/performers?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode((string)$response, true);
    $results = $data['results'] ?? [];

    cache_set($cacheKey, $results);

    return $results;
}

function cache_dir() {
    $dir = __DIR__ . '/cache/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function cache_file_path($key) {
    $safeKey = preg_replace('/[^a-z0-9_\-]/i', '_', (string)$key);
    return cache_dir() . $safeKey . '.json';
}

function cache_get($key, $ttl = 86400) {
    $file = cache_file_path($key);

    if (!is_file($file)) return false;
    if ($ttl > 0 && (time() - filemtime($file)) > $ttl) return false;

    $json = file_get_contents($file);
    if ($json === false || $json === '') return false;

    $data = json_decode($json, true);
    return is_array($data) ? $data : false;
}

function cache_set($key, $data) {
    $file = cache_file_path($key);

    $tmp = $file . '.' . uniqid('tmp_', true);
    $json = json_encode($data, JSON_UNESCAPED_SLASHES);

    if ($json === false) return false;

    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return rename($tmp, $file);
}

function renderSkeletonCardsEvents($count = 8) {
    for ($i = 0; $i < $count; $i++) {
        echo '
        <a href="javascript:void(0)" class="team-link skeleton-link">
            <article class="event-card skeleton-card">
                <div class="event-card__img skeleton-img"></div>
                <div class="event-card__body">
                    <div class="skeleton-line skeleton-title"></div>
                    <div class="skeleton-meta">
                        <span class="skeleton-line skeleton-date"></span>
                        <span class="dot"></span>
                        <span class="skeleton-line skeleton-venue"></span>
                    </div>
                    <div class="skeleton-line skeleton-price"></div>
                </div>
            </article>
        </a>';
    }
}

function generateTeamSkeleton($count = 6) {

    $html = '<div class="team-slider">';

    for ($i = 0; $i < $count; $i++) {

        $html .= '
        <a href="javascript:void(0)" class="team-link skeleton-link">
            <div class="team-card team-card-skeleton">
                <span class="skeleton-line skeleton-team-name"></span>
            </div>
        </a>
        ';
    }

    return $html . '</div>';
}

function buildVenueSkeleton($count = 8) {

    $html = '';

    for ($i = 0; $i < $count; $i++) {

        $html .= '
            <div class="venue-card-skeleton">
                <div class="skeleton-img shimmer"></div>
                <div class="venue-content text-center p-3">
                    <div class="skeleton-line skeleton-title shimmer"></div>
                    <div class="skeleton-line skeleton-location shimmer"></div>
                </div>
            </div>
        ';
    }

    return $html;
}

function getAllCatsEventsCount() {

    $concertPath = ".1859.1986.";
	$sportsPath = ".1859.1988.";
	$theaterPath = ".1859.1989.";
    $today = date('Y-m-d');
    $params = [
        'filter' => "date/date ge $today and (startswith(defaultCategory/path, '$sportsPath') or startswith(defaultCategory/path, '$concertPath') or startswith(defaultCategory/path, '$theaterPath'))",
        'perPage' => 500,
        'page' => 1
    ];

    $json = tnRequest('/catalog/v2/events/', $params);

    $count = (int) ($json['count'] ?? 0);

    return $count;
}

function getAllCatsEvents() {

    $accessToken = getTnAccessToken();

    $concertPath = ".1859.1986.";
	$sportsPath = ".1859.1988.";
	$theaterPath = ".1859.1989.";

	$today = date('Y-m-d');

    $params = [
        'filter' => "date/date ge $today and (startswith(defaultCategory/path, '$sportsPath') or startswith(defaultCategory/path, '$concertPath') or startswith(defaultCategory/path, '$theaterPath'))",
        'perPage' => 20,
        'page' => 1
    ];

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data;
}

function getSportsCatEventsCount() {

	$sportsPath = ".1859.1988.";	
    $today = date('Y-m-d');

    $params = [
        'filter' => "date/date ge $today and startswith(defaultCategory/path, '$sportsPath')",
        'perPage' => 500,
        'page' => 1
    ];

    $json = tnRequest('/catalog/v2/events/', $params);

    $count = (int) ($json['count'] ?? 0);

    return $count;
}

function getSportsCatEvents() {

    $accessToken = getTnAccessToken();

    $sportsPath = ".1859.1988.";
	$today = date('Y-m-d');

    $params = [
        'filter' => "date/date ge $today and startswith(defaultCategory/path, '$sportsPath')",
        'perPage' => 20,
        'page' => 1
    ];

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data;
}

function getConcertsCatEventsCount() {

	$concertPath = ".1859.1986.";	
    $today = date('Y-m-d');

    $params = [
        'filter' => "date/date ge $today and startswith(defaultCategory/path, '$concertPath')",
        'perPage' => 500,
        'page' => 1
    ];

    $json = tnRequest('/catalog/v2/events/', $params);

    $count = (int) ($json['count'] ?? 0);

    return $count;
}

function getConcertsCatEvents() {

    $accessToken = getTnAccessToken();

    $concertPath = ".1859.1986.";
	$today = date('Y-m-d');

    $params = [
        'filter' => "date/date ge $today and startswith(defaultCategory/path, '$concertPath')",
        'perPage' => 20,
        'page' => 1
    ];

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data;
}

function getTheaterCatEventsCount() {

	$theaterPath = ".1859.1989.";
    $today = date('Y-m-d');

    $params = [
        'filter' => "date/date ge $today and startswith(defaultCategory/path, '$theaterPath')",
        'perPage' => 500,
        'page' => 1
    ];

    $json = tnRequest('/catalog/v2/events/', $params);

    $count = (int) ($json['count'] ?? 0);

    return $count;
}

function getTheaterCatEvents() {

    $accessToken = getTnAccessToken();

    $theaterPath = ".1859.1989.";
	$today = date('Y-m-d');

    $params = [
        'filter' => "date/date ge $today and startswith(defaultCategory/path, '$theaterPath')",
        'perPage' => 20,
        'page' => 1
    ];

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data;
}

function getFestivalCatEventsCount() {

	$festivalPath = ".1859.1989.";
    $today = date('Y-m-d');

    $params = [
        'filter' => "date/date ge $today and startswith(defaultCategory/path, '$festivalPath')",
        'perPage' => 500,
        'page' => 1
    ];

    $json = tnRequest('/catalog/v2/events/', $params);

    $count = (int) ($json['count'] ?? 0);

    return $count;
}

function getFestivalCatEvents() {

    $accessToken = getTnAccessToken();

    $festivalPath = ".1859.1989.";
	$today = date('Y-m-d');

    $params = [
        'filter' => "date/date ge $today and startswith(defaultCategory/path, '$festivalPath')",
        'perPage' => 20,
        'page' => 1
    ];

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data;
}

function searchPostalCodes($text, $country = 'US', $limit = 20) {

    $accessToken = getTnAccessToken();

    if (!$text) {
        return [];
    }

    $text = trim($text);

    // Detect if search text is zipcode (numbers only)
    if (preg_match('/^[0-9]+$/', $text)) {
        $filter = "code eq '{$text}' and country/alphaCode eq '{$country}'";
    } else {
        $filter = "startswith(city/text/name,'{$text}') and country/alphaCode eq '{$country}'";
    }

    $params = [
        'filter'  => $filter,
        'perPage' => $limit,
        'sort'    => 'city'
    ];

    $url = BASE_URL . '/catalog/v2/postalCodes/?' . http_build_query($params);
    
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    return $data;
}

function fixImageOrientation($imageContent) {

    $tmp = tempnam(sys_get_temp_dir(), 'img_');
    file_put_contents($tmp, $imageContent);

    $image = imagecreatefromstring($imageContent);
    if (!$image) return $imageContent;

    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);

        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
        }
    }

    ob_start();
    imagejpeg($image, null, 90);
    $fixed = ob_get_clean();

    imagedestroy($image);
    unlink($tmp);

    return $fixed;
}

// function getWikimediaImage($title = "", $type) {

//     if (!$title) return '';

//     $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
//     $clean = trim($slug, '-');
//     $key  = $type . "/{$clean}.webp";

//     if (s3ObjectExists($key)) {
//         return getS3PublicUrl($key);
//     }

//     $url = "https://en.wikipedia.org/w/api.php?" . http_build_query([
//         "action" => "query",
//         "titles" => $title,
//         "prop" => "pageimages",
//         "piprop" => "original",
//         "format" => "json"
//     ]);

//     $ch = curl_init($url);

//     curl_setopt_array($ch, [
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_USERAGENT => "SeatOutletBot/1.0"
//     ]);

//     $response = curl_exec($ch);
//     curl_close($ch);

//     if (!$response) return '';

//     $data = json_decode($response, true);

//     $pages = $data['query']['pages'] ?? [];
//     $page  = reset($pages);
//     $image = $page['original']['source'] ?? '';

//     return $image;
// }

function getWikimediaImage($title = "") {

    if (!$title) return '';

    $url = "https://en.wikipedia.org/w/api.php?" . http_build_query([
        "action" => "query",
        "titles" => $title,
        "prop" => "pageimages",
        "piprop" => "original",
        "format" => "json"
    ]);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => "SeatOutletBot/1.0"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return '';

    $data = json_decode($response, true);

    $pages = $data['query']['pages'] ?? [];
    $page  = reset($pages);

    return $page['original']['source'] ?? '';
}

function getStoredImageUrl($name, $type) {

    if (!$name) return '';

    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
    $clean = trim($slug, '-');
    $key  = "{$type}/{$clean}.webp";

    if (s3ObjectExists($key)) {
        return getS3PublicUrl($key);
    }

    return '';
}

function processAndStoreImage($imageUrl, $name, $type) {

    if (!$imageUrl || !$name) return '';

    // 🔑 Generate key
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
    $clean = trim($slug, '-');
    $key  = "{$type}/{$clean}.webp";

    // ✅ Double-check (safe fallback)
    if (s3ObjectExists($key)) {
        return getS3PublicUrl($key);
    }

    // ⚠️ Heavy work (still sync, can be async later)
    $imageContent = downloadImage($imageUrl);
    if (!$imageContent) return '';

    $imageContent = fixImageOrientation($imageContent);

    $webpImage = resizeAndConvertToWebP($imageContent, 800, 80);
    if (!$webpImage) return '';

    uploadImageToS3($webpImage, $key, 'image/webp');

    return getS3PublicUrl($key);
}

function getCategoryFallbackImage($defaultCategory, $tab) {

    $subcategory = '';

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

    $slug = str_replace([' ', '/', '(', ')', '-', '&'], '', $subcategory);

    if ($slug == 'OTHER' || empty($slug)) {
        $slug = $tab;
    }

    return AWS_CDN_URL . 'categories/' . strtolower($slug) . '.jpg';
}

function getEventImage($artist, $defaultCategory, $event, $tab) {

    // ✅ 1. Check stored EVENT image
    $storedEvent = getStoredImageUrl($event, 'events');
    if ($storedEvent) return $storedEvent;

    // ✅ 2. Fetch + store EVENT image
    $eventImage = getWikimediaImage($event);
    if ($eventImage) {
        return processAndStoreImage($eventImage, $event, 'events');
    }

    // ✅ 3. Check stored ARTIST image
    if ($artist) {
        $storedArtist = getStoredImageUrl($artist, 'artists');
        if ($storedArtist) return $storedArtist;

        // fetch + store
        $artistImage = getWikimediaImage($artist);
        if ($artistImage) {
            return processAndStoreImage($artistImage, $artist, 'artists');
        }
    }

    // ✅ 4. Category fallback
    return getCategoryFallbackImage($defaultCategory, $tab);
}

function getVenueImage($venue) {
    $venueImageUrl = getWikimediaImage($venue, 'venues');
    if(!empty($venueImageUrl)) {
        $imageName = $venue;
        $imageUrl = $venueImageUrl;
    }else{
        $imageUrl = '';
    }    
        
    if(!empty($imageUrl)) {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($imageName));
        $clean = trim($slug, '-');
        $key  = "venues/{$clean}.webp";
       
        if (s3ObjectExists($key)) {
            return getS3PublicUrl($key);
        }

        $imageContent = downloadImage($imageUrl);
        if (!$imageContent) return '';

        $imageContent = fixImageOrientation($imageContent);

        $webpImage = resizeAndConvertToWebP($imageContent, 800, 80);
        if (!$webpImage) return '';

        uploadImageToS3($webpImage, $key, 'image/webp');
        return getS3PublicUrl($key);
    }
    
    return '';
}

function getArtistImage($artist) {
    $artistImageUrl = getWikimediaImage($artist, 'artists');
    if(!empty($artistImageUrl)) {
        $imageName = $artist;
        $imageUrl = $artistImageUrl;
    }else{
        $imageUrl = '';
    }    
        
    if(!empty($imageUrl)) {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($imageName));
        $clean = trim($slug, '-');
        $key  = "artists/{$clean}.webp";
       
        if (s3ObjectExists($key)) {
            return getS3PublicUrl($key);
        }

        $imageContent = downloadImage($imageUrl);
        if (!$imageContent) return '';

        $imageContent = fixImageOrientation($imageContent);

        $webpImage = resizeAndConvertToWebP($imageContent, 800, 80);
        if (!$webpImage) return '';

        uploadImageToS3($webpImage, $key, 'image/webp');
        return getS3PublicUrl($key);
    }
    
    return '';
}

function convertToFloat($value) {

    if (substr($value, 0, 1) === '-') {
        $value = substr($value, 1);
        $parts = explode('-', $value, 2);
        return '-' . $parts[0] . '.' . $parts[1];
    }

    $parts = explode('-', $value, 2);
    return $parts[0] . '.' . $parts[1];
}

function searchSuggestions($q, $type) {
    $params = [
        'filter' => "startswith(text/name,'$q')",
        'perPage' => 20
    ];

    $url = BASE_URL . '/catalog/v2/'.$type.'/?' . http_build_query($params);    

    $data = tnCurlRequest($url);

    if($type == 'performers') {
        if (!empty($data['results'])) {
            foreach ($data['results'] as $item) {
                $suggestions[] = [
                    'id'   => $item['id'] ?? '',
                    'name' => $item['text']['name'] ?? '',
                    'slug' => $item['uriComponent'] ?? '',
                    'cat'  => $item['defaultCategory'],
                ];
            }
        }
    }elseif($type == 'venues') {
        if (!empty($data['results'])) {
            foreach ($data['results'] as $venue) {
                $suggestions[] = [
                    'id'    => $venue['id'] ?? '',
                    'name'  => $venue['text']['name'] ?? '',
                    'city'  => $venue['city']['text']['name'] ?? '',
                    'state' => $venue['stateProvince']['text']['abbr'] ?? '',
                    'slug'  => $venue['uriComponent'] ?? ''
                ];
            }
        }
    }
    return $suggestions ?? [];
}