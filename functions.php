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
        'url'   => HOME_URL
    ];

    if($defaultCategory['depth'] == 1) {
        $breadcrumb[] = [
            'label' => ucwords(strtolower($defaultCategory['text']['name'])),
           'url'   => '/' . sanitize_title($defaultCategory['text']['name'])
        ];
    }
    if (!empty($defaultCategory['ancestors'])) {
        foreach ($defaultCategory['ancestors'] as $ancestor) {
            if($ancestor['depth'] == 1) {
                $breadcrumb[] = [
                    'label' => ucwords(strtolower($ancestor['text']['name'])),
                    'url'   => '/' . sanitize_title($ancestor['text']['name'])
                ];
            }
        }
        foreach ($defaultCategory['ancestors'] as $ancestor) {
            if($ancestor['depth'] == 2) {
                $path = $ancestor['path'];
                $path = str_replace(['.1859.1986.', '.1859.1988.', '.1859.1989.'], '', $path); 
                $path = str_replace('.', '', $path);
                $breadcrumb[] = [
                    'label' => ucwords(strtolower($ancestor['text']['name'])),
                    'url'   => '/category/' . sanitize_title($ancestor['text']['name']) . '-' . $path
                ];
            }
        }
    }
    if(count($breadcrumb) < 3) {
        $path = $defaultCategory['path'];
        $path = str_replace(['.1859.1986.', '.1859.1988.', '.1859.1989.'], '', $path); 
        $path = str_replace('.', '', $path);
        $breadcrumb[] = [
            'label' => ucwords(strtolower($defaultCategory['text']['name'])),
            'url'   => '/category/' . sanitize_title($defaultCategory['text']['name']) . '-' . $path
        ];
    }

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

function getArtistBio($artistName, $performerId) {

    $bio = get_bio($performerId);

    if(!empty($bio)) {
        return $bio['bio'];
    }

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
    set_bio($performerId, $page['extract']);
    return trim($page['extract'] ?? '');
}

