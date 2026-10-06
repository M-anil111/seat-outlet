<?php
// Offline test: the old name-and-id address forms still resolve (so they can 301 to the clean slug). Run: php tools/test-slug-legacy.php
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/entity-pages.php';   // SO_MAX_ENTITY_ID, soAsciiFold()
require_once __DIR__ . '/../inc/slugs.php';
$fail = 0;
function slOk($c, $m) { global $fail; if (!$c) { $fail++; echo "FAIL: $m\n"; } }
[$id, $legacy] = soSlugResolve('event', 'some-name-7506179');  slOk($id === 7506179 && $legacy, 'name and id');
[$id, $legacy] = soSlugResolve('event', '-7506179');           slOk($id === 7506179 && $legacy, 'nameless "-id" form (an event with no name was linked this way)');
[$id] = soSlugResolve('performer', '-288928');                  slOk($id === 288928, 'performer nameless form');
[$id] = soSlugResolve('event', 'no-id-here');                   slOk($id === null, 'no id means no match');
[$id] = soSlugResolve('event', '-0');                           slOk($id === null, 'id 0 is not an id');
slOk(soSlugLegacyPlausible('-7506179', true, '') === true, 'long id trusted');
slOk(soSlugLegacyPlausible('-5', true, 'Blink') === false, 'short nameless id is not trusted');
echo $fail ? "$fail failure(s)\n" : "slug legacy: all passed\n"; exit($fail ? 1 : 0);
