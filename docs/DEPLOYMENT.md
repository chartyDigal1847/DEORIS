# DEORIS Portal — Deployment Guide

## Development (XAMPP / Windows)

See [SETUP.md](../SETUP.md) for the full local development setup.

Quick start:
```powershell
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
.\scripts\start-deoris-portal.bat
```

---

## Production Deployment

### Prerequisites

| Component | Requirement |
|-----------|-------------|
| PHP | 8.2+ with extensions: pdo_mysql, redis, mbstring, openssl, tokenizer, xml, ctype, json, bcmath |
| MySQL | 8.0+ |
| Redis | 6.0+ |
| Web server | Nginx or Apache with HTTPS |
| Node.js | 18+ (build only, not needed at runtime) |
| Supervisor | Process manager for queue workers and Reverb |

---

### Environment Configuration

Copy `.env.example` to `.env` and configure:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://deoris.yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deoris_identity_db
DB_USERNAME=deoris_user
DB_PASSWORD=strong-password

# Session (CRITICAL for iframe SSO)
SESSION_DRIVER=database
SESSION_COOKIE=deoris_identity_session
SESSION_DOMAIN=".yourdomain.com"
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

# Redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=redis-password
QUEUE_CONNECTION=redis
CACHE_STORE=redis
BROADCAST_CONNECTION=reverb

# Reverb WebSockets
REVERB_APP_ID=deoris-portal
REVERB_APP_KEY=your-reverb-key
REVERB_APP_SECRET=your-reverb-secret
REVERB_HOST=deoris.yourdomain.com
REVERB_PORT=8080
REVERB_SCHEME=https

# Module URLs
ENTRYEASE_URL=https://entryease.yourdomain.com
# ... (all module URLs)

# Module event secrets (use long random strings)
ENTRYEASE_EVENT_SECRET=generate-with-openssl-rand-hex-32
# ... (all module secrets)

# Sanctum stateful domains
SANCTUM_STATEFUL_DOMAINS=deoris.yourdomain.com,entryease.yourdomain.com,...
```

---

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name deoris.yourdomain.com;

    ssl_certificate     /etc/ssl/deoris/fullchain.pem;
    ssl_certificate_key /etc/ssl/deoris/privkey.pem;

    root /var/www/deoris/public;
    index index.php;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Reverb WebSocket proxy
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
    }

    # Reverb auth endpoint
    location /broadcasting {
        try_files $uri $uri/ /index.php?$query_string;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name deoris.yourdomain.com;
    return 301 https://$host$request_uri;
}
```

---

### Supervisor Configuration

Create `/etc/supervisor/conf.d/deoris.conf`:

```ini
[program:deoris-queue-events]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/deoris/artisan queue:work redis --queue=events,notifications,default --tries=3 --backoff=15 --timeout=60 --sleep=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/deoris/queue.log
stopwaitsecs=3600

[program:deoris-reverb]
process_name=%(program_name)s
command=php /var/www/deoris/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/deoris/reverb.log

[program:deoris-redis-listener]
process_name=%(program_name)s
command=php /var/www/deoris/artisan deoris:events:listen
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/deoris/redis-listener.log

[program:deoris-scheduler]
process_name=%(program_name)s
command=php /var/www/deoris/artisan schedule:work
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/deoris/scheduler.log
```

Apply:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

### Deployment Steps

```bash
# 1. Pull latest code
cd /var/www/deoris
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Seed service registry (first deploy only)
php artisan db:seed --class=ServiceRegistrySeeder --force

# 5. Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Restart workers
sudo supervisorctl restart deoris-queue-events:*
sudo supervisorctl restart deoris-reverb
sudo supervisorctl restart deoris-redis-listener
sudo supervisorctl restart deoris-scheduler

# 7. Verify
php artisan deoris:events:health
php artisan deoris:verify-integration
```

---

### Subdomain Routing

Each module runs as an independent Laravel application on its own subdomain:

| Module | Subdomain |
|--------|-----------|
| Portal | `deoris.yourdomain.com` |
| EntryEase | `entryease.yourdomain.com` |
| EnrollEase | `enrollease.yourdomain.com` |
| GradeTrack | `gradetrack.yourdomain.com` |
| MediTrack | `meditrack.yourdomain.com` |
| LibrarySys | `librarysys.yourdomain.com` |
| TaskFlow | `taskflow.yourdomain.com` |
| CareerConnect | `careerconnect.yourdomain.com` |
| AssessPay | `assesspay.yourdomain.com` |
| VoteSys | `votesys.yourdomain.com` |
| ClearCheck | `clearcheck.yourdomain.com` |

**TLS:** Use a wildcard certificate for `*.yourdomain.com` (Let's Encrypt with DNS challenge, or mkcert for local dev).

**Session cookie:** `SESSION_DOMAIN=".yourdomain.com"` with a leading dot covers all subdomains. `SameSite=None; Secure` is required for the session cookie to be sent inside cross-origin iframes.

---

### Health Checks

```bash
# Portal health
curl https://deoris.yourdomain.com/up

# Redis connectivity
php artisan deoris:events:health

# Service registry health check (polls all modules)
php artisan deoris:services:health-check

# Queue status
php artisan queue:monitor redis:events,redis:notifications --max=100

# Failed jobs
php artisan queue:failed
```

---

### Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` is a unique 32-byte random key
- [ ] All `*_EVENT_SECRET` values are long random strings (32+ bytes)
- [ ] `SESSION_SECURE_COOKIE=true` and `SESSION_SAME_SITE=none`
- [ ] HTTPS enforced on all subdomains
- [ ] HSTS header configured in Nginx
- [ ] Redis password set
- [ ] Database user has minimal permissions (no SUPER, no FILE)
- [ ] `storage/` and `bootstrap/cache/` are writable by web server
- [ ] Log files are not publicly accessible
- [ ] `SANCTUM_STATEFUL_DOMAINS` lists only your own subdomains
- [ ] CORS `allowed_origins` lists only your own subdomains (no wildcards)
