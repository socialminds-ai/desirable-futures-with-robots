#!/usr/bin/env bash
# Render kit.html → desirable-futures-kit.pdf via headless Chrome.
# Requires google-chrome (or chromium) on PATH.

set -euo pipefail
cd "$(dirname "$0")"

CHROME="$(command -v google-chrome || command -v google-chrome-stable || command -v chromium || command -v chromium-browser || true)"
if [ -z "$CHROME" ]; then
  echo "Could not find google-chrome or chromium on PATH." >&2
  exit 1
fi

"$CHROME" \
  --headless=new \
  --disable-gpu \
  --no-sandbox \
  --hide-scrollbars \
  --no-pdf-header-footer \
  --virtual-time-budget=15000 \
  --print-to-pdf="desirable-futures-kit.pdf" \
  "file://$PWD/kit.html"

echo "→ desirable-futures-kit.pdf  ($(du -h desirable-futures-kit.pdf | cut -f1))"
