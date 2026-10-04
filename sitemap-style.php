<?php
// The browser stylesheet for the XML sitemaps, sent as text/xsl (a bare .xsl file would be sent as a generic download,
// which Safari and Firefox refuse to apply). Search engines ignore it and read the XML.
header('Content-Type: text/xsl; charset=utf-8');
header('Cache-Control: public, max-age=86400');
readfile(__DIR__ . '/sitemap.xsl');