function set_bio($performerId, $bio, $mysqli = MYSQLI) {

    $stmt = $mysqli->prepare("
        INSERT INTO bios (performerId, bio)
        VALUES (?, ?)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ds", $performerId, $bio);

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}

function get_bio($performerId, $mysqli = MYSQLI) {

    $stmt = $mysqli->prepare("
        SELECT bio FROM bios WHERE performerId = ? LIMIT 1
    ");

    $stmt->bind_param("d", $performerId);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $result ?? null;
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
        'perPage'   => $limit,
        'sort' => '-salesRank',
        'salesRankOptions' => '{"interval":"week","metric":"ticketVolume"}'
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
        'sort' => '-salesRank', 
        'salesRankOptions' => '{"interval":"day","metric":"ticketVolume"}', 
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

    $params = [
        'q' => $q,
        'performersRequested' => 5,
        'venuesRequested' => 5,
        'citiesRequested' => 5
    ];

    $url = BASE_URL . '/catalog/v2/suggest?' . http_build_query($params);

    $accessToken = getTnAccessToken();

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);

    return $data;
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

function getEmptySuggestionResponse() {
    return [
        'performers' => [
            'totalResultCount' => 0,
            'results' => []
        ],
        'venues' => [
            'totalResultCount' => 0,
            'results' => []
        ],
        'cities' => [
            'totalResultCount' => 0,
            'results' => []
        ]
    ];
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

function getLoadMoreEvents($params = []) {

    $accessToken = getTnAccessToken();

    $url = BASE_URL . '/catalog/v2/events';

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

    $params = [
        'filter' => "date/date ge $today and contains(defaultCategory/path,'$rootPath')",
        'perPage' => $limit
    ];

    if ($type === 'll') {
        $lat = floatval($loc1);
        $lng = floatval($loc2);
        $params['geoFilter'] = sprintf('nearby(%F,%F,50mi)', $lat, $lng);
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

	$festivalPath = ".1859.1986.1877.";
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

    $festivalPath = ".1859.1986.1877.";
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

    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
    $clean = trim($slug, '-');
    $key  = "{$type}/{$clean}.webp";

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

function getCategoryFallbackImage($defaultCategory, $tab = '') {

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

    if(($slug == 'OTHER' || empty($slug)) && !empty($tab)) {
        $slug = $tab;
    }

    return AWS_CDN_URL . 'categories/' . strtolower($slug) . '.jpg';
}

function getEventImage($artist, $defaultCategory, $event, $tab) {

    $storedEvent = getStoredImageUrl($event, 'events');
    if ($storedEvent) return $storedEvent;

    $eventImage = getWikimediaImage($event);
    if ($eventImage) {
        return processAndStoreImage($eventImage, $event, 'events');
    }

    if ($artist) {
        $cat = '';
        $subcat = '';
        if($defaultCategory['depth'] == 1) {
            $cat = strtolower($defaultCategory['text']['name']);
        }
        if($defaultCategory['depth'] == 2) {
            $subcat = $defaultCategory['text']['name'];
        }
        if(empty($cat) || empty($subcat)) {
            if (!empty($defaultCategory['ancestors'])) {
                if(empty($cat)) {
                    foreach ($defaultCategory['ancestors'] as $ancestor) {
                        if($ancestor['depth'] == 1) {
                            $cat = strtolower($ancestor['text']['name']);
                        }
                    }
                }
                if(empty($subcat)) {
                    foreach ($defaultCategory['ancestors'] as $ancestor) {
                        if($ancestor['depth'] == 2) {
                            $cat = $ancestor['text']['name'];
                        }
                    }
                }
            }
        }
        $storedArtist = getStoredImageUrl($artist, 'artists');
        if ($storedArtist) return $storedArtist;

        $artistImage = getWikimediaImage($artist);
        if ($artistImage) {
            return processAndStoreImage($artistImage, $artist, 'artists');
        }else{
            $image = getCorrectKGEntity($artist, $cat, $subcat);
            if($image) {
                return processAndStoreImage($image, $artist, 'artists');
            }
        }
    }

    return getCategoryFallbackImage($defaultCategory, $cat);
}

function getArtistImage($artist, $defaultCategory) {

    if (!$artist) return '';

    $cat = '';
    $subcat = '';
    if($defaultCategory['depth'] == 1) {
        $cat = strtolower($defaultCategory['text']['name']);
    }
    if($defaultCategory['depth'] == 2) {
        $subcat = $defaultCategory['text']['name'];
    }
    if(empty($cat) || empty($subcat)) {
        if (!empty($defaultCategory['ancestors'])) {
            if(empty($cat)) {
                foreach ($defaultCategory['ancestors'] as $ancestor) {
                    if($ancestor['depth'] == 1) {
                        $cat = strtolower($ancestor['text']['name']);
                    }
                }
            }
            if(empty($subcat)) {
                foreach ($defaultCategory['ancestors'] as $ancestor) {
                    if($ancestor['depth'] == 2) {
                        $cat = $ancestor['text']['name'];
                    }
                }
            }
        }
    }

    $storedArtist = getStoredImageUrl($artist, 'artists');
    if ($storedArtist) return $storedArtist;

    $artistImage = getWikimediaImage($artist);
    if ($artistImage) {
        return processAndStoreImage($artistImage, $artist, 'artists');
    }else{        
        $image = getCorrectKGEntity($artist, $cat, $subcat);
        if($image) {
            return processAndStoreImage($image, $artist, 'artists');
        }
    }

    return getCategoryFallbackImage($defaultCategory, $cat);
}

function getVenueImage($venue) {

    if (!$venue) return '';

    $storedVenue = getStoredImageUrl($venue, 'venues');
    if ($storedVenue) return $storedVenue;

    $venueImage = getWikimediaImage($venue);
    if ($venueImage) {
        return processAndStoreImage($venueImage, $venue, 'venues');
    }else{
        $results = openverse_search($venue);
        $image = pickBestImage($results, $venue);
        if($image) {
            return processAndStoreImage($image, $venue, 'venues');
        }
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
        'filter' => "contains(text/name,'$q')",
        'perPage' => 10
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

function displayPHPErrors() {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

function getTnCityEvents($cityId = 0, $params = []) {

    $today = date('Y-m-d');
    if ($cityId > 0) {
        $params['filter'] = "city/id eq $cityId and date/date ge $today";
    }

    return tnRequest('/catalog/v2/events/', $params);
}

function getTnCityEventsCount($cityId = 0, $params = []) {
    
    $today = date('Y-m-d');
    if ($cityId > 0) {
        $params['filter'] = "city/id eq $cityId and date/date ge $today";
    }

    $params['page']    = 1;
    $params['perPage'] = 500;

    $json = tnRequest('/catalog/v2/events/', $params);

    $count = (int) ($json['count'] ?? 0);

    return $count;
}

function getTnVenueEvents($venueId = 0, $params = []) {

    $today = date('Y-m-d');
    if ($venueId > 0) {
        $params['filter'] = "venue/id eq $venueId and date/date ge $today";
    }

    return tnRequest('/catalog/v2/events/', $params);
}

function getTnVenueEventsCount($venueId = 0, $params = []) {
    
    $today = date('Y-m-d');
    if ($venueId > 0) {
        $params['filter'] = "venue/id eq $venueId and date/date ge $today";
    }

    $params['page']    = 1;
    $params['perPage'] = 500;

    $json = tnRequest('/catalog/v2/events/', $params);

    $count = (int) ($json['count'] ?? 0);

    return $count;
}

function getTopFestivalPerformers() {

    $params = [
        'q' => 'festival',
        'performersRequested' => 8,
        'venuesRequested' => 0,
        'citiesRequested' => 0
    ];

    $url = BASE_URL . '/catalog/v2/suggest?' . http_build_query($params);

    $data = tnCurlRequest($url);

    return $data['performers']['results'];
}

function createSlug($name, $id) {
    $slug = strtolower($name . '-' . $id);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    return $slug;
}

function getTnCityById($cityId) {
    $endpoint = "/catalog/v2/cities/" . (int) $cityId;
    return tnRequest($endpoint);
}

function getTnVenueById($venueId) {
    $endpoint = "/catalog/v2/venues/" . (int) $venueId;
    return tnRequest($endpoint);
}

function getTnCatEvents($catId = 0, $params = []) {

    $today = date('Y-m-d');
    if ($catId > 0) {
        $params['filter'] = "contains(defaultCategory/path, '$catId') and date/date ge $today";
    }

    return tnRequest('/catalog/v2/events/', $params);
}

function getTnCatEventsCount($catId = 0, $params = []) {
    
    $today = date('Y-m-d');
    if ($catId > 0) {
        $params['filter'] = "contains(defaultCategory/path, '$catId') and date/date ge $today";
    }

    $params['page']    = 1;
    $params['perPage'] = 500;

    $json = tnRequest('/catalog/v2/events/', $params);

    $count = (int) ($json['count'] ?? 0);

    return $count;
}

function getTnCatById($catId) {
    $params['filter'] = "contains(path, '$catId') and depth eq 2";
    $params['perPage'] = 1;
    return tnRequest("/catalog/v2/categories/", $params);
}

function openverse_search($q) {

    $url = "https://api.openverse.engineering/v1/images?q=". urlencode($q) . "&page_size=10";

    $response = file_get_contents($url);

    if (!$response) return [];

    $data = json_decode($response, true);

    return $data['results'] ?? [];
}

function pickBestImage($results, $name) {

    $name = strtolower(str_replace(' ', '', $name));

    foreach ($results as $img) {
        $tags  = array_map(function($t) {
            return strtolower($t['name']);
        }, $img['tags'] ?? []);

        if(!empty($tags) && in_array($name, $tags)) {
            return $img['thumbnail'];
        }
    }

    return $results[0]['thumbnail'] ?? '';
}

function getTeamImage($team, $cat, $subcat) {
    $cacheKeyBase = 'team|' . $team;
    $imageCacheKey = 'so_img_' . md5($cacheKeyBase);
    $img = get_image($imageCacheKey);
    
    if($img) {
        return $img; 
    }

    $image = '';
    $results = openverse_search($team);
    if(!empty($results)) {
        foreach($results as $res) {
            if(!empty($res['tags'])) {
                foreach($res['tags'] as $tag) {
                    if($tag['name'] == $cat || $tag['name'] == $subcat) {
                        $image = $res['thumbnail'];
                    }
                }
            }
        }
    }
    if($image) {
        $imageUrl = processAndStoreImage($image, $team, 'artistteams');
        set_image($imageCacheKey, $imageUrl);
        return $imageUrl;
    }
    return AWS_CDN_URL . 'categories/'.$subcat.'.jpg';
}

function set_image($imageCacheKey, $imageUrl, $mysqli = MYSQLI) {

    if (empty($imageCacheKey) || empty($imageUrl)) {
        return false;
    }

    $stmt = $mysqli->prepare("
        INSERT INTO images (imgkey, url)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE url = VALUES(url)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $imageCacheKey, $imageUrl);

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}

function get_image($imageCacheKey, $mysqli = MYSQLI) {

    $stmt = $mysqli->prepare("
        SELECT url FROM images WHERE imgkey = ? LIMIT 1
    ");

    $stmt->bind_param("s", $imageCacheKey);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $result['url'] ?? null;
}

function set_keyword($keyword, $results, $mysqli = MYSQLI) {

    if (empty($keyword) || empty($results)) {
        return false;
    }

    $stmt = $mysqli->prepare("
        INSERT INTO keywords (keyword, results)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE results = VALUES(results)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $keyword, $results);

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}

function get_keyword($keyword, $mysqli = MYSQLI) {

    $stmt = $mysqli->prepare("
        SELECT results FROM keywords WHERE keyword = ? LIMIT 1
    ");

    $stmt->bind_param("s", $keyword);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $result['results'] ?? null;
}

$concertsKeywords = [
    "50s-60s-era" => ['classic','oldies','retro','vintage music'],
    "alternative" => ['alternative','indie','rock band'],
    "bluegrass" => ['bluegrass','acoustic','folk band'],
    "classical" => ['classical','orchestra','composer','symphony'],
    "comedy" => ['comedian','stand-up','comedy'],
    "country-folk" => ['country','folk','singer'],
    "festival-tour" => ['festival','music festival','tour'],
    "hard-rock-metal" => ['rock','metal','heavy metal','band'],
    "holiday" => ['holiday show','christmas music'],
    "jazz-blues" => ['jazz','blues','musician'],
    "las-vegas-shows" => ['las vegas show','residency','live show'],
    "latin" => ['latin music','reggaeton','latin artist'],
    "new-age" => ['new age','instrumental','ambient'],
    "other" => ['music','artist'],
    "pop-rock" => ['pop','rock','band','artist'],
    "rnb-soul" => ['rnb','soul','singer'],
    "rap-hip-hop" => ['rap','hip hop','rapper'],
    "reggae-reggaeton" => ['reggae','reggaeton'],
    "religious" => ['gospel','christian','worship'],
    "techno-electronic" => ['dj','edm','electronic'],
    "world" => ['world music','international artist'],
    "performance-series" => ['live performance','series'],
    "children-family" => ['kids show','family show']
];

$sportsKeywords = [
    "baseball" => ['baseball','mlb','pitcher','batter','team'],
    "basketball" => ['basketball','nba','player','dunk'],
    "boxing" => ['boxing','boxer','fight','ring'],
    "cricket" => ['cricket','batsman','bowler','innings'],
    "football" => ['football','nfl','quarterback','touchdown'],
    "golf" => ['golf','golfer','pga','tournament'],
    "gymnastics" => ['gymnastics','gymnast','olympic'],
    "hockey" => ['hockey','nhl','ice hockey','goalie'],
    "lacrosse" => ['lacrosse','team','match'],
    "olympics" => ['olympics','olympic athlete'],
    "other" => ['sports','athlete'],
    "racing" => ['racing','nascar','formula','driver'],
    "rodeo" => ['rodeo','bull riding','cowboy'],
    "rugby" => ['rugby','team','match'],
    "skating" => ['skating','figure skating','ice skating'],
    "soccer" => ['soccer','football','fifa','goal'],
    "tennis" => ['tennis','player','grand slam'],
    "volleyball" => ['volleyball','team','match'],
    "wrestling" => ['wrestling','wwe','wrestler'],
    "mixed-martial-arts" => ['mma','ufc','fighter','fight'],
    "softball" => ['softball','team','pitcher']
];

$theaterKeywords = [
    "ballet" => ['ballet','dance','performance','company'],
    "broadway" => ['broadway','theatre','musical','stage'],
    "children-family" => ['kids show','family show','children theatre'],
    "dance" => ['dance','dance performance','choreography'],
    "las-vegas" => ['las vegas show','residency','live show'],
    "musical-play" => ['musical','play','theatre','stage show'],
    "off-broadway" => ['off broadway','theatre','play'],
    "opera" => ['opera','opera singer','classical performance'],
    "other" => ['theatre','performance'],
    "cirque-du-soleil" => ['cirque','acrobatics','circus','performance'],
    "west-end" => ['west end','london theatre','stage'],
    "festival" => ['theatre festival','performance festival']
];

$festivalKeywords = [
    "festival-tour" => ['festival','music festival','tour'],
];

function kgSlugify(string $value) {
    $value = strtolower(trim($value));
    $value = preg_replace('/&/', 'and', $value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim($value, '-');
}

function getKgKeywordsByCategory($category, $subcategory) {

    global $concertsKeywords, $sportsKeywords, $theaterKeywords, $festivalKeywords;

    $category = strtolower(trim($category));
    $slug = kgSlugify($subcategory);

    if ($category === 'concerts') {
        return $concertsKeywords[$slug] ?? [];
    }

    if ($category === 'sports') {
        return $sportsKeywords[$slug] ?? [];
    }

    if ($category === 'theater') {
        return $theaterKeywords[$slug] ?? [];
    }

    if ($category === 'festival') {
        return $theaterKeywords[$slug] ?? [];
    }

    return [];
}

function getWikiTitle($wikiUrl) {
    if (!$wikiUrl) return null;

    $path = parse_url($wikiUrl, PHP_URL_PATH);
    $title = basename($path);

    if (!$title) return null;

    $title = urldecode($title);
    $title = str_replace('_', ' ', $title);

    return $title;
}

function getCorrectKGEntity($performerName, $category, $subcategory, $apiKey = GKGSAPI_KEY) {
    $keywords = getKgKeywordsByCategory($category, $subcategory);

    $url = 'https://kgsearch.googleapis.com/v1/entities:search?' . http_build_query([
        'query' => $performerName,
        'limit' => 10,
        'key'   => $apiKey
    ]);

    $response = @file_get_contents($url);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    if (empty($data['itemListElement']) || !is_array($data['itemListElement'])) {
        return null;
    }

    foreach ($data['itemListElement'] as $item) {
        if (empty($item['result']) || !is_array($item['result'])) {
            continue;
        }

        $entity = $item['result']; 
        $description = strtolower($entity['description'] ?? '');
        $types = array_map('strtolower', $entity['@type'] ?? []);
        
        foreach($keywords as $word) {
            $word = strtolower($word);
            if ($word !== '' && strpos($description, $word) !== false && $entity['name'] == $performerName) {                                
                if(!empty($entity['image']['contentUrl'])) {
                    $image = $entity['image']['contentUrl'];
                }else{
                    $wikiUrl = $entity['detailedDescription']['url'];
                    $title = getWikiTitle($wikiUrl);
                    $image = getWikimediaImage($title);
                }
                return $image;             
            }
        }  
    }
}