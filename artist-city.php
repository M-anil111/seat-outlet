<?php
// Track A page: one performer's events, filtered to a location - here,
// a city. (See artist-state.php/artist-country.php/artist-venue.php for
// the other three dimensions - all four are thin wrappers around the
// shared renderArtistLocationPage() in functions.php.)
//
// URL contract: two path segments - performer slug, then location slug,
// e.g. /artist-city/taylor-swift-1234/austin-tx-247 - rewritten server-side
// into $_GET['slug'] (performer) and $_GET['loc'] (location). That rewrite
// isn't in this repo (no .htaccess exists here - see CONTRIBUTING.md); if
// $_GET['loc'] isn't arriving yet, the rewrite rule needs adding first.
require_once 'functions.php';
renderArtistLocationPage('city', 'artist-city');
