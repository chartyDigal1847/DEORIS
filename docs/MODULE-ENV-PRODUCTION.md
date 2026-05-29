# Module Production `.env` Matrix (`deoris.net`)

Use this for each module app running on Docker or dedicated hosts.

## Required Per-Module Core Values

Set these in every module's `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<module>.deoris.net

DEORIS_URL=https://deoris.net
PORTAL_URL=https://deoris.net

DB_HOST=mysql
REDIS_HOST=redis
```

## Module URL Mapping

| Module | APP_URL |
|---|---|
| EntryEase | `https://entryease.deoris.net` |
| EnrollEase | `https://enrollease.deoris.net` |
| GradeTrack | `https://gradetrack.deoris.net` |
| MediTrack | `https://meditrack.deoris.net` |
| LibrarySys | `https://librarysys.deoris.net` |
| TaskFlow | `https://taskflow.deoris.net` |
| CareerConnect | `https://careerconnect.deoris.net` |
| AssessPay | `https://assesspay.deoris.net` |
| VoteSys | `https://votesys.deoris.net` |
| ClearCheck | `https://clearcheck.deoris.net` |

## DEORIS Portal `.env` Values that Must Match

These values in DEORIS must be aligned with module hosts:

- `APP_URL=https://deoris.net`
- all module URL envs (`ENTRYEASE_URL`, `ENROLLEASE_URL`, ...)
- `SANCTUM_STATEFUL_DOMAINS` includes portal + all module subdomains
- `REVERB_HOST=deoris.net`
- `REVERB_SCHEME=https`

## Validation Command Checklist

After setting env files:

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan optimize
```

For DEORIS also run:

```bash
php artisan deoris:services:health-check
php artisan deoris:events:health
```
