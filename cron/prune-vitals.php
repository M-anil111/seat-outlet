<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Deletes real-user speed measurements older than 90 days (db/migrations/0009_web_vitals.sql). Weekly is plenty:
 *   php cron/prune-vitals.php
 */
require_once __DIR__ . '/../db/config.php';
$mysqli->query('DELETE FROM web_vitals WHERE created_at < (NOW() - INTERVAL 90 DAY)');
printf("pruned %d web_vitals rows on %s\n", $mysqli->affected_rows, date('Y-m-d H:i:s'));
