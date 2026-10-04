<?php
// Old address: permanent redirect to the one sitemap URL.
http_response_code(301);
header('Location: /sitemap.xml');
