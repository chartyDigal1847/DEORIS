# DEORIS — SOA Architecture

## System Role

The DEORIS Core Portal (`https://deoris.test`) is the **central orchestration layer** for the entire DEORIS ecosystem. It is **not a monolith** and contains **no academic or business logic**.

```
┌─────────────────────────────────────────────────────────────────────┐
│                        DEORIS Core Portal                           │
│                      https://deoris.test                            │
│                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │  Identity    │  │  SSO Broker  │  │   Portal Dashboard Shell │  │
│  │  Provider    │  │  (iframe)    │  │   (role-aware UI)        │  │
│  └──────────────┘  └──────────────┘  └──────────────────────────┘  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │  Event Hub   │  │  Notification│  │   Federated Search       │  │
│  │  (Redis/HTTP)│  │  Hub (WS)    │  │   Gateway                │  │
│  └──────────────┘  └──────────────┘  └──────────────────────────┘  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │  API Gateway │  │  Service     │  │   Access Control Layer   │  │
│  │              │  │  Registry    │  │   (RBAC + Module Guard)  │  │
│  └──────────────┘  └──────────────┘  └──────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
         │              │              │              │
         ▼              ▼              ▼              ▼
  ┌──────────┐  ┌──────────────┐  ┌──────────┐  ┌──────────────┐
  │EntryEase │  │  EnrollEase  │  │GradeTrack│  │  AssessPay   │
  │:443      │  │  :443        │  │:443      │  │  :443        │
  └──────────┘  └──────────────┘  └──────────┘  └──────────────┘
  ┌──────────┐  ┌──────────────┐  ┌──────────┐  ┌──────────────┐
  │MediTrack │  │  LibrarySys  │  │ TaskFlow │  │CareerConnect │
  │:443      │  │  :443        │  │:443      │  │  :443        │
  └──────────┘  └──────────────┘  └──────────┘  └──────────────┘
  ┌──────────┐  ┌──────────────┐
  │ VoteSys  │  │  ClearCheck  │
  │:443      │  │  :443        │
  └──────────┘  └──────────────┘
```

---

## Architecture Style

**Hybrid SOA** — Service-Oriented Architecture with event-driven communication.

| Layer | Technology |
|-------|-----------|
| Portal framework | Laravel 12 |
| Authentication | Fortify + Jetstream + Sanctum |
| SSO | Single-use Sanctum tokens via postMessage |
| Real-time | Laravel Reverb (WebSockets) |
| Event bus | Redis Pub/Sub + HTTP ingest |
| Queue | Redis (separate DBs for events/notifications) |
| Cache | Redis |
| Database | MySQL (`deoris_identity_db`) |
| Frontend | Vanilla JS + custom CSS (no SPA framework) |

---

## Core Responsibilities

### 1. Centralized Authentication

The portal is the **only identity provider** in the ecosystem.

- Registration, login, logout, password reset, email verification
- Two-factor authentication (TOTP + recovery codes)
- Session management (database-backed, encrypted)
- Role-based access control (9 roles)
- Module access middleware

**Roles:**

| Role | Key | Description |
|------|-----|-------------|
| Administrator | `admin` | Full system access |
| Student | `student` | Progressive module unlock |
| Instructor | `instructor` | GradeTrack, TaskFlow, CareerConnect |
| Cashier | `cashier` | AssessPay only |
| Librarian | `librarian` | LibrarySys only |
| Admission Officer | `admission_officer` | EntryEase, EnrollEase, ClearCheck |
| Nurse | `nurse` | MediTrack only |
| Election Officer | `election_officer` | VoteSys, ClearCheck |
| Candidate | `candidate` | VoteSys (when election active) |

### 2. Single Sign-On (SSO) Broker

Centralized iframe SSO for all module services.

**Flow:**

```
Module iframe loads
       │
       ▼
module-bridge.js sends REQUEST_SSO via postMessage
       │
       ▼
portal-bridge.js receives (origin validated)
       │
       ▼
Portal calls GET /api/v1/sso/token (session auth)
       │
       ▼
Portal issues single-use Sanctum token (ability: 'sso')
       │
       ▼
Portal sends SSO_TOKEN via postMessage to module origin
       │
       ▼
module-bridge.js calls POST /api/v1/sso/exchange
       │
       ▼
Portal validates token, deletes it, returns user identity
       │
       ▼
Module receives { id, name, email, role, ... }
```

**Security properties:**
- Single-use tokens (revoke-before-issue + delete-on-exchange)
- Memory-only token handling (no localStorage/sessionStorage)
- Strict origin whitelist (exact match, no wildcards)
- RequestId prevents cross-iframe token injection
- HMAC-SHA256 replay protection on event endpoints
- Comprehensive audit logging to `storage/logs/sso.log`

### 3. Service Registry

Database-backed registry of all ecosystem services.

```
service_registry table:
  service_key       — unique identifier (e.g. 'entryease')
  label             — display name
  url               — base URL
  api_version       — API version prefix
  status            — active | inactive | degraded | maintenance
  allowed_roles     — JSON array of roles that can access this service
  environment_config — JSON config (secret env names, etc.)
  health_check_url  — URL polled by deoris:services:health-check
  health_ok         — last health check result
  last_health_check_at — timestamp of last check
```

### 4. API Gateway

