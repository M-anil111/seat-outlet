<?php
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/cron-report.php'; soCronWatch('home-top-performers');   // a failure reaches the error log and Sentry
require_once __DIR__ . '/../functions.php';

// The site also refreshes this by itself in the background (inc/top-performers.php); this script is for a cron or a manual run.
echo soTopPerformersRefresh() ? "home top performers cache refreshed on " . date('Y-m-d H:i:s') : "API returned nothing; kept the old cache";
