<?php
/**
 * Sitemap entry point.
 *
 *   /sitemap.php            the sitemap index (a list of the typed sitemap files, see inc/sitemap-build.php)
 *   /sitemap.php?f=NAME     one file, for example events-3 or performers-1 (used when the web server cannot serve /sitemaps/NAME.xml itself)
 *
 * Until the first full crawl of the catalog has finished there is no index yet: the older single-file sitemap answers instead, so the
 * address never goes empty.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/inc/sitemap-build.php';

// The browser stylesheet, sent with an XSLT content type (the web server would send a bare .xsl file as a generic download,
// which Safari and Firefox refuse to apply).
if (($_GET['f'] ?? '') === 'style') {
    header('Content-Type: text/xsl; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    readfile(__DIR__ . '/sitemap.xsl');
    exit;
}

$name = isset($_GET['f']) ? (string) $_GET['f'] : 'sitemap-index';
$file = soSitemapFile($name);
if ($file !== null) {
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=' . ($name === 'sitemap-index' ? 600 : 1800));
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int) filemtime($file)) . ' GMT');
    // Files built before the stylesheet existed get it added on the way out, so every sitemap is readable in a browser.
    $xml = (string) file_get_contents($file);
    if (strpos($xml, '<?xml-stylesheet') === false) {
        $xml = preg_replace('/^(<\?xml[^>]*\?>\s*)/', '$1<?xml-stylesheet type="text/xsl" href="/sitemap.php?f=style"?>' . "\n", $xml, 1);
    }
    echo $xml;
    exit;
}
if (isset($_GET['f'])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found\n";
    exit;
}
require __DIR__ . '/inc/sitemap-legacy.php';
