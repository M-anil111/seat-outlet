<?php
// Include-only: the statement splitter and file runner shared by db/migrate.php (command line) and inc/auto-migrate.php (beta, after deploys).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }

/**
 * Split an SQL file into statements without being fooled by semicolons inside quoted strings, backtick names or comments.
 * (No DELIMITER support: none of the migrations use it.)
 */
function migrate_split_sql(string $sql): array {
    $out = []; $buf = ''; $n = strlen($sql); $i = 0; $quote = null;
    while ($i < $n) {
        $c = $sql[$i];
        if ($quote !== null) {
            $buf .= $c;
            if ($c === '\\' && $quote !== '`' && $i + 1 < $n) { $buf .= $sql[++$i]; }          // backslash escape
            elseif ($c === $quote) {
                if ($i + 1 < $n && $sql[$i + 1] === $quote) { $buf .= $sql[++$i]; }                // doubled quote
                else { $quote = null; }
            }
        } elseif ($c === "'" || $c === '"' || $c === '`') { $quote = $c; $buf .= $c; }
        elseif (($c === '-' && substr($sql, $i, 3) === '-- ') || $c === '#') { while ($i < $n && $sql[$i] !== "\n") $i++; continue; }   // line comment
        elseif ($c === '/' && substr($sql, $i, 2) === '/*' && substr($sql, $i, 3) !== '/*!') { $e = strpos($sql, '*/', $i + 2); $i = $e === false ? $n : $e + 2; continue; }
        elseif ($c === ';') { if (trim($buf) !== '') $out[] = trim($buf); $buf = ''; }
        else { $buf .= $c; }
        $i++;
    }
    if (trim($buf) !== '') $out[] = trim($buf);
    return $out;
}

// Errors that only mean "this schema change was already made" (a migration that was interrupted part-way, or applied by hand):
// 1050 table exists, 1060 duplicate column, 1061 duplicate key name, 1091 cannot drop (does not exist).
const MIGRATE_ALREADY_DONE_ERRNOS = [1050, 1060, 1061, 1091];

function migrate_run_sql_file(mysqli $mysqli, string $path): void {
    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException("Could not read $path");
    }
    foreach (migrate_split_sql($sql) as $i => $statement) {
        try {
            $mysqli->query($statement);
        } catch (mysqli_sql_exception $e) {
            if (in_array((int) $e->getCode(), MIGRATE_ALREADY_DONE_ERRNOS, true)) {
                if (PHP_SAPI === 'cli') echo "note     statement " . ($i + 1) . " in " . basename($path) . " was already applied, skipped (" . $e->getMessage() . ")\n";
                continue;
            }
            throw new RuntimeException("Error in $path (statement " . ($i + 1) . "): " . $e->getMessage());
        }
        if ($mysqli->errno) {   // when mysqli is not set to throw exceptions
            if (in_array((int) $mysqli->errno, MIGRATE_ALREADY_DONE_ERRNOS, true)) { if (PHP_SAPI === 'cli') echo "note     statement " . ($i + 1) . " in " . basename($path) . " was already applied, skipped\n"; continue; }
            throw new RuntimeException("Error in $path (statement " . ($i + 1) . "): " . $mysqli->error);
        }
    }
}

