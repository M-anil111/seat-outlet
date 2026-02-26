<?php 

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

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

/**
 * Retrieve TicketNetwork access token.
 *
 * Uses a static per-request cache to avoid multiple token calls in a single request.
 */
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

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception('TicketNetwork API error. HTTP ' . $httpCode . ' Response: ' . $response);
    }

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

    // Home (optional)
    $breadcrumb[] = [
        'label' => 'Home',
        'url'   => '/'
    ];

    // Ancestors (parent categories)
    if (!empty($defaultCategory['ancestors'])) {
        foreach ($defaultCategory['ancestors'] as $ancestor) {
            $breadcrumb[] = [
                'label' => ucwords(strtolower($ancestor['text']['name'])),
                'url'   => '/category' . $ancestor['path']
            ];
        }
    }

    // Current category
    $breadcrumb[] = [
        'label' => ucwords(strtolower($defaultCategory['text']['name'])),
        'url'   => '/category' . $defaultCategory['path']
    ];

    return $breadcrumb;
}

function getRelatedPerformers($categoryPath, $currentPerformerId, $limit = 8) {
    $categoryPathEsc = tnEscapeFilterValue($categoryPath);
    $params = [
        'filter' => "defaultCategory/path eq '$categoryPathEsc'",
        'sort'   => 'salesRank',
        'page'   => 1,
        'perPage'=> 20
    ];

    $data = tnRequest('/catalog/v2/performers', $params);

    // Remove current performer
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
            'filter'  => "contains(city/text/name,'$keyword') or contains(stateProvince/text/abbr,'$stateKeyword') or contains(stateProvince/text/name,'$keyword')",
            'rollup'  => 'city',
            'perPage' => 100,
            'page'   => 1
        ];
        $output = tnRequest('/catalog/v2/events', $params);
        $output['searchType'] = 'city';
    }   

    return $output;
}

function getTnEvents($params = []) {
    // Base endpoint (IMPORTANT: no /search)
    $url = BASE_URL . '/catalog/v2/events';

    // Default params
    $defaultParams = [
        'page'    => 1,
        'perPage' => 20,
    ];

    $today = date('Y-m-d');
    $params['filter'] = "date/date ge $today";

    $params = array_merge($defaultParams, $params);

    return tnRequest('/catalog/v2/events', $params);
}

/**
 * Escape single quotes for use inside TicketNetwork filter strings.
 */
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

function getWikipediaPageTitle($artistName) {

    $url = 'https://en.wikipedia.org/w/api.php'
         . '?action=query'
         . '&list=search'
         . '&srsearch=' . urlencode($artistName)
         . '&srlimit=1'
         . '&format=json';

    $response = curlGet($url);
    if (!$response) return '';

    $data = json_decode($response, true);
    return $data['query']['search'][0]['title'] ?? '';
}

function getWikipediaArtistImage($pageTitle) {

    $url = 'https://en.wikipedia.org/w/api.php'
         . '?action=query'
         . '&titles=' . urlencode($pageTitle)
         . '&prop=pageimages'
         . '&piprop=thumbnail|original'
         . '&pithumbsize=800'
         . '&format=json';

    $response = curlGet($url);
    if (!$response) return '';

    $data = json_decode($response, true);
    $pages = $data['query']['pages'] ?? [];
    $page  = reset($pages);

    // Prefer original image
    if (!empty($page['original']['source'])) {
        return $page['original']['source'];
    }

    return $page['thumbnail']['source'] ?? '';
}

function getArtistImageFromWikimedia($artistName) {

    $artistName = trim(preg_replace('/\s*\(.*?\)|\s*feat\.?.*/i', '', $artistName));

    $slug = strtolower(str_replace(' ', '-', $artistName));
    $key  = "artists/{$slug}.webp";

    // 1️⃣ Check if already exists in R2
    if (s3ObjectExists($key)) {
        return getS3PublicUrl($key);
    }

    // 2️⃣ If not, fetch from Wikipedia
    $pageTitle = getWikipediaPageTitle($artistName);
    if (!$pageTitle) return '';

    $imageUrl = getWikipediaArtistImage($pageTitle);
    if (!$imageUrl) return '';

    $imageContent = downloadImage($imageUrl);
    if (!$imageContent) return '';

    $webpImage = resizeAndConvertToWebP($imageContent, 800, 80);
    if (!$webpImage) return '';

    // 3️⃣ Upload to R2
    uploadImageToS3($webpImage, $key, 'image/webp');

    return getS3PublicUrl($key);
}


