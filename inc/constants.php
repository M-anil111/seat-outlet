<?php
// Non-sensitive config: safe to keep sane defaults if the env var isn't set.
define('WEBSITE_CONFIG_ID', getenv('WEBSITE_CONFIG_ID') ?: 12498);
define('WEBSITE_CONFIG_ID_LIVE', getenv('WEBSITE_CONFIG_ID_LIVE') ?: 27773);
define('BASE_URL', getenv('BASE_URL') ?: 'https://sandbox.tn-apis.com');
define('BROKER_ID', getenv('BROKER_ID') ?: 9250);
define('SITE_ID', getenv('SITE_ID') ?: 30);
define('HOME_URL', getenv('HOME_URL') ?: 'https://beta.seatoutlet.com');
// The code root on this server, wherever the deploy put it (it moved to
// .../beta.seatoutlet.com/seat-outlet); override with HOME_PATH if needed.
define('HOME_PATH', getenv('HOME_PATH') ?: dirname(__DIR__) . '/');
define('AWS_ACCOUNT_ID', getenv('AWS_ACCOUNT_ID') ?: '2f20a4f9aec4a1c3b457bc4a6165f503');
define('AWS_BUCKET_NAME', getenv('AWS_BUCKET_NAME') ?: 'seat-outlet-assets');
define('AWS_CDN_URL', getenv('AWS_CDN_URL') ?: 'https://cdn-beta.seatoutlet.com/');
// Search engine indexing. Every page carried a hard-coded "noindex nofollow"
// from the original build (right for a beta host, fatal for launch). Now:
// explicit SITE_INDEXABLE=1/0 wins; otherwise a beta./localhost HOME_URL is
// noindex and any other host is indexable. search/admin stay noindex.
$indexableEnv = getenv('SITE_INDEXABLE');
define('SITE_INDEXABLE', $indexableEnv !== false && $indexableEnv !== ''
    ? in_array(strtolower($indexableEnv), ['1', 'true', 'yes'], true)
    : !preg_match('#//(beta\.|staging\.|dev\.|127\.0\.0\.1|localhost)#i', HOME_URL));

define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY') ?: '6Lfnn9AsAAAAAOQN5tu9jU-fBVBCdYXKPgmkc8J1');

// Sentry error monitoring: optional. A DSN only lets Sentry *receive* events -
// it's not a secret in the same sense as an API key - but it still comes from
// the environment rather than being hardcoded, same as everything else here.
// Sentry is simply not initialized (see functions.php) when this is empty, so
// local/dev environments without a DSN configured work exactly as before.
// Optional image-source keys (see inc/images.php). Missing = source skipped
// (Pexels) or the provider's public free key is used (TheSportsDB key "3").
define('THESPORTSDB_KEY', getenv('THESPORTSDB_KEY') ?: '3');
define('PEXELS_API_KEY', getenv('PEXELS_API_KEY') ?: '');
// Google Knowledge Graph is no longer an image source; the key is optional.
define('GKGSAPI_KEY', getenv('GKGSAPI_KEY') ?: '');

// MaxMind GeoLite2-City database (inc/geoip.php, cron/geoip-update.php).
// Default is a sibling of the web root so the file is never served; override
// with GEOIP_DB_PATH. MAXMIND_ACCOUNT_ID / MAXMIND_LICENSE_KEY are read by
// the cron only.
define('GEOIP_DB_PATH', getenv('GEOIP_DB_PATH') ?: dirname(rtrim(HOME_PATH, '/')) . '/geoip/GeoLite2-City.mmdb');

// TicketNetwork-hosted (white-label) checkout that the Seatics widget deep-links
// to with ?tgid=&qty=&prc=. The website config (12498 sandbox / 27773 live)
// carries this value too; the env var lets us override it and lets /checkout
// hand off to it. Mercury (self-hosted checkout) is not enabled on this API
// subscription - see checkout.php.
define('TN_CHECKOUT_URL', rtrim(getenv('TN_CHECKOUT_URL') ?: 'https://checkout.seatoutlet.com', '/'));
// Google Tag Manager container (GTM-XXXXXXX). Empty = no tag, dataLayer still
// receives the ecommerce events so a container can be added without a deploy.
define('GTM_ID', getenv('GTM_ID') ?: '');

define('SENTRY_DSN', getenv('SENTRY_DSN') ?: '');
define('SENTRY_ENVIRONMENT', getenv('SENTRY_ENVIRONMENT') ?: (BASE_URL === 'https://www.tn-apis.com' ? 'production' : 'sandbox'));

// Real credentials: must come from the environment, no literal fallback. They are
// loaded from the server's env file by inc/env.php (via db/config.php) before
// this file is included.
$requiredSecrets = [
    'CONSUMER_KEY',
    'CONSUMER_SECRET',
    'GAPI_KEY',
    'AWS_ACCESS_KEY',
    'AWS_SECRET_KEY',
    'RECAPTCHA_SECRET_KEY',
];
$missingSecrets = [];
foreach ($requiredSecrets as $secretName) {
    $value = getenv($secretName);
    if ($value === false || $value === '') {
        $missingSecrets[] = $secretName;
        continue;
    }
    define($secretName, $value);
}
if (!empty($missingSecrets)) {
    die('Missing required environment variable(s): ' . implode(', ', $missingSecrets));
}


