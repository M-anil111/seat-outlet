#!/usr/bin/env bash
# Builds the minified front-end assets that header.php / footer.php prefer
# (see soAsset() in functions.php):
#   css/style.min.css   = css/fonts.css + css/style.css + css/skeleton.css + css/icons.css (icon subset, tools/build-icons.py), minified
#   js/<name>.min.js    = each js/<name>.js, compressed and mangled
#   css/bootstrap.min.css = lib/bootstrap/5.3.8/bootstrap.min.css reduced to the classes the site uses
#                         (tools/purgecss.config.cjs; a class that is only built at runtime must be safelisted there)
# Run after editing any source file, and commit the .min files.
#
#   tools/build-assets.sh           rebuild
#   tools/build-assets.sh --check   fail (exit 1) if committed .min files are stale (used by CI)
set -euo pipefail
cd "$(dirname "$0")/.."

CHECK=0; [ "${1:-}" = "--check" ] && CHECK=1
OUT="$(mktemp -d)"
trap 'rm -rf "$OUT"' EXIT

cat css/fonts.css css/style.css css/skeleton.css css/icons.css | npx --yes clean-css-cli@5.6.3 -O1 -o "$OUT/style.min.css"
for f in js/*.js; do
  case "$f" in *.min.js) continue;; esac
  name="$(basename "$f" .js)"
  npx --yes terser@5.51.2 "$f" --compress --mangle -o "$OUT/$name.min.js"
done

mkdir -p "$OUT/purged"
npx --yes purgecss@6.0.0 --config tools/purgecss.config.cjs --output "$OUT/purged/" >/dev/null
cp "$OUT/purged/bootstrap.min.css" "$OUT/bootstrap.purged.css"

status=0
install_or_compare() {
  src="$1"; dest="$2"
  if [ "$CHECK" = 1 ]; then
    cmp -s "$src" "$dest" || { echo "STALE: $dest (run tools/build-assets.sh and commit)"; status=1; }
  else
    cp "$src" "$dest"
  fi
}
install_or_compare "$OUT/style.min.css" css/style.min.css
install_or_compare "$OUT/bootstrap.purged.css" css/bootstrap.min.css
for f in "$OUT"/*.min.js; do install_or_compare "$f" "js/$(basename "$f")"; done
[ "$CHECK" = 1 ] && [ "$status" = 0 ] && echo "assets up to date"
exit $status
