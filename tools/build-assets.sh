#!/usr/bin/env bash
# Builds the minified front-end assets that header.php / footer.php prefer
# (see soAsset() in functions.php):
#   css/event.min.css   = css/event.css, minified
#   css/checkout.min.css = css/checkout.css, minified (the /checkout review page only, linked by checkout.php)
#   css/spec-cards.min.css = css/spec-cards.css, minified (event and artist location pages: About / guide / upcoming cards)
#   css/style.min.css   = css/fonts.css + css/style.css + css/skeleton.css, with the rules for classes the site never uses removed
#                         (tools/purgecss-style.config.cjs), + css/icons.css (icon subset, tools/build-icons.py), minified
#   css/style.<page type>.min.css = the site stylesheet reduced to one page type (inc/css-groups.php, tools/css-split.php), in files under 110 KB
#   js/<name>.min.js    = each js/<name>.js, compressed and mangled
#   js/site-extras.min.js = nav-feedback, install-prompt, menu-near, analytics-events and lead-capture in one file
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

# Site stylesheet: unused rules out first (icons.css is generated from the glyphs in use, so it is added after and never purged).
mkdir -p "$OUT/style-src" "$OUT/style-purged"
cat css/fonts.css css/style.css css/skeleton.css > "$OUT/style-src/style.css"
npx --yes clean-css-cli@5.6.3 -O1 css/event.css -o "$OUT/event.min.css"   # event pages only (seat-map widget skin), linked by inc/seo-event.php
npx --yes clean-css-cli@5.6.3 -O1 css/checkout.css -o "$OUT/checkout.min.css"   # /checkout only, linked by checkout.php
npx --yes clean-css-cli@5.6.3 -O1 css/spec-cards.css -o "$OUT/spec-cards.min.css"   # event and artist location pages (About / guide / upcoming cards)
for f in js/*.js; do
  case "$f" in *.min.js) continue;; esac
  name="$(basename "$f" .js)"
  npx --yes terser@5.51.2 "$f" --compress --mangle -o "$OUT/$name.min.js"
done

# The scripts every page loads, as one file (fewer requests): footer.php prefers it, same order as the separate files.
cat js/nav-feedback.js js/install-prompt.js js/menu-near.js js/analytics-events.js js/lead-capture.js | sed -e '$a\' > "$OUT/site-extras.src.js"
npx --yes terser@5.51.2 "$OUT/site-extras.src.js" --compress --mangle -o "$OUT/site-extras.min.js"

mkdir -p "$OUT/purged"
npx --yes purgecss@6.0.0 --config tools/purgecss.config.cjs --output "$OUT/purged/" >/dev/null
cp "$OUT/purged/bootstrap.min.css" "$OUT/bootstrap.purged.css"

npx --yes purgecss@6.0.0 --config tools/purgecss-style.config.cjs --css "$OUT/style-src/style.css" --output "$OUT/style-purged/" >/dev/null
cat "$OUT/style-purged/style.css" css/icons.css | npx --yes clean-css-cli@5.6.3 -O1 -o "$OUT/style.min.css"

# One stylesheet per page type (inc/css-groups.php): the same rules in the same order, minus what that page type cannot use.
# Each file stays under 110 KB (SE Ranking flags a CSS file over 150 KB), split in source order when needed.
mkdir -p "$OUT/groups" "$OUT/groups-css"
php tools/css-split.php "$OUT/groups" 2>/dev/null
for txt in "$OUT"/groups/*.txt; do
  g="$(basename "$txt" .txt)"
  mkdir -p "$OUT/groups-purged/$g"
  SO_CSS_CONTENT="$txt" npx --yes purgecss@6.0.0 --config tools/purgecss-group.config.cjs --css "$OUT/style-src/style.css" --output "$OUT/groups-purged/$g/" >/dev/null
  cat "$OUT/groups-purged/$g/style.css" css/icons.css | npx --yes clean-css-cli@5.6.3 -O1 -o "$OUT/groups-css/$g.min.css"
  python3 tools/css-chunk.py "$OUT/groups-css/$g.min.css" "$OUT/groups-css/style.$g" 110000 >/dev/null
  rm -f "$OUT/groups-css/$g.min.css"
done

# "full": the whole stylesheet for pages that belong to no page type, also split so no file passes the limit
python3 tools/css-chunk.py "$OUT/style.min.css" "$OUT/groups-css/style.full" 110000 >/dev/null

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
install_or_compare "$OUT/event.min.css" css/event.min.css
install_or_compare "$OUT/checkout.min.css" css/checkout.min.css
install_or_compare "$OUT/spec-cards.min.css" css/spec-cards.min.css
install_or_compare "$OUT/bootstrap.purged.css" css/bootstrap.min.css
for f in "$OUT"/*.min.js; do install_or_compare "$f" "js/$(basename "$f")"; done
for f in "$OUT"/groups-css/style.*.min.css; do install_or_compare "$f" "css/$(basename "$f")"; done
# page type files that no longer come out of the build
for f in css/style.*.min.css; do
  [ -e "$OUT/groups-css/$(basename "$f")" ] && continue
  if [ "$CHECK" = 1 ]; then echo "STALE: $f (no longer built; run tools/build-assets.sh and commit)"; status=1; else rm -f "$f"; fi
done
[ "$CHECK" = 1 ] && [ "$status" = 0 ] && echo "assets up to date"
exit $status
