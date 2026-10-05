#!/usr/bin/env bash
#
# Copy the LIVE database over the BETA database, so beta has real-looking data to test against. Run it ON THE SERVER by hand
# (or from a weekly cron if you want beta refreshed). It only ever writes to the database named in BETA_DB, and refuses to run
# unless that name contains "beta" and differs from LIVE_DB.
#
#   LIVE_DB=seatoutlet_live BETA_DB=seatoutlet_beta ./clone-live-db-to-beta.sh
#
# Credentials come from MySQL option files, not from this script (so no password is ever in a command line or in git):
#   ~/.my-live.cnf   [client] user=... password=...     (read-only user is enough)
#   ~/.my-beta.cnf   [client] user=... password=...     (a user that can write BETA_DB)
# chmod 600 both files.
#
# Personal data: beta must not email real people (the site's mailer is switched off on beta unless SO_ALLOW_BETA_MAIL=1), and
# this script deletes the sign-up lists from the copy (leads, lead_interests, newsletter_leads, contact_messages) so a copy of
# live customer data does not sit on the test site. Admin logins are kept so you can log in.
set -euo pipefail

LIVE_DB="${LIVE_DB:?set LIVE_DB}"
BETA_DB="${BETA_DB:?set BETA_DB}"
LIVE_CNF="${LIVE_CNF:-$HOME/.my-live.cnf}"
BETA_CNF="${BETA_CNF:-$HOME/.my-beta.cnf}"

case "$BETA_DB" in *beta*) ;; *) echo "refusing: BETA_DB ($BETA_DB) does not contain 'beta'"; exit 1 ;; esac
[ "$LIVE_DB" != "$BETA_DB" ] || { echo "refusing: LIVE_DB and BETA_DB are the same"; exit 1; }
[ -r "$LIVE_CNF" ] && [ -r "$BETA_CNF" ] || { echo "missing $LIVE_CNF or $BETA_CNF"; exit 1; }

TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
echo "dumping $LIVE_DB ..."
mysqldump --defaults-extra-file="$LIVE_CNF" --single-transaction --quick --no-tablespaces "$LIVE_DB" | gzip > "$TMP/live.sql.gz"
echo "restoring into $BETA_DB (replaces its tables) ..."
gunzip -c "$TMP/live.sql.gz" | mysql --defaults-extra-file="$BETA_CNF" "$BETA_DB"

echo "removing sign-up and contact data from the beta copy ..."
for t in lead_interests leads newsletter_leads contact_messages price_alerts; do
  mysql --defaults-extra-file="$BETA_CNF" "$BETA_DB" -e "SET FOREIGN_KEY_CHECKS=0; TRUNCATE TABLE \`$t\`;" 2>/dev/null || true
done
echo "done. Beta now has live's data (minus customer lists) and its own schema_migrations; the next beta deploy applies any newer migrations."
