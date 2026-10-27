<?php
/**
 * CI: a link to a genre or league goes straight to its clean page (/nfl-tickets), never to /category/<name>, which answers 301
 * (SE Ranking audit, "internal links to 3XX pages"). Run: php tools/test-category-links.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/genre-pages.php';
require_once __DIR__ . '/../inc/category-tree.php';

$fail = 0;
function clCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name $detail\n"; $fail++; } }

$ids = [];
foreach (SO_GENRE_PAGES as $slug => $g) {
    $ids[(int) $g[0]] = $slug;
    clCheck("soCategoryHref($slug)", soCategoryHref((int) $g[0], $g[1]) === '/' . $slug, soCategoryHref((int) $g[0], $g[1]));
}

// No page builds /category/<slug> from a literal genre id: a hand-written link would be a 301 on every page that carries it.
$root = dirname(__DIR__);
$skip = ['vendor', 'tools', 'admin', 'node_modules', '.git'];
$it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    fn($f) => !in_array($f->getFilename(), $skip, true)));
foreach ($it as $f) {
    if (substr($f->getFilename(), -4) !== '.php') continue;
    $src = (string) file_get_contents($f->getPathname());
    if (preg_match_all("/'\\/category\\/'\\s*\\.\\s*soSlug\\('category',\\s*[^,]+,\\s*(\\d+)\\)/", $src, $m)) {
        foreach ($m[1] as $id) clCheck('literal genre id in a /category/ link: ' . substr($f->getPathname(), strlen($root) + 1), !isset($ids[(int) $id]), "id $id is /" . ($ids[(int) $id] ?? ''));
    }
}

// The builders that read the id at run time go through soCategoryHref().
foreach (['functions.php', 'inc/menu.php'] as $file) {
    $src = (string) file_get_contents($root . '/' . $file);
    clCheck("$file uses soCategoryHref", strpos($src, 'soCategoryHref(') !== false);
}

if ($fail) { echo "$fail failure(s)\n"; exit(1); }
echo "ok: category links go straight to the genre page\n";
