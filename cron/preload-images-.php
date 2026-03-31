<?php
require_once '../functions.php';

/* ==============================
   CONFIG
============================== */

$cacheDir = __DIR__ . '/../cache/';
$limitPerRun = 50; // max images per run

$processed = 0;
$wikiCache = [];

/* ==============================
   HELPER: Generate S3 Key
============================== */

function generateImageKey($name) {
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
    $clean = trim($slug, '-');
    return "events/{$clean}.webp";
}

/* ==============================
   HELPER: Wikipedia Cache (memory)
============================== */

function getWikiCached($title) {
    global $wikiCache;

    if (!$title) return '';

    if (isset($wikiCache[$title])) {
        return $wikiCache[$title];
    }

    $img = getWikimediaImage($title);
    $wikiCache[$title] = $img;

    return $img;
}

/* ==============================
   GET CACHE FILES (LATEST FIRST)
============================== */

$files = glob($cacheDir . '*.json');

if (!$files) {
    echo "No cache files found\n";
    exit;
}

// Sort latest updated first
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

/* ==============================
   LOOP FILES
============================== */

foreach ($files as $file) {

    if ($processed >= $limitPerRun) break;

    echo "\n📂 File: " . basename($file) . "\n";

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    if (empty($data)) continue;

    foreach ($data as $event) {

        if ($processed >= $limitPerRun) break;

        $eventName  = $event['name'] ?? '';
        $artistName = $event['artist'] ?? ''; // ✅ improved (if available)

        if (!$eventName) continue;

        // 🔑 Prefer artist → fallback to event
        $baseName = $artistName ?: $eventName;
        $key = generateImageKey($baseName);

        // ✅ Skip if already exists
        if (s3ObjectExists($key)) {
            echo "⏭️ Skip (exists): {$key}\n";
            continue;
        }

        echo "🔄 Processing: {$baseName}\n";

        /* ==============================
           FETCH IMAGE
        ============================== */

        $imageUrl = getWikiCached($eventName);

        if (empty($imageUrl) && $artistName) {
            $imageUrl = getWikiCached($artistName);
        }

        if (!$imageUrl) {
            echo "❌ No image found\n";
            continue;
        }

        /* ==============================
           DOWNLOAD
        ============================== */

        $imageContent = downloadImage($imageUrl);

        if (!$imageContent) {
            echo "❌ Download failed\n";
            continue;
        }

        /* ==============================
           PROCESS IMAGE
        ============================== */

        $imageContent = fixImageOrientation($imageContent);

        $webpImage = resizeAndConvertToWebP($imageContent, 800, 80);

        if (!$webpImage) {
            echo "❌ Conversion failed\n";
            continue;
        }

        /* ==============================
           UPLOAD TO S3
        ============================== */

        uploadImageToS3($webpImage, $key, 'image/webp');

        echo "✅ Uploaded: {$key}\n";

        $processed++;
    }
}

/* ==============================
   DONE
============================== */

echo "\n🎯 Done. Total processed: {$processed}\n";