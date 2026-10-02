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
#   DEPLOY_WEBROOT   live site dir   default /home/seatoutlet-beta/htdocs/beta.seatoutlet.com
#   DEPLOY_WORKDIR   checkout dir    default /home/seatoutlet-beta/deploy/checkout
#   DEPLOY_MIGRATE   1 = run php db/migrate.php after copying (default 1)
#   DEPLOY_PHP       php binary      default php
#
# Never touched by a deploy: inc/env.local.php (the server's own secrets file)
# and existing files under cache/ (runtime feed and image cache). Files that
# were removed from git since the last deploy are removed from the web root too
# (so a deleted vulnerable file cannot stay live); nothing else is deleted.
# Repo-only files (.github, docs, CONTRIBUTING.md, composer.json/lock, .env.example) are not copied to the web root.
set -euo pipefail

REPO="${DEPLOY_REPO:-git@github.com:M-anil111/seat-outlet.git}"
BRANCH="${DEPLOY_BRANCH:-main}"
WEBROOT="${DEPLOY_WEBROOT:-/home/seatoutlet-beta/htdocs/beta.seatoutlet.com}"
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
# Files deleted in git since the last deploy (computed before the checkout moves; skipped on the first deploy).
REMOVED=""
if [ "$OLD" != "none" ] && git cat-file -e "$OLD^{commit}" 2>/dev/null; then
  REMOVED="$(git diff --name-only --diff-filter=D "$OLD" "origin/$BRANCH" || true)"
fi
git reset --quiet --hard "origin/$BRANCH"

# Code: everything except .git, the secrets file and the runtime cache.
tar --exclude=./.git --exclude=./cache --exclude=./inc/env.local.php \
    --exclude=./deploy --exclude=./.github --exclude=./docs --exclude=./CONTRIBUTING.md \
    --exclude=./composer.json --exclude=./composer.lock --exclude=./.env.example -cf - . | tar -C "$WEBROOT" -xf -

# Remove what was deleted in git (never the secrets file or anything under cache/, and never outside the web root).
if [ -n "$REMOVED" ]; then
  while IFS= read -r f; do
    case "$f" in ''|/*|*..*|cache/*|inc/env.local.php) continue ;; esac
    [ -f "$WEBROOT/$f" ] && { rm -f -- "$WEBROOT/$f"; log "removed $f"; }
  done <<< "$REMOVED"
fi

# Seed feeds: copy cache/ files only where the server does not have them yet.
if [ -d cache ]; then
  mkdir -p "$WEBROOT/cache"
  cp -a --update=none cache/. "$WEBROOT/cache/" 2>/dev/null || cp -an cache/. "$WEBROOT/cache/"
fi

if [ ! -f "$WEBROOT/inc/env.local.php" ]; then
  log "WARNING: $WEBROOT/inc/env.local.php is missing - the site has no API keys or DB password until it is created (see deploy/env.local.php.example)"
fi

if [ "$MIGRATE" = "1" ] && [ -f "$WEBROOT/inc/env.local.php" ]; then
  log "applying database migrations"
  (cd "$WEBROOT" && "$PHP_BIN" db/migrate.php) || { log "MIGRATION FAILED - code is live, deployed marker NOT updated so the next run retries"; exit 1; }
fi

echo "$NEW" > "$STAMP"
log "deployed $NEW"
