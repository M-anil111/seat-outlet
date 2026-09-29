<?php
/**
 * Download / refresh the MaxMind GeoLite2-City database used by inc/geoip.php.
 *
 * Needs MAXMIND_ACCOUNT_ID and MAXMIND_LICENSE_KEY in the environment (free
 * MaxMind account; the GeoLite2 EULA requires attribution on the site, which
 * footer.php carries, and forbids redistributing the file, which is why it
 * lives outside the web root at GEOIP_DB_PATH).
 *
 * MaxMind publishes GeoLite2 updates twice a week; run this weekly:
 *   php cron/geoip-update.php
 */
require_once __DIR__ . '/../functions.php';

$accountId  = getenv('MAXMIND_ACCOUNT_ID');
$licenseKey = getenv('MAXMIND_LICENSE_KEY');
$target     = GEOIP_DB_PATH;

if ($accountId === false || $accountId === '' || $licenseKey === false || $licenseKey === '') {
    fwrite(STDERR, "geoip-update: MAXMIND_ACCOUNT_ID / MAXMIND_LICENSE_KEY are not set; nothing downloaded.\n");
    exit(1);
}

$dir = dirname($target);
if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
    fwrite(STDERR, "geoip-update: cannot create $dir\n");
    exit(1);
}

// Skip if the current file is fresh (MaxMind asks clients not to download
// more than necessary).
if (is_file($target) && (time() - filemtime($target)) < 3 * 86400 && empty($argv[1])) {
    echo "geoip-update: database is " . round((time() - filemtime($target)) / 3600) . "h old, skipping (pass 'force' to override)\n";
    exit(0);
}

$tmpTgz = $dir . '/GeoLite2-City.' . uniqid('dl_', true) . '.tar.gz';
$ch = curl_init('https://download.maxmind.com/geoip/databases/GeoLite2-City/download?suffix=tar.gz');
$fh = fopen($tmpTgz, 'wb');
curl_setopt_array($ch, [
    CURLOPT_FILE           => $fh,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERPWD        => $accountId . ':' . $licenseKey,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 300,
    CURLOPT_FAILONERROR    => true,
]);
$ok   = curl_exec($ch);
$err  = curl_error($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($fh);

if (!$ok || $code !== 200) {
    @unlink($tmpTgz);
    fwrite(STDERR, "geoip-update: download failed (HTTP $code) $err\n");
    exit(1);
}

// The archive holds GeoLite2-City_YYYYMMDD/GeoLite2-City.mmdb (+ licence texts).
try {
    $tar = new PharData($tmpTgz);
    $mmdbEntry = null;
    foreach (new RecursiveIteratorIterator($tar) as $file) {
        if (substr($file->getFilename(), -5) === '.mmdb') { $mmdbEntry = $file; break; }
    }
    if (!$mmdbEntry) {
        throw new RuntimeException('no .mmdb entry in archive');
    }
    $tmpDb = $target . '.' . uniqid('new_', true);
    if (@file_put_contents($tmpDb, file_get_contents($mmdbEntry->getPathname())) === false) {
        throw new RuntimeException('could not write ' . $tmpDb);
    }
    // Sanity check before swapping it in.
    $reader = new \MaxMind\Db\Reader($tmpDb);
    $meta = $reader->metadata();
    $reader->close();
    @chmod($tmpDb, 0640);
    if (!@rename($tmpDb, $target)) {
        throw new RuntimeException('could not move database into place');
    }
    @unlink($tmpTgz);
    printf("geoip-update: installed %s (built %s, %d nodes) at %s\n",
        $meta->databaseType, date('Y-m-d', $meta->buildEpoch), $meta->nodeCount, $target);
} catch (Throwable $e) {
    @unlink($tmpTgz);
    if (isset($tmpDb)) @unlink($tmpDb);
    fwrite(STDERR, "geoip-update: " . $e->getMessage() . "\n");
    exit(1);
}
