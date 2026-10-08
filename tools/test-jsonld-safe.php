<?php
// Offline test (no network, no database): text from a visitor (the search query) must never close a JSON-LD <script> tag.
// Run: php tools/test-jsonld-safe.php
$src = file_get_contents(__DIR__ . '/../functions.php');
preg_match("/function outputJsonLdGraph\(.*?\n}\n/s", $src, $m1);
preg_match("/function soInjectFaqSchema\(.*?\n}\n/s", $src, $m2);
if (!$m1 || !$m2) { fwrite(STDERR, "FAIL: could not find the JSON-LD functions in functions.php\n"); exit(1); }
if (!function_exists('mb_strlen') || !function_exists('mb_strpos')) { fwrite(STDERR, "FAIL: mbstring is needed\n"); exit(1); }
eval($m1[0] . $m2[0]);

$fails = 0;
function jsonldCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

$evil = '</script><script>window.__x=1</script>';
ob_start();
outputJsonLdGraph([['@type' => 'SearchResultsPage', 'name' => $evil . ' Tickets', 'description' => $evil]]);
$out = ob_get_clean();
jsonldCheck('graph output has exactly one </script>', substr_count(strtolower($out), '</script>') === 1);
jsonldCheck('graph output has exactly one <script', substr_count(strtolower($out), '<script') === 1);
preg_match('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $out, $m);
$d = json_decode($m[1] ?? '', true);
jsonldCheck('graph JSON still decodes to the original text', ($d['@graph'][0]['name'] ?? '') === $evil . ' Tickets');

$GLOBALS['pageCanonicalUrl'] = 'https://seatoutlet.com/search';
$html = '<html><body><details class="so-faq"><summary>Is ' . htmlspecialchars($evil, ENT_QUOTES) . ' ok here?</summary><p>An answer that is long enough to count as one.</p></details>'
      . '<details class="so-faq"><summary>Second question here?</summary><p>Another answer that is long enough to count too.</p></details></body></html>';
$res = soInjectFaqSchema($html);
jsonldCheck('FAQ schema was added', $res !== $html);
jsonldCheck('FAQ schema tag does not let text close the script', substr_count(strtolower($res), '</script>') === 1);
echo $fails ? "$fails failed\n" : "jsonld safe: all passed\n";
exit($fails ? 1 : 0);
