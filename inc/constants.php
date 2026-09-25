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

// Real credentials: must come from the environment, no literal fallback. A deploy
// generates inc/env.local.php from Bitbucket's secured repository variables before
// this file is included - see functions.php.
$requiredSecrets = [
    'CONSUMER_KEY',
    'CONSUMER_SECRET',
    'GAPI_KEY',
    'GKGSAPI_KEY',
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


