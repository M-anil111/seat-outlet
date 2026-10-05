<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Asks TicketNetwork, once per directory, whether its category filter works on the performers endpoint and how many names come back.
 * Run on the server (it needs the real API keys), after a deploy and after any API change:
 *
 *   php tools/check-directory-api.php
 *
 * Exit 0 when every directory returns at least one performer. A directory that fails or comes back empty is printed with the filter
 * used, so the filter or the category path in inc/directories.php can be corrected. Not part of CI: CI has no API access.
 */
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/directories.php';

$bad = 0;
foreach (SO_DIRECTORIES as $key => $cfg) {
    $filters = [];
    if (!empty($cfg['category'])) $filters[] = "startswith(defaultCategory/path, '" . tnEscapeFilterValue($cfg['category']) . "')";
    $params = ['perPage' => 5, 'sort' => 'text/name'];
    if ($filters) $params['filter'] = implode(' and ', $filters);
    try {
        $res = getTnPerformers($params);
        $n = count($res['results'] ?? []);
        $total = $res['totalCount'] ?? ($res['total'] ?? '?');
        $first = $n ? (string) ($res['results'][0]['text']['name'] ?? '') : '';
        printf("%-22s %s  %d returned, total %s%s\n", $key, $n > 0 ? 'OK  ' : 'EMPTY', $n, $total, $first !== '' ? ', first: ' . $first : '');
        if ($n === 0) { $bad++; echo '    filter: ' . ($params['filter'] ?? '(none)') . "\n"; }
    } catch (Throwable $e) {
        $bad++;
        printf("%-22s FAIL  %s\n    filter: %s\n", $key, $e->getMessage(), $params['filter'] ?? '(none)');
    }
}
exit($bad ? 1 : 0);
