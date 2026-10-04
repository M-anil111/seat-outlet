<?php
/**
 * CI check: no URL on the site may carry a ticket API id. Every entity link is built by soSlug() (inc/slugs.php).
 * Fails (exit 1) when PHP code
 *   - calls the removed createSlug(),
 *   - calls soLegacySlug() outside inc/slugs.php (it is the id form, kept only as a last resort inside the registry),
 *   - writes an entity path ("/artist/", "/venue/", "/city/", "/state/", "/country/", "/category/", "/event/" ...) followed by a
 *     hand-written name and a number ("/city/austin-247").
 * Run: php tools/check-url-slugs.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';

$root = realpath(__DIR__ . '/..');
$skip = ['vendor', 'cache', 'node_modules', '.git', 'tools', 'docs', 'db', 'tests'];
$it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    function ($f) use ($skip) { return !in_array($f->getFilename(), $skip, true); }
));
$fail = 0;
foreach ($it as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = substr($file->getPathname(), strlen($root) + 1);
    $inSlugs = $path === 'inc/slugs.php';
    foreach (file($file->getPathname()) as $n => $line) {
        $t = ltrim($line);
        if ($t !== '' && ($t[0] === '*' || str_starts_with($t, '//') || str_starts_with($t, '/*') || $t[0] === '#')) continue;   // comments
        if (!$inSlugs && preg_match('/\bcreateSlug\s*\(|\bsoLegacySlug\s*\(/', $line)) {
            echo "$path:" . ($n + 1) . " builds a slug by hand, use soSlug()\n"; $fail++;
        }
        if (preg_match('~["\']/(artist|venue|city|state|country|category|event|event-city|sports-city|concerts-city|theater-city|artist-city)/[a-z][a-z0-9-]*-\d{2,6}(["\'?/#])~', $line)) {
            echo "$path:" . ($n + 1) . " has a hand-written URL with an id\n"; $fail++;
        }
        if (preg_match('~\'[a-z][a-z-]*-[a-z]{2}-\d{2,5}\'~', $line)) {
            echo "$path:" . ($n + 1) . " has a hand-written city slug with an id\n"; $fail++;
        }
    }
}
echo $fail ? "$fail problem(s)\n" : "no ids in URLs\n";
exit($fail ? 1 : 0);
