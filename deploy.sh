#!/usr/bin/env bash
# deploy.sh: automatic deploys for legitsourceanalysis.com.
#
# Run every few minutes by a server cron job (see docs/automation.md). Each run:
#   1. checks GitHub for new commits on main; exits quietly if there are none
#   2. backs up the database (SQLite or MySQL, read from .env; keeps the last 10)
#   3. fast-forwards to the new code (never merges or overwrites local edits)
#   4. runs composer install if composer.lock changed
#   5. runs migrations, then rebuilds Laravel's caches
# Everything is logged to storage/logs/deploy.log. A lock stops two runs overlapping.
#
#   bash deploy.sh           normal (cron) mode: does nothing unless GitHub has new commits
#   bash deploy.sh --force   back up + migrate + optimize now, even if already up to date
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"
mkdir -p storage/logs
exec >>storage/logs/deploy.log 2>&1

exec 9>"/tmp/lsa-deploy-$(id -u).lock"
flock -n 9 || exit 0

PHP="${PHP:-$(command -v php)}"
COMPOSER="${COMPOSER:-$(command -v composer || true)}"
FORCE="${1:-}"

git fetch --quiet origin main
LOCAL="$(git rev-parse HEAD)"
REMOTE="$(git rev-parse origin/main)"
if [ "$LOCAL" = "$REMOTE" ] && [ "$FORCE" != "--force" ]; then
  exit 0
fi

echo "=== $(date '+%F %T') deploying ${LOCAL:0:7} -> ${REMOTE:0:7} ${FORCE}"

# --- database backup (before any migration) -------------------------------
# Reads one value from .env; never fails the script if the key is missing.
envval() { { grep -E "^$1=" .env 2>/dev/null || true; } | tail -1 | cut -d= -f2- | tr -d "\"'"; }

BACKUP_DIR="$APP_DIR/storage/backups"
mkdir -p "$BACKUP_DIR"
STAMP="$(date +%F-%H%M%S)"
CONN="$(envval DB_CONNECTION)"
CONN="${CONN:-sqlite}"

if [ "$CONN" = "sqlite" ]; then
  DB_FILE="$(envval DB_DATABASE)"
  DB_FILE="${DB_FILE:-database/database.sqlite}"
  if [ -f "$DB_FILE" ]; then
    cp "$DB_FILE" "$BACKUP_DIR/db-$STAMP.sqlite"
    echo "backup: $DB_FILE -> storage/backups/db-$STAMP.sqlite"
  else
    echo "backup: sqlite file not found at '$DB_FILE' (DB_DATABASE in .env), skipped"
  fi
elif [ "$CONN" = "mysql" ] || [ "$CONN" = "mariadb" ]; then
  DB_HOST_VAL="$(envval DB_HOST)"
  DB_PORT_VAL="$(envval DB_PORT)"
  MYSQL_PWD="$(envval DB_PASSWORD)" mysqldump -h "${DB_HOST_VAL:-127.0.0.1}" -P "${DB_PORT_VAL:-3306}" \
    -u "$(envval DB_USERNAME)" "$(envval DB_DATABASE)" | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz"
  echo "backup: mysql -> storage/backups/db-$STAMP.sql.gz"
else
  echo "backup: DB_CONNECTION=$CONN not handled, skipped"
fi
ls -1t "$BACKUP_DIR"/db-* 2>/dev/null | tail -n +11 | xargs -r rm -f

# --- code -----------------------------------------------------------------
if [ "$LOCAL" != "$REMOTE" ]; then
  CHANGED="$(git diff --name-only "$LOCAL" "$REMOTE")"
  git merge --ff-only --quiet origin/main
  if echo "$CHANGED" | grep -qx "composer.lock" && [ -n "$COMPOSER" ]; then
    "$COMPOSER" install --no-dev --no-interaction --prefer-dist --optimize-autoloader
  fi
fi

"$PHP" artisan migrate --force
"$PHP" artisan optimize

echo "=== $(date '+%F %T') deployed ${REMOTE:0:7} OK (database: $CONN)"
