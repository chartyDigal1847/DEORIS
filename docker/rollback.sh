#!/usr/bin/env bash
# DEORIS Docker rollback helper (code + DB restore)
#
# Usage:
#   ROLLBACK_REF=<git-ref> DB_BACKUP=<path-to-sql> ./docker/rollback.sh
#
# Example:
#   ROLLBACK_REF=2ad7765 DB_BACKUP=./backups/deoris-db-20260530-020000.sql ./docker/rollback.sh

set -euo pipefail

ROLLBACK_REF="${ROLLBACK_REF:-}"
DB_BACKUP="${DB_BACKUP:-}"

if [[ -z "${ROLLBACK_REF}" ]]; then
  echo "[rollback] ROLLBACK_REF is required."
  exit 1
fi

if [[ -z "${DB_BACKUP}" || ! -f "${DB_BACKUP}" ]]; then
  echo "[rollback] DB_BACKUP must point to an existing SQL dump file."
  exit 1
fi

echo "[rollback] Entering maintenance mode..."
docker compose up -d app >/dev/null
docker compose exec -T app php artisan down || true

echo "[rollback] Resetting code to ${ROLLBACK_REF}..."
git fetch --all --tags
git checkout "${ROLLBACK_REF}"

echo "[rollback] Rebuilding containers from rollback ref..."
docker compose up -d --build

echo "[rollback] Restoring database from ${DB_BACKUP}..."
docker compose up -d mysql >/dev/null
docker compose exec -T mysql sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < "${DB_BACKUP}"

echo "[rollback] Clearing runtime caches..."
docker compose exec -T app php artisan optimize:clear

echo "[rollback] Bringing application back online..."
docker compose exec -T app php artisan up || true

echo "[rollback] Completed."
