<?php
// Include-only: narrowly scoped recovery for standalone, buffered catalogue reads.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }

/** Retry one standalone SELECT on a fresh connection after an idle connection is lost. Never replay writes. */
function soDbRead(string $sql, array $params = [], string $types = '', $mysqli = MYSQLI) {
    if (!preg_match('/^\s*SELECT\b/i', $sql)) throw new InvalidArgumentException('soDbRead only accepts SELECT statements');
    for ($attempt = 0; $attempt < 2; $attempt++) {
        try {
            if (!$params) return $mysqli->query($sql);
            $stmt = $mysqli->prepare($sql);
            try {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                return $stmt->get_result();
            } finally {
                $stmt->close();
            }
        } catch (mysqli_sql_exception $e) {
            if ($attempt !== 0 || !in_array((int) $e->getCode(), [2006, 2013], true)) throw $e;
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            $mysqli->set_charset('utf8mb4');
        }
    }
}
