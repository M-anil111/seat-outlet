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
# Configuration (environment variables, all optional; a file pull-deploy.conf next to this script is sourced first, so the server's
# paths live in one place outside the repository: see deploy/pull-deploy.conf.example):
#   DEPLOY_REPO      git URL         default git@github.com:M-anil111/seat-outlet.git
#   DEPLOY_BRANCH    branch to ship  default main
#   DEPLOY_WEBROOT   live site dir   default /home/seatoutlet-beta/htdocs/beta.seatoutlet.com  (MUST be the directory nginx serves)
#   DEPLOY_WORKDIR   checkout dir    default /home/seatoutlet-beta/deploy/checkout  (never inside the web root, never the web root)
#   DEPLOY_MIGRATE   1 = run php db/migrate.php after copying (default 1)
#   DEPLOY_PHP       php binary      default php
#   DEPLOY_SSH_KEY   private key for GitHub (a read-only deploy key); cron then never depends on an agent or a prompt
#   DEPLOY_REF       deploy this commit or tag instead of the branch tip (emergency rollback; the next run without it redeploys the tip)
#
# Never touched by a deploy: inc/env.local.php (the server's own secrets file)
# and existing files under cache/ (runtime feed and image cache). Files that
# were removed from git since the last deploy are removed from the web root too
# (so a deleted vulnerable file cannot stay live); nothing else is deleted.
# Repo-only files (.github, docs, CONTRIBUTING.md, composer.json/lock, .env.example) are not copied to the web root.
set -euo pipefail

# Server configuration file (plain KEY=value lines, no secrets) in the same directory as the copy cron runs. A variable already set in
# the environment wins over the file, so a one-off run such as "DEPLOY_WEBROOT=/path/to/scratch DEPLOY_MIGRATE=0 ./pull-deploy.sh" is
# always possible without editing the file.
CONF="$(dirname "$(readlink -f "$0" 2>/dev/null || echo "$0")")/pull-deploy.conf"
if [ -f "$CONF" ]; then
  while IFS= read -r line || [ -n "$line" ]; do
    line="${line%%#*}"; line="${line#"${line%%[![:space:]]*}"}"; line="${line%"${line##*[![:space:]]}"}"
    case "$line" in DEPLOY_[A-Z_]*=*) k="${line%%=*}"; v="${line#*=}"; [ -z "${!k:-}" ] && export "$k=$v" ;; esac
  done < "$CONF"
fi

REPO="${DEPLOY_REPO:-git@github.com:M-anil111/seat-outlet.git}"
BRANCH="${DEPLOY_BRANCH:-main}"
WEBROOT="${DEPLOY_WEBROOT:-/home/seatoutlet-beta/htdocs/beta.seatoutlet.com}"
WORKDIR="${DEPLOY_WORKDIR:-/home/seatoutlet-beta/deploy/checkout}"
MIGRATE="${DEPLOY_MIGRATE:-1}"
PHP_BIN="${DEPLOY_PHP:-php}"
STATE_DIR="$(dirname "$WORKDIR")"
STAMP="$WEBROOT/.deployed-commit"

log() { echo "$(date -u +%FT%TZ) $*"; }

# The checkout and the web root must be two different trees: with the same directory (or one inside the other) the copy below would
# write a tree onto itself, and "git reset --hard" would wipe the server's secrets file and cache.
WR="$(readlink -f "$WEBROOT" 2>/dev/null || echo "$WEBROOT")"; WD="$(readlink -f "$WORKDIR" 2>/dev/null || echo "$WORKDIR")"
case "$WD/" in "$WR/"|"$WR/"*) log "REFUSED: DEPLOY_WORKDIR ($WORKDIR) is the web root or inside it"; exit 2 ;; esac
case "$WR/" in "$WD/"*) log "REFUSED: DEPLOY_WEBROOT ($WEBROOT) is inside the checkout"; exit 2 ;; esac

# GitHub over SSH from cron: a fixed key, no agent, no prompt, a bounded connect time. DEPLOY_SSH_KEY unset keeps the user's own SSH config.
if [ -n "${DEPLOY_SSH_KEY:-}" ]; then
  export GIT_SSH_COMMAND="ssh -i $DEPLOY_SSH_KEY -o IdentitiesOnly=yes -o BatchMode=yes -o ConnectTimeout=20 -o StrictHostKeyChecking=accept-new"
fi

mkdir -p "$STATE_DIR" "$WEBROOT"
exec 9>"$STATE_DIR/.lock"
flock -n 9 || { log "another deploy is running, skipping"; exit 0; }

if [ ! -d "$WORKDIR/.git" ]; then
  log "first run: cloning $REPO ($BRANCH)"
  git clone --quiet --branch "$BRANCH" --single-branch "$REPO" "$WORKDIR"
fi

