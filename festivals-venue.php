<?php
// Track B page: every event in the "Festival" category, filtered to
// a location - here, a venue. No fixed performer (see concerts.php
// for the real, working listing pattern this is modeled on). This and its
// sibling location-dimension files are thin wrappers around the shared
// renderCategoryLocationPage() in functions.php.
//
// URL contract: one path segment, the location slug, e.g.
// /festivals-venue/austin-tx-247 - rewritten server-side into $_GET['slug'].
require_once 'functions.php';
renderCategoryLocationPage('festivals', 'Festival', 'venue', 'festivals-venue');
