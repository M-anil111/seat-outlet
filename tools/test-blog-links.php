<?php
// Old numeric-id links in blog posts are rewritten to the final address. Run: php tools/test-blog-links.php
require __DIR__ . '/../inc/blog-render.php';
$fail = 0;
function blCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
$res = fn($type, $name, $id) => $id === 3027 ? 'new-york-ny' : strtolower(str_replace(' ', '-', $name)) . '-' . $id;   // the registry knows only New York
blCheck('a city link loses its id', soBlogModernLinks('<a href="/concerts-city/new-york-ny-3027">x</a>', $res) === '<a href="/concerts-city/new-york-ny">x</a>');
blCheck('event-city becomes the city page', soBlogModernLinks('<a href="/event-city/new-york-ny-3027">x</a>', $res) === '<a href="/city/new-york-ny">x</a>');
blCheck('theater-city keeps its family', soBlogModernLinks('<a href="/theater-city/new-york-ny-3027">x</a>', $res) === '<a href="/theater-city/new-york-ny">x</a>');
$same = '<a href="/concerts-city/austin-tx-247">x</a>';
blCheck('a city the registry does not know is left as written', soBlogModernLinks($same, $res) === $same);
$other = '<a href="/artist/taylor-swift">x</a><a href="/event/betty-new-york-ny-2026-12-06">y</a>';
blCheck('other links are untouched', soBlogModernLinks($other, $res) === $other);
exit($fail ? 1 : 0);
