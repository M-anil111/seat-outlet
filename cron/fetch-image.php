<?php
require_once '../functions.php';

$artist = $_GET['artist'] ?? '';
$event  = $_GET['event'] ?? '';
$key    = $_GET['key'] ?? '';

if (!$key) exit;

// Skip if already exists
if (s3ObjectExists($key)) exit;

// Try event first
$imageUrl = getWikimediaImage($event);

// Fallback to artist
if (empty($imageUrl)) {
    $imageUrl = getWikimediaImage($artist);
}

if (!$imageUrl) exit;

// Download
$imageContent = downloadImage($imageUrl);
if (!$imageContent) exit;

// Fix orientation
$imageContent = fixImageOrientation($imageContent);

// Convert to webp
$webpImage = resizeAndConvertToWebP($imageContent, 800, 80);
if (!$webpImage) exit;

// Upload to S3
uploadImageToS3($webpImage, $key, 'image/webp');