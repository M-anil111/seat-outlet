<?php
/**
 * Performers whose page answers 404, so no list links to them (QA-007: category and directory pages linked to /artist/<slug> pages
 * that said "Performer not found").
 *
 * A performer can appear in an event's performer list, or in a stale directory list, while its own page has nothing to show: the
 * catalog no longer has it, or it has no default category. The performer page already answers 404 for both. This remembers that answer
 * for three days in cache/dead_performers.json (id => time), and the chip lists and performer lists drop those ids, so the dead link
 * stops being offered. A performer page that renders again removes its id at once. The first visit to a dead page still sees the 404,
 * which is the honest answer; the cost of a wrong entry is a missing link for three days, never a missing page.
 * Kept free of the rest of the site so tools/test-dead-performers.php can load it ($dir and $now are for tests).
 */

const SO_DEAD_PERFORMER_TTL = 259200;   // 3 days

function soDeadPerformerFile(?string $dir = null): string {
    return ($dir ?? cache_dir()) . 'dead_performers.json';
}

/** @return array<int,int> performer id => unix time it was last seen dead, only the entries still inside the window */
function soDeadPerformerIds(?string $dir = null, ?int $now = null): array {
    $now = $now ?? time();
    $raw = @file_get_contents(soDeadPerformerFile($dir));
    $all = $raw === false || $raw === '' ? [] : json_decode($raw, true);
    if (!is_array($all)) return [];
    $fresh = [];
    foreach ($all as $id => $seen) {
        if ((int) $id > 0 && (int) $seen > $now - SO_DEAD_PERFORMER_TTL) $fresh[(int) $id] = (int) $seen;
    }
    return $fresh;
}

/** Add ($dead true) or remove ($dead false) one id, pruning expired entries; one small locked rewrite, and a no-op when nothing changes. */
function soDeadPerformerSet(int $id, bool $dead, ?string $dir = null, ?int $now = null): void {
    if ($id <= 0) return;
    $now = $now ?? time();
    try {
        $file = soDeadPerformerFile($dir);
        if (!$dead && !is_file($file)) return;   // the common case on a healthy page: nothing to read, nothing to write
        $fh = @fopen($file, 'c+');
        if (!$fh) return;
        if (flock($fh, LOCK_EX)) {
            $raw = stream_get_contents($fh);
            $all = $raw === '' || $raw === false ? [] : json_decode($raw, true);
            $all = is_array($all) ? $all : [];
            $before = $all;
            foreach ($all as $k => $seen) { if ((int) $seen <= $now - SO_DEAD_PERFORMER_TTL) unset($all[$k]); }
            if ($dead) $all[$id] = $now; else unset($all[$id]);
            if ($all !== $before) {
                ftruncate($fh, 0);
                rewind($fh);
                fwrite($fh, json_encode($all));
                fflush($fh);
            }
            flock($fh, LOCK_UN);
        }
        fclose($fh);
    } catch (Throwable $e) {
        // remembering never breaks a page
    }
}

function soDeadPerformerRemember(int $id, ?string $dir = null, ?int $now = null): void { soDeadPerformerSet($id, true, $dir, $now); }
function soDeadPerformerForget(int $id, ?string $dir = null, ?int $now = null): void { soDeadPerformerSet($id, false, $dir, $now); }

/** A list of performer rows (each with an 'id') without the dead ones; the order of the rest is kept. */
function soDropDeadPerformers(array $rows, ?string $dir = null, ?int $now = null): array {
    $dead = soDeadPerformerIds($dir, $now);
    if (!$dead || !$rows) return $rows;
    return array_values(array_filter($rows, fn($r) => !isset($dead[(int) (is_array($r) ? ($r['id'] ?? 0) : 0)])));
}
