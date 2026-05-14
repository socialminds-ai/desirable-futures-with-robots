#!/usr/bin/env bash
# Render form.html → desirable-futures-form.pdf via headless Chrome.
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
  --virtual-time-budget=10000 \
  --print-to-pdf="desirable-futures-form.pdf" \
  "file://$PWD/form.html"

echo "→ desirable-futures-form.pdf  ($(du -h desirable-futures-form.pdf | cut -f1))"
