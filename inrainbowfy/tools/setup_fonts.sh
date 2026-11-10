#!/usr/bin/env bash
# One-step setup of the Transport web font (not redistributed in this repo):
# downloads the free Transport Medium from roads.org.uk, builds
# fonts/transportmedium.woff2 with tools/build_transport.py, and writes its
# width table with tools/font_widths.py. Uses a throwaway Python virtual
# environment for fonttools, and cleans up after itself.
#
#   tools/setup_fonts.sh
#
# Needs: bash, curl, unzip, python3 (with the venv module).
set -euo pipefail

ZIP_URL="https://www.roads.org.uk/sites/default/files/pages/fonts/transportmedium.zip"
APP="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

for cmd in curl unzip python3; do
  command -v "$cmd" >/dev/null || { echo "error: $cmd is required" >&2; exit 1; }
done

echo "downloading Transport Medium..."
curl -fsSL -o "$WORK/transportmedium.zip" "$ZIP_URL"
unzip -q "$WORK/transportmedium.zip" -d "$WORK/zip"
TTF="$(find "$WORK/zip" -name 'Transport Medium.ttf' -not -path '*__MACOSX*' | head -1)"
[ -n "$TTF" ] || { echo "error: 'Transport Medium.ttf' not found in the download" >&2; exit 1; }

echo "installing fonttools into a temporary environment..."
python3 -m venv "$WORK/venv"
"$WORK/venv/bin/pip" install -q fonttools brotli

echo "building..."
"$WORK/venv/bin/python" -I "$APP/tools/build_transport.py" "$TTF" \
  "$APP/fonts/roadgeek2005lowline.ttf" "$APP/fonts/transportmedium.woff2"
"$WORK/venv/bin/python" -I "$APP/tools/font_widths.py" \
  "$APP/fonts/transportmedium.woff2" "$APP/fonts/transportmedium-widths.json"

echo "done: fonts/transportmedium.woff2 and fonts/transportmedium-widths.json"
