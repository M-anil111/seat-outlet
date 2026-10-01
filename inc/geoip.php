<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/*
|--------------------------------------------------------------------------
| Visitor geo-IP lookup (homepage "near you" default, newsletter country)
|--------------------------------------------------------------------------
| MaxMind GeoLite2-City, read locally with maxmind-db/reader (vendored).
| A lookup is a few microseconds against a memory-mapped file, so there is
| no network call, no rate limit and no per-IP cache to manage.
|
| Database file: GEOIP_DB_PATH (inc/constants.php), refreshed by
| cron/geoip-update.php with a MaxMind account ID + license key. Until the
| file exists, lookups return null and the site simply does not pre-select
| a location (the visitor can still pick one).
|
| GeoLite2 licence: attribution is required on the site (see footer.php)
| and the database must not be redistributed, which is why it lives
| outside the web root.
|
| The previous provider (ip-api.com, HTTP-only, non-commercial free tier)
| is gone. Set GEOIP_FALLBACK_IPAPI=1 only if you knowingly want it back
| while the MaxMind download is being set up.
*/

require_once __DIR__ . '/../vendor/autoload.php';

// Callers that don't load functions.php (ajax/get_ip_details.php,
// newsletter-email.php) still need the deploy-time env and the path default.
if (is_file(__DIR__ . '/env.local.php')) {
    require_once __DIR__ . '/env.local.php';
}

function geoIpDatabasePath() {
    if (defined('GEOIP_DB_PATH')) return GEOIP_DB_PATH;
    $env = getenv('GEOIP_DB_PATH');
    if ($env !== false && $env !== '') return $env;
    $home = getenv('HOME_PATH') ?: '/home/seatoutlet-beta/htdocs/beta.seatoutlet.com/';
    return dirname(rtrim($home, '/')) . '/geoip/GeoLite2-City.mmdb';
}

function geoIpReader() {
    static $reader = null;
    static $tried = false;
    if ($reader !== null || $tried) return $reader;
    $tried = true;
    $path = geoIpDatabasePath();
    if ($path === '' || !is_file($path)) {
        return null;
    }
    try {
        $reader = new \MaxMind\Db\Reader($path);
    } catch (\Throwable $e) {
        if (function_exists('\Sentry\captureMessage')) {
            \Sentry\captureMessage('GeoLite2 database could not be opened (' . $path . '): ' . $e->getMessage());
        }
        $reader = null;
    }
    return $reader;
}

/** @return array{city:string,state:string,country:string,lat:string,lng:string}|null */
function geoIpLookup($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return null;
    }

    $reader = geoIpReader();
    if ($reader !== null) {
        try {
            $rec = $reader->get($ip);
        } catch (\Throwable $e) {
            $rec = null;
        }
        if (!is_array($rec)) {
            return null;
        }
        $city    = (string) ($rec['city']['names']['en'] ?? '');
        $state   = (string) ($rec['subdivisions'][0]['iso_code'] ?? $rec['subdivisions'][0]['names']['en'] ?? '');
        $country = (string) ($rec['country']['names']['en'] ?? '');
        $lat     = isset($rec['location']['latitude'])  ? (string) $rec['location']['latitude']  : '';
        $lng     = isset($rec['location']['longitude']) ? (string) $rec['location']['longitude'] : '';
        if ($city === '' && $country === '') {
            return null;
        }
        return ['city' => $city, 'state' => $state, 'country' => $country, 'lat' => $lat, 'lng' => $lng];
    }

    if (getenv('GEOIP_FALLBACK_IPAPI') === '1') {
        return geoIpLookupIpApi($ip);
    }
    return null;
}

/** Legacy provider, opt-in only. Non-commercial terms, HTTP-only, rate limited. */
function geoIpLookupIpApi($ip) {
    $ch = curl_init('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,city,region,country,lat,lon');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 3]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = $body !== false && $code === 200 ? json_decode($body, true) : null;
    if (empty($data) || ($data['status'] ?? '') !== 'success') {
        return null;
    }
    return [
        'city'    => (string) ($data['city'] ?? ''),
        'state'   => (string) ($data['region'] ?? ''),
        'country' => (string) ($data['country'] ?? ''),
        'lat'     => (string) ($data['lat'] ?? ''),
        'lng'     => (string) ($data['lon'] ?? ''),
    ];
}
