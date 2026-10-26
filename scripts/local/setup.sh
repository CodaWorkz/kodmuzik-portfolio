#!/usr/bin/env bash
# One-time setup of a local copy of kodmuzik.com on a Mac.
# Uses the PHP and MySQL/MariaDB already on the Mac when it finds them
# (Homebrew, XAMPP or MAMP); installs them with Homebrew only when missing.
# Clones the repo, creates its own local database (kodmuzik_local, so an
# existing kodmuzik_events database is not touched), loads the events and
# writes api/config.php.
#
#   bash setup.sh                 # clones into ~/kodmuzik-local
#   bash setup.sh ~/somewhere     # or another folder
#
# Then start the site with:  bash ~/kodmuzik-local/scripts/local/start.sh

set -euo pipefail

DIR="${1:-$HOME/kodmuzik-local}"
BRANCH="${KOD_BRANCH:-feature/mobile-nav-white}"
REPO="https://github.com/CodaWorkz/kodmuzik-portfolio.git"
DB_NAME="kodmuzik_local"
DB_USER="kod"
DB_PASS="kodlocal"
ADMIN_PASS="kodlocal"

step() { printf '\n\033[1m%s\033[0m\n' "$1"; }

# MySQL tools from XAMPP or MAMP are not on the PATH by default
for d in /Applications/XAMPP/xamppfiles/bin /Applications/MAMP/Library/bin; do
  [ -x "$d/mysql" ] && PATH="$PATH:$d"
done
export PATH

# 1. PHP and MySQL/MariaDB: check first, install only what is missing
step "1/5 PHP and MySQL"
if command -v php >/dev/null 2>&1 && php -m 2>/dev/null | grep -qi '^pdo_mysql$'; then
  echo "PHP found: $(php -r 'echo PHP_VERSION;') at $(command -v php) (kept)"
elif [ "$(uname)" = "Darwin" ] && command -v brew >/dev/null 2>&1; then
  echo "PHP with MySQL support not found, installing with Homebrew..."
  brew install php
else
  echo "PHP with MySQL support not found. Install Homebrew (https://brew.sh) and run this again."
  exit 1
fi

if command -v mysqladmin >/dev/null 2>&1 && mysqladmin ping >/dev/null 2>&1; then
  echo "MySQL server already running: $(mysql --version | sed 's/^mysql *//') (kept)"
elif [ "$(uname)" = "Darwin" ] && command -v brew >/dev/null 2>&1 && \
     { brew list mysql >/dev/null 2>&1 || brew list mariadb >/dev/null 2>&1; }; then
  F=$(brew list mysql >/dev/null 2>&1 && echo mysql || echo mariadb)
  echo "Starting the installed Homebrew $F..."
  brew services start "$F" >/dev/null
elif [ -d /Applications/XAMPP ] || [ -d /Applications/MAMP ]; then
  echo "XAMPP or MAMP is installed but its MySQL is not running."
  echo "Start MySQL from the XAMPP/MAMP control panel, then run this script again."
  exit 1
elif [ "$(uname)" = "Darwin" ] && command -v brew >/dev/null 2>&1; then
  echo "No MySQL found, installing MariaDB with Homebrew..."
  brew install mariadb
  brew services start mariadb >/dev/null
else
  echo "No MySQL server found. Install Homebrew (https://brew.sh) and run this again."
  exit 1
fi
for i in $(seq 1 20); do
  mysqladmin ping >/dev/null 2>&1 && break
  sleep 1
done

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
step "3/5 Local database ($DB_NAME)"
SQL="CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;"
if echo "$SQL" | mysql -u root 2>/dev/null; then :
elif echo "$SQL" | mysql -u "$(whoami)" 2>/dev/null; then :
elif echo "$SQL" | sudo -n mysql -u root 2>/dev/null; then :
else
  echo "Enter the MySQL root password (just press Enter if there is none):"
  echo "$SQL" | mysql -u root -p
fi

# 4. api/config.php (local only, gitignored)
step "4/5 api/config.php"
if [ ! -f api/config.php ]; then
  sed -e "s/define('DB_HOST', 'localhost');/define('DB_HOST', '127.0.0.1');/" \
      -e "s/define('DB_NAME', '\[CHANGE_ME\]');/define('DB_NAME', '$DB_NAME');/" \
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
ADMINS=$(mysql -h 127.0.0.1 -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT COUNT(*) FROM admin_users" "$DB_NAME" 2>/dev/null || echo 0)
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
