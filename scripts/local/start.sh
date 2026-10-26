#!/usr/bin/env bash
# Starts the local copy of kodmuzik.com. Stop it with Ctrl+C.
#   bash scripts/local/start.sh          # port 8000
#   bash scripts/local/start.sh 8080     # another port

set -euo pipefail
PORT="${1:-8000}"
cd "$(dirname "$0")/../.."

for d in /Applications/XAMPP/xamppfiles/bin /Applications/MAMP/Library/bin; do
  [ -x "$d/mysql" ] && PATH="$PATH:$d"
done

# Make sure the database server is running
if ! mysqladmin ping >/dev/null 2>&1; then
  if command -v brew >/dev/null 2>&1 && brew list mysql >/dev/null 2>&1; then
    brew services start mysql >/dev/null
  elif command -v brew >/dev/null 2>&1 && brew list mariadb >/dev/null 2>&1; then
    brew services start mariadb >/dev/null
  else
    echo "MySQL is not running. Start it (XAMPP/MAMP control panel or Homebrew) and try again."
    exit 1
  fi
  for i in $(seq 1 20); do mysqladmin ping >/dev/null 2>&1 && break; sleep 1; done
fi

if [ "$(uname)" = "Darwin" ]; then
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
