# DEORIS Portal — Setup Guide

Use this guide when setting up the portal on a **new PC** or after cloning the repo.

## What this project is

The DEORIS portal (`https://deoris.test`) is the central shell for SSO, module iframes, notifications, event orchestration, and federated search. Each module (EnrollEase, GradeTrack, etc.) stays a **separate** Laravel app on its own subdomain.

---

## 1. Prerequisites

| Tool | Purpose |
|------|---------|
| **PHP 8.2+** | Laravel 12 (XAMPP: `C:\xampp\php\php.exe`) |
| **Composer** | PHP dependencies |
| **Node.js + npm** | Vite / Echo frontend |
| **MySQL** | Portal database (`deoris_identity_db`) |
| **Redis** | Queues, cache, Pub/Sub (WSL, Memurai, or native Windows Redis) |
| **XAMPP Apache** | HTTPS vhost for `deoris.test` |

---

## 2. First-time install

```powershell
cd C:\xampp\htdocs\DEORIS   # or your clone path

composer install
npm install

copy .env.example .env
php artisan key:generate
```

Edit `.env`:

- Database: `DB_*` → MySQL credentials
- `APP_URL=https://deoris.test`
- `SESSION_DOMAIN=.deoris.test` and `SESSION_SAME_SITE=none` (required for module iframes)
- Redis: `REDIS_HOST=127.0.0.1`, `QUEUE_CONNECTION=redis`
- Reverb: `REVERB_*` and matching `VITE_REVERB_*`
- Per-module secrets: `*_EVENT_SECRET` (use long random strings)

Run migrations:

```powershell
php artisan migrate
php artisan db:seed   # optional demo accounts
```

Build frontend (if not using Vite dev server):

```powershell
npm run build
```

---

## 3. HTTPS (one-time per machine)

Run **as Administrator**:

```powershell
cd C:\xampp\htdocs\DEORIS
.\setup-https.ps1
```

This script:

- Adds `deoris.test` and module subdomains to the hosts file
- Installs mkcert and generates wildcard TLS certs
- Writes Apache virtual hosts to `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
- Restarts Apache

Manual vhost reference (if needed): see comments at the top of `setup-https.ps1`.

Then open **https://deoris.test**

> Verify module folder paths inside `setup-https.ps1` match your actual `C:\xampp\htdocs\` layout.

---

## 4. Start Redis

Redis must answer `PONG`:

```powershell
redis-cli ping
```

Or:

```powershell
php artisan deoris:events:health
```

---

## 5. Run the portal (every dev session)

**Recommended — one launcher** (opens Queue, Reverb, Redis listener, and Vite):

```powershell
.\scripts\start-deoris-portal.bat
```

Or:

```powershell
.\scripts\start-deoris-portal.ps1
composer run portal
```

**Without Vite** (use pre-built assets):

```powershell
.\scripts\start-deoris-portal.ps1 -SkipVite -BuildAssets
```

Keep **XAMPP Apache** running. The launcher does **not** start `php artisan serve`.

### What each window does

| Service | Purpose |
|---------|---------|
| Queue worker | Processes events → notifications |
| Reverb | WebSocket live bell updates |
| Redis subscriber | Ingests module Pub/Sub events |
| Vite | Hot reload for JS/CSS (optional) |

---

## 6. Demo logins (after `db:seed`)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | Admin@Password1 |
| Student | student@example.com | Student@Password1 |
| Instructor | instructor@example.com | Instructor@Password1 |

See `database/seeders/DatabaseSeeder.php` for all roles.

---

## 7. New device checklist

- [ ] Clone repo / copy project folder
- [ ] `composer install` && `npm install`
- [ ] Copy `.env` from secure backup or `.env.example`
- [ ] `php artisan key:generate` (only if new `.env`)
- [ ] `php artisan migrate`
- [ ] Run `setup-https.ps1` (Admin) if this PC has no certs yet
- [ ] Start Redis
- [ ] Run `scripts\start-deoris-portal.bat`
- [ ] Open https://deoris.test
- [ ] Hard refresh browser (`Ctrl+Shift+R`)

---

## 8. Troubleshooting

| Issue | Fix |
|-------|-----|
| `npm.ps1` blocked | Launcher uses `npm.cmd` in cmd window — use `start-deoris-portal.bat` |
| WebSocket failed | Start Reverb window. For HTTPS, proxy `/app` to port 8080 in Apache (see `docs/apache-reverb-proxy.conf`) or set `VITE_REVERB_ENABLED=false` and use `npm run build` |
| Notifications 401 | Use `/portal/notifications` (fixed in shell JS). Run `php artisan optimize:clear` after pull |
| Bell count but no live updates | Reverb not running — REST still works on click |
| Redis connection refused | Start Redis service |
| Notifications not processing | Queue worker window must be running |
| Search returns nothing | Set `*_SEARCH_TOKEN` in `.env` and implement `/api/search` in modules |
| Missing logo | Add `public/login_ui/assets/logo.png` |

---

## 9. Further reading

- **Event Hub (deep dive):** [docs/DEORIS-EVENT-HUB.md](docs/DEORIS-EVENT-HUB.md)
- **Environment template:** [.env.example](.env.example)
- **Module integration package:** [packages/DeorisIntegration](packages/DeorisIntegration)

---

## 10. Useful commands

```powershell
php artisan deoris:events:health
php artisan deoris:events:publish-test --email=student@example.com
php artisan queue:failed
php artisan test --filter=EventHubTest
php artisan optimize:clear
```
