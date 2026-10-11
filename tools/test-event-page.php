<?php
// Render the real event template with fixture services and no SEO-side variables, as with an admin title override.
$root = dirname(__DIR__);
require_once $root . '/inc/page-spec.php';
foreach (['TN_CATEGORY_PATH_SPORTS' => '.sports.', 'TN_CATEGORY_PATH_THEATER' => '.theater.', 'TN_CATEGORY_PATH_FESTIVAL' => '.festival.',
          'HOME_URL' => 'https://fixture.invalid', 'WEBSITE_CONFIG_ID' => 0, 'TN_CHECKOUT_URL' => 'https://checkout.fixture.invalid'] as $k => $v) define($k, $v);
$fixtureEvent = ['id' => 42, 'text' => ['name' => 'Fixture Show'], 'venue' => ['id' => 7, 'text' => ['name' => 'Fixture Arena']],
    'city' => ['id' => 8, 'text' => ['name' => 'Austin']], 'stateProvince' => ['text' => ['abbr' => 'TX']], 'country' => ['alphaCode' => 'US'],
    'date' => ['date' => '2027-01-01T19:00:00'], 'defaultCategory' => ['path' => '.theater.', 'text' => ['name' => 'Theater']],
    'performers' => null, 'pricingInfo' => null];
// Fixture adapters are separate from the production renderers under test.
foreach ([
    'getTnEventById($id) { $GLOBALS["eventLookups"]++; return $GLOBALS["fixtureEvent"]; }',
    'tnEntityMissing($e) { return !$e; }',
    'soCategoryDisplayName($n) { return $n; }',
    'getCategoryEventsByLocation(...$a) { return ["results"=>[],"totalCount"=>0]; }',
    'tnRequestCached(...$a) { return null; }',
    'getCategoryCityLinkPrefix($p) { return "city"; }',
    'soVenueSlug(...$a) { return "fixture-arena"; }',
    'soSlug(...$a) { return "fixture"; }',
    'soEventSlug($e) { return "fixture-show"; }',
    'soStateIcon($k) { return ""; }',
    'soLeadForm($a) { return ""; }',
    'soBuyerGuaranteeSection($a) {}',
    'soEntityFacts(...$a) { return []; }',
    'buildFaqPageSchema($a) { return []; }',
] as $stub) eval('function ' . $stub);
$dir = sys_get_temp_dir() . '/so-event-test-' . bin2hex(random_bytes(6));
mkdir($dir, 0700);
file_put_contents($dir . '/header.php', '<?php // Admin SEO override deliberately provides no event variables.');
file_put_contents($dir . '/footer.php', '<?php');
$cwd = getcwd();
try {
    chdir($dir);
    set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
    foreach (['concerts' => '.concert.', 'sports' => '.sports.', 'theater' => '.theater.', 'festival' => '.festival.'] as $kind => $path) {
        // A fresh include scope models a new request, with null optional nested API fields.
        $render = function () use ($root) { $_GET = ['id' => 42]; ob_start(); try { include $root . '/event.php'; return ob_get_contents(); } finally { ob_end_clean(); } };
        $fixtureEvent['defaultCategory']['path'] = $path;
        $GLOBALS['eventLookups'] = 0;
        $html = $render();
        if (!str_contains($html, 'Fixture Arena') || !preg_match('/<h1[^>]*>\s*Buy Fixture Show/', $html) || $GLOBALS['eventLookups'] !== 1) throw new RuntimeException('Incomplete ' . $kind . ' render');
        echo "ok   $kind event renders without SEO variables and with null optional API fields\n";
    }
} finally {
    restore_error_handler(); chdir($cwd);
    unlink($dir . '/header.php'); unlink($dir . '/footer.php'); rmdir($dir);
}
