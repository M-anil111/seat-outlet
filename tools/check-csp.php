<?php
/**
 * CSP validation (CI, no database). Fails when:
 *   - the policy lacks default-src 'self', object-src 'none' or base-uri 'self', or contains 'unsafe-eval';
 *   - a wildcard host, scheme source or 'unsafe-*' keyword is in the policy without an entry in SO_CSP_EXCEPTIONS, or an entry no longer matches the policy;
 *   - the header value does not parse back to the policy (a typo that browsers would silently ignore);
 *   - a template or script loads an external script, style sheet, frame, image or font host that the policy does not allow for that kind of load.
 * Run: php tools/check-csp.php [-v]
 */
require_once __DIR__ . '/../inc/csp.php';
$root = dirname(__DIR__);
$verbose = in_array('-v', $argv ?? [], true);
$fail = 0;
function cspCheck(string $label, bool $ok, string $detail = ''): void { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . ($ok || $detail === '' ? '' : ': ' . $detail) . "\n"; if (!$ok) $fail++; }

$policy = SO_CSP_POLICY;
cspCheck("default-src is 'self'", ($policy['default-src'] ?? []) === ["'self'"]);
cspCheck("object-src is 'none'", ($policy['object-src'] ?? []) === ["'none'"]);
cspCheck("base-uri is 'self'", ($policy['base-uri'] ?? []) === ["'self'"]);
$all = []; foreach ($policy as $d => $s) foreach ($s as $x) $all[] = strtolower($x);
cspCheck("no 'unsafe-eval' (the seat map needs it: documented in docs/csp.md, not allowed)", !in_array("'unsafe-eval'", $all, true) && !isset(SO_CSP_EXCEPTIONS["script-src 'unsafe-eval'"]));
cspCheck("no bare * and no http: source", !in_array('*', $all, true) && !in_array('http:', $all, true));
foreach (['script-src', 'style-src', 'frame-src', 'connect-src', 'object-src'] as $d) cspCheck("$d never allows a scheme-wide source (https: http: data: blob:)", !array_intersect($policy[$d] ?? [], ['https:', 'http:', 'data:', 'blob:']));

// header round trip
$header = soCspPolicy(false);
$parsed = soCspParse($header);
$roundtrip = $parsed; unset($roundtrip['report-uri']);
cspCheck('the header value parses back to the policy', $roundtrip === $policy);
cspCheck('violations are reported to /ajax/csp-report.php', ($parsed['report-uri'] ?? []) === ['/ajax/csp-report.php']);
cspCheck("frame-ancestors is added only when enforcing", !isset($parsed['frame-ancestors']) && (soCspParse(soCspPolicy(true))['frame-ancestors'] ?? []) === ["'self'"]);

// every risky source documented, every documented source present
$risky = soCspRiskySources(SO_CSP_POLICY);
$undocumented = array_values(array_diff($risky, array_keys(SO_CSP_EXCEPTIONS)));
$stale = array_values(array_diff(array_keys(SO_CSP_EXCEPTIONS), $risky));
cspCheck('every wildcard, scheme and unsafe source has a documented reason', !$undocumented, implode('; ', $undocumented));
cspCheck('every documented exception is still in the policy', !$stale, implode('; ', $stale));
cspCheck('every reason is a real sentence', !array_filter(SO_CSP_EXCEPTIONS, fn($r) => strlen($r) < 25));
$doc = (string) @file_get_contents($root . '/docs/csp.md');
cspCheck('docs/csp.md exists and names each exception', $doc !== '' && !array_filter(array_keys(SO_CSP_EXCEPTIONS), fn($k) => strpos($doc, $k) === false), 'missing in docs/csp.md: ' . implode('; ', array_filter(array_keys(SO_CSP_EXCEPTIONS), fn($k) => strpos($doc, $k) === false)));

