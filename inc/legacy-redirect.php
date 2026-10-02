<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Permanent redirect from a renamed page URL to its new keyword URL (the map lives in inc/seo-keywords.php).
 * The old file names (concerts.php, sports.php, ...) are kept as two-line stubs that include this file, so the web
 * server still routes the old address to PHP and the visitor, or a search engine, is sent on with a 301.
 * The query string (filters such as ?when= and ?sort=) is carried over.
 */
$soLegacy = (require __DIR__ . '/seo-keywords.php')['legacy'];
$soOldPath = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$soOldPath = preg_replace('/\.php$/', '', $soOldPath);
$soTarget = $soLegacy[$soOldPath] ?? null;
if ($soTarget === null) {
    http_response_code(404);
    exit;
}
$soQuery = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
header('Location: ' . $soTarget . ($soQuery !== '' ? '?' . $soQuery : ''), true, 301);
header('Cache-Control: public, max-age=3600');
exit;
