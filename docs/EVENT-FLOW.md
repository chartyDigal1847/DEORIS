# DEORIS — Event Flow Diagrams

## 1. HTTP Event Ingest Flow

```
Module Service (e.g. EnrollEase)
        │
        │  POST /api/v1/events
        │  Headers:
        │    X-DEORIS-Module: EnrollEase
        │    X-DEORIS-Timestamp: 1716000000
        │    X-DEORIS-Nonce: uuid
        │    X-DEORIS-Signature: hmac-sha256
        │
        ▼
VerifyModuleSignature middleware
  ├─ Is module trusted? (TrustedModuleRegistry)
  ├─ Is timestamp within ±300s?
  ├─ Is nonce unique? (Redis cache, 300s TTL)
  └─ Is HMAC-SHA256 signature valid?
        │
        ▼ (pass)
EventIngestController::store()
  ├─ EcosystemEvent::fromArray($payload)
  ├─ EventValidator::validate($event)
  │    ├─ Schema validation (id, name, source_module, payload, etc.)
  │    ├─ Event name in allowed_events list?
  │    └─ Payload has required identifiers?
  ├─ EventLogService::received($event)  → event_logs (status: received)
  └─ EcosystemEventReceived::dispatch($event->toArray())
        │
        ▼
QueueEcosystemEvent listener
  └─ ProcessEcosystemEvent::dispatch($event)  → Redis queue 'events'
        │
        ▼ (queue worker picks up)
ProcessEcosystemEvent::handle()
  ├─ Already processed? (idempotency check)
  ├─ Claim processing slot (optimistic lock)
  ├─ EventValidator::validate($event)
  ├─ For each recipient (NotificationFactory::recipients):
  │    ├─ NotificationFactory::content($event)
  │    ├─ PortalNotification::create(...)
  │    └─ PortalNotificationCreated::dispatch(notification, unreadCount)
  │              │
  │              ▼ (ShouldBroadcastNow)
  │         Reverb broadcasts to:
  │         private-users.{userId}.notifications
  │         Event: notification.created
  │
  ├─ EventLogService::processed($event->id)  → status: processed
  └─ EcosystemEventProcessed::dispatch($eventLog)
              │
              ▼ (ShouldBroadcastNow)
         Reverb broadcasts to:
         private-event-monitoring
         Event: event.processed
```

---

## 2. Redis Pub/Sub Event Flow

```
Module Service
        │
        │  Redis PUBLISH deoris.events <signed-envelope>
        │
        ▼
deoris:events:listen command (long-running)
  └─ Redis::subscribe(['deoris.events'], callback)
        │
        ▼
RedisEventVerifier::verifyAndUnwrap($message)
  ├─ JSON decode envelope
  ├─ Resolve module secret (TrustedModuleRegistry)
  ├─ Verify HMAC-SHA256 signature
  ├─ Check timestamp replay window
  └─ Check nonce uniqueness (Redis cache)
        │
        ▼
EventValidator::validate($event)
EventLogService::received($event)
EcosystemEventReceived::dispatch($event->toArray())
        │
        ▼
(same queue processing flow as HTTP ingest above)
```

---

## 3. SSO Flow (iframe postMessage)