function resizeAndConvertToWebP($imageContent, $maxWidth = 800, $quality = 80) {

    $source = imagecreatefromstring($imageContent);
    if (!$source) return false;

    $width  = imagesx($source);
    $height = imagesy($source);

    // If image smaller than max width, keep original size
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

function getArtistBioFromWikipedia($artistName) {

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

 /*
    Detect input type:
    1. ZIP (numeric 5 digit)
    2. City + State (array with city/state)
    3. Lat/Long (array with lat/lng)
*/
function getLocationFromInput($input, $mysqli = MYSQLI) {

    $type = null;
    $zip = $city = $state = $lat = $lng = null;

    // -------------------------
    // 1️⃣ Detect Input Type
    // -------------------------

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

    // -------------------------
    // 2️⃣ Check Database First
    // -------------------------

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

    // -------------------------
    // 3️⃣ Call TicketNetwork API
    // -------------------------

    $accessToken = getTnAccessToken();
    $params = ['perPage' => 1];

    if ($type === 'zip') {
        $params['filter'] = "code eq '$zip'";
    }

    if ($type === 'city') {
        $params['filter'] = "city/text/name eq '$city' and stateProvince/text/abbr eq '$state'";
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

    // -------------------------
    // 4️⃣ Insert Into DB
    // -------------------------

    $stmt = $mysqli->prepare("
        INSERT IGNORE INTO locations (city, state, country, zip, latitude, longitude)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("ssssss", $city, $state, $country, $zip, $latitude, $longitude);
    $stmt->execute();
    $stmt->close();

    // -------------------------
    // 5️⃣ Fetch Inserted Record
    // -------------------------

    $stmt = $mysqli->prepare("SELECT * FROM locations WHERE zip = ? LIMIT 1");
    $stmt->bind_param("s", $zip);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result;
}

function getWikipediaVenueImage($venueName) {

    $venueName = trim($venueName) . ' venue';

    $params = [
        'action'      => 'query',
        'format'      => 'json',
        'generator'   => 'search',
        'gsrsearch'   => $venueName,
        'gsrlimit'    => 1,
        'gsrnamespace'=> 6, // IMPORTANT: File namespace
        'prop'        => 'imageinfo',
        'iiprop'      => 'url',
    ];

    $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query($params);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'SeatOutletBot/1.0 (contact@seatoutlet.com)' // REQUIRED by Wikimedia policy
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return '';
    }

    curl_close($ch);

    $data = json_decode($response, true);
    
    if (!empty($data['query']['pages'])) {
        $page = array_values($data['query']['pages'])[0];

        if (!empty($page['imageinfo'][0]['url'])) {
            return $page['imageinfo'][0]['url'];
        }
    }

    return '';
}

function getVenueImageFromWikimedia($venueName) {

    $slug = strtolower(str_replace(' ', '-', $venueName));
    $key  = "venues/{$slug}.webp";

    // 1️⃣ Check if already exists in R2
    if (s3ObjectExists($key)) {
        return getS3PublicUrl($key);
    }

    $imageUrl = getWikipediaVenueImage($venueName);
    if (!$imageUrl) return '';

    $imageContent = downloadImage($imageUrl);
    if (!$imageContent) return '';

    $webpImage = resizeAndConvertToWebP($imageContent, 800, 80);
    if (!$webpImage) return '';

    // 3️⃣ Upload to R2
    uploadImageToS3($webpImage, $key, 'image/webp');

    return getS3PublicUrl($key);
}

function getNearbyVenues($latitude = '', $longitude = '') {

    $accessToken = getTnAccessToken();
    $location = getUserLocationFromCookie();
    if(!empty($latitude)) { 
        $lat = $latitude;
    }else{
        $lat = $location['latitude'];
    }
    if(!empty($longitude)) {
        $lng = $longitude;
    }else{
        $lng = $location['longitude'];
    }
    $radius = '50mi';
    
    $params = [
        'geoFilter' => "nearby($lat, $lng, $radius)",
        'perPage'   => 10
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

function getNearbyCities($latitude = '', $longitude = '') {

    $accessToken = getTnAccessToken();
    $location = getUserLocationFromCookie();
    if(!empty($latitude)) { 
        $lat = $latitude;
    }else{
        $lat = $location['latitude'];
    }
    if(!empty($longitude)) {
        $lng = $longitude;
    }else{
        $lng = $location['longitude'];
    }
    $radius = '50mi';
    
    $params = [
        'q'         => '*',
        'geoFilter' => "nearby($lat, $lng, $radius)",
        'numberOfSuggestions'   => 15
    ];

    $url = BASE_URL . '/catalog/v2/cities/suggest/?' . http_build_query($params);

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

function getLocationDataByLatLng($lat, $lng) {
    $latitude = number_format($lat, 4);
    $longitude = number_format($lng, 4);
    $mysqli = MYSQLI;
    $stmt = $mysqli->prepare("SELECT city, short_state, country, postal_code, latitude, longitude FROM postal_codes_worldwide WHERE latitude = ? AND longitude = ? LIMIT 1");
    $stmt->bind_param("ss", $latitude, $longitude);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($result) return $result;
}

function getUserLocationFromCookie() {

    if (!empty($_COOKIE['so_location'])) {

        $data = json_decode(
            base64_decode($_COOKIE['so_location']),
            true
        );

        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            return $data;
        }
    }

    return null;
}

function getAllConcertsNestedCategories($limit = 8) {

    $accessToken = getTnAccessToken();

    $params = [
        'filter'  => "contains(path,'.1859.1986.') and depth eq 2 and _metadata/hasEvents eq true",
        'sort'    => '_metadata/eventCount',
        'perPage' => $limit
    ];

    $url = BASE_URL . '/catalog/v2/categories/?' . http_build_query($params);

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

    $names = [];

    if (!empty($data['results'])) {
        foreach ($data['results'] as $category) {
            $evtCount = $category['_metadata']['eventCount'];
            $name = $category['text']['name'];
            $names[] = [$evtCount, ucwords(strtolower($name)), $category['uriComponent']];
        }

        usort($names, function ($a, $b) {
            return $b[0] <=> $a[0]; // DESC
        });

    }

    return $names;
}

function getAllSportsNestedCategories($limit = 8) {

    $accessToken = getTnAccessToken();

    $params = [
        'filter'  => "contains(path,'.1859.1988.') and depth eq 2 and _metadata/hasEvents eq true",
        'sort'    => '_metadata/eventCount',
        'perPage' => $limit
    ];

    $url = BASE_URL . '/catalog/v2/categories/?' . http_build_query($params);

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

    $names = [];

    if (!empty($data['results'])) {
        foreach ($data['results'] as $category) {
            $evtCount = $category['_metadata']['eventCount'];
            $name = $category['text']['name'];
            $names[] = [$evtCount, ucwords(strtolower($name)), $category['uriComponent']];
        }

        usort($names, function ($a, $b) {
            return $b[0] <=> $a[0]; // DESC
        });

    }

    return $names;
}

function getAllTheaterNestedCategories($limit = 8) {

    $accessToken = getTnAccessToken();

    $params = [
        'filter'  => "contains(path,'.1859.1987.') and depth eq 2 and _metadata/hasEvents eq true",
        'sort'    => '_metadata/eventCount',
        'perPage' => $limit
    ];

    $url = BASE_URL . '/catalog/v2/categories/?' . http_build_query($params);

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

    $names = [];

    if (!empty($data['results'])) {
        foreach ($data['results'] as $category) {
            $evtCount = $category['_metadata']['eventCount'];
            $name = $category['text']['name'];
            $names[] = [$evtCount, ucwords(strtolower($name)), $category['uriComponent']];
        }

        usort($names, function ($a, $b) {
            return $b[0] <=> $a[0]; // DESC
        });

    }

    return $names;
}

function getAllFestivalsNestedCategories($limit = 8) {

    $accessToken = getTnAccessToken();

    $params = [
        'filter'  => "contains(path,'.1859.1989.') and depth eq 2 and _metadata/hasEvents eq true",
        'sort'    => '_metadata/eventCount',
        'perPage' => $limit
    ];

    $url = BASE_URL . '/catalog/v2/categories/?' . http_build_query($params);

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

    $names = [];

    if (!empty($data['results'])) {
        foreach ($data['results'] as $category) {
            $evtCount = $category['_metadata']['eventCount'];
            $name = $category['text']['name'];
            $names[] = [$evtCount, ucwords(strtolower($name)), $category['uriComponent']];
        }

        usort($names, function ($a, $b) {
            return $b[0] <=> $a[0]; // DESC
        });

    }

    return $names;
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

    $accessToken = getTnAccessToken();

    $results = [
        'artists' => [],
        'events'  => [],
        'venues'  => []
    ];

    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . $accessToken,
        'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
    ];

    /*
    ==========================
    ARTISTS
    ==========================
    */
    $artistParams = [
        'q'       => $q,
        'numberOfSuggestions' => 10
    ];

    $artistUrl = BASE_URL . '/catalog/v2/performers/suggest/?' . http_build_query($artistParams);

    $artistData = tnCurlRequest($artistUrl, $headers);

    if (!empty($artistData['results'])) {
        foreach ($artistData['results'] as $artist) {
            $results['artists'][] = [
                'id'   => $artist['id'] ?? '',
                'name' => $artist['name'] ?? '',
                'slug' => $artist['uriComponent'] ?? ''
            ];
        }
    }

    /*
    ==========================
    EVENTS
    ==========================
    */
    $eventParams = [
        'q'       => $q,
        'numberOfSuggestions' => 10
    ];

    $eventUrl = BASE_URL . '/catalog/v2/events/suggest/?' . http_build_query($eventParams);

    $eventData = tnCurlRequest($eventUrl, $headers);

    if (!empty($eventData['results'])) {
        foreach ($eventData['results'] as $event) {
            $results['events'][] = [
                'id'   => $event['id'] ?? '',
                'name' => $event['name'] ?? '',
                'date' => $event['date'] ?? '',
                'slug' => $event['uriComponent'] ?? ''
            ];
        }
    }

    /*
    ==========================
    VENUES
    ==========================
    */
    $venueParams = [
        'q'       => $q,
        'numberOfSuggestions' => 10
    ];

    $venueUrl = BASE_URL . '/catalog/v2/venues/suggest/?' . http_build_query($venueParams);

    $venueData = tnCurlRequest($venueUrl, $headers);

    if (!empty($venueData['results'])) {
        foreach ($venueData['results'] as $venue) {
            $results['venues'][] = [
                'id'    => $venue['id'] ?? '',
                'name'  => $venue['name'] ?? '',
                'city'  => $venue['city']['name'] ?? '',
                'state' => $venue['stateProvince']['abbr'] ?? '',
                'slug'  => $venue['uriComponent'] ?? ''
            ];
        }
    }

    return $results;
}

function tnCurlRequest($url, $headers) {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
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
            'filter'  => "date/date ge " . $today . " and contains(defaultCategory/path,'$catPath')",
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

function fetchLocationCategoryEvents($rootPath, $type, $loc1, $loc2) {
    $accessToken = getTnAccessToken();
    $rootPath = tnEscapeFilterValue($rootPath);
    if($type === 'll') {
        $lat = floatval($loc1);
        $lng = floatval($loc2);
        $params = [
            'filter' => "contains(defaultCategory/path,'$rootPath')",
            'geoFilter' => sprintf('nearby(%F,%F,50mi)', $lat, $lng),
            'perPage' => 12
        ];
    }elseif($type === 'cs') {
        $city  = ucfirst(strtolower($loc1));
        $state = strtoupper($loc2);
        $params = [
            'filter' => "contains(defaultCategory/path,'$rootPath') and city/text/name eq '$city' and stateProvince/text/abbr eq '$state'",
            'perPage' => 12
        ];
    }else{
        $params = [
            'filter' => "contains(defaultCategory/path,'$rootPath')",
            'perPage' => 12
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

function getTeamsByCategory($categorySlug) {

    $accessToken = getTnAccessToken();
    $location = getUserLocationFromCookie();   

    // Map category slug to TN category path
    $categoryPaths = [
        'NFL' => '.1859.1988.1879.1959.',   // example path (update as per your TN path)
        'NBA' => '.1859.1988.1865.1971.',
        'MLB' => '.1859.1988.1864.1969.',
        'NHL' => '.1859.1988.1883.1972.',
        'MLS' => '.1859.1988.1913.1970.',
    ];

    if (!isset($categoryPaths[$categorySlug])) {
        return [];
    }

    $params = [
        'filter'  => "contains(defaultCategory/path,'{$categoryPaths[$categorySlug]}') and _metadata/hasEvents eq true",
        'sort' => 'salesRank',
        'perPage' => 10,
        'page'    => 1
    ];

    if(!empty($location)) {
        $params['eventFilter'] = "country/alphaCode eq 'US'";        
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

    $data = json_decode($response, true);

    return $data['results'] ?? [];
}

function getEventImage($artist, $venue) {
    $artistImageUrl = getArtistImageFromWikimedia($artist);
    $venueImageUrl = getVenueImageFromWikimedia($venue);
    if(!empty($artistImageUrl)) {
        $imageUrl = $artistImageUrl;
    }elseif(!empty($venueImageUrl)) {
        $imageUrl = $venueImageUrl;
    }else{
        $imageUrl = HOME_URL . '/assets/placeholder.webp';
    }    
    $ext = pathinfo($imageUrl, PATHINFO_EXTENSION);
    if(in_array(strtolower($ext), ['pdf', 'tif'])) {
        $imageUrl = HOME_URL . '/assets/placeholder.webp';
    }
    return $imageUrl;
}

function getVenueImage($venue) {
    $venueImageUrl = getVenueImageFromWikimedia($venue);
    if(!empty($venueImageUrl)) {
        $imageUrl = $venueImageUrl;
    }else{
        $imageUrl = HOME_URL . '/assets/placeholder.webp';
    }
    $ext = pathinfo($imageUrl, PATHINFO_EXTENSION);
    if(in_array(strtolower($ext), ['pdf', 'tif'])) {
        $imageUrl = HOME_URL . '/assets/placeholder.webp';
    }
    return $imageUrl;
}