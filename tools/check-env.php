<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Go-live check: which settings are missing, or still on their beta/sandbox defaults.
 *
 *   php tools/check-env.php               report (exit 1 only when something REQUIRED is missing)
 *   php tools/check-env.php --production  also exit 1 for every beta/sandbox default (use this before and right after launch)
 *
 * Reads inc/env.local.php the same way db/config.php does and compares with the defaults in inc/constants.php.
 * Never prints a secret value: it only says set / not set.
 */
foreach ([__DIR__ . '/../inc/env.local.php', __DIR__ . '/../../inc/env.local.php'] as $f) {
    if (is_file($f)) { require_once $f; $envFile = realpath($f); break; }
}
$production = in_array('--production', $argv ?? [], true);

$rows = [];   // [status, setting, note]
$fail = 0;
$add = function ($status, $name, $note) use (&$rows, &$fail, $production) {
    if ($status === 'BETA' && $production) $status = 'FAIL';
    if ($status === 'FAIL') $fail++;
    $rows[] = [$status, $name, $note];
};
$env = function ($k) { $v = getenv($k); return $v === false ? '' : (string) $v; };

$add(isset($envFile) ? 'OK' : 'FAIL', 'inc/env.local.php', isset($envFile) ? 'found' : 'not found: create it from deploy/env.local.php.example');

