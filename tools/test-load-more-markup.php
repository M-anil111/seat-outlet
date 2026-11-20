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
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $p = $f->getPathname();
    if (substr($p, -4) !== '.php' || preg_match('#/(vendor|node_modules|tools|cache|docs|\.git)/#', $p)) continue;
    $src = (string) file_get_contents($p);
    if (strpos($src, 'id="loadMoreBtn"') === false) continue;
    $files[] = substr($p, strlen($root) + 1);
    $btn = substr_count($src, 'id="loadMoreBtn"');
    $err = substr_count($src, 'soLoadMoreErrorHtml()') - substr_count($src, 'function soLoadMoreErrorHtml()');
    lmCheck(substr($p, strlen($root) + 1) . ": a button needs an error block ($btn button(s), $err block(s))", $err >= $btn);
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
$js = (string) file_get_contents($root . '/js/events-listing.js');
lmCheck('the script looks up both ids', strpos($js, "soById('loadMoreError')") !== false && strpos($js, '#loadMoreRetry') !== false);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: every More Events button has its error block\n";
