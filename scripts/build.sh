#!/usr/bin/env bash
# Export one demo from its running Playground site, using site.config.json for the port and public base URL.
# usage: scripts/build.sh <demo>    (start it first: scripts/serve.sh <source-demo> <port> [blueprint])
set -euo pipefail
cd "$(dirname "$0")/.."
DEMO="$1"
PORT=$(node -e "console.log(require('./site.config.json').demos['$DEMO'].port)")
SRC=$(node -e "const d=require('./site.config.json').demos['$DEMO'];console.log(d.source||'$DEMO')")
EXTRA=()
[ -f "demos/$SRC/theme/assets/fonts/OFL.txt" ] && [ "$DEMO" != harbor-pine-naive ] && EXTRA+=("/wp-content/themes/$SRC/assets/fonts/OFL.txt")
node scripts/export.mjs "http://127.0.0.1:$PORT" "dist/$DEMO" "${EXTRA[@]}"
[ -f "demos/$SRC/data/redirects.csv" ] && node scripts/redirect-stubs.mjs "dist/$DEMO" "demos/$SRC/data/redirects.csv"
node scripts/root-files.mjs dist
