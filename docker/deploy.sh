#!/usr/bin/env bash
# DEORIS Docker production deploy helper (Contabo/deoris.net)
#
# Usage:
#   ./docker/deploy.sh
#
# Optional env vars:
#   BRANCH=main
#   SKIP_BUILD=0
#   BACKUP_DIR=./backups

set -euo pipefail

BRANCH="${BRANCH:-main}"
SKIP_BUILD="${SKIP_BUILD:-0}"
BACKUP_DIR="${BACKUP_DIR:-./backups}"
STAMP="$(date +%Y%m%d-%H%M%S)"

if [[ ! -f ".env" ]]; then
  echo "[deploy] .env not found. Copy .env.production or .env.example first."
  exit 1
fi

mkdir -p "${BACKUP_DIR}"

echo "[deploy] Current branch: $(git rev-parse --abbrev-ref HEAD)"
echo "[deploy] Fetching latest ${BRANCH}..."
git fetch origin
git checkout "${BRANCH}"
git pull --ff-only origin "${BRANCH}"

echo "[deploy] Creating DB backup..."
docker compose up -d mysql >/dev/null
docker compose exec -T mysql sh -c 'mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  > "${BACKUP_DIR}/deoris-db-${STAMP}.sql"
cp .env "${BACKUP_DIR}/deoris-env-${STAMP}.bak"

if [[ "${SKIP_BUILD}" == "1" ]]; then
  echo "[deploy] SKIP_BUILD=1 -> using existing images"
  docker compose up -d
else
  echo "[deploy] Rebuilding and starting containers (portal + modules)..."
  docker compose up -d --build
fi

echo "[deploy] Waiting for MySQL/Redis health..."
docker compose ps
sleep 8

echo "[deploy] Running migrations..."
docker compose exec -T app php artisan migrate --force

echo "[deploy] Clearing and rebuilding runtime caches..."
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan event:cache

echo "[deploy] Ensuring runtime permissions..."
docker compose exec -T app chmod -R 775 storage bootstrap/cache

echo "[deploy] Basic health checks..."
docker compose exec -T app php artisan deoris:events:health || true
docker compose exec -T app php artisan deoris:services:health-check || true

echo "[deploy] Completed successfully."
echo "[deploy] Backup files:"
echo "  - ${BACKUP_DIR}/deoris-db-${STAMP}.sql"
echo "  - ${BACKUP_DIR}/deoris-env-${STAMP}.bak"
