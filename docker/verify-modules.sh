#!/usr/bin/env bash
# Post-setup verification for DEORIS portal + module subdomains.
#
# Usage:
#   cd /opt/deoris/DEORIS
#   ./docker/verify-modules.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEORIS_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${DEORIS_ROOT}"

MODULES=(
  "entryease|https://entryease.deoris.net/up"
  "enrollease|https://enrollease.deoris.net/up"
  "gradetrack|https://gradetrack.deoris.net/up"
  "meditrack|https://meditrack.deoris.net/up"
  "assesspay|https://assesspay.deoris.net/up"
  "librarysys|https://librarysys.deoris.net/up"
  "taskflow|https://taskflow.deoris.net/up"
  "votesys|https://votesys.deoris.net/up"
  "clearcheck|https://clearcheck.deoris.net/up"
  "careerconnect|https://careerconnect.deoris.net/up"
)

echo "[verify] Container status"
docker compose ps

echo ""
echo "[verify] Portal health commands"
docker compose exec -T app php artisan deoris:events:health || true
docker compose exec -T app php artisan deoris:services:health-check || true
docker compose exec -T app php artisan queue:failed || true

echo ""
echo "[verify] Module /up endpoints (from app container)"
for entry in "${MODULES[@]}"; do
  key="${entry%%|*}"
  url="${entry#*|}"
  code="$(docker compose exec -T app curl -sk -o /dev/null -w '%{http_code}' "${url}" || echo "000")"
  if [[ "${code}" == "200" ]]; then
    echo "  [OK] ${key} HTTP ${code}"
  else
    echo "  [FAIL] ${key} HTTP ${code} (${url})"
  fi
done

echo ""
echo "[verify] Portal homepage"
portal_code="$(docker compose exec -T app curl -sk -o /dev/null -w '%{http_code}' https://deoris.net/ || echo "000")"
echo "  deoris.net HTTP ${portal_code}"

echo ""
echo "[verify] Manual browser checks still required:"
echo "  - Login at https://deoris.net"
echo "  - Open each module iframe from the homepage"
echo "  - Confirm no CSP/CORS/401/419 errors in DevTools"
