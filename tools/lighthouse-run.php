<?php
/**
 * Runs real Google Lighthouse (must be on PATH - see
 * .github/workflows/lighthouse.yml, which installs it via npm) against
 * every URL in the live sitemap and prints the combined results as JSON
 * to stdout.
 *
 * Usage:
 *   php tools/lighthouse-run.php https://beta.seatoutlet.com > data/lighthouse-scores.json
 *
 * Deliberately skips every /event/... URL: sitemap.php lists every
 * currently-live TicketNetwork event, which can be hundreds of URLs and
 * changes daily as events sell out or new ones are listed - not a stable,
 * meaningful set to track Lighthouse scores for, and running a full
 * Lighthouse pass (20-60s each) against all of them would make this
 * workflow impractically slow. Everything else in the sitemap - static
 * marketing pages, partner network pages, the blog listing, and every
 * individual blog post - is a real, stable page worth tracking.
 */

if ($argc < 2 || trim($argv[1]) === '') {
    fwrite(STDERR, "Usage: php tools/lighthouse-run.php <site-url>\n");
    exit(1);
}

$siteUrl = rtrim($argv[1], '/');

$sitemapXml = @file_get_contents($siteUrl . '/sitemap.php');
if ($sitemapXml === false) {
    fwrite(STDERR, "Could not fetch $siteUrl/sitemap.php\n");
    exit(1);
}

$sitemap = @simplexml_load_string($sitemapXml);
if ($sitemap === false) {
    fwrite(STDERR, "Could not parse sitemap XML from $siteUrl/sitemap.php\n");
    exit(1);
}

$urls = [];
foreach ($sitemap->url as $urlNode) {
    $loc = trim((string) $urlNode->loc);
    if ($loc === '' || str_contains($loc, '/event/')) {
        continue;
    }
    $urls[] = $loc;
}

if (empty($urls)) {
    fwrite(STDERR, "No non-event URLs found in the sitemap - nothing to score.\n");
    exit(1);
}

$results = [];
foreach ($urls as $url) {
    fwrite(STDERR, "Running Lighthouse against $url ...\n");

    $tmpFile = tempnam(sys_get_temp_dir(), 'lh_') . '.json';
    $cmd = sprintf(
        'lighthouse %s --output=json --output-path=%s --quiet ' .
        '--chrome-flags="--headless=new --no-sandbox --disable-gpu" ' .
        '--only-categories=performance,accessibility,best-practices,seo 2>&1',
        escapeshellarg($url),
        escapeshellarg($tmpFile)
    );
    exec($cmd, $output, $exitCode);

    if ($exitCode !== 0 || !file_exists($tmpFile)) {
        fwrite(STDERR, "  Lighthouse failed for $url (exit $exitCode): " . implode("\n", $output) . "\n");
        @unlink($tmpFile);
        continue;
    }

    $report = json_decode(file_get_contents($tmpFile), true);
    @unlink($tmpFile);

    if (!is_array($report) || empty($report['categories'])) {
        fwrite(STDERR, "  Could not parse Lighthouse report for $url\n");
        continue;
    }

    $categories = $report['categories'];
    $results[] = [
        'url' => $url,
        'performance' => isset($categories['performance']['score']) ? (int) round($categories['performance']['score'] * 100) : null,
        'accessibility' => isset($categories['accessibility']['score']) ? (int) round($categories['accessibility']['score'] * 100) : null,
        'best_practices' => isset($categories['best-practices']['score']) ? (int) round($categories['best-practices']['score'] * 100) : null,
        'seo' => isset($categories['seo']['score']) ? (int) round($categories['seo']['score'] * 100) : null,
        'fetched_at' => gmdate('c'),
        'lighthouse_version' => $report['lighthouseVersion'] ?? null,
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
