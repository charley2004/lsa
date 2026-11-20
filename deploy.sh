#!/usr/bin/env bash
# deploy.sh: automatic deploys for legitsourceanalysis.com.
#
# Run every few minutes by a server cron job (see docs/automation.md). Each run:
#   1. checks GitHub for new commits on main; exits quietly if there are none
#   2. backs up the database (keeps the last 10 backups)
#   3. fast-forwards to the new code (never merges or overwrites local edits)
#   4. runs composer install if composer.lock changed
#   5. runs migrations, then rebuilds Laravel's caches
# Everything is logged to storage/logs/deploy.log. A lock stops two runs overlapping.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"
mkdir -p storage/logs
exec >>storage/logs/deploy.log 2>&1

exec 9>"/tmp/lsa-deploy-$(id -u).lock"
flock -n 9 || exit 0

PHP="${PHP:-$(command -v php)}"
COMPOSER="${COMPOSER:-$(command -v composer || true)}"

git fetch --quiet origin main
LOCAL="$(git rev-parse HEAD)"
REMOTE="$(git rev-parse origin/main)"
[ "$LOCAL" = "$REMOTE" ] && exit 0

echo "=== $(date '+%F %T') deploying ${LOCAL:0:7} -> ${REMOTE:0:7}"

# --- database backup (before any migration) -------------------------------
envval() { grep -E "^$1=" .env 2>/dev/null | tail -1 | cut -d= -f2- | tr -d '"' | tr -d "'"; }
BACKUP_DIR="$APP_DIR/storage/backups"
mkdir -p "$BACKUP_DIR"
STAMP="$(date +%F-%H%M%S)"
CONN="$(envval DB_CONNECTION)"
if [ "${CONN:-sqlite}" = "sqlite" ]; then
  DB_FILE="$(envval DB_DATABASE)"; DB_FILE="${DB_FILE:-database/database.sqlite}"
  [ -f "$DB_FILE" ] && cp "$DB_FILE" "$BACKUP_DIR/db-$STAMP.sqlite" && echo "backup: db-$STAMP.sqlite"
elif [ "$CONN" = "mysql" ] || [ "$CONN" = "mariadb" ]; then
  MYSQL_PWD="$(envval DB_PASSWORD)" mysqldump -h "$(envval DB_HOST)" -P "$(envval DB_PORT || echo 3306)" \
    -u "$(envval DB_USERNAME)" "$(envval DB_DATABASE)" | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz" && echo "backup: db-$STAMP.sql.gz"
fi
ls -1t "$BACKUP_DIR"/db-* 2>/dev/null | tail -n +11 | xargs -r rm -f

# --- code -----------------------------------------------------------------
CHANGED="$(git diff --name-only "$LOCAL" "$REMOTE")"
git merge --ff-only --quiet origin/main

if echo "$CHANGED" | grep -qx "composer.lock" && [ -n "$COMPOSER" ]; then
  "$COMPOSER" install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

"$PHP" artisan migrate --force
"$PHP" artisan optimize

echo "=== $(date '+%F %T') deployed ${REMOTE:0:7} OK"
