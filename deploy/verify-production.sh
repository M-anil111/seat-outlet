#!/usr/bin/env bash
# READ-ONLY post-deploy check of the live site (PR #288 and #289 fixes). Sends a few GET requests and prints PASS/FAIL per check; it
# changes nothing. Four of the requests are pages that may ask TicketNetwork once each when they are not cached; the rest are static.
#
# Usage:  bash deploy/verify-production.sh [expected-commit]            (base URL: https://seatoutlet.com)
#         SITE=https://beta.seatoutlet.com bash deploy/verify-production.sh 0123abcd
#         HEALTH_TOKEN=... bash deploy/verify-production.sh             (adds the detailed health fields; the token is sent as a header
#                                                                        and never printed)
# Exit status: 0 when every check passes, 1 otherwise.
set -uo pipefail

SITE="${SITE:-https://seatoutlet.com}"; SITE="${SITE%/}"
EXPECT="${1:-}"
fails=0
pass() { printf 'PASS  %s\n' "$1"; }
fail() { printf 'FAIL  %s\n' "$1"; fails=$((fails + 1)); }
info() { printf 'INFO  %s\n' "$1"; }
tmp="$(mktemp -d)"; trap 'rm -rf "$tmp"' EXIT

# get PATH -> sets CODE, LOC; body in $tmp/body (no redirects followed)
get() {
  local out
  out="$(curl -sS -o "$tmp/body" -w '%{http_code} %{redirect_url}' --max-time 60 -A 'SeatOutlet-verify/1.0' "$SITE$1" 2>/dev/null)" || out="000 "
  CODE="${out%% *}"; LOC="${out#* }"
}
robots() { grep -oiE '<meta name="robots" content="[^"]*"' "$tmp/body" | head -1 | sed -E 's/.*content="([^"]*)"/\1/'; }
adunits() { grep -o '<ins class="adsbygoogle' "$tmp/body" | wc -l | tr -d ' '; }   # every match, not matching lines (one HTML line can hold several)

# 1. Health: the deployed commit
get /ajax/health.php
commit="$(grep -oE '"commit":"[0-9a-f]+"' "$tmp/body" | cut -d'"' -f4)"
if [ "$CODE" = 200 ] && grep -q '"ok":true' "$tmp/body"; then pass "health answers ok (commit ${commit:-unknown})"; else fail "health: HTTP $CODE, body $(head -c 120 "$tmp/body")"; fi
if [ -n "$EXPECT" ]; then
  case "$commit" in "${EXPECT:0:8}"*) pass "deployed commit is $EXPECT";; *) fail "deployed commit is ${commit:-unknown}, expected $EXPECT";; esac
fi

# 2. Detailed health (only with HEALTH_TOKEN): auto-migrate off, no pending migration, no deploy error, catalog copy
if [ -n "${HEALTH_TOKEN:-}" ]; then
  if curl -sS -o "$tmp/h" --max-time 30 -H "X-Health-Token: $HEALTH_TOKEN" "$SITE/ajax/health.php" 2>/dev/null; then
    if command -v php >/dev/null 2>&1; then
      php -r '
        $h = json_decode((string) file_get_contents($argv[1]), true);
        if (!is_array($h) || !isset($h["migrations"])) { echo "FAIL  detailed health not returned (token refused?)\n"; exit(1); }
        $f = 0; $o = function ($ok, $m) use (&$f) { echo ($ok ? "PASS  " : "FAIL  "), $m, "\n"; if (!$ok) $f++; };
        $m = $h["migrations"];
        $o(empty($m["autoMigrate"]), "auto-migrate is off (" . var_export($m["autoMigrate"] ?? null, true) . ")");
        $o((int) ($m["pending"] ?? 1) === 0, "no pending migration (" . ($m["pending"] ?? "?") . ")");
        $o(empty($h["deployError"]), "no deploy error" . (empty($h["deployError"]) ? "" : ": " . $h["deployError"]));
        $b = $h["ticketnetwork"]["bulk"] ?? null;
        echo "INFO  catalog copy: ", json_encode($b), "\n";
        echo "INFO  TicketNetwork no-data rate (visitors): ", json_encode($h["ticketnetwork"]["visitorNoDataRate"] ?? null), ", alert: ", json_encode($h["ticketnetwork"]["alert"] ?? null), "\n";
        exit($f ? 1 : 0);
      ' "$tmp/h" || fails=$((fails + 1))
    else
      info "php not found: detailed health saved unparsed"
    fi
  else
    fail "detailed health request failed"
  fi
else
  info "HEALTH_TOKEN not set: skipping auto-migrate, migration, deploy-error and catalog-copy checks"
fi

# 3. A past event redirects to its performer page (302), not 503
get /event/keller-williams-pioneertown-ca-2026-11-05
case "$CODE" in 301|302) case "$LOC" in *"/artist/"*) pass "past event redirects to the performer ($CODE)";; *) fail "past event redirects to $LOC";; esac;; *) fail "past event answered $CODE (expected 302 to /artist/)";; esac

# 4. An old name-and-id event link the catalog no longer has: 404 or a redirect, never 503
get /event/lenny-pearce-8048654
case "$CODE" in 404|301|302) pass "old event id answers $CODE (not 503)";; *) fail "old event id answered $CODE";; esac

# 5. An empty category page: 200, noindex, no ad unit
get /category/west-end
r="$(robots)"; a="$(adunits)"
[ "$CODE" = 200 ] && pass "empty category answers 200" || fail "empty category answered $CODE"
case "$r" in *noindex*) pass "empty category is noindex ($r)";; *) fail "empty category robots: '${r:-none}' (expected noindex)";; esac
[ "$a" = 0 ] && pass "empty category has no ad unit" || fail "empty category has $a ad unit(s)"

# 6. A 404 page: 404, noindex, no ad unit
get /this-page-does-not-exist-verify-check
r="$(robots)"; a="$(adunits)"
[ "$CODE" = 404 ] && pass "unknown page answers 404" || fail "unknown page answered $CODE"
[ "$a" = 0 ] && pass "404 page has no ad unit" || fail "404 page has $a ad unit(s)"

# 7. A city page prints the date strip once
get /city/boston-ma
n="$(grep -o '<div class="so-dates-wrap' "$tmp/body" | wc -l | tr -d ' ')"
[ "$CODE" = 200 ] && [ "$n" = 1 ] && pass "city page has one date strip" || fail "city page: HTTP $CODE, $n date strip(s)"

# 8. Private paths are not served
for p in /.git/HEAD /cron/backup-db.php /cache/tn_bucket.json /inc/env.local.php /deploy/pull-deploy.sh; do
  get "$p"
  case "$CODE" in 403|404) pass "$p is not served ($CODE)";; 200) if [ ! -s "$tmp/body" ]; then pass "$p returns an empty body (200)"; else fail "$p is served (200, $(wc -c < "$tmp/body") bytes)"; fi;; *) fail "$p answered $CODE";; esac
done

echo
if [ "$fails" -eq 0 ]; then echo "All checks passed."; else echo "$fails check(s) failed."; fi
[ "$fails" -eq 0 ]
