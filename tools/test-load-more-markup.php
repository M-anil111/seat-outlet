<?php
/**
 * Every page that prints the "More Events" button (#loadMoreBtn) also prints the error block js/events-listing.js shows when a page of
 * events cannot be loaded (#loadMoreError with a #loadMoreRetry button, from soLoadMoreErrorHtml()). Without the block a failed or empty
 * answer changed nothing on screen and the click looked dead (artist, venue and city pages, 20 Nov 2026).
 * Needs no database. Run: php tools/test-load-more-markup.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$root = dirname(__DIR__);
$fail = 0;
function lmCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }

$files = [];
$skip = ['vendor', 'node_modules', 'tools', 'cache', 'docs', '.git'];
$it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    fn($f, $k, $iter) => !($iter->hasChildren() && in_array($f->getFilename(), $skip, true))   // prune these folders instead of walking into them
));
foreach ($it as $f) {
    $p = $f->getPathname();
    if (substr($p, -4) !== '.php') continue;
    $src = (string) file_get_contents($p);
    if (strpos($src, 'id="loadMoreBtn"') === false) continue;
    $files[] = substr($p, strlen($root) + 1);
    $btn = substr_count($src, 'id="loadMoreBtn"');
    $err = substr_count($src, 'soLoadMoreErrorHtml()') - substr_count($src, 'function soLoadMoreErrorHtml()');
    lmCheck(substr($p, strlen($root) + 1) . ": exactly one error block per button ($btn button(s), $err block(s))", $err === $btn);   // two blocks would repeat the #loadMoreError id
}
sort($files);
lmCheck('the pages with the button are the ones this test knows (add a new one here on purpose)', $files === ['functions.php', 'inc/entity-listing.php', 'inc/listing.php', 'performer.php'], implode(', ', $files));

// The block itself: the two ids the script looks up, hidden at first, announced to screen readers.
$listing = (string) file_get_contents($root . '/inc/listing.php');
preg_match("/function soLoadMoreErrorHtml\(\): string \{\s*return '(.*?)';\s*\}/s", $listing, $m);
$html = $m[1] ?? '';
lmCheck('the helper returns the error block', $html !== '');
lmCheck('it has #loadMoreError, hidden, as an alert', strpos($html, 'id="loadMoreError"') !== false && strpos($html, ' hidden>') !== false && strpos($html, 'role="alert"') !== false);
lmCheck('it has the #loadMoreRetry button', strpos($html, 'id="loadMoreRetry"') !== false);
// The pages that print the button also load the script that drives it (footer.php: $soHasEventList). County pages did not, so their button was inert.
$footer = (string) file_get_contents($root . '/footer.php');
preg_match("/\\\$soHasEventList = \(bool\) preg_match\('(#.*?#)', \\\$soPath\)/", $footer, $fm);
$re = $fm[1] ?? '';
lmCheck('the footer path rule was found', $re !== '');
foreach (['/artist/x', '/venue/x', '/city/x', '/state/x', '/country/x', '/county/x', '/category/x', '/artist-city/x/y', '/concerts-city/x', '/search', '/concert-tickets-for-sale'] as $path) {
    lmCheck("footer loads the listing script on $path", $re !== '' && preg_match($re, $path) === 1);
}
// An error answer is never cached: the 5 minute header is for answers, the failure path sets no-store before the 500.
$ajax = (string) file_get_contents($root . '/ajax/load-more-events.php');
lmCheck('the endpoint answers a failure with no-store', preg_match("/catch \(Throwable \\\$e\) \{\s*header\('Cache-Control: no-store'\);[^}]*http_response_code\(500\)/s", $ajax) === 1);
$js = (string) file_get_contents($root . '/js/events-listing.js');
lmCheck('the retry and "Try again" bypass the HTTP cache', strpos($js, "cache: (attempt > 0 || fresh) ? 'reload' : 'default'") !== false && strpos($js, 'soLoadMore(b, true)') !== false);
lmCheck('the script looks up both ids', strpos($js, "soById('loadMoreError')") !== false && strpos($js, '#loadMoreRetry') !== false);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: every More Events button has its error block\n";
