#!/usr/bin/env bash
# One-time setup of a local copy of kodmuzik.com on a Mac.
# Installs PHP and MariaDB with Homebrew (if missing), clones the repo,
# creates a local database, loads the events and writes api/config.php.
#
#   bash setup.sh                 # clones into ~/kodmuzik-local
#   bash setup.sh ~/somewhere     # or another folder
#
# Then start the site with:  bash ~/kodmuzik-local/scripts/local/start.sh

set -euo pipefail

DIR="${1:-$HOME/kodmuzik-local}"
BRANCH="${KOD_BRANCH:-feature/mobile-nav-white}"
REPO="https://github.com/CodaWorkz/kodmuzik-portfolio.git"
DB_NAME="kodmuzik_events"
DB_USER="kod"
DB_PASS="kodlocal"
ADMIN_PASS="kodlocal"

step() { printf '\n\033[1m%s\033[0m\n' "$1"; }

# 1. PHP and MariaDB
if [ "$(uname)" = "Darwin" ]; then
  if ! command -v brew >/dev/null 2>&1; then
    echo "Homebrew is not installed. Install it first from https://brew.sh, then run this script again."
    exit 1
  fi
  step "1/5 PHP and MariaDB"
  brew list php >/dev/null 2>&1 || brew install php
  brew list mariadb >/dev/null 2>&1 || brew install mariadb
  brew services start mariadb >/dev/null
  for i in $(seq 1 20); do
    mysqladmin ping >/dev/null 2>&1 && break
    sleep 1
  done
else
  step "1/5 PHP and MariaDB (not macOS: using what is installed)"
fi
command -v php >/dev/null || { echo "php not found"; exit 1; }
command -v mysql >/dev/null || { echo "mysql not found"; exit 1; }

# 2. Code
step "2/5 Code ($BRANCH) in $DIR"
if [ -d "$DIR/.git" ]; then
  git -C "$DIR" fetch origin
  git -C "$DIR" checkout "$BRANCH"
  git -C "$DIR" pull --ff-only origin "$BRANCH"
else
  git clone --branch "$BRANCH" "$REPO" "$DIR"
fi
cd "$DIR"

# 3. Database and user
step "3/5 Local database"
SQL="CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;"
if ! echo "$SQL" | mysql -u "$(whoami)" 2>/dev/null; then
  if ! echo "$SQL" | mysql -u root 2>/dev/null; then
    echo "Asking for your Mac password to create the database (sudo):"
    echo "$SQL" | sudo mysql -u root
  fi
fi

# 4. api/config.php (local only, gitignored)
step "4/5 api/config.php"
if [ ! -f api/config.php ]; then
  sed -e "s/define('DB_NAME', '\[CHANGE_ME\]');/define('DB_NAME', '$DB_NAME');/" \
      -e "s/define('DB_USER', '\[CHANGE_ME\]');/define('DB_USER', '$DB_USER');/" \
      -e "s/define('DB_PASS', '\[CHANGE_ME\]');/define('DB_PASS', '$DB_PASS');/" \
      api/config.example.php > api/config.php
  echo "Written."
else
  echo "Already there, left as it is."
fi
mkdir -p uploads/gallery uploads/thumbnails

# 5. Data: tables + admin user, then the live events on top
step "5/5 Events"
ADMINS=$(mysql -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT COUNT(*) FROM admin_users" "$DB_NAME" 2>/dev/null || echo 0)
if [ "$ADMINS" = "0" ]; then
  ADMIN_INITIAL_PASS="$ADMIN_PASS" php db/migrate_json_to_db.php | grep -E "Events in database|Admin users" || true
fi
SYNC_OUT=$(php db/sync-from-live.php 2>&1 || true)
if printf '%s' "$SYNC_OUT" | grep -q "✗"; then
  echo "Could not copy events from the live site; using the events file from the repo instead."
else
  printf '%s\n' "$SYNC_OUT" | tail -n 4
fi

printf '\n\033[1mDone.\033[0m Start the site with:\n  bash %s/scripts/local/start.sh\n' "$DIR"
printf 'Admin panel: /admin/  user: admin  password: %s  (local copy only)\n' "$ADMIN_PASS"
