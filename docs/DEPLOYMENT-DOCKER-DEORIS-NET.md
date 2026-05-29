# DEORIS Docker Production Runbook (`deoris.net`)

This runbook is the production checklist for deploying DEORIS on a Docker-based Contabo server.

## 1) DNS and TLS

Create DNS records pointing to your Contabo public IP:

- `deoris.net`
- `www.deoris.net`
- `entryease.deoris.net`
- `enrollease.deoris.net`
- `gradetrack.deoris.net`
- `meditrack.deoris.net`
- `librarysys.deoris.net`
- `taskflow.deoris.net`
- `careerconnect.deoris.net`
- `assesspay.deoris.net`
- `votesys.deoris.net`
- `clearcheck.deoris.net`

TLS recommendation:

- Use wildcard cert (`*.deoris.net`) + apex cert (`deoris.net`) if available.
- Otherwise use SAN cert covering all hosts above.
- Install cert/key at:
  - `docker/nginx/certs/deoris.net.crt`
  - `docker/nginx/certs/deoris.net.key`

## 2) Server `.env` Workflow

```bash
cd /opt/deoris/DEORIS
cp .env.example .env
nano .env
```

Minimum required production values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://deoris.net

DB_HOST=mysql
REDIS_HOST=redis

REVERB_HOST=deoris.net
REVERB_SCHEME=https

SANCTUM_STATEFUL_DOMAINS=deoris.net,entryease.deoris.net,enrollease.deoris.net,gradetrack.deoris.net,meditrack.deoris.net,librarysys.deoris.net,taskflow.deoris.net,careerconnect.deoris.net,assesspay.deoris.net,votesys.deoris.net,clearcheck.deoris.net
```

Use long random secrets for all `*_EVENT_SECRET`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, and database credentials.

## 3) Docker Runtime Topology

The stack is defined in:

- `docker-compose.yml`
- `docker/nginx/default.conf`

Expected services:

- `nginx`
- `app`
- `mysql`
- `redis`
- `queue`
- `reverb`
- `redis_listener`
- `scheduler`

Security posture:

- MySQL/Redis are private on Docker network only (no public host port mapping).
- Nginx is the only public entrypoint (`80`, `443`).
- Reverb websocket path is proxied via `https://deoris.net/app`.

## 4) Deployment Steps (DEORIS)

Use helper script:

```bash
cd /opt/deoris/DEORIS
chmod +x docker/deploy.sh
./docker/deploy.sh
```

What it does:

1. Pulls latest `main`.
2. Creates DB backup + `.env` backup.
3. Builds/starts Docker services.
4. Runs migrations with `--force`.
5. Clears/rebuilds Laravel caches.
6. Runs health checks.

## 5) Module Deployment Order

After DEORIS deploys successfully, deploy modules in this order:

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

- pull latest code
- install production dependencies
- run migrations
- clear/rebuild caches
- restart workers/services

## 6) Post-Deploy Verification

Portal checks:

- `https://deoris.net` login works.
- homepage loads and module cards open.
- `https://deoris.net/api/v1/sso/check` and `/api/v1/sso/token` respond for authenticated session.

Module checks:

- iframe boot works for each role path.
- no CSP/CORS/SameSite cookie errors in browser console.
- core business flows pass smoke tests:
  - admission
  - enrollment
  - grading
  - medical
  - payments
  - voting
  - clearance
  - career

Realtime checks:

- websocket handshake succeeds through `wss://deoris.net/app`.

## 7) Rollback Procedure

Use helper:

```bash
cd /opt/deoris/DEORIS
chmod +x docker/rollback.sh
ROLLBACK_REF=<known-good-commit-or-tag> DB_BACKUP=./backups/<db-dump>.sql ./docker/rollback.sh
```

Rollback requirements:

- Keep at least one known-good git ref.
- Keep SQL backups for each deployment window.
- Keep a copy of production `.env`.
