<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Says where the site looks for the GeoLite2 database and whether it can read it.
 *
 *   php tools/geoip-check.php            show the path and the file state
 *   php tools/geoip-check.php 8.8.8.8    also look up an address (use a real visitor address to test)
 *
 * The footer's MaxMind credit is only the licence notice. The location feature needs the database FILE at this path
 * (default: a "geoip" folder next to the site folder, for example /home/seatoutlet-beta/htdocs/geoip/GeoLite2-City.mmdb),
 * or at the path in the GEOIP_DB_PATH setting.
 */
require_once __DIR__ . '/../inc/geoip.php';

$path = geoIpDatabasePath();
echo "Looking for: $path\n";
if (!is_file($path)) {
    echo "File: NOT FOUND. Put GeoLite2-City.mmdb there, set GEOIP_DB_PATH to where it is, or run cron/geoip-update.php with MAXMIND_ACCOUNT_ID and MAXMIND_LICENSE_KEY set.\n";
    exit(1);
}
echo 'File: found, ' . number_format(filesize($path)) . ' bytes, updated ' . date('Y-m-d H:i', filemtime($path)) . "\n";
echo 'Readable by this user: ' . (is_readable($path) ? 'yes' : 'NO (fix the file permissions)') . "\n";
$ip = $argv[1] ?? '';
if ($ip !== '') {
    $geo = geoIpLookup($ip);
    echo $geo ? 'Lookup ' . $ip . ': ' . json_encode($geo) . "\n" : "Lookup $ip: no result\n";
}