// hosts the templates and scripts load
$kinds = ['dynamic' => 'script-src', 'script' => 'script-src', 'style' => 'style-src', 'frame' => 'frame-src', 'img' => 'img-src', 'font' => 'font-src', 'connect' => 'connect-src'];
$found = [];   // "kind host" => [files]
$skipDirs = '#^(\.git|vendor|node_modules|cache|docs|tools|db|deploy|worker|phpmailer)/#';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($root) + 1);
    if (preg_match($skipDirs, $rel) || !preg_match('/\.(php|js)$/', $rel) || substr($rel, -7) === '.min.js') continue;
    $src = (string) file_get_contents($f->getPathname());
    $add = function (string $kind, string $url) use (&$found, $rel) { if (preg_match('#^https://[^/\s\'"<>?]+#i', $url, $m)) $found[$kind . ' ' . strtolower(substr($m[0], 8))][$rel] = true; };
    if (preg_match_all('#<script\b[^>]*\bsrc=["\'](https://[^"\']+)#i', $src, $m)) foreach ($m[1] as $u) $add('script', $u);
    if (preg_match_all('#<link\b[^>]*\brel=["\']stylesheet["\'][^>]*\bhref=["\'](https://[^"\']+)#i', $src, $m)) foreach ($m[1] as $u) $add('style', $u);
    if (preg_match_all('#<link\b[^>]*\bhref=["\'](https://[^"\']+)["\'][^>]*\brel=["\']stylesheet#i', $src, $m)) foreach ($m[1] as $u) $add('style', $u);
    if (preg_match_all('#<iframe\b[^>]*\bsrc=["\'](https://[^"\']+)#i', $src, $m)) foreach ($m[1] as $u) $add('frame', $u);
    if (preg_match_all('#@import\s+(?:url\()?["\']?(https://[^"\')\s]+)#i', $src, $m)) foreach ($m[1] as $u) $add('style', $u);
    if (preg_match_all('#\bfetch\(\s*["\'](https://[^"\']+)#', $src, $m)) foreach ($m[1] as $u) $add('connect', $u);
    if (preg_match_all('#\.src\s*=\s*["\'](https://[^"\']+)#', $src, $m)) foreach ($m[1] as $u) $add('dynamic', $u);   // a script, frame or image added by JavaScript
}
ksort($found);
$bad = [];
foreach ($found as $key => $files) {
    [$kind, $host] = explode(' ', $key, 2);
    $ok = soCspUrlAllowed('https://' . $host . '/', $policy[$kinds[$kind]] ?? [], '')
        || ($kind === 'dynamic' && (soCspUrlAllowed('https://' . $host . '/', $policy['frame-src'] ?? [], '') || soCspUrlAllowed('https://' . $host . '/', $policy['img-src'] ?? [], '')));
    if ($verbose) echo '     ' . ($ok ? 'allowed ' : 'BLOCKED ') . $kinds[$kind] . ' ' . $host . '  (' . implode(', ', array_keys($files)) . ")\n";
    if (!$ok) $bad[] = $kinds[$kind] . ' ' . $host . ' in ' . implode(', ', array_keys($files));
}
cspCheck('every external script, style sheet, frame and fetch host in the code is allowed by its directive (' . count($found) . ' found)', !$bad, implode('; ', $bad));

// the URL matcher itself
cspCheck('matcher: exact host', soCspUrlAllowed('https://cdn.jsdelivr.net/x.js', ['https://cdn.jsdelivr.net']));
cspCheck('matcher: wildcard covers a subdomain', soCspUrlAllowed('https://mapwidget3.seatics.com/a', ['https://*.seatics.com']));
cspCheck('matcher: wildcard does not cover the bare domain', !soCspUrlAllowed('https://seatics.com/a', ['https://*.seatics.com']));
cspCheck('matcher: a look-alike host is refused', !soCspUrlAllowed('https://evilseatics.com/a', ['https://*.seatics.com']) && !soCspUrlAllowed('https://seatics.com.evil.test/a', ['https://*.seatics.com']));
cspCheck("matcher: 'self' needs the site host", soCspUrlAllowed('https://example.test/a', ["'self'"], 'example.test') && !soCspUrlAllowed('https://other.test/a', ["'self'"], 'example.test'));
cspCheck('matcher: https: allows any https host and no http host', soCspUrlAllowed('https://x.test/a', ['https:']) && !soCspUrlAllowed('http://x.test/a', ['https:']));
cspCheck('risky: finds a wildcard, a scheme, unsafe-eval and data: in script-src', soCspRiskySources(['script-src' => ["'self'", 'https://*.a.test', 'https:', "'unsafe-eval'", 'data:'], 'img-src' => ['data:']]) === ['script-src https://*.a.test', 'script-src https:', "script-src 'unsafe-eval'", 'script-src data:']);
exit($fail ? 1 : 0);
