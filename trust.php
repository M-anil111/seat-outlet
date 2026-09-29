<?php
// /trust was named in the keyword research but never built; the content it
// would carry (guarantee, buyer protection, how we verify sellers) already
// lives on /buyer-protection. Permanent redirect so the URL is not a 404.
require_once __DIR__ . '/inc/constants.php';
header('Location: ' . HOME_URL . '/buyer-protection', true, 301);
exit;
