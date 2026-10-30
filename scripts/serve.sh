#!/usr/bin/env bash
# usage: scripts/serve.sh <demo> [port] [blueprint]   boots WordPress Playground with the demo blueprint
set -euo pipefail
cd "$(dirname "$0")/.."
DEMO="$1"; PORT="${2:-9400}"; D="demos/$DEMO"; BP="${3:-$D/blueprint.json}"
ARGS=(--port="$PORT" --blueprint="$BP" --mount="$D/mu-plugins:/wordpress/wp-content/mu-plugins")
[ -d "$D/theme" ] && ARGS+=(--mount="$D/theme:/wordpress/wp-content/themes/$DEMO")
[ -d "$D/data" ] && ARGS+=(--mount="$D/data:/wordpress/wp-content/demo-data")
exec npx -y @wp-playground/cli@latest server "${ARGS[@]}"
