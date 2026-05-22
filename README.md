# DEORIS Portal

Central SOA orchestration layer for the DEORIS ecosystem — identity provider, SSO broker, event hub, notification hub, federated search gateway, service registry, API gateway, and portal dashboard shell.

## Quick Start

1. Read **[SETUP.md](SETUP.md)** — full install, HTTPS, Redis, and run instructions.
2. Copy `.env.example` → `.env` and configure database + Redis.
3. Run `composer install`, `npm install`, `php artisan migrate`, `php artisan db:seed`.
4. One-time HTTPS: `.\setup-https.ps1` (Administrator).
5. Start services: **`scripts\start-deoris-portal.bat`**
6. Open **https://deoris.test**

## Documentation

| File | Description |
|------|-------------|
| [SETUP.md](SETUP.md) | Install, new PC checklist, troubleshooting |
| [docs/SOA-ARCHITECTURE.md](docs/SOA-ARCHITECTURE.md) | Full SOA architecture, roles, responsibilities |
| [docs/API-REFERENCE.md](docs/API-REFERENCE.md) | All API endpoints with request/response examples |
| [docs/EVENT-FLOW.md](docs/EVENT-FLOW.md) | Event flow diagrams (HTTP, Redis, SSO, notifications, search, gateway) |
| [docs/DEORIS-EVENT-HUB.md](docs/DEORIS-EVENT-HUB.md) | Event hub deep dive, Redis, queue workers, module integration |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Production deployment, Nginx, Supervisor, security checklist |
| [.env.example](.env.example) | All environment variables |

## Architecture

The portal is **not a monolith**. It contains no academic or business logic. It serves as:

- **Identity Provider** — Fortify + Jetstream + Sanctum auth for all 9 roles
- **SSO Broker** — Single-use iframe postMessage SSO for all module services
- **Event Hub** — HMAC-signed event ingest via HTTP and Redis Pub/Sub
- **Notification Hub** — Real-time WebSocket notifications via Reverb
- **Federated Search** — Parallel search across all accessible modules
- **Service Registry** — Database-backed registry of all ecosystem services
- **API Gateway** — Authenticated request forwarding to module services
- **Access Control** — Role-based module visibility and middleware

## Services

| Module | Subdomain | Roles |
|--------|-----------|-------|
| EntryEase | entryease.deoris.test | admin, student, admission_officer |
| EnrollEase | enrollease.deoris.test | admin, student, admission_officer |
| GradeTrack | gradetrack.deoris.test | admin, student, instructor |
| MediTrack | meditrack.deoris.test | admin, student, nurse |
| LibrarySys | librarysys.deoris.test | admin, student, librarian |
| TaskFlow | taskflow.deoris.test | admin, student, instructor |
| CareerConnect | careerconnect.deoris.test | admin, instructor |
| AssessPay | assesspay.deoris.test | admin, student, cashier |
| VoteSys | votesys.deoris.test | admin, student, election_officer, candidate |
| ClearCheck | clearcheck.deoris.test | admin, student, admission_officer, election_officer |

## Scripts

| Script | Purpose |
|--------|---------|
| `scripts/start-deoris-portal.bat` | Start queue, Reverb, Redis listener, Vite |
| `setup-https.ps1` | mkcert + Apache vhosts (one-time) |

## Useful Commands

```powershell
php artisan deoris:events:health
php artisan deoris:verify-integration
php artisan deoris:services:health-check
php artisan deoris:events:publish-test --email=student@example.com
php artisan queue:failed
php artisan test
php artisan optimize:clear
```

## Demo Accounts (after `db:seed`)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | Admin@Password1 |
| Student | student@example.com | Student@Password1 |
| Instructor | instructor@example.com | Instructor@Password1 |
| Cashier | cashier@example.com | Cashier@Password1 |
| Librarian | librarian@example.com | Librarian@Password1 |
| Admission Officer | admission@example.com | Admission@Password1 |
| Nurse | nurse@example.com | Nurse@Password1234 |
| Election Officer | election@example.com | Election@Password1 |
| Candidate | candidate@example.com | Candidate@Password1 |

## License

MIT
