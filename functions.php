<?php 

include 'constants.php';
require 'vendor/autoload.php';

use Predis\Client as PredisClient;

function redis() {
    static $redis = null;

    if ($redis === null) {
        $redis = new PredisClient([
            'scheme'   => 'tcp',
            'host'     => REDIS_HOST,
            'port'     => REDIS_PORT,
            'password' => REDIS_PASSWORD,
            'database' => 0,
        ]);
    }

    return $redis;
}



function getTnAccessToken() {

    $basicAuth = base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET);

    $ch = curl_init('https://key-manager.tn-apis.com/oauth2/token');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $basicAuth,
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'client_credentials'
            // scope is OPTIONAL here since Catalog is already subscribed
        ])
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (empty($data['access_token'])) {
        throw new Exception('Token error: ' . $response);
    }

    return $data['access_token'];
}

function tnRequest($endpoint, $params = []) {
    $accessToken = getTnAccessToken(); 
   
    $url = BASE_URL . $endpoint;

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            'X-Listing-Context: website-config-id=' . WEBSITE_CONFIG_ID
        ],
        CURLOPT_TIMEOUT => 30
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

    $accessToken = getTnAccessToken();
    
    $url = BASE_URL . '/catalog/v2/performers';

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
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('HTTP ' . $httpCode . ': ' . $response);
    }

    return json_decode($response, true);
}

function getTnPerformerEvents($performerId = 0, $params = []) {

    $accessToken = getTnAccessToken();

    if($performerId > 0) {
        $params['performerFilter'] = 'id eq ' . $performerId;
    }

    $today = date('Y-m-d');
    $params['filter'] = "date/date ge $today";
    
    $url = BASE_URL . '/catalog/v2/events/';
    
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
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('HTTP ' . $httpCode . ': ' . $response);
    }

    return json_decode($response, true);
}

function getTnPerformerEventsCount($performerId = 0, $params = [])
{
    $redis = redis();

    // Unique cache key
    $cacheKey = 'tn:performer:event_count:' . ($performerId ?: 'all');

    // Return cached value if exists
    if ($redis->exists($cacheKey)) {
        return (int) $redis->get($cacheKey);
    }

    $accessToken = getTnAccessToken();

    if ($performerId > 0) {
        $params['performerFilter'] = 'id eq ' . $performerId;
    }

    $today = date('Y-m-d');
    $params['filter']  = "date/date ge $today";
    $params['page']    = 1;
    $params['perPage'] = 500;

    $url = BASE_URL . '/catalog/v2/events/?' . http_build_query($params);

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

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('HTTP ' . $httpCode . ': ' . $response);
    }

    $json = json_decode($response, true);

    $count = (int) ($json['count'] ?? 0);

    // Cache for 30 minutes (adjust if needed)
    $redis->setex($cacheKey, 1800, $count);

    return $count;
}

/*function getTnPerformerEventsCount($performerId = 0, $params = []) {

    $accessToken = getTnAccessToken();

    if($performerId > 0) {
        $params['performerFilter'] = 'id eq ' . $performerId;
    }

    $today = date('Y-m-d');
    $params['filter'] = "date/date ge $today";
    $params['page'] = 1;
    $params['perPage'] = 500;
    
    $url = BASE_URL . '/catalog/v2/events/';
    
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
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('HTTP ' . $httpCode . ': ' . $response);
    }

    $json_decode = json_decode($response, true);

    return $json_decode['count'];
}*/


function getTnPerformerById($performerId) {

    $accessToken = getTnAccessToken();

    $url = BASE_URL . "/catalog/v2/performers/" . intval($performerId);

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

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('HTTP ' . $httpCode . ': ' . $response);
    }

    return json_decode($response, true);
}

function getTnEventById($eventId) {

    $accessToken = getTnAccessToken();

    $url = BASE_URL . "/catalog/v2/events/" . intval($eventId);

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

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('HTTP ' . $httpCode . ': ' . $response);
    }

    return json_decode($response, true);
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

    $accessToken = getTnAccessToken();

    $params = [
        'filter' => "defaultCategory/path eq '$categoryPath'",
        'sort'   => 'salesRank',
        'page'   => 1,
        'perPage'=> 20
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

    // Remove current performer
    $related = array_filter($data['results'], function ($p) use ($currentPerformerId) {
        return $p['id'] != $currentPerformerId;
    });

    return array_slice($related, 0, $limit);
}

function getLocationSuggestions($keyword) {

    $accessToken = getTnAccessToken();
    
    $stateKeyword = strtoupper($keyword);
    $params = [
        'filter'  => "contains(city/text/name,'$keyword') or contains(stateProvince/text/abbr,'$stateKeyword') or contains(stateProvince/text/name,'$keyword')",
        'rollup'  => 'city',
        'perPage' => 100
    ];
    
    $url = BASE_URL . '/catalog/v2/events?' . http_build_query($params);

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

    return json_decode($response, true);
}

function getTnEvents($params = []) {

    $accessToken = getTnAccessToken();

    // Base endpoint (IMPORTANT: no /search)
    $url = BASE_URL . '/catalog/v2/events';

    // Default params
    $defaultParams = [
        'page'    => 1,
        'perPage' => 20,
    ];

    $params = array_merge($defaultParams, $params);

    $url .= '?' . http_build_query($params);

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

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('HTTP ' . $httpCode . ': ' . $response);
    }

    return json_decode($response, true);
}

function sanitize_title($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}