cd "$WORKDIR"
# A fetch that fails on a dropped connection is retried twice (5 s, then 15 s); a third failure is reported and the run ends, so the next
# cron run tries again. Nothing on the web root changes until the fetch has succeeded.
fetched=0
for wait in 0 5 15; do
  [ "$wait" -gt 0 ] && sleep "$wait"
  if git fetch --quiet origin "$BRANCH" 2>"$STATE_DIR/.fetch-error"; then fetched=1; break; fi
done
if [ "$fetched" != 1 ]; then log "ERROR: git fetch failed three times: $(tr '\n' ' ' < "$STATE_DIR/.fetch-error" | cut -c1-200)"; exit 1; fi
rm -f "$STATE_DIR/.fetch-error"
if [ -n "${DEPLOY_REF:-}" ]; then
  NEW="$(git rev-parse --verify "${DEPLOY_REF}^{commit}" 2>/dev/null || git rev-parse --verify "origin/${DEPLOY_REF}^{commit}")"
  log "DEPLOY_REF set: deploying $DEPLOY_REF (${NEW:0:8}) instead of the tip of $BRANCH"
else
  NEW="$(git rev-parse "origin/$BRANCH")"
fi
OLD="$(cat "$STAMP" 2>/dev/null || echo none)"

if [ "$NEW" = "$OLD" ]; then
  exit 0
fi

log "deploying ${OLD:0:8} -> ${NEW:0:8} into $WEBROOT"
# Files deleted in git since the last deploy, computed before the checkout moves: against the last SUCCESSFUL deploy (the marker) and
# against the last COPIED commit (the checkout's HEAD; a deploy whose migration failed copied its files but left the marker alone).
REMOVED=""
PREV="$(git rev-parse HEAD 2>/dev/null || echo none)"
for base in "$OLD" "$PREV"; do
  if [ "$base" != "none" ] && [ "$base" != "$NEW" ] && git cat-file -e "$base^{commit}" 2>/dev/null; then
    REMOVED="$REMOVED
$(git diff --name-only --diff-filter=D "$base" "$NEW" || true)"
  fi
done
REMOVED="$(printf '%s\n' "$REMOVED" | sort -u)"
git reset --quiet --hard "$NEW"

# Cron runs a copy of this script (~/deploy/pull-deploy.sh, see docs/pull-deploy.md), so a fix merged to the script never reached the
# server by itself. Refresh that copy from the checkout for the NEXT run (a new inode via mv: the running bash keeps reading this one).
SELF="$(readlink -f "$0" 2>/dev/null || echo "$0")"
if [ -f "$SELF" ] && [ -f "$WORKDIR/deploy/pull-deploy.sh" ] && [ "$SELF" != "$(readlink -f "$WORKDIR/deploy/pull-deploy.sh")" ] \
   && ! cmp -s "$WORKDIR/deploy/pull-deploy.sh" "$SELF"; then
  if cp "$WORKDIR/deploy/pull-deploy.sh" "$SELF.new" && chmod 0755 "$SELF.new" && mv -f "$SELF.new" "$SELF"; then
    log "deploy script updated from the checkout; the new version runs from the next cron run"
  else
    log "WARNING: could not update $SELF from the checkout (permissions?)"; rm -f "$SELF.new"
  fi
fi

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

# Safety net for removed files the git diff above did not catch (a deploy that was skipped, or files put there by hand or by the SFTP
# fallback): deploy/retired-files.txt lists them, one relative path per line.
if [ -f deploy/retired-files.txt ]; then
  while IFS= read -r f; do
    case "$f" in ''|'#'*|/*|*..*|*\**|cache/*|inc/env.local.php) continue ;; esac
    [ -f "$WEBROOT/$f" ] && { rm -f -- "$WEBROOT/$f"; log "removed retired file $f"; }
  done < deploy/retired-files.txt
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
  # The failure is written where ajax/health.php can read it (deployError): the code is live while the marker is not, which otherwise
  # shows only as commit = null on the health page.
  # migrate.php prints one "skip" line per already-applied file; only applied/failed lines are worth the log.
  (cd "$WEBROOT" && "$PHP_BIN" db/migrate.php | { grep -v '^skip ' || true; }) || { log "MIGRATION FAILED - code is live, deployed marker NOT updated so the next run retries"; echo "$(date -u +%FT%TZ) migration failed deploying ${NEW:0:8}" > "$WEBROOT/.deploy-error"; chmod 0644 "$WEBROOT/.deploy-error" 2>/dev/null || true; exit 1; }
fi

echo "$NEW" > "$STAMP"
chmod 0644 "$STAMP" 2>/dev/null || true   # PHP-FPM runs as another user: a marker written 0600 reads as commit = null on the health page
rm -f "$WEBROOT/.deploy-error"
log "deployed $NEW"
