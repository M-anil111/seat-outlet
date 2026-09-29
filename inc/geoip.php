<?php
/*
|--------------------------------------------------------------------------
| Visitor geo-IP lookup (homepage "near you" default, newsletter country)
|--------------------------------------------------------------------------
| Both callers used to hit ip-api.com live on every visit with no cache: one
| request per page view per visitor until a location cookie existed, plus
| one per newsletter signup. Results are now cached per IP for 24h (keyed
| by a hash, the IP itself is not written to disk) and the call is bounded.
|
| ip-api.com's free endpoint is HTTP-only and its terms limit it to
| non-commercial use; see the PR notes for the recommended replacement
| (MaxMind GeoLite2 locally, or Cloudflare's geolocation headers).
*/

function geoIpCacheFile($ip) {
    $dir = rtrim(sys_get_temp_dir(), '/') . '/seatoutlet_geoip';
    if (!is_dir($dir)) { @mkdir($dir, 0700, true); }
    return $dir . '/' . hash('sha256', 'geo|' . $ip) . '.json';
}

/** @return array{city:string,state:string,country:string,lat:string,lng:string}|null */
function geoIpLookup($ip, $ttl = 86400) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return null;
    }
    $file = geoIpCacheFile($ip);
    if (is_file($file) && (time() - filemtime($file)) < $ttl) {
        $cached = json_decode((string) file_get_contents($file), true);
        return is_array($cached) ? ($cached ?: null) : null;
    }

    $ch = curl_init('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,city,region,country,lat,lon');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 3]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = $body !== false && $code === 200 ? json_decode($body, true) : null;
    if (empty($data) || ($data['status'] ?? '') !== 'success') {
        // Cache the miss briefly so a rate-limited hour doesn't retry per view.
        if ($code === 429) { @file_put_contents($file, '[]'); }
        return null;
    }
    $out = [
        'city'    => (string) ($data['city'] ?? ''),
        'state'   => (string) ($data['region'] ?? ''),
        'country' => (string) ($data['country'] ?? ''),
        'lat'     => (string) ($data['lat'] ?? ''),
        'lng'     => (string) ($data['lon'] ?? ''),
    ];
    @file_put_contents($file, json_encode($out));
    return $out;
}
