#!/usr/bin/env bash
# Downloads the third-party front-end libraries the site serves from its own domain (lib/), pinned to exact versions.
# Self-hosting removes two third-party connections (cdn.jsdelivr.net, code.jquery.com) from every page's critical path and
# the dependency on a CDN being up. Source-map comments are stripped so browsers do not request .map files that are not shipped.
#   tools/vendor-assets.sh            re-download (only needed to change a version)
set -euo pipefail
cd "$(dirname "$0")/.."
J=https://cdn.jsdelivr.net/npm
BOOTSTRAP=5.3.8; JQUERY=3.7.1; FLATPICKR=4.6.13; SLICK=1.8.1

fetch() { # url dest
  mkdir -p "$(dirname "$2")"
  curl -fsS --max-time 60 "$1" -o "$2"
  python3 - "$2" <<'PY'
import re, sys
p = sys.argv[1]
s = open(p, encoding='utf-8', errors='surrogateescape').read()
s = re.sub(r'\n?//# sourceMappingURL=\S*\s*$', '\n', s)           # JS
s = re.sub(r'\n?/\*# sourceMappingURL=[^*]*\*/\s*$', '\n', s)      # CSS
open(p, 'w', encoding='utf-8', errors='surrogateescape').write(s)
PY
}
fetch "$J/bootstrap@$BOOTSTRAP/dist/css/bootstrap.min.css"            "lib/bootstrap/$BOOTSTRAP/bootstrap.min.css"
fetch "$J/bootstrap@$BOOTSTRAP/dist/js/bootstrap.bundle.min.js"       "lib/bootstrap/$BOOTSTRAP/bootstrap.bundle.min.js"
fetch "https://code.jquery.com/jquery-$JQUERY.min.js"                 "lib/jquery/$JQUERY/jquery.min.js"
fetch "$J/flatpickr@$FLATPICKR/dist/flatpickr.min.css"                "lib/flatpickr/$FLATPICKR/flatpickr.min.css"
fetch "$J/flatpickr@$FLATPICKR/dist/flatpickr.min.js"                 "lib/flatpickr/$FLATPICKR/flatpickr.min.js"
fetch "$J/slick-carousel@$SLICK/slick/slick.min.js"                   "lib/slick-carousel/$SLICK/slick.min.js"
fetch "$J/slick-carousel@$SLICK/slick/slick.css"                      "lib/slick-carousel/$SLICK/slick.css"
fetch "$J/slick-carousel@$SLICK/slick/slick-theme.css"                "lib/slick-carousel/$SLICK/slick-theme.css"
# slick-theme.css references these relative to itself
fetch "$J/slick-carousel@$SLICK/slick/ajax-loader.gif"                "lib/slick-carousel/$SLICK/ajax-loader.gif"
for f in slick.eot slick.svg slick.ttf slick.woff; do fetch "$J/slick-carousel@$SLICK/slick/fonts/$f" "lib/slick-carousel/$SLICK/fonts/$f"; done
echo "vendored: bootstrap $BOOTSTRAP, jquery $JQUERY, flatpickr $FLATPICKR, slick $SLICK"
