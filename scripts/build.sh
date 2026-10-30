#!/usr/bin/env bash
# Export one demo from its running Playground site, using site.config.json for the port and public base URL.
# Root files (robots, sitemap index, landing canonical) are refreshed and checked at the end.
# usage: scripts/build.sh <demo>    (start it first: scripts/serve.sh <source-demo> <port> [blueprint])
set -euo pipefail
cd "$(dirname "$0")/.."
DEMO="$1"
PORT=$(node -e "console.log(require('./site.config.json').demos['$DEMO'].port)")
SRC=$(node -e "const d=require('./site.config.json').demos['$DEMO'];console.log(d.source||'$DEMO')")
EXTRA=()
[ -f "demos/$SRC/theme/assets/fonts/OFL.txt" ] && [ "$DEMO" != harbor-pine-naive ] && EXTRA+=("/wp-content/themes/$SRC/assets/fonts/OFL.txt")
node scripts/export.mjs "http://127.0.0.1:$PORT" "dist/$DEMO" ${EXTRA[@]+"${EXTRA[@]}"}
if [ -f "demos/$SRC/data/redirects.csv" ]; then node scripts/redirect-stubs.mjs "dist/$DEMO" "demos/$SRC/data/redirects.csv"; fi
node scripts/root-files.mjs
