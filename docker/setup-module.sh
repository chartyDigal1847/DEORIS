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
PORTAL_URL="$(read_env_value .env APP_URL)"
PORTAL_URL="${PORTAL_URL:-https://deoris.net}"

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
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' "${ENV_FILE}"
sed -i 's/^DB_HOST=.*/DB_HOST=mysql/' "${ENV_FILE}"
sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/' "${ENV_FILE}"
sed -i 's/^DEORIS_DB_HOST=.*/DEORIS_DB_HOST=mysql/' "${ENV_FILE}" 2>/dev/null || true
for PORTAL_ENV_KEY in APP_PORTAL_URL AUTH_SERVICE_URL DEORIS_PORTAL_URL PORTAL_BASE_URL; do
  grep -q "^${PORTAL_ENV_KEY}=" "${ENV_FILE}" || echo "${PORTAL_ENV_KEY}=${PORTAL_URL}" >> "${ENV_FILE}"
  sed -i "s#^${PORTAL_ENV_KEY}=.*#${PORTAL_ENV_KEY}=${PORTAL_URL}#" "${ENV_FILE}"
done
# Portal HTTP is served by nginx (app is PHP-FPM only). Modules reach the portal API via nginx + Host.
INTERNAL_PORTAL_BASE="https://nginx"
for INTERNAL_PORTAL_ENV_KEY in DEORIS_PORTAL_INTERNAL_URL AUTH_SERVICE_INTERNAL_URL; do
  grep -q "^${INTERNAL_PORTAL_ENV_KEY}=" "${ENV_FILE}" || echo "${INTERNAL_PORTAL_ENV_KEY}=${INTERNAL_PORTAL_BASE}" >> "${ENV_FILE}"
  sed -i "s#^${INTERNAL_PORTAL_ENV_KEY}=.*#${INTERNAL_PORTAL_ENV_KEY}=${INTERNAL_PORTAL_BASE}#" "${ENV_FILE}"
done
if [[ "${MODULE_KEY}" == "meditrack" ]]; then
  PORTAL_HOST="$(echo "${PORTAL_URL}" | sed -E 's#^https?://([^/:]+).*#\1#')"
  grep -q '^MEDITRACK_PORTAL_EXCHANGE_HOST=' "${ENV_FILE}" || echo "MEDITRACK_PORTAL_EXCHANGE_HOST=${PORTAL_HOST}" >> "${ENV_FILE}"
  sed -i "s#^MEDITRACK_PORTAL_EXCHANGE_HOST=.*#MEDITRACK_PORTAL_EXCHANGE_HOST=${PORTAL_HOST}#" "${ENV_FILE}"
  grep -q '^LOG_CHANNEL=' "${ENV_FILE}" || echo 'LOG_CHANNEL=stack' >> "${ENV_FILE}"
  sed -i 's/^LOG_CHANNEL=.*/LOG_CHANNEL=stack/' "${ENV_FILE}"
  grep -q '^LOG_STACK=' "${ENV_FILE}" || echo 'LOG_STACK=single,stderr' >> "${ENV_FILE}"
  sed -i 's/^LOG_STACK=.*/LOG_STACK=single,stderr/' "${ENV_FILE}"
fi

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

if [[ "${MODULE_KEY}" == "assesspay" ]]; then
  PORTAL_DB="$(read_env_value "${DEORIS_ROOT}/.env" DB_DATABASE)"
  PORTAL_DB="${PORTAL_DB:-deoris_identity_db}"
  ENROLLEASE_ENV="${DEORIS_ROOT}/../EnrollEase/.env"
  ENROLLEASE_DB="enrollease"
  if [[ -f "${ENROLLEASE_ENV}" ]]; then
    ENROLLEASE_DB="$(read_env_value "${ENROLLEASE_ENV}" DB_DATABASE)"
    ENROLLEASE_DB="${ENROLLEASE_DB:-enrollease}"
  fi
  CLEARCHECK_ENV="${DEORIS_ROOT}/../ClearCheck/.env"
  CLEARCHECK_SERVICE_KEY="clearcheck-service"
  if [[ -f "${CLEARCHECK_ENV}" ]]; then
    CLEARCHECK_SERVICE_KEY="$(read_env_value "${CLEARCHECK_ENV}" CLEARCHECK_SERVICE_KEY)"
    CLEARCHECK_SERVICE_KEY="${CLEARCHECK_SERVICE_KEY:-clearcheck-service}"
  fi

  echo "[setup-module] Granting AssessPay read access to ${PORTAL_DB}/${ENROLLEASE_DB} and portal clearance updates..."
  docker compose exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
