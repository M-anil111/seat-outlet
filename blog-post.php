<?php
// Old two-script blog address: /blog-post?slug=x answers 301 to /blog/x (blog.php is the one entry point).
$soSlug = preg_match('/^[a-z0-9-]+$/', (string) ($_GET['slug'] ?? '')) ? $_GET['slug'] : '';
http_response_code(301);
header('Location: /blog' . ($soSlug !== '' ? '/' . $soSlug : ''));
header('Cache-Control: public, max-age=3600');
