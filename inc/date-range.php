<?php
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/** Accept real ISO calendar dates in chronological order; invalid input means no custom range. */
function soValidDateRange(string $start, string $end): ?array {
    foreach ([$start, $end] as $value) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) return null;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) return null;
    }
    return $start <= $end ? [$start, $end] : null;
}
