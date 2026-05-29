# Rollback Playbook (Production)

Use this when a deployment introduces a critical regression.

## Preconditions

- You have a known-good git ref (tag or commit hash).
- You have the SQL backup created by `docker/deploy.sh`.
- You have a backup of production `.env`.

## 1) Put App in Maintenance Mode

```bash
cd /opt/deoris/DEORIS
docker compose exec -T app php artisan down
```

## 2) Execute Rollback

```bash
chmod +x docker/rollback.sh
ROLLBACK_REF=<known-good-ref> DB_BACKUP=./backups/<db-dump>.sql ./docker/rollback.sh
```

## 3) Validate Recovery

- portal loads and login works
- core module iframe routing works
- `php artisan deoris:events:health` passes
- no critical errors in logs

## 4) Re-enable Traffic

```bash
docker compose exec -T app php artisan up
```

## Backup Retention Policy

- Keep at least one successful pre-deploy SQL backup per release.
- Retain last known-good release tag/commit.
- Retain `.env` backup per deployment window.
