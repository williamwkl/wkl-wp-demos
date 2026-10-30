#!/usr/bin/env bash
# Runs demos/makers/tests/importer-tests.php against a running makers site (scripts/serve.sh makers 9403).
set -euo pipefail
cd "$(dirname "$0")/.."
PORT="${1:-9403}"; T=demos/makers/mu-plugins/zz-importer-tests.php
cp demos/makers/tests/importer-tests.php "$T"; trap 'rm -f "$T"' EXIT
OUT=$(curl -s "http://127.0.0.1:$PORT/?fw_importer_tests=1")
echo "$OUT"
! grep -q '^FAIL' <<<"$OUT" && grep -q '^PASS' <<<"$OUT"
