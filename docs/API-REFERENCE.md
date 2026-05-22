# DEORIS Portal — API Reference

Base URL: `https://deoris.test`

All API responses are JSON. All authenticated endpoints require either:
- A valid portal session cookie (`deoris_identity_session`), or
- A Sanctum bearer token with the appropriate ability

---

## Authentication

### POST /login
Authenticate with email and password.

**Request:**
```json
{ "email": "admin@example.com", "password": "Admin@Password1" }
```

**Response:** Redirect to `/homepage` (web) or `{ "two_factor": false }` (JSON)

---

### POST /logout
End the current session.

---

### POST /register
Create a new portal account (student role by default).

**Request:**
```json
{
  "name": "Juan Dela Cruz",
  "email": "juan@example.com",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123"
}
```

---

## SSO Endpoints

All SSO endpoints are throttled at **60 requests/minute**.

### GET /api/v1/sso/check
Check if the current portal session is authenticated.

**Auth:** Portal session cookie (no bearer token needed)

**Response 200:**
```json
{
  "success": true,
  "authenticated": true,
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "admin",
    "email_verified_at": "2026-05-18T00:00:00+00:00",
    "admission_status": null,
    "enrollment_status": null,
    "clearcheck_passed": false,
    "visible_modules": ["entryease", "enrollease", "gradetrack", "..."]
  }
}
```

**Response 401:**
```json
{ "success": false, "error": "unauthenticated" }
```

---

### GET /api/v1/sso/token
Issue a single-use SSO token for the authenticated user.

**Auth:** Portal session cookie

**Response 200:**
```json
{ "success": true, "token": "1|abc123..." }
```

**Notes:**
- Token has `sso` ability only (cannot be used as a general API token)
- Any existing SSO tokens for this user are revoked before issuing
- Token is deleted immediately after exchange (single-use)

---

### POST /api/v1/sso/exchange
Exchange a single-use SSO token for user identity.

**Auth:** Bearer token (the SSO token from `/sso/token`)

**Request:**
```json
{ "token": "1|abc123..." }
```

