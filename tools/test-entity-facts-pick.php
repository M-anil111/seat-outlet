<?php
// Wikidata match for a venue must not be a same-named venue in another city. Run: php tools/test-entity-facts-pick.php
define('SO_TEST', 1);
require __DIR__ . '/../inc/entity-facts.php';
$fail = 0;
function efCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
$hit = fn($desc) => ['search' => [['id' => 'Q1', 'label' => 'Palace of Fine Arts', 'description' => $desc]]];

efCheck('the same name in another city is rejected', soFactsPick($hit('building in Mexico City'), 'Palace of Fine Arts', 'venue', 'San Francisco') === null);
efCheck('the right city is kept', soFactsPick($hit('exhibition hall in San Francisco, California'), 'Palace of Fine Arts', 'venue', 'San Francisco') !== null);
efCheck('a description with no place is kept', soFactsPick($hit('arena'), 'Palace of Fine Arts', 'venue', 'San Francisco') !== null);
efCheck('no city given: unchanged', soFactsPick($hit('building in Mexico City'), 'Palace of Fine Arts', 'venue', '') !== null);
$perf = ['search' => [['id' => 'Q2', 'label' => 'Daniel Sloss', 'description' => 'comedian in Scotland']]];
efCheck('performers are not affected', soFactsPick($perf, 'Daniel Sloss', 'performer', 'Glasgow') !== null);
exit($fail ? 1 : 0);
