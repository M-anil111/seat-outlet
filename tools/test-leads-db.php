<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test for email sign-ups (inc/leads-core.php): a new address, a repeat that adds an interest and fills a missing name, and what the
 * visitor is told in both cases. Needs the migrated database. Uses addresses at zq-test.example and deletes them at the end.
 * Run: php tools/test-leads-db.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
putenv('RECAPTCHA_SECRET_KEY=dummy'); putenv('SO_RECAPTCHA_SKIP=1');
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../inc/constants.php';
require_once __DIR__ . '/../inc/leads-core.php';

$fail = 0;
function ldCheck(string $what, $got, $want): void { global $fail; if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } else echo "ok   $what\n"; }
$clean = function () { MYSQLI->query("DELETE li FROM lead_interests li JOIN leads l ON l.id = li.lead_id WHERE l.email LIKE '%@zq-test.example'"); MYSQLI->query("DELETE FROM leads WHERE email LIKE '%@zq-test.example'"); MYSQLI->query('DELETE FROM rate_limits'); };   // the test database only: keeps this run inside the sign-up limits
$clean();

$a = soLeadSubmit(['email' => 'Ada@ZQ-Test.example', 'fname' => '', 'lname' => '', 'source' => 'home', 'token' => 'x']);
ldCheck('a new address is stored', [$a['status'], $a['new']], ['success', true]);
$count = fn() => (int) MYSQLI->query("SELECT COUNT(*) FROM leads WHERE email = 'ada@zq-test.example'")->fetch_row()[0];
ldCheck('the address is stored once, in lower case', $count(), 1);

$b = soLeadSubmit(['email' => 'ada@zq-test.example', 'fname' => 'Ada', 'lname' => 'Zq', 'source' => 'artist-follow', 'interest_type' => 'performer', 'interest_id' => 9100001, 'interest_name' => 'Zq Band', 'token' => 'x']);
ldCheck('a repeat is recognised inside the server', [$b['status'], $b['new'], $b['after']], ['duplicate', false, null]);
ldCheck('...and no second row is made', $count(), 1);
$r = MYSQLI->query("SELECT fname, lname FROM leads WHERE email = 'ada@zq-test.example'")->fetch_assoc();
ldCheck('...it fills in the name we did not have', [$r['fname'], $r['lname']], ['Ada', 'Zq']);
ldCheck('...and adds the interest', (int) MYSQLI->query("SELECT COUNT(*) FROM lead_interests li JOIN leads l ON l.id = li.lead_id WHERE l.email = 'ada@zq-test.example' AND li.interest_id = 9100001")->fetch_row()[0], 1);
$pa = soLeadPublic($a); $pb = soLeadPublic($b);
ldCheck('the visitor hears the same thing for a new and a repeat address', [$pb['status'], $pb['message']], [$pa['status'], $pa['message']]);
ldCheck('the unsubscribe token survives a repeat sign-up', strlen(soLeadEnsureToken($b['lead_id'])) >= 20, true);
ldCheck('an invalid address is rejected', soLeadSubmit(['email' => 'nope', 'token' => 'x'])['status'], 'invalid');

$clean();
exit($fail ? 1 : 0);
