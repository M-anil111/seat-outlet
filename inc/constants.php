<?php
// Non-sensitive config: safe to keep sane defaults if the env var isn't set.
define('WEBSITE_CONFIG_ID', getenv('WEBSITE_CONFIG_ID') ?: 12498);
define('WEBSITE_CONFIG_ID_LIVE', getenv('WEBSITE_CONFIG_ID_LIVE') ?: 27773);
define('BASE_URL', getenv('BASE_URL') ?: 'https://sandbox.tn-apis.com');
define('BROKER_ID', getenv('BROKER_ID') ?: 9250);
define('SITE_ID', getenv('SITE_ID') ?: 30);
define('HOME_URL', getenv('HOME_URL') ?: 'https://beta.seatoutlet.com');
define('HOME_PATH', getenv('HOME_PATH') ?: '/home/seatoutlet-beta/htdocs/beta.seatoutlet.com/');
define('AWS_ACCOUNT_ID', getenv('AWS_ACCOUNT_ID') ?: '2f20a4f9aec4a1c3b457bc4a6165f503');
define('AWS_BUCKET_NAME', getenv('AWS_BUCKET_NAME') ?: 'seat-outlet-assets');
define('AWS_CDN_URL', getenv('AWS_CDN_URL') ?: 'https://cdn-beta.seatoutlet.com/');
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

define('SENTRY_DSN', getenv('SENTRY_DSN') ?: '');
define('SENTRY_ENVIRONMENT', getenv('SENTRY_ENVIRONMENT') ?: (BASE_URL === 'https://www.tn-apis.com' ? 'production' : 'sandbox'));

// Real credentials: must come from the environment, no literal fallback. A deploy
// generates inc/env.local.php from GitHub's encrypted repository secrets before
// this file is included - see functions.php.
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


