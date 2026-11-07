<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * The script policy has no 'unsafe-inline': every inline script carries the per-response nonce, and no template has an inline on*
 * handler (which the policy also blocks). Source scan plus the policy string. Run: php tools/test-csp-nonce.php
 */
$root = dirname(__DIR__);
$fail = 0;
function nzCheck(string $what, bool $ok): void { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $what . "\n"; if (!$ok) $fail++; }

require_once $root . '/inc/csp.php';

$nonce = soCspNonce();
nzCheck('the nonce is URL-safe base64 and at least 16 characters', strlen($nonce) >= 16 && preg_match('/^[A-Za-z0-9_-]+$/', $nonce) === 1);
nzCheck('the nonce is the same within one response', soCspNonce() === $nonce);
$p = soCspParse(soCspPolicy(false, $nonce));
nzCheck('script-src carries the nonce', in_array("'nonce-$nonce'", $p['script-src'] ?? [], true));
nzCheck('script-src has no unsafe-inline', !in_array("'unsafe-inline'", $p['script-src'] ?? [], true));
nzCheck('style-src keeps unsafe-inline (documented exception)', in_array("'unsafe-inline'", $p['style-src'] ?? [], true));
nzCheck('no unsafe-eval anywhere', strpos(soCspPolicy(true, $nonce), 'unsafe-eval') === false);
nzCheck('the exceptions list no longer excuses script-src unsafe-inline', !array_key_exists("script-src 'unsafe-inline'", SO_CSP_EXCEPTIONS));
nzCheck('the nonce attribute helper renders nonce="..."', soCspNonceAttr() === ' nonce="' . $nonce . '"');

// Every executable inline script in the sources carries the nonce.
$bare = [];
$handlers = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    $path = $file->getPathname();
    if (!preg_match('/\.(php|js)$/', $path)) continue;
    $rel = substr($path, strlen($root) + 1);
    if (preg_match('#^(vendor/|node_modules/|lib/|phpmailer/|tools/|deploy/|\.git/)#', $rel) || preg_match('/\.min\.js$/', $rel)) continue;
    $src = (string) file_get_contents($path);
    if (preg_match_all('/<script(\s[^>]*)?>/i', $src, $m)) {
        foreach ($m[0] as $tag) {
            if (preg_match('/\ssrc=/i', $tag) || preg_match('/type=["\']?(application\/ld\+json|application\/json|text\/template)/i', $tag)) continue;
            if (strpos($tag, 'nonce=') !== false || strpos($tag, 'soCspNonceAttr') !== false) continue;
            $bare[] = $rel . ' ' . trim($tag);
        }
    }
    if ($rel !== '' && substr($rel, -4) === '.php' && preg_match_all('/\son(load|click|change|submit|error|input|keyup|keydown|focus|blur|mouseover)=["\']/i', $src, $h)) {
        foreach ($h[0] as $hh) $handlers[] = $rel . ' ' . trim($hh);
    }
}
nzCheck('every inline script carries the nonce' . ($bare ? ' (' . count($bare) . ' missing: ' . implode(' | ', array_slice($bare, 0, 3)) . ')' : ''), !$bare);
nzCheck('no inline on* event handlers in templates' . ($handlers ? ' (' . count($handlers) . ': ' . implode(' | ', array_slice($handlers, 0, 3)) . ')' : ''), !$handlers);

echo $fail ? "\ncsp nonce: $fail failed\n" : "\ncsp nonce: all passed\n";
exit($fail ? 1 : 0);
