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

## 2) Deploy Modules in Dependency Order

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

For each module:

```bash
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

If module uses queue/scheduler/reverb workers, restart those workers after deploy.

## 3) Immediate Sanity Checks

- login at `https://deoris.net`
- load homepage and open every module once
- verify no 401/419/CSP/CORS errors in browser console
- verify module bootstrap APIs respond normally
