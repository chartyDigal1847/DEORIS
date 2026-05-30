#!/usr/bin/env bash
# Bootstrap one module inside the DEORIS Docker stack.
#
# Usage:
#   cd /opt/deoris/DEORIS
#   ./docker/setup-module.sh entryease
#
# Module keys:
#   entryease enrollease gradetrack meditrack librarysys
#   taskflow careerconnect assesspay votesys clearcheck

set -euo pipefail

MODULE_KEY="${1:-}"
if [[ -z "${MODULE_KEY}" ]]; then
  echo "Usage: $0 <module-key>"
  echo "Example: $0 entryease"
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEORIS_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${DEORIS_ROOT}"

if [[ ! -f ".env" ]]; then
  echo "[setup-module] Missing ${DEORIS_ROOT}/.env"
  exit 1
fi

read_env_value() {
  local file="$1"
  local key="$2"
  local line value
  line="$(grep -E "^${key}=" "${file}" | tail -n 1 || true)"
  value="${line#${key}=}"
  echo "${value}"
}

MYSQL_ROOT_PASSWORD="$(read_env_value .env MYSQL_ROOT_PASSWORD)"
if [[ -z "${MYSQL_ROOT_PASSWORD}" ]]; then
  echo "[setup-module] MYSQL_ROOT_PASSWORD missing in DEORIS .env"
  exit 1
fi

case "${MODULE_KEY}" in
  entryease)
    SERVICE="module_entryease"
    FOLDER="../entryEase"
    ;;
  enrollease)
    SERVICE="module_enrollease"
    FOLDER="../EnrollEase"
    ;;
  gradetrack)
    SERVICE="module_gradetrack"
    FOLDER="../gradeTrack"
    ;;
  meditrack)
    SERVICE="module_meditrack"
    FOLDER="../MediTrack"
    ;;
  librarysys)
    SERVICE="module_librarysys"
    FOLDER="../LibrarySys"
    ;;
  taskflow)
    SERVICE="module_taskflow"
    FOLDER="../taskflow"
    ;;
  careerconnect)
    SERVICE="module_careerconnect"
    FOLDER="../carrerConnect"
    ;;
  assesspay)
    SERVICE="module_assesspay"
    FOLDER="../asssesspay"
    ;;
  votesys)
    SERVICE="module_votesys"
    FOLDER="../VoteSys"
    ;;
  clearcheck)
    SERVICE="module_clearcheck"
    FOLDER="../ClearCheck"
    ;;
  *)
    echo "[setup-module] Unknown module key: ${MODULE_KEY}"
    exit 1
    ;;
esac

MODULE_PATH="$(cd "${DEORIS_ROOT}/${FOLDER}" && pwd)"
ENV_FILE="${MODULE_PATH}/.env"
ENV_EXAMPLE="${MODULE_PATH}/.env.example"

echo "[setup-module] ${MODULE_KEY} -> ${MODULE_PATH} (${SERVICE})"

if [[ ! -d "${MODULE_PATH}" ]]; then
  echo "[setup-module] Module folder not found: ${MODULE_PATH}"
  exit 1
fi

if [[ ! -f "${ENV_FILE}" ]]; then
  if [[ ! -f "${ENV_EXAMPLE}" ]]; then
    echo "[setup-module] Missing .env and .env.example in ${MODULE_PATH}"
    exit 1
  fi
  cp "${ENV_EXAMPLE}" "${ENV_FILE}"
  echo "[setup-module] Created .env from .env.example"
fi

# Docker network service names for shared MySQL/Redis.
sed -i 's/^DB_HOST=.*/DB_HOST=mysql/' "${ENV_FILE}"
sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/' "${ENV_FILE}"
sed -i 's/^DEORIS_DB_HOST=.*/DEORIS_DB_HOST=mysql/' "${ENV_FILE}" 2>/dev/null || true

DB_DATABASE="$(read_env_value "${ENV_FILE}" DB_DATABASE)"
DB_USERNAME="$(read_env_value "${ENV_FILE}" DB_USERNAME)"
DB_PASSWORD="$(read_env_value "${ENV_FILE}" DB_PASSWORD)"

if [[ -z "${DB_DATABASE}" || -z "${DB_USERNAME}" || -z "${DB_PASSWORD}" ]]; then
  echo "[setup-module] DB_DATABASE, DB_USERNAME, and DB_PASSWORD must be set in ${ENV_FILE}"
  exit 1
fi

echo "[setup-module] Ensuring MySQL database and user..."
docker compose up -d mysql redis "${SERVICE}" >/dev/null

docker compose exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\`;
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%';
FLUSH PRIVILEGES;
SQL

echo "[setup-module] Installing PHP dependencies..."
docker compose exec -T "${SERVICE}" composer install --no-dev --optimize-autoloader --no-interaction

if docker compose exec -T "${SERVICE}" test -f package.json; then
  echo "[setup-module] Building frontend assets..."
  docker compose exec -T "${SERVICE}" npm ci
  docker compose exec -T "${SERVICE}" npm run build
fi

APP_KEY_VALUE="$(read_env_value "${ENV_FILE}" APP_KEY)"
if [[ -z "${APP_KEY_VALUE}" ]]; then
  echo "[setup-module] Generating APP_KEY..."
  docker compose exec -T "${SERVICE}" php artisan key:generate --force
fi

echo "[setup-module] Running migrations..."
docker compose exec -T "${SERVICE}" php artisan migrate --force

if docker compose exec -T "${SERVICE}" php artisan list --raw 2>/dev/null | grep -q '^db:seed'; then
  echo "[setup-module] Seeding database (if seeders exist)..."
  docker compose exec -T "${SERVICE}" php artisan db:seed --force || true
fi

echo "[setup-module] Caching config/routes/views..."
docker compose exec -T "${SERVICE}" php artisan optimize:clear
docker compose exec -T "${SERVICE}" php artisan config:cache
if ! docker compose exec -T "${SERVICE}" php artisan route:cache; then
  echo "[setup-module] WARN: route:cache failed; continuing without route cache."
  docker compose exec -T "${SERVICE}" php artisan route:clear || true
fi
docker compose exec -T "${SERVICE}" php artisan view:cache

echo "[setup-module] Fixing permissions..."
docker compose exec -T "${SERVICE}" sh -c 'chmod -R 775 storage bootstrap/cache 2>/dev/null || true'

docker compose restart nginx "${SERVICE}" >/dev/null

echo "[setup-module] Completed: ${MODULE_KEY}"