```
User logs in at https://deoris.test
        │
        ▼
Portal session established (deoris_identity_session cookie)
Session cookie: SameSite=None; Secure; Domain=.deoris.test
        │
        ▼
User navigates to /entryease in portal shell
        │
        ▼
DashboardController renders homepage.blade.php
iframe src = https://entryease.deoris.test?embedded=1
        │
        ▼
EntryEase loads module-bridge.js (served from portal)
        │
        ▼
module-bridge.js:
  ├─ Generates requestId (memory only)
  ├─ Sets 8-second timeout
  └─ window.parent.postMessage({ type: 'REQUEST_SSO', requestId }, PORTAL_ORIGIN)
        │
        ▼
portal-bridge.js (running in portal shell):
  ├─ Validates origin (exact match against ALLOWED_ORIGINS)
  └─ Calls GET /api/v1/sso/token (portal session cookie)
        │
        ▼
SsoController::issueToken()
  ├─ Verify portal session (auth('web')->user())
  ├─ Revoke all existing 'sso-token' tokens for this user
  ├─ Create new Sanctum token (ability: 'sso', no expiry)
  └─ Return { token: "1|abc..." }
        │
        ▼
portal-bridge.js:
  └─ event.source.postMessage({ type: 'SSO_TOKEN', token, requestId }, moduleOrigin)
        │
        ▼
module-bridge.js:
  ├─ Validates requestId matches
  ├─ Stores token in memory (pendingToken)
  └─ Calls POST /api/v1/sso/exchange { token }
        │
        ▼
SsoController::exchangeToken()
  ├─ TokenValidator::validateAndConsume($token)
  │    ├─ Find token in personal_access_tokens
  │    ├─ Verify ability === 'sso'
  │    ├─ Verify tokenable is User
  │    ├─ DELETE token immediately (single-use)
  │    └─ Assert token is gone (post-deletion check)
  └─ Return { user: { id, name, email, role, ... } }
        │
        ▼
module-bridge.js:
  ├─ window.PORTAL_USER = user
  └─ Dispatches 'module:ready' event
        │
        ▼
EntryEase application boots with authenticated user identity
```

---

## 4. Notification Delivery Flow

```
ProcessEcosystemEvent creates PortalNotification
        │
        ▼
PortalNotificationCreated event dispatched (ShouldBroadcastNow)
        │
        ├─── Reverb WebSocket ──────────────────────────────────────────────┐
        │    Channel: private-users.{userId}.notifications                  │
        │    Event: notification.created                                     │
        │    Payload: { notification: {...}, unread_count: N }              │
        │                                                                    │
        │    portal-notifications.js receives:                              │
        │    ├─ setUnreadCount(N)  → updates bell badge                     │
        │    └─ loadNotifications() → refreshes notification list           │
        │                                                                    │
        └─── Polling fallback (if Reverb unavailable) ──────────────────────┘
             Every 30 seconds:
             GET /portal/notifications/unread-count
             → updates badge silently
```

---

## 5. Federated Search Flow

```
User types in portal search bar (debounced 250ms)
        │
        ▼
GET /portal/search?q=juan&limit=8
        │
        ▼
FederatedSearchController
  └─ user.visibleModules() → allowed module keys
        │
        ▼
FederatedSearchService::search(query, limit, allowedModules)
  ├─ Check cache (key: sha1(query|limit|moduleScope), TTL: 60s)
  │
  └─ Cache miss: Http::pool() — parallel requests to all allowed modules:
       GET {MODULE_URL}/api/search?q=juan&limit=8
       Authorization: Bearer {MODULE_SEARCH_TOKEN}
       Timeout: 4 seconds per module
        │
        ▼
  Aggregate results from all responding modules
  Score each result (exact match = 1.0, partial = 0.3)
  Sort by score descending
  Slice to limit * 3 results
        │
        ▼
Return { query, results: [...], modules: { ok/fail per module } }
```

---

## 6. API Gateway Flow

```
Client (portal shell or module)
        │
        │  GET /api/v1/gateway/enrollease/students?page=1
        │  Authorization: Bearer {sanctum-token}
        │
        ▼
ApiGatewayController::forward('enrollease', 'students')
  ├─ Auth check (Sanctum)
  ├─ Rate limit check (120/min per user, Redis)
  ├─ Module access check (user.canAccessModule('enrollease'))
  ├─ Resolve module URL (ModuleRegistry)
  ├─ Generate correlation ID (UUID)
  ├─ Strip sensitive headers (Authorization, Cookie, X-CSRF-TOKEN, etc.)
  ├─ Inject identity headers:
  │    X-Portal-User-Id: 42
  │    X-Portal-User-Role: student
  │    X-Portal-User-Email: juan@example.com
  │    X-Correlation-Id: uuid
  │    X-Forwarded-By: deoris-portal
  └─ Forward: GET https://enrollease.deoris.test/api/v1/students?page=1
        │
        ▼
EnrollEase service processes request
(trusts X-Portal-User-* headers from portal)
        │
        ▼
Response proxied back to client
X-Correlation-Id header included in response
```
