<?php
// Intentionally disabled - not abandoned. This corresponds to the SEO
// documentation's "Section 2: Personalized Picks (Logged-In Users)":
// "If user is logged in: show events only from followed artists/teams/
// preferred genres/previously viewed events. If not logged in: don't show
// this section." That's the correct behavior - and this app has no public
// user accounts, login, or preference-tracking system at all (only admin
// auth exists, for the CMS). Every visitor is logged out, so per that
// spec this section should never render, which is exactly what leaving
// it unincluded (grepped: nothing includes this file) achieves.
//
// The previous version of this file was live HTML (commented out, but
// with real markup) hardcoding fabricated events/venues/prices ("Rockets
// at Lakers" at "OVO Hydro" for $124, repeated) - i.e. fake personalized
// picks for a feature with no real personalization data behind it. That
// content is removed rather than kept commented out, so it can't
// accidentally get uncommented and shipped as real content later.
//
// To build this for real: user accounts + login, a followed-artists/
// teams/genres model, and view-history tracking, then a query against
// that data (mirroring getPerformerEventsByLocation()'s pattern in
// functions.php) - not before.
