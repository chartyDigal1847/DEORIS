#!/usr/bin/env bash
# Wipe and rebuild all module databases, then fresh-migrate + seed DEORIS portal only.
#
# DESTRUCTIVE: drops every table in each module DB and the portal DB.
# Module apps are NOT seeded — only the main DEORIS portal (demo users + service registry).
#
# Usage (on VPS):
#   cd /opt/deoris/DEORIS
#   chmod +x docker/reset-all-databases.sh
#   ./docker/reset-all-databases.sh
#
# Optional:
#   SKIP_PULL=1 ./docker/reset-all-databases.sh
#   SKIP_SETUP=1 ./docker/reset-all-databases.sh   # skip setup-all-modules after reset

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEORIS_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${DEORIS_ROOT}"

if [[ ! -f ".env" ]]; then
  echo "[reset-all] Missing ${DEORIS_ROOT}/.env"
  exit 1
fi

MODULES=(
  entryease
  enrollease
  gradetrack
  meditrack
  assesspay
  librarysys
  taskflow
  votesys
  clearcheck
  careerconnect
)

echo "[reset-all] WARNING: This will DROP all data in every module database and the portal database."
echo "[reset-all] Module seeders are NOT run — only DEORIS portal will be seeded."

if [[ "${RESET_CONFIRM:-}" != "RESET" ]]; then
  read -r -p "Type RESET to continue: " confirm
  if [[ "${confirm}" != "RESET" ]]; then
    echo "[reset-all] Aborted."
    exit 1
  fi
fi

if [[ "${SKIP_PULL:-0}" != "1" ]]; then
  echo "[reset-all] Pulling latest code..."
  "${SCRIPT_DIR}/pull-all.sh"
fi

echo "[reset-all] Ensuring MySQL and module containers are up..."
up_services=(mysql redis app)
for module in "${MODULES[@]}"; do
  up_services+=("module_${module}")
done
docker compose up -d "${up_services[@]}" >/dev/null

for module in "${MODULES[@]}"; do
  service="module_${module}"
  echo ""
  echo "[reset-all] migrate:fresh ${module} (no seed)..."
  docker compose exec -T "${service}" php artisan migrate:fresh --force
done

echo ""
echo "[reset-all] migrate:fresh DEORIS portal + main seed..."
docker compose exec -T app php artisan migrate:fresh --force --seed
docker compose exec -T app php artisan db:seed --class=ServiceRegistrySeeder --force

if [[ "${SKIP_SETUP:-0}" != "1" ]]; then
  echo ""
  echo "[reset-all] Re-running module bootstrap (migrate, caches, grants — no module seed)..."
  export SKIP_MODULE_SEED=1
  "${SCRIPT_DIR}/setup-all-modules.sh"
else
  echo "[reset-all] Skipping setup-all-modules (SKIP_SETUP=1)."
  docker compose exec -T app php artisan optimize:clear
  docker compose exec -T app php artisan config:cache
  docker compose restart nginx app queue redis_listener reverb scheduler >/dev/null || true
fi

echo ""
echo "[reset-all] Done. Portal demo logins: admin@example.com / Admin@Password1"
echo "[reset-all] Modules have empty schemas — submit new data through each module UI."
