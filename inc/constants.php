<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
// Loaded once: admin pages and ajax endpoints include this from more than one place.
if (defined('SO_CONSTANTS_LOADED')) { return; }
// One explicit PHP timezone for the whole site. TicketNetwork event dates are the venue's local calendar day with no offset
// ("2026-10-02", "2026-10-02T19:30:00"), and every listing asks the API for "date ge <today>". On a UTC server "today" rolled
// over at 8pm Eastern / 5pm Pacific and dropped tonight's events from every list and from the "Today" filter while they were
// still on sale. Los Angeles is the last major US timezone to reach midnight, so "today" here never drops an event that is
// still tonight anywhere in the lower 48 (cost: for up to three hours after midnight Eastern, listings still show the evening
// that just ended, which is still "tonight" on the west coast). Display formatting of event, blog and sitemap dates is not
// shifted: those strings are parsed and printed in this same zone, so they round-trip unchanged.
date_default_timezone_set(getenv('SITE_TIMEZONE') ?: 'America/Los_Angeles');
// Non-sensitive config: safe to keep sane defaults if the env var isn't set.
define('BASE_URL', getenv('BASE_URL') ?: 'https://sandbox.tn-apis.com');
define('WEBSITE_CONFIG_ID_LIVE', getenv('WEBSITE_CONFIG_ID_LIVE') ?: 27773);
// The website config id must match the API host: 12498 belongs to the sandbox, 27773 to the live API (mixing them makes the seat map
// report every event as expired). An explicit WEBSITE_CONFIG_ID always wins; otherwise the live API host selects the live id.
define('SO_TN_LIVE_API', preg_match('#^https://(www\.)?tn-apis\.com#i', BASE_URL) === 1);
define('WEBSITE_CONFIG_ID', getenv('WEBSITE_CONFIG_ID') ?: (SO_TN_LIVE_API ? WEBSITE_CONFIG_ID_LIVE : 12498));
define('BROKER_ID', getenv('BROKER_ID') ?: 9250);
define('SITE_ID', getenv('SITE_ID') ?: 30);
define('HOME_URL', getenv('HOME_URL') ?: 'https://beta.seatoutlet.com');
define('HOME_PATH', getenv('HOME_PATH') ?: '/home/seatoutlet-beta/htdocs/beta.seatoutlet.com/');
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
/**
 * Configuration that cannot work: answer with a plain 503 page and put the reason in the server log. The page must not name
 * settings or hosts to visitors.
 */
if (!function_exists('soConfigUnavailable')) {
function soConfigUnavailable($detail) {
    error_log('Seat Outlet configuration problem: ' . $detail . ' (run: php tools/check-env.php)');
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Configuration problem: ' . $detail . "\n");
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(503);
        header('Retry-After: 60');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Temporarily unavailable | Seat Outlet</title></head>'
       . '<body style="font-family:system-ui,sans-serif;text-align:center;padding:12vh 16px;color:#1f2937"><h1 style="font-size:24px;margin:0 0 8px">We will be right back</h1>'
       . '<p style="margin:0;color:#5b6573">Seat Outlet is temporarily unavailable. Please try again in a minute.</p></body></html>';
    exit;
}
}
if (!empty($missingSecrets)) {
    soConfigUnavailable('missing required environment variable(s): ' . implode(', ', $missingSecrets));
}
// Live ticket prices under a beta/staging/local address would publish canonical, sitemap and Open Graph URLs for the wrong host.
// Set SO_ALLOW_MIXED_ENV=1 to test that combination on purpose.
if (SO_TN_LIVE_API && preg_match('#//(beta\.|staging\.|dev\.|127\.0\.0\.1|localhost)#i', HOME_URL) && getenv('SO_ALLOW_MIXED_ENV') !== '1') {
    soConfigUnavailable('BASE_URL is the live TicketNetwork API but HOME_URL is still a beta/local address');
}
define('SO_CONSTANTS_LOADED', true);


