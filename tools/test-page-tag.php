<?php
// ?page=N titles, descriptions and H1s differ from page one. Run: php tools/test-page-tag.php
require __DIR__ . '/../inc/title.php';
$fail = 0;
function ptCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
$src = file_get_contents(__DIR__ . '/../functions.php');
preg_match('/function soPageTagH1\(\$html\) \{.*?\n\}\n/s', $src, $m);
eval($m[0]);
$GLOBALS['soPageTag'] = ' (Page 3)';
ptCheck('first plain H1 gets the number', soPageTagH1('<h1 class="x">Broadway Shows</h1><h1>Other</h1>') === '<h1 class="x">Broadway Shows (Page 3)</h1><h1>Other</h1>');
ptCheck('an H1 holding markup is left alone', soPageTagH1('<h1>A <em>b</em></h1>') === '<h1>A <em>b</em></h1>');
$GLOBALS['soPageTag'] = '';
ptCheck('page one is untouched', soPageTagH1('<h1>Broadway Shows</h1>') === '<h1>Broadway Shows</h1>');
$hdr = file_get_contents(__DIR__ . '/../header.php');
ptCheck('header tags title and description for page two and up', strpos($hdr, "\$soPageTag = \$soPageNo >= 2") !== false && strpos($hdr, 'seoClampTitle($pageMetaTitle, (!empty($pageTitleMax)') !== false);
$t = seoClampTitle('Broadway Shows Tickets at Seat Outlet', 59 - 9) . ' (Page 2)';
ptCheck('title with the tag stays within 59', mb_strlen($t) <= 59 && substr($t, -9) === ' (Page 2)');
exit($fail ? 1 : 0);
