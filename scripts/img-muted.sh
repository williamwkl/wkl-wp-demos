#!/usr/bin/env bash
# usage: scripts/img.sh <src.jpg> <out-base> <ratio W:H> <width> [width2 ...]
# Center-crops to the ratio, writes <out-base>-<w>.webp per width (q72) with a warm grade.
set -euo pipefail
src=$1; out=$2; ratio=$3; shift 3
rw=${ratio%:*}; rh=${ratio#*:}
for w in "$@"; do
  h=$(( w * rh / rw ))
  ffmpeg -loglevel error -y -i "$src" -vf "crop='min(iw,ih*$rw/$rh)':'min(ih,iw*$rh/$rw)',scale=$w:$h,eq=saturation=0.8,colorbalance=rm=0.03:bm=-0.03" "${TMPDIR:-/tmp}/_img.png"
  cwebp -quiet -q ${Q:-72} "${TMPDIR:-/tmp}/_img.png" -o "$out-$w.webp"
done
