# DEORIS Central Event Orchestration Hub

The portal owns orchestration only: signed event intake, queueing, notification fan-out, websocket updates, monitoring, and federated search. Every module keeps its own database and business logic.

## Redis On Windows

Use one of these supported Windows paths:

1. Install Redis with WSL: `wsl --install`, then inside Ubuntu run `sudo apt update && sudo apt install redis-server`.
2. Or install Memurai/Redis-compatible server for native Windows development.
3. Verify: `redis-cli ping` should return `PONG`.

Laravel settings are in `.env`, `config/database.php`, and `config/queue.php`:

```dotenv
QUEUE_CONNECTION=redis
CACHE_STORE=redis
BROADCAST_CONNECTION=reverb
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_PUBSUB_DB=2
REDIS_QUEUE=events
DEORIS_EVENTS_REDIS_CHANNEL=deoris.events
```

Troubleshooting:

- `Class Redis not found`: set `REDIS_CLIENT=predis` or enable the PHP Redis extension used by XAMPP.
- `Connection refused`: start Redis and confirm the port is `6379`.
- Queues not moving: run `php artisan queue:failed`, then `php artisan queue:retry all` after fixing the error.
- Config not changing: run `php artisan optimize:clear`.

## Queue Workers

Start all required background services from the project root:

```powershell
.\scripts\start-deoris-portal.bat
# or
.\scripts\start-deoris-portal.ps1
```

See [SETUP.md](../SETUP.md) for full install and troubleshooting.

Useful artisan commands:

```powershell
php artisan migrate
php artisan deoris:events:health
php artisan queue:work redis --queue=events,notifications,default --tries=3 --backoff=15
php artisan queue:monitor redis:events,redis:notifications --max=100
php artisan queue:failed
php artisan queue:retry all
php artisan queue:forget {failed_job_uuid}
php artisan queue:flush
php artisan reverb:start --debug
```

In production on Windows Server, run `start-deoris-portal.ps1` services under Task Scheduler or NSSM (or split queue/reverb/listener into separate services). On Linux, use Supervisor equivalents.

## Event Security

Each module has its own event secret:

```dotenv
ENROLLEASE_EVENT_SECRET=long-random-secret
```

Module requests to `POST https://deoris.test/api/events` must include:

- `X-DEORIS-Module`
- `X-DEORIS-Timestamp`
- `X-DEORIS-Nonce`
- `X-DEORIS-Signature`

The signature is `HMAC-SHA256(timestamp.nonce.raw_json_body, module_secret)`. The portal rejects unknown modules, stale timestamps, replayed nonces, invalid signatures, and invalid payloads.

## Full Flow Example

EnrollEase publishes `StudentEnrolled`:

```php
use Deoris\Integration\EventPublisher;
use Deoris\Integration\Events\StudentEnrolled;

$event = new StudentEnrolled('EnrollEase', [
    'user_id' => $student->portal_user_id,
    'student_number' => $student->student_number,
    'student_email' => $student->email,
    'student_name' => $student->full_name,
    'program' => $enrollment->program_name,
]);

app(EventPublisher::class)->publishHttp($event);
// Optional internal bus:
app(EventPublisher::class)->publishRedis($event);
```

Portal flow:

1. `VerifyModuleSignature` authenticates the module and blocks replay.
2. `EventIngestController` validates and logs the event as `received`.
3. `ProcessEcosystemEvent` runs on Redis queue `events`.
4. The job creates one or more rows in `notifications`.
5. `PortalNotificationCreated` broadcasts to `private-users.{id}.notifications` through Reverb.
6. `homepage.js` updates the topbar badge and list live.
7. `event_logs` stores status, payload, errors, and correlation ID for monitoring.

## Federated Search Contract

The portal calls every module at:

```text
GET {MODULE_URL}/api/search?q=student&limit=8
Authorization: Bearer {MODULE_SEARCH_TOKEN}
```

Expected module response:

```json
{
  "data": [
    {
      "type": "student",
      "title": "Juan Dela Cruz",
      "subtitle": "BSIT 2A",
      "url": "https://enrollease.deoris.test/students/123",
      "score": 0.95,
      "meta": {"student_number": "2026-001"}
    }
  ]
}
```

The portal merges, ranks, and caches results briefly. It does not copy module records.
