<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * <title> and meta description for static pages that set neither $pageMetaTitle nor an entry in the focus keyword plan.
 * Keyed by URL path, value [title, description]; header.php adds the brand ("Title—Seat Outlet").
 * The pages that used to be listed here (about, contact, FAQ, buyer protection, legal, cities, partners) now get their
 * title and description from inc/seo-keywords.php, together with their focus keyword.
 */
return [
];