GRANT SELECT ON \`${PORTAL_DB}\`.* TO '${DB_USERNAME}'@'%';
GRANT UPDATE (\`clearcheck_passed\`, \`updated_at\`) ON \`${PORTAL_DB}\`.\`users\` TO '${DB_USERNAME}'@'%';
GRANT SELECT ON \`${ENROLLEASE_DB}\`.* TO '${DB_USERNAME}'@'%';
FLUSH PRIVILEGES;
SQL

  grep -q '^DEORIS_DB_HOST=' "${ENV_FILE}" || echo 'DEORIS_DB_HOST=mysql' >> "${ENV_FILE}"
  sed -i 's/^DEORIS_DB_HOST=.*/DEORIS_DB_HOST=mysql/' "${ENV_FILE}"
  grep -q '^DEORIS_DB_DATABASE=' "${ENV_FILE}" || echo "DEORIS_DB_DATABASE=${PORTAL_DB}" >> "${ENV_FILE}"
  sed -i "s/^DEORIS_DB_DATABASE=.*/DEORIS_DB_DATABASE=${PORTAL_DB}/" "${ENV_FILE}"
  grep -q '^CLEARCHECK_SERVICE_KEY=' "${ENV_FILE}" || echo "CLEARCHECK_SERVICE_KEY=${CLEARCHECK_SERVICE_KEY}" >> "${ENV_FILE}"
  sed -i "s/^CLEARCHECK_SERVICE_KEY=.*/CLEARCHECK_SERVICE_KEY=${CLEARCHECK_SERVICE_KEY}/" "${ENV_FILE}"
  grep -q '^ENROLLEASE_DB_HOST=' "${ENV_FILE}" || echo 'ENROLLEASE_DB_HOST=mysql' >> "${ENV_FILE}"
  sed -i 's/^ENROLLEASE_DB_HOST=.*/ENROLLEASE_DB_HOST=mysql/' "${ENV_FILE}"
  grep -q '^ENROLLEASE_DB_DATABASE=' "${ENV_FILE}" || echo "ENROLLEASE_DB_DATABASE=${ENROLLEASE_DB}" >> "${ENV_FILE}"
  sed -i "s/^ENROLLEASE_DB_DATABASE=.*/ENROLLEASE_DB_DATABASE=${ENROLLEASE_DB}/" "${ENV_FILE}"
fi

if [[ "${MODULE_KEY}" == "enrollease" ]]; then
  PORTAL_DB="$(read_env_value "${DEORIS_ROOT}/.env" DB_DATABASE)"
  PORTAL_DB="${PORTAL_DB:-deoris_identity_db}"
  ENTRYEASE_ENV="${DEORIS_ROOT}/../entryEase/.env"
  ENTRYEASE_DB="entryEase_db"
  if [[ -f "${ENTRYEASE_ENV}" ]]; then
    ENTRYEASE_DB="$(read_env_value "${ENTRYEASE_ENV}" DB_DATABASE)"
    ENTRYEASE_DB="${ENTRYEASE_DB:-entryEase_db}"
  fi

  echo "[setup-module] Granting EnrollEase read access to ${PORTAL_DB} users and ${ENTRYEASE_DB} applicant documents..."
  docker compose exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
GRANT SELECT ON \`${PORTAL_DB}\`.* TO '${DB_USERNAME}'@'%';
GRANT SELECT ON \`${ENTRYEASE_DB}\`.* TO '${DB_USERNAME}'@'%';
FLUSH PRIVILEGES;
SQL

  grep -q '^DEORIS_DB_HOST=' "${ENV_FILE}" || echo 'DEORIS_DB_HOST=mysql' >> "${ENV_FILE}"
  sed -i 's/^DEORIS_DB_HOST=.*/DEORIS_DB_HOST=mysql/' "${ENV_FILE}"
  grep -q '^DEORIS_DB_DATABASE=' "${ENV_FILE}" || echo "DEORIS_DB_DATABASE=${PORTAL_DB}" >> "${ENV_FILE}"
  sed -i "s/^DEORIS_DB_DATABASE=.*/DEORIS_DB_DATABASE=${PORTAL_DB}/" "${ENV_FILE}"
  grep -q '^ENTRYEASE_DB_HOST=' "${ENV_FILE}" || echo 'ENTRYEASE_DB_HOST=mysql' >> "${ENV_FILE}"
  sed -i 's/^ENTRYEASE_DB_HOST=.*/ENTRYEASE_DB_HOST=mysql/' "${ENV_FILE}"
  grep -q '^ENTRYEASE_DB_DATABASE=' "${ENV_FILE}" || echo "ENTRYEASE_DB_DATABASE=${ENTRYEASE_DB}" >> "${ENV_FILE}"
  sed -i "s/^ENTRYEASE_DB_DATABASE=.*/ENTRYEASE_DB_DATABASE=${ENTRYEASE_DB}/" "${ENV_FILE}"
  grep -q '^ENTRYEASE_PRIVATE_STORAGE_PATH=' "${ENV_FILE}" || echo 'ENTRYEASE_PRIVATE_STORAGE_PATH=/var/deoris/entryease/private' >> "${ENV_FILE}"
  sed -i 's#^ENTRYEASE_PRIVATE_STORAGE_PATH=.*#ENTRYEASE_PRIVATE_STORAGE_PATH=/var/deoris/entryease/private#' "${ENV_FILE}"
fi

echo "[setup-module] Installing PHP dependencies..."
docker compose exec -T "${SERVICE}" composer install --no-dev --optimize-autoloader --no-interaction

if docker compose exec -T "${SERVICE}" test -f package.json; then
  echo "[setup-module] Building frontend assets..."
  if docker compose exec -T "${SERVICE}" test -f package-lock.json; then
    docker compose exec -T "${SERVICE}" npm ci
  else
    echo "[setup-module] No package-lock.json; using npm install."
    docker compose exec -T "${SERVICE}" npm install
  fi
  if docker compose exec -T "${SERVICE}" grep -q '"build"' package.json 2>/dev/null; then
    docker compose exec -T "${SERVICE}" npm run build
  else
    echo "[setup-module] No npm build script; skipping frontend build."
  fi
fi

APP_KEY_VALUE="$(read_env_value "${ENV_FILE}" APP_KEY)"
if [[ -z "${APP_KEY_VALUE}" ]]; then
  echo "[setup-module] Generating APP_KEY..."
  docker compose exec -T "${SERVICE}" php artisan key:generate --force
fi

echo "[setup-module] Running migrations..."
docker compose exec -T "${SERVICE}" php artisan migrate --force

if [[ "${SKIP_MODULE_SEED:-0}" != "1" ]]; then
  if docker compose exec -T "${SERVICE}" php artisan list --raw 2>/dev/null | grep -q '^db:seed'; then
    echo "[setup-module] Seeding database (if seeders exist)..."
    docker compose exec -T "${SERVICE}" php artisan db:seed --force || true
  fi
else
  echo "[setup-module] Skipping module seed (SKIP_MODULE_SEED=1)."
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
docker compose exec -T "${SERVICE}" sh -c 'chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true'
docker compose exec -T "${SERVICE}" sh -c 'chmod -R 775 storage bootstrap/cache 2>/dev/null || true'

docker compose restart nginx "${SERVICE}" >/dev/null

echo "[setup-module] Completed: ${MODULE_KEY}"
