#!/usr/bin/env bash
# READ-ONLY. Lists files in the web root that the repository does not ship: pages uploaded by hand before the move to GitHub, old
# copies, test files. pull-deploy.sh removes files that were deleted in git, but never a file git never had, so these stay served.
# Nothing is deleted or changed; the owner reviews the list and decides (CLAUDE.md: deleting files needs the owner's approval).
#
# Run on the server as the site user:   bash deploy/list-stray-files.sh            (reads pull-deploy.conf beside it, same defaults as pull-deploy.sh)
#                                       DEPLOY_WEBROOT=/path DEPLOY_WORKDIR=/path bash deploy/list-stray-files.sh
# Output: one path per line, relative to the web root, with size and last change; served types (.php .html .htm .txt .xml) first.
set -euo pipefail

CONF="${DEPLOY_CONF:-$(dirname "$(readlink -f "$0" 2>/dev/null || echo "$0")")/pull-deploy.conf}"   # the same file pull-deploy.sh reads when run from the same directory
if [ -f "$CONF" ]; then
  while IFS= read -r line || [ -n "$line" ]; do
    line="${line%%#*}"; line="${line#"${line%%[![:space:]]*}"}"; line="${line%"${line##*[![:space:]]}"}"
    case "$line" in DEPLOY_[A-Z_]*=*) k="${line%%=*}"; v="${line#*=}"; [ -z "${!k:-}" ] && export "$k=$v" ;; esac
  done < "$CONF"
fi
WEBROOT="${DEPLOY_WEBROOT:-/home/seatoutlet-beta/htdocs/beta.seatoutlet.com}"
WORKDIR="${DEPLOY_WORKDIR:-/home/seatoutlet-beta/deploy/checkout}"

[ -d "$WEBROOT" ] || { echo "web root not found: $WEBROOT" >&2; exit 2; }
git -C "$WORKDIR" rev-parse --git-dir >/dev/null 2>&1 || { echo "deploy checkout not found: $WORKDIR" >&2; exit 2; }

tracked="$(mktemp)"; present="$(mktemp)"
trap 'rm -f "$tracked" "$present"' EXIT
git -C "$WORKDIR" ls-files | LC_ALL=C sort -u > "$tracked"
# Runtime and server-only paths are expected to be untracked: the cache, the secrets file, deploy markers, uploads and logs.
(cd "$WEBROOT" && find . -type f \
    -not -path './cache/*' -not -path './.git/*' -not -path './uploads/*' -not -path './images/uploads/*' -not -path './data/*' \
    -not -path './logs/*' -not -name '*.log' \
    -not -path './inc/env.local.php' -not -name '.deployed-commit' -not -name '.deploy-error' -not -name '.user.ini' -not -name '.htaccess' \
    | sed 's#^\./##' | LC_ALL=C sort -u) > "$present"

stray="$(LC_ALL=C comm -23 "$present" "$tracked")"
if [ -z "$stray" ]; then echo "No stray files: every file in $WEBROOT is shipped by the repository (or is runtime data)."; exit 0; fi
count="$(printf '%s\n' "$stray" | wc -l | tr -d ' ')"
echo "# $count file(s) in $WEBROOT that the repository does not ship (read-only report, nothing changed)"
echo "# size  last-change  path"
show() { while IFS= read -r f; do [ -n "$f" ] && printf '%8s  %s  %s\n' "$(stat -c %s "$WEBROOT/$f")" "$(date -u -d "@$(stat -c %Y "$WEBROOT/$f")" +%F)" "$f"; done; }
printf '%s\n' "$stray" | grep -E '\.(php|html?|txt|xml)$' | show || true
printf '%s\n' "$stray" | grep -vE '\.(php|html?|txt|xml)$' | show || true
