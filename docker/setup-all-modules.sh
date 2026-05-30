#!/usr/bin/env bash
# Bootstrap all DEORIS modules in dependency order.
#
# Usage:
#   cd /opt/deoris/DEORIS
#   ./docker/setup-all-modules.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEORIS_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${DEORIS_ROOT}"

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

echo "[setup-all] Starting module bootstrap (${#MODULES[@]} modules)"

for module in "${MODULES[@]}"; do
  echo ""
  "${SCRIPT_DIR}/setup-module.sh" "${module}"
done

echo ""
echo "[setup-all] Rebuilding portal caches..."
docker compose exec -T app php artisan db:seed --class=ServiceRegistrySeeder --force
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose restart nginx app queue redis_listener reverb scheduler >/dev/null

echo "[setup-all] All modules configured."