The portal forwards authenticated requests to module services.

```
Client → POST /api/v1/gateway/{module}/{path}
              │
              ▼
         Auth check (Sanctum)
              │
              ▼
         Role/module access check
              │
              ▼
         Rate limit (120 req/min per user)
              │
              ▼
         Strip sensitive headers
              │
              ▼
         Inject identity context headers:
           X-Portal-User-Id
           X-Portal-User-Role
           X-Portal-User-Email
           X-Correlation-Id
              │
              ▼
         Forward to {MODULE_URL}/api/v1/{path}
              │
              ▼
         Return response to client
```

### 5. Event Hub

Centralized event-driven communication between services.

**Ingest channels:**
- HTTP: `POST /api/v1/events` (signed, throttled)
- Redis Pub/Sub: `deoris.events` channel (via `deoris:events:listen`)

**Event envelope:**

```json
{
  "id": "uuid",
  "name": "StudentEnrolled",
  "source_module": "EnrollEase",
  "payload": { "user_id": 42, "program": "BSIT" },
  "occurred_at": "2026-05-18T10:00:00+00:00",
  "correlation_id": "uuid",
  "schema_version": "1.0"
}
```

**Supported events:**

| Event | Source | Description |
|-------|--------|-------------|
| `ApplicationSubmitted` | EntryEase | Student submitted admission application |
| `ApplicationStatusChanged` | EntryEase | Application status updated |
| `AdmissionApproved` | EntryEase | Application approved |
| `AdmissionRejected` | EntryEase | Application rejected |
| `ExamAssigned` | EntryEase | Entrance exam schedule assigned |
| `ExamCompleted` | EntryEase | Entrance exam submitted |
| `ExamScoreReleased` | EntryEase | Exam results published |
| `StudentEnrolled` | EnrollEase | Student enrollment completed |
| `TuitionPaid` | AssessPay | Tuition payment received |
| `GradeReleased` | GradeTrack | Grade published |
| `MedicalApproved` | MediTrack | Medical clearance approved |
| `LibraryPenaltyAdded` | LibrarySys | Library penalty added |
| `ClearanceUpdated` | ClearCheck | Clearance status changed |

**Security:**
- HMAC-SHA256 signatures (`X-DEORIS-Signature`)
- Nonce deduplication (Redis, 5-minute window)
- Timestamp replay protection (±300 seconds)
- Trusted module registry (per-module secrets)

### 6. Notification Hub

Real-time notifications via WebSockets.

```
Event processed by queue worker
       │
       ▼
NotificationFactory resolves recipients + content
       │
       ▼
PortalNotification created in DB
       │
       ▼
PortalNotificationCreated event dispatched
       │
       ▼
Reverb broadcasts to private-users.{id}.notifications
       │
       ▼
portal-notifications.js updates badge + list live
```

**Channels:**
- `private-users.{userId}.notifications` — per-user notifications
- `private-event-monitoring` — admin event hub monitor

### 7. Federated Search Gateway

Parallel search across all accessible module services.

```
GET /portal/search?q=juan
       │
       ▼
Resolve user's visible modules
       │
       ▼
Parallel HTTP pool to all module /api/search endpoints
(4-second timeout per module)
       │
       ▼
Aggregate + score results
       │
       ▼
Cache by (query + role scope) for 60 seconds
       │
       ▼
Return merged, ranked results
```

### 8. Role-Based Access Control

**Student progressive unlock:**

| Step | Condition | Accessible Modules |
|------|-----------|-------------------|
| 1 | Admission pending | EntryEase |
| 2 | Exam submitted, still pending | EntryEase |
| 3 | Admission approved, not enrolled | EnrollEase |
| 4 | Enrolled, clearcheck pending | EnrollEase, AssessPay, ClearCheck |
| 5 | Enrolled + clearcheck passed | EnrollEase, GradeTrack, AssessPay, LibrarySys, TaskFlow, MediTrack, ClearCheck (+ VoteSys if election active) |

CareerConnect is **never** accessible to students.

---

## Database Schema (Portal Only)

The portal owns only identity and orchestration data. Module business data stays in each module's own database.

```
users                    — portal identity, roles, admission/enrollment status
sessions                 — database-backed sessions
password_reset_tokens    — password reset
personal_access_tokens   — Sanctum SSO tokens (single-use, short-lived)
notifications            — portal notification inbox
event_logs               — event hub audit trail
service_registry         — registered ecosystem services
cache                    — Redis-backed (table fallback)
jobs / failed_jobs       — queue workers
```

---

## Loose Coupling Principles

1. **No shared database** — each service owns its own DB
2. **No direct service-to-service calls** — all cross-service communication goes through the portal event hub or API gateway
3. **Identity via SSO only** — modules never store passwords; they receive user identity from the portal
4. **Events are the integration contract** — modules publish events; the portal routes them
5. **Module business logic stays in modules** — the portal only orchestrates

---

## Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Portal framework | Laravel | 12.x |
| Auth | Fortify + Jetstream | 5.x |
| API tokens | Sanctum | 4.x |
| WebSockets | Reverb | 1.x |
| Queue/Cache/Pub-Sub | Redis (predis) | 2.x |
| Database | MySQL | 8.x |
| Frontend | Vanilla JS + CSS | — |
| Integration package | DeorisIntegration | local |
