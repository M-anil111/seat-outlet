<?php 

include 'constants.php';

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

    if($performerId > 0) $params['performerFilter'] = 'id eq ' . $performerId;
   
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

