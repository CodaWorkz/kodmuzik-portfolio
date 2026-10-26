#!/usr/bin/env bash
# Starts the local copy of kodmuzik.com. Stop it with Ctrl+C.
#   bash scripts/local/start.sh          # port 8000
#   bash scripts/local/start.sh 8080     # another port

set -euo pipefail
PORT="${1:-8000}"
cd "$(dirname "$0")/../.."

if [ "$(uname)" = "Darwin" ]; then
  brew services start mariadb >/dev/null 2>&1 || true
  IP=$(ipconfig getifaddr en0 2>/dev/null || true)
fi

echo "KOD Müzik local site"
echo "  On this Mac:   http://localhost:$PORT/"
if [ -n "${IP:-}" ]; then
  echo "  On your phone: http://$IP:$PORT/   (same Wi-Fi)"
fi
echo "  Admin panel:   http://localhost:$PORT/admin/"
echo "Note: .htaccess redirects do not run here; pages, menu, events and admin do."
echo "Press Ctrl+C to stop."
echo

php -S 0.0.0.0:"$PORT" -t .