// Required secrets
foreach (['DB_PASS', 'CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) {
    $v = $env($k);
    if ($v === '') $add('FAIL', $k, 'not set (the site will not start)');
    elseif (in_array(strtolower($v), ['dummy', 'changeme', 'test'], true)) $add('FAIL', $k, 'placeholder value');
    else $add('OK', $k, 'set');
}

// Environment
$home = $env('HOME_URL') ?: 'https://beta.seatoutlet.com';
$base = $env('BASE_URL') ?: 'https://sandbox.tn-apis.com';
$live = preg_match('#^https://(www\.)?tn-apis\.com#i', $base) === 1;
$betaHost = preg_match('#//(beta\.|staging\.|dev\.|127\.0\.0\.1|localhost)#i', $home) === 1;
$add($env('HOME_URL') === '' ? 'BETA' : ($betaHost ? 'BETA' : 'OK'), 'HOME_URL', ($env('HOME_URL') === '' ? 'not set, defaults to ' : '') . $home . ($betaHost ? ' (beta/local address: pages are noindex, canonical and email links point there)' : ''));
if (!preg_match('#^https://#i', $home) && !$betaHost) $add('FAIL', 'HOME_URL', 'must start with https:// on production');
$add($live ? 'OK' : 'BETA', 'BASE_URL', $live ? 'live TicketNetwork API' : $base . ' (sandbox data: events in 2070, $1 prices)');
$cfg = $env('WEBSITE_CONFIG_ID');
$cfgLive = $env('WEBSITE_CONFIG_ID_LIVE') ?: '27773';
$effective = $cfg !== '' ? $cfg : ($live ? $cfgLive : '12498');
if ($live && $cfg === '12498') $add('FAIL', 'WEBSITE_CONFIG_ID', '12498 is the sandbox id but the API is live: the seat map will report every event expired');
elseif (!$live && $cfg !== '' && $cfg === $cfgLive) $add('FAIL', 'WEBSITE_CONFIG_ID', 'live id with the sandbox API');
else $add('OK', 'WEBSITE_CONFIG_ID', 'effective id ' . $effective . ($live ? ' (live)' : ' (sandbox)'));
if ($live && $betaHost && $env('SO_ALLOW_MIXED_ENV') !== '1') $add('FAIL', 'HOME_URL + BASE_URL', 'live API with a beta/local address: the site answers 503 until HOME_URL is the real domain');

foreach (['DB_NAME' => 'seatoutlet-beta', 'DB_USER' => 'beta-seatoutlet'] as $k => $default) {
    $v = $env($k) ?: $default;
    $add($v === $default ? 'BETA' : 'OK', $k, $env($k) === '' ? 'not set, defaults to the beta database name' : 'set');
}
$add('OK', 'DB_HOST', $env('DB_HOST') === '' ? 'not set, defaults to 127.0.0.1' : 'set');
$path = $env('HOME_PATH') ?: '/home/seatoutlet-beta/htdocs/beta.seatoutlet.com/';
$root = realpath(__DIR__ . '/..');
$add($env('HOME_PATH') === '' ? 'BETA' : (realpath($path) === $root ? 'OK' : 'WARN'), 'HOME_PATH', $env('HOME_PATH') === '' ? 'not set, defaults to the beta server path (also decides where the GeoIP database is looked for)' : (realpath($path) === $root ? 'matches this folder' : 'does not match this folder (' . $root . ')'));
$idx = $env('SITE_INDEXABLE');
$effIdx = $idx !== '' ? in_array(strtolower($idx), ['1', 'true', 'yes'], true) : !$betaHost;
$add($effIdx ? 'OK' : ($production ? 'FAIL' : 'WARN'), 'SITE_INDEXABLE', $effIdx ? 'pages are indexable' : 'pages are noindex and /robots.txt blocks crawling (right for beta, wrong for launch)');

// Site features
$add($env('GTM_ID') !== '' ? 'OK' : 'WARN', 'GTM_ID', $env('GTM_ID') !== '' ? 'set' : 'not set: no Google Tag Manager (no analytics or ad conversion tags)');
$add($env('RECAPTCHA_SITE_KEY') !== '' ? 'OK' : 'WARN', 'RECAPTCHA_SITE_KEY', $env('RECAPTCHA_SITE_KEY') !== '' ? 'set' : 'not set: the key built into inc/constants.php is used; check in the Google reCAPTCHA console that it allows the live domain and matches RECAPTCHA_SECRET_KEY');
$add($env('SMTP_USER') !== '' && $env('SMTP_PASS') !== '' ? 'OK' : 'WARN', 'SMTP_USER / SMTP_PASS', $env('SMTP_USER') !== '' && $env('SMTP_PASS') !== '' ? 'set' : 'not set: no sign-up, alert or password reset emails are sent');
$add($env('SO_MAIL_ADDRESS') !== '' ? 'OK' : 'WARN', 'SO_MAIL_ADDRESS', $env('SO_MAIL_ADDRESS') !== '' ? 'set' : 'not set: marketing emails go out without the postal address US law asks for');
$add($env('BREVO_API_KEY') !== '' ? 'OK' : 'INFO', 'BREVO_API_KEY', $env('BREVO_API_KEY') !== '' ? 'set (sign-ups are copied to Brevo)' : 'not set: sign-ups stay in the leads table only');
$add($env('SO_LEAD_NOTIFY_TO') !== '' ? 'OK' : 'INFO', 'SO_LEAD_NOTIFY_TO', $env('SO_LEAD_NOTIFY_TO') !== '' ? 'set' : 'not set: nobody is emailed per new sign-up (they are in the leads table)');
$add($env('SENTRY_DSN') !== '' ? 'OK' : 'WARN', 'SENTRY_DSN', $env('SENTRY_DSN') !== '' ? 'set' : 'not set: no error monitoring');
$add($env('THESPORTSDB_KEY') !== '' && $env('THESPORTSDB_KEY') !== '3' ? 'OK' : 'WARN', 'THESPORTSDB_KEY', $env('THESPORTSDB_KEY') !== '' && $env('THESPORTSDB_KEY') !== '3' ? 'set' : 'public free key in use (30 requests a minute): fine for beta, get a paid key for production image volume');
$cdn = $env('AWS_CDN_URL') ?: 'https://cdn-beta.seatoutlet.com/';
$add(stripos($cdn, 'cdn-beta') !== false ? 'BETA' : 'OK', 'AWS_CDN_URL', $env('AWS_CDN_URL') === '' ? 'not set, defaults to ' . $cdn : 'set');
$add($env('TN_CHECKOUT_URL') === '' ? 'WARN' : 'OK', 'TN_CHECKOUT_URL', $env('TN_CHECKOUT_URL') === '' ? 'not set, defaults to https://checkout.seatoutlet.com: confirm that is the live hosted checkout address' : 'set');
$geo = $env('GEOIP_DB_PATH') ?: dirname(rtrim($path, '/')) . '/geoip/GeoLite2-City.mmdb';
$add(is_file($geo) ? 'OK' : 'WARN', 'GeoIP database', is_file($geo) ? 'present' : 'not found at the expected path: visitor location falls back to the browser (run cron/geoip-update.php with MAXMIND_* set)');
$cache = __DIR__ . '/../cache';
$add(is_dir($cache) && is_writable($cache) ? 'OK' : 'FAIL', 'cache/ folder', is_dir($cache) && is_writable($cache) ? 'writable' : 'missing or not writable by this user');
$add($env('CRON_TOKEN') !== '' ? 'WARN' : 'OK', 'CRON_TOKEN', $env('CRON_TOKEN') !== '' ? 'set: cron scripts can be started by URL with that token; prefer running them with php and leave this unset' : 'not set (cron scripts only run from the command line)');

$w = 0;
foreach ($rows as $r) { $w = max($w, strlen($r[1])); }
$counts = [];
foreach ($rows as [$status, $name, $note]) {
    $counts[$status] = ($counts[$status] ?? 0) + 1;
    printf("%-5s %-{$w}s  %s\n", $status, $name, $note);
}
echo "\n";
foreach (['FAIL', 'BETA', 'WARN', 'INFO', 'OK'] as $s) { if (!empty($counts[$s])) echo $s . ': ' . $counts[$s] . '  '; }
echo "\n" . ($fail ? "Not ready for production.\n" : ($production ? "Production settings look complete.\n" : "No blocking problems. Run with --production before launch to treat beta defaults as failures.\n"));
exit($fail ? 1 : 0);
