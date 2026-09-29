<?php
// Track A page: one performer's events, filtered to a location - here,
// a state. (See artist-city.php for the shared renderer and the
// full URL-contract note; all four dimension files are thin wrappers
// around renderArtistLocationPage() in functions.php.)
require_once 'functions.php';
renderArtistLocationPage('state', 'artist-state');
