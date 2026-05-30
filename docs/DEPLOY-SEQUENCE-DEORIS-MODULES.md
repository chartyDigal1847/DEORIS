# Deployment Sequence (Portal + Modules)

Use this exact order for go-live to minimize cross-service breakage.

## 0) Pre-Deploy Backups

- snapshot all DBs
- backup `.env` for portal + each module
- record current git commit per service

## 1) Deploy DEORIS First

```bash
cd /opt/deoris/DEORIS
./docker/deploy.sh
```

## 2) Deploy Modules (Docker nginx front door)

All modules run as PHP-FPM containers behind the same DEORIS Docker nginx.

```bash
cd /opt/deoris/DEORIS
chmod +x docker/pull-all.sh docker/setup-all-modules.sh docker/verify-modules.sh

./docker/pull-all.sh
./docker/setup-all-modules.sh
./docker/verify-modules.sh
```

Single-module bootstrap:

```bash
./docker/setup-module.sh entryease
```

Module order inside `setup-all-modules.sh`:

1. `entryEase`
2. `EnrollEase`
3. `gradeTrack`
4. `MediTrack`
5. `asssesspay`
6. `LibrarySys`
7. `taskflow`
8. `VoteSys`
9. `ClearCheck`
10. `carrerConnect`

Before running setup, replace all `change-me-*` values in each module `.env` and ensure event/search secrets match the DEORIS portal `.env`.

## 3) Immediate Sanity Checks

- login at `https://deoris.net`
- load homepage and open every module once
- verify no 401/419/CSP/CORS errors in browser console
- verify module bootstrap APIs respond normally

See also: [DEPLOYMENT-DOCKER-DEORIS-NET.md](./DEPLOYMENT-DOCKER-DEORIS-NET.md)
