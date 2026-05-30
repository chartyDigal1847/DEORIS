#!/usr/bin/env bash
# Pull latest main branch for DEORIS portal and all module repos.
#
# Usage (from DEORIS repo root or anywhere):
#   ./docker/pull-all.sh
#
# Expected layout on the VPS:
#   /opt/deoris/DEORIS
#   /opt/deoris/entryEase
#   /opt/deoris/EnrollEase
#   ...

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEORIS_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
PARENT="$(cd "${DEORIS_ROOT}/.." && pwd)"
BRANCH="${BRANCH:-main}"

REPOS=(
  "DEORIS"
  "entryEase"
  "EnrollEase"
  "gradeTrack"
  "MediTrack"
  "asssesspay"
  "LibrarySys"
  "taskflow"
  "VoteSys"
  "ClearCheck"
  "carrerConnect"
)

echo "[pull-all] Pulling branch ${BRANCH} under ${PARENT}"

for repo in "${REPOS[@]}"; do
  path="${PARENT}/${repo}"
  echo ""
  echo "=== ${repo} ==="
  if [[ ! -d "${path}/.git" ]]; then
    echo "[pull-all] SKIP: ${path} is not a git repo"
    continue
  fi
  git -C "${path}" fetch origin
  git -C "${path}" checkout "${BRANCH}" 2>/dev/null || git -C "${path}" checkout -B "${BRANCH}" "origin/${BRANCH}"
  git -C "${path}" pull --ff-only origin "${BRANCH}"
  git -C "${path}" rev-parse --short HEAD
done

echo ""
echo "[pull-all] Done."
