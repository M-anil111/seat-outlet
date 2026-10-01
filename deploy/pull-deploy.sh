#!/usr/bin/env bash
#
# Pull-based deploy for Seat Outlet beta. Runs ON the web server from cron, so
# it only needs OUTBOUND access to GitHub: no inbound SFTP port, no GitHub
# Actions IP allow-list, no self-hosted runner.
#
#   */2 * * * * /home/seatoutlet-beta/deploy/pull-deploy.sh >> /home/seatoutlet-beta/deploy/deploy.log 2>&1
#
# Every run: fetch the branch; if it moved, copy the new code into the web
# root, apply database migrations, and record the deployed commit. If nothing
# changed it exits in about a second. A lock stops overlapping runs.
#
# Configuration (environment variables, all optional):
#   DEPLOY_REPO      git URL         default git@github.com:M-anil111/seat-outlet.git
#   DEPLOY_BRANCH    branch to ship  default main
#   DEPLOY_WEBROOT   live site dir   default /home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet
#                                    (the CloudPanel site root)
#   DEPLOY_WORKDIR   checkout dir    default /home/seatoutlet-beta/deploy/checkout
#   DEPLOY_MIGRATE   1 = run php db/migrate.php after copying (default 1)
#   DEPLOY_PHP       php binary      default php
#
# Secrets are NOT in the web root: they live in ~/.seatoutlet/env.local.php
# (outside htdocs; override with SEATOUTLET_ENV_FILE), which inc/env.php loads
# at runtime, so a deploy can never overwrite or delete them. A legacy
# inc/env.local.php in the web root is also left untouched. Existing files
# under cache/ (runtime feed and image cache) are kept. Files are only added
# or overwritten, never deleted.
set -euo pipefail

REPO="${DEPLOY_REPO:-git@github.com:M-anil111/seat-outlet.git}"
BRANCH="${DEPLOY_BRANCH:-main}"
WEBROOT="${DEPLOY_WEBROOT:-/home/seatoutlet-beta/htdocs/beta.seatoutlet.com/seat-outlet}"
SECRETS="${SEATOUTLET_ENV_FILE:-$HOME/.seatoutlet/env.local.php}"
WORKDIR="${DEPLOY_WORKDIR:-/home/seatoutlet-beta/deploy/checkout}"
MIGRATE="${DEPLOY_MIGRATE:-1}"
PHP_BIN="${DEPLOY_PHP:-php}"
STATE_DIR="$(dirname "$WORKDIR")"
STAMP="$WEBROOT/.deployed-commit"

mkdir -p "$STATE_DIR" "$WEBROOT"
exec 9>"$STATE_DIR/.lock"
flock -n 9 || { echo "$(date -u +%FT%TZ) another deploy is running, skipping"; exit 0; }

log() { echo "$(date -u +%FT%TZ) $*"; }

if [ ! -d "$WORKDIR/.git" ]; then
  log "first run: cloning $REPO ($BRANCH)"
  git clone --quiet --branch "$BRANCH" --single-branch "$REPO" "$WORKDIR"
fi

cd "$WORKDIR"
git fetch --quiet origin "$BRANCH"
NEW="$(git rev-parse "origin/$BRANCH")"
OLD="$(cat "$STAMP" 2>/dev/null || echo none)"

if [ "$NEW" = "$OLD" ]; then
  exit 0
fi

log "deploying ${OLD:0:8} -> ${NEW:0:8}"
git reset --quiet --hard "origin/$BRANCH"

# Code: everything except .git, the secrets file and the runtime cache.
tar --exclude=./.git --exclude=./cache --exclude=./inc/env.local.php \
    --exclude=./deploy -cf - . | tar -C "$WEBROOT" -xf -

# Seed feeds: copy cache/ files only where the server does not have them yet.
if [ -d cache ]; then
  mkdir -p "$WEBROOT/cache"
  cp -a --update=none cache/. "$WEBROOT/cache/" 2>/dev/null || cp -an cache/. "$WEBROOT/cache/"
fi

HAVE_SECRETS=0
if [ -f "$SECRETS" ] || [ -f "$WEBROOT/inc/env.local.php" ]; then
  HAVE_SECRETS=1
else
  log "WARNING: no secrets file ($SECRETS) - the site has no DB password or API keys until it is created (see deploy/env.local.php.example)"
fi

if [ "$MIGRATE" = "1" ] && [ "$HAVE_SECRETS" = "1" ]; then
  log "applying database migrations"
  (cd "$WEBROOT" && "$PHP_BIN" db/migrate.php) || { log "MIGRATION FAILED - code is live, deployed marker NOT updated so the next run retries"; exit 1; }
fi

echo "$NEW" > "$STAMP"
log "deployed $NEW"
