<?php

declare(strict_types=1);

/**
 * Verify deployed blog pages against the projected migration state.
 *
 * Usage: php tools/audit-live-blogs.php [base-url]
 */

$baseUrl = rtrim($argv[1] ?? 'https://seatoutlet.com', '/');
$auditCommand = escapeshellarg(PHP_BINARY) . ' '
    . escapeshellarg(__DIR__ . '/audit-blog-migrations.php') . ' --json';
$json = shell_exec($auditCommand);
$posts = is_string($json) ? json_decode($json, true) : null;

if (!is_array($posts)) {
    fwrite(STDERR, "Unable to load the projected blog inventory.\n");
    exit(1);
}

$seenImages = [];
$failures = [];

printf("%-48s %5s %7s %-46s %s\n", 'SLUG', 'HTTP', 'WORDS', 'IMAGE', 'ALT');

foreach ($posts as $post) {
    $slug = (string) ($post['slug'] ?? '');
    $url = $baseUrl . '/blog/' . rawurlencode($slug);
    $context = stream_context_create([
        'http' => [
            'follow_location' => 0,
            'ignore_errors' => true,
            'timeout' => 20,
            'user_agent' => 'SeatOutlet-Live-Blog-Audit/1.0',
        ],
    ]);
    $html = file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    $status = 0;

    if (isset($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $match)) {
        $status = (int) $match[1];
    }

    $words = 0;
    $image = '';
    $alt = '';

    if (is_string($html) && $html !== '') {
        libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $content = $xpath->query(
            '//*[contains(concat(" ", normalize-space(@class), " "), " blog-post-content ")]'
        )->item(0);
        $featuredImage = $xpath->query(
            '//img[contains(concat(" ", normalize-space(@class), " "), " so-art__img ")]'
        )->item(0);

        if ($content instanceof DOMNode) {
            $text = html_entity_decode($content->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $words = preg_match_all("/[\\p{L}\\p{N}]+(?:['’-][\\p{L}\\p{N}]+)*/u", $text) ?: 0;
        }
        if ($featuredImage instanceof DOMElement) {
            $image = $featuredImage->getAttribute('src');
            $alt = trim($featuredImage->getAttribute('alt'));
        }
        libxml_clear_errors();
    }

    printf(
        "%-48s %5d %7d %-46s %s\n",
        $slug,
        $status,
        $words,
        $image,
        $alt === '' ? '[missing]' : $alt
    );

    if ($status !== 200) {
        $failures[] = "$slug returned HTTP $status";
    }
    if ($words < 3500) {
        $failures[] = "$slug has $words article words";
    }
    if ($image === '') {
        $failures[] = "$slug has no featured image";
    } elseif (isset($seenImages[$image])) {
        $failures[] = "$slug reuses the image from {$seenImages[$image]}";
    } else {
        $seenImages[$image] = $slug;
    }
    if ($alt === '') {
        $failures[] = "$slug has no featured image alt text";
    }
}

printf(
    "\nSummary: %d posts, %d unique images, %d failures\n",
    count($posts),
    count($seenImages),
    count($failures)
);

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "- $failure\n");
    }
    exit(1);
}
