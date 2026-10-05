<?php
// The branded 404 page. It must answer HTTP 404 and noindex (it used to answer 200, a soft 404 search engines index).
// Also the page for unknown URLs when the web server's error_page points at it (see docs/server-rewrites.md).
require_once __DIR__ . '/functions.php';
soAliasRedirect();
renderNotFoundPage('Page');
