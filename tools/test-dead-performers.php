<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline test for inc/dead-performers.php (performers whose page answers 404 are left out of the lists that link to them) and its
 * wiring. No database, no network. Run: php tools/test-dead-performers.php
 */
require_once __DIR__ . '/../inc/dead-performers.php';

$fail = 0;
function dpCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } else { echo "ok   $what\n"; }
}
$dir = sys_get_temp_dir() . '/so-dead-' . getmypid() . '/';
@mkdir($dir, 0775, true);
@unlink($dir . 'dead_performers.json');   // a run that was interrupted before may have left state under a reused PID
$t = 1800000000;

dpCheck('nothing is remembered at first', soDeadPerformerIds($dir, $t), []);
soDeadPerformerRemember(111, $dir, $t);
soDeadPerformerRemember(222, $dir, $t + 10);
dpCheck('remembered ids are returned with the time they were seen', soDeadPerformerIds($dir, $t + 20), [111 => $t, 222 => $t + 10]);
$rows = [['id' => 5, 'name' => 'A'], ['id' => 111, 'name' => 'B'], ['id' => '222', 'name' => 'C'], ['id' => 9, 'name' => 'D']];
dpCheck('dead performers are dropped from a list, the order of the rest is kept', array_column(soDropDeadPerformers($rows, $dir, $t + 20), 'name'), ['A', 'D']);
dpCheck('a list without dead ids is returned as it was', soDropDeadPerformers([['id' => 5]], $dir, $t + 20), [['id' => 5]]);
dpCheck('rows without an id are kept (never guessed dead)', soDropDeadPerformers([['name' => 'x']], $dir, $t + 20), [['name' => 'x']]);
soDeadPerformerForget(111, $dir, $t + 30);
dpCheck('a performer page that renders again removes its id at once', array_keys(soDeadPerformerIds($dir, $t + 40)), [222]);
dpCheck('an entry expires after three days', soDeadPerformerIds($dir, $t + 10 + SO_DEAD_PERFORMER_TTL + 1), []);
soDeadPerformerRemember(333, $dir, $t + SO_DEAD_PERFORMER_TTL + 100);
dpCheck('expired entries are pruned when the file is next written', array_keys(json_decode((string) file_get_contents(soDeadPerformerFile($dir)), true)), [333]);
dpCheck('zero and negative ids are ignored', (function () use ($dir, $t) { soDeadPerformerRemember(0, $dir, $t); soDeadPerformerRemember(-4, $dir, $t); return array_keys(soDeadPerformerIds($dir, $t + SO_DEAD_PERFORMER_TTL + 101)); })(), [333]);
file_put_contents(soDeadPerformerFile($dir), '{not json');
dpCheck('a damaged file reads as empty and is rewritten cleanly', [soDeadPerformerIds($dir, $t), (function () use ($dir, $t) { soDeadPerformerRemember(7, $dir, $t); return array_keys(soDeadPerformerIds($dir, $t)); })()], [[], [7]]);
@unlink(soDeadPerformerFile($dir)); @rmdir($dir);
$missing = sys_get_temp_dir() . '/so-dead-none-' . getmypid() . '/';
soDeadPerformerForget(5, $missing, $t);
dpCheck('forgetting on a healthy site writes nothing (no file, no directory)', [is_dir($missing), is_file(soDeadPerformerFile($missing))], [false, false]);

// wiring
$root = dirname(__DIR__);
$perf = file_get_contents($root . '/performer.php');
dpCheck('performer.php remembers only a real "not found" or a performer without a category', strpos($perf, 'if (tnEntityDefinitelyMissing($performer) || (!empty($performer[\'id\']) && empty($performer[\'defaultCategory\']))) soDeadPerformerRemember((int) $id);') !== false, true);
dpCheck('performer.php forgets the id when the page renders', strpos($perf, 'soDeadPerformerForget((int) $id);') !== false, true);
dpCheck('the remember call comes before the 404 page is sent', strpos($perf, 'soDeadPerformerRemember((int) $id);') < strpos($perf, "renderNotFoundPage('Performer', \$performer, \$soPerformerLabel);"), true);
$fn = file_get_contents($root . '/functions.php');
dpCheck('functions.php loads the module and filters both performer lists', strpos($fn, "require_once __DIR__ . '/inc/dead-performers.php';") !== false && strpos($fn, "\$data['results'] = soDropDeadPerformers(\$data['results']);") !== false && strpos($fn, 'array_filter(soDropDeadPerformers($data[\'results\'] ?? [])') !== false, true);
$ep = file_get_contents($root . '/inc/entity-pages.php');
dpCheck('the top performer chips skip dead ids before the cut', strpos($ep, '$perf = array_diff_key($perf, $dead);') !== false && strpos($ep, '$perf = array_diff_key($perf, $dead);') < strpos($ep, "return ['performers' => \$sort("), true);
$tp = file_get_contents($root . '/ajax/get-top-performers.php');
dpCheck('the home cards drop dead performers when built and when served from the cache', strpos($fn, 'foreach (soDropDeadPerformers($data[\'results\']) as $item) {') !== false && strpos($tp, "\$data[\$key] = soDropDeadPerformers(\$data[\$key]);") !== false && strpos($tp, 'soDropDeadPerformers') < strpos($tp, 'getEntityImage'), true);
echo $fail ? "$fail failed\n" : "dead performers: all passed\n";
exit($fail ? 1 : 0);