**Response 200:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "admin",
    "email_verified_at": "2026-05-18T00:00:00+00:00",
    "admission_status": null,
    "enrollment_status": null,
    "clearcheck_passed": false
  }
}
```

**Response 401:**
```json
{ "success": false, "error": "invalid_sso_token" }
```

---

### POST /api/v1/sso/revoke
Revoke an outstanding SSO token (idempotent cleanup).

**Request:**
```json
{ "token": "1|abc123..." }
```

**Response 200:**
```json
{ "success": true, "revoked": true }
```

---

## Event Hub

### POST /api/v1/events
Ingest a signed ecosystem event from a trusted module service.

**Auth:** HMAC-SHA256 module signature headers

**Required headers:**
```
X-DEORIS-Module: EnrollEase
X-DEORIS-Timestamp: 1716000000
X-DEORIS-Nonce: uuid-v4
X-DEORIS-Signature: hmac-sha256-hex
Content-Type: application/json
```

**Request body:**
```json
{
  "id": "uuid",
  "name": "StudentEnrolled",
  "source_module": "EnrollEase",
  "payload": {
    "user_id": 42,
    "student_name": "Juan Dela Cruz",
    "student_email": "juan@example.com",
    "program": "BSIT"
  },
  "occurred_at": "2026-05-18T10:00:00+00:00",
  "correlation_id": "uuid",
  "schema_version": "1.0"
}
```

**Response 202:**
```json
{
  "accepted": true,
  "event_id": "uuid",
  "correlation_id": "uuid"
}
```

**Response 401:** Missing/invalid signature
**Response 409:** Duplicate nonce (replay attack rejected)
**Response 422:** Validation failed

---

## Notifications

All notification endpoints require portal session or Sanctum auth.

### GET /portal/notifications
List notifications for the authenticated user.

**Query params:** `limit` (default: 15)

**Response 200:**
```json
{
  "unread_count": 3,
  "data": [
    {
      "id": "uuid",
      "type": "admission",
      "title": "New admission application",
      "body": "Juan Dela Cruz submitted a Grade 7 application.",
      "source_module": "EntryEase",
      "event_name": "ApplicationSubmitted",
      "action_url": "/entryease",
      "read_at": null,
      "created_at": "2026-05-18T10:00:00+00:00"
    }
  ]
}
```

### GET /portal/notifications/unread-count
```json
{ "unread_count": 3 }
```

### PATCH /portal/notifications/{id}/read
Mark a single notification as read.

### PATCH /portal/notifications/read-all
Mark all notifications as read.

---

## Federated Search

### GET /portal/search
Search across all modules the authenticated user can access.

**Auth:** Portal session

**Query params:**
- `q` (required, min 2 chars, max 120)
- `limit` (optional, 1–25, default 8)

**Response 200:**
```json
{
  "query": "juan",
  "results": [
    {
      "module": "enrollease",
      "module_label": "EnrollEase",
      "type": "student",
      "title": "Juan Dela Cruz",
      "subtitle": "BSIT 2A",
      "url": "https://enrollease.deoris.test/students/42",
      "score": 1.0,
      "meta": { "student_number": "2026-001" }
    }
  ],
  "modules": {
    "enrollease": { "ok": true, "status": 200 },
    "gradetrack": { "ok": false, "status": 503 }
  }
}
```

---

## Service Registry

### GET /api/v1/services
List all active services visible to the authenticated user (role-filtered).

**Auth:** Sanctum

**Response 200:**
```json
{
  "data": [
    {
      "service_key": "entryease",
      "label": "EntryEase",
      "url": "https://entryease.deoris.test",
      "api_version": "v1",
      "status": "active",
      "health_ok": true
    }
  ]
}
```

### GET /api/v1/services/{service}
Get full service details including config. **Admin only.**

### POST /api/v1/services
Register or update a service. **Admin only.**

**Request:**
```json
{
  "service_key": "newservice",
  "label": "New Service",
  "url": "https://newservice.deoris.test",
  "api_version": "v1",
  "status": "active",
  "allowed_roles": ["admin", "student"],
  "health_check_url": "https://newservice.deoris.test/up"
}
```

### PATCH /api/v1/services/{service}/status
Update service status. **Admin only.**

```json
{ "status": "maintenance", "health_ok": false }
```

### DELETE /api/v1/services/{service}
Remove a service from the registry. **Admin only.**

---

## API Gateway

### ANY /api/v1/gateway/{module}/{path}
Forward an authenticated request to a module service.

**Auth:** Sanctum (session or token)

**Rate limit:** 120 requests/minute per user

**Example:**
```
GET /api/v1/gateway/enrollease/students?page=1
→ forwards to https://enrollease.deoris.test/api/v1/students?page=1
```

**Injected headers (portal → module):**
```
X-Portal-User-Id: 42
X-Portal-User-Role: student
X-Portal-User-Email: juan@example.com
X-Correlation-Id: uuid
X-Forwarded-By: deoris-portal
```

**Response:** Proxied response from the module service.

**Error responses:**
- `403` — module access denied for this role
- `404` — module not found in registry
- `429` — rate limit exceeded
- `503` — module service unavailable
- `502` — gateway error

---

## Admin Statistics

### GET /api/v1/admin/stats
Live portal statistics. **Admin only.**

**Response 200:**
```json
{
  "data": {
    "total_students": 150,
    "total_instructors": 12,
    "pending_admissions": 8,
    "cleared_students": 95,
    "enrolled_students": 130,
    "events_today": 47,
    "events_failed": 2,
    "unread_notifications": 15,
    "total_users": 175
  }
}
```

---

## Event Logs

### GET /portal/event-logs
Paginated event hub audit log. **Admin only.**

**Query params:** `status`, `module`, `per_page` (default 25)

**Response 200:** Paginated `EventLog` records.

---

## WebSocket Channels

Authenticated via `/broadcasting/auth` (Sanctum session).

### private-users.{userId}.notifications
Receives `notification.created` events when a new notification is created for the user.

**Payload:**
```json
{
  "notification": {
    "id": "uuid",
    "title": "...",
    "body": "...",
    "type": "admission",
    "source_module": "EntryEase",
    "event_name": "ApplicationSubmitted",
    "action_url": "/entryease",
    "created_at": "..."
  },
  "unread_count": 4
}
```

### private-event-monitoring
Admin/instructor channel. Receives `event.processed` events when the queue worker finishes processing an ecosystem event.

---

## Module Integration Contract

### What modules must implement

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/search` | GET | Federated search (Bearer token auth) |
| `/up` | GET | Health check (returns 200) |
| `/api/v1/*` | ANY | Business API (receives portal identity headers) |

### What modules receive from the portal

**Via SSO exchange:**
```json
{
  "id": 42,
  "name": "Juan Dela Cruz",
  "email": "juan@example.com",
  "role": "student",
  "admission_status": "approved",
  "enrollment_status": "enrolled",
  "clearcheck_passed": true
}
```

**Via API gateway headers:**
```
X-Portal-User-Id: 42
X-Portal-User-Role: student
X-Portal-User-Email: juan@example.com
X-Correlation-Id: uuid
```

### Publishing events from a module

```php
use Deoris\Integration\Events\StudentEnrolled;
use Deoris\Integration\Contracts\EventPublisherInterface;

$event = new StudentEnrolled('EnrollEase', [
    'user_id'        => $student->portal_user_id,
    'student_email'  => $student->email,
    'student_name'   => $student->full_name,
    'student_number' => $student->student_number,
    'program'        => $enrollment->program_name,
]);

app(EventPublisherInterface::class)->publishHttp($event);
```

Required `.env` in each module:
```dotenv
DEORIS_PORTAL_URL=https://deoris.test
DEORIS_MODULE_NAME=EnrollEase
ENROLLEASE_EVENT_SECRET=long-random-secret
```
