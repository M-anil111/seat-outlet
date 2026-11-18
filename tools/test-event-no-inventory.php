<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * An event page whose catalog record says no ticket is listed is noindex,follow; every other record is left as it is. Run: php tools/test-event-no-inventory.php
 */
$fails = 0;
function niCheck(string $name, bool $ok): void { global $fails; if (!$ok) { echo "FAIL $name\n"; $fails++; } }
require_once __DIR__ . '/../inc/event-redirects.php';
niCheck('hasTickets false: no inventory', soEventHasNoInventory(['_metadata' => ['hasTickets' => false, 'ticketCount' => 0]]) === true);
niCheck('hasTickets 0: no inventory', soEventHasNoInventory(['_metadata' => ['hasTickets' => 0]]) === true);
niCheck('hasTickets true: has inventory', soEventHasNoInventory(['_metadata' => ['hasTickets' => true, 'ticketCount' => 12]]) === false);
niCheck('no _metadata (a saved copy, a stand-in): never treated as empty', soEventHasNoInventory(['id' => 1]) === false);
niCheck('_metadata without the field: never treated as empty', soEventHasNoInventory(['_metadata' => ['ticketCount' => 0]]) === false);
niCheck('not an event: false', soEventHasNoInventory(null) === false && soEventHasNoInventory('x') === false);
$h = (string) file_get_contents(__DIR__ . '/../header.php');
niCheck('header.php applies it to a live event page', strpos($h, "if (soEventHasNoInventory(\$soEvCheck)) { \$pageRobots = 'noindex, follow'; }") !== false);
niCheck('only in the branch of an event that is live and upcoming (after the redirects and 404)', strpos($h, 'soEventHasNoInventory($soEvCheck)') > strpos($h, 'soEventRemember($soEvCheck);'));
if ($fails) { echo "$fails check(s) failed\n"; exit(1); }
echo "ok: an event with no tickets listed is noindex,follow\n";
