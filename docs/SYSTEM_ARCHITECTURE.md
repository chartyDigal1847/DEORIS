# DEORIS — System Architecture Diagram

## 1. High-Level System Overview

```mermaid
graph TB
    subgraph BROWSER["🌐 Client Browser"]
        UI["Portal Shell UI\n(homepage.blade.php)"]
        IFRAME["Module iframes\n(embedded modules)"]
        WS_CLIENT["WebSocket Client\n(portal-notifications.js)"]
    end

    subgraph DEORIS_PORTAL["🏛️ DEORIS Core Portal — deoris.test"]
        direction TB
        subgraph PORTAL_LAYERS["Core Services"]
            AUTH["🔐 Auth / Identity\n(Fortify + Jetstream)"]
            SSO["🎫 SSO Broker\n(Sanctum single-use tokens)"]
            GATEWAY["🔀 API Gateway\n(/api/v1/gateway/{module})"]
            EVENTHUB["📡 Event Hub\n(HTTP ingest + Redis Pub/Sub)"]
            NOTIF["🔔 Notification Hub\n(Reverb WebSockets)"]
            SEARCH["🔍 Federated Search\n(parallel HTTP pool)"]
            REGISTRY["📋 Service Registry\n(health checks)"]
            RBAC["🛡️ RBAC + Module Guard\n(9 roles, progressive unlock)"]
        end

        subgraph PORTAL_DB["Portal Database — deoris_identity_db"]
            DB_USERS[("users\n(identity + status)")]
            DB_EVENTS[("event_logs\n(audit trail)")]
            DB_NOTIFS[("notifications\n(inbox)")]
            DB_REGISTRY[("service_registry")]
            DB_TOKENS[("personal_access_tokens\n(SSO tokens)")]
            DB_SESSIONS[("sessions")]
        end

        subgraph PORTAL_INFRA["Infrastructure"]
            REDIS_QUEUE[("Redis DB:0\nQueue: events")]
            REDIS_CACHE[("Redis DB:1\nCache")]
            REDIS_PUBSUB[("Redis DB:2\nPub/Sub: deoris.events")]
            REVERB["Reverb WS Server\n:8081"]
            QUEUE_WORKER["Queue Worker\n(ProcessEcosystemEvent)"]
        end
    end

    subgraph MODULES["📦 Module Services (each on own subdomain + DB)"]
        direction LR
        M1["📝 entryEase\nentryease.deoris.test\nSQLite"]
        M2["📋 EnrollEase\nenrollease.deoris.test\nenrolldb"]
        M3["📊 gradeTrack\ngradetrack.deoris.test\ngradetrack"]
        M4["💰 asssesspay\nassessepay.deoris.test\nassespaydb"]
        M5["✅ ClearCheck\nclearcheck.deoris.test\ncleardb"]
        M6["📚 LibrarySys\nlibrarysys.deoris.test\nlibrary"]
        M7["🏥 MediTrack\nmeditrack.deoris.test\nmeditrack_db"]
        M8["🗳️ VoteSys\nvotesys.deoris.test\nvotesys_db"]
        M9["📌 taskflow\ntaskflow.deoris.test\ndeoris_taskflow"]
        M10["🎓 carrerConnect\ncareerconnect.deoris.test\ncareerconnect"]
    end

    %% Browser ↔ Portal
    UI -->|"HTTPS requests"| AUTH
    UI -->|"postMessage bridge"| SSO
    WS_CLIENT <-->|"WSS :8081"| REVERB
    IFRAME <-->|"postMessage (SSO)"| SSO

    %% Portal internal
    AUTH --> DB_USERS
    AUTH --> DB_SESSIONS
    SSO --> DB_TOKENS
    GATEWAY --> RBAC
    EVENTHUB --> DB_EVENTS
    EVENTHUB --> REDIS_QUEUE
    EVENTHUB --> REDIS_PUBSUB
    NOTIF --> DB_NOTIFS
    NOTIF --> REVERB
    REGISTRY --> DB_REGISTRY
    QUEUE_WORKER --> REDIS_QUEUE
    QUEUE_WORKER --> DB_NOTIFS
    QUEUE_WORKER --> DB_USERS
    QUEUE_WORKER --> REVERB
    REDIS_PUBSUB --> QUEUE_WORKER

    %% Portal → Modules (SSO + Gateway)
    SSO -->|"identity token"| M1
    SSO -->|"identity token"| M2
    SSO -->|"identity token"| M3
    SSO -->|"identity token"| M4
    SSO -->|"identity token"| M5
    SSO -->|"identity token"| M6
    SSO -->|"identity token"| M7
    SSO -->|"identity token"| M8
    SSO -->|"identity token"| M9
    SSO -->|"identity token"| M10

    %% Modules → EventHub
    M1 -->|"POST /api/events\n(HMAC signed)"| EVENTHUB
    M2 -->|"POST /api/events\n(HMAC signed)"| EVENTHUB
    M3 -->|"POST /api/events\n(HMAC signed)"| EVENTHUB
    M4 -->|"POST /api/events\n(HMAC signed)"| EVENTHUB
    M5 -->|"POST /api/events\n(HMAC signed)"| EVENTHUB
    M6 -->|"POST /api/events\n(HMAC signed)"| EVENTHUB
    M7 -->|"POST /api/events\n(HMAC signed)"| EVENTHUB
    M8 -->|"POST /api/events\n(HMAC signed)"| EVENTHUB
    M9 -->|"POST /api/events\n(HMAC signed)"| EVENTHUB
    M10 -->|"POST /api/events\n(HMAC signed)"| EVENTHUB

    %% Search
    SEARCH -->|"GET /api/search\n(Bearer token)"| M1
    SEARCH -->|"GET /api/search\n(Bearer token)"| M2
    SEARCH -->|"GET /api/search\n(Bearer token)"| M3

    %% ClearCheck → Modules (validation queries)
    M5 -->|"REST API validation"| M2
    M5 -->|"REST API validation"| M4
    M5 -->|"REST API validation"| M6
    M5 -->|"REST API validation"| M3

    style DEORIS_PORTAL fill:#1a1a2e,color:#e0e0ff,stroke:#4a4aff
    style BROWSER fill:#0d2137,color:#e0f0ff,stroke:#2a6aaa
    style MODULES fill:#1a2e1a,color:#e0ffe0,stroke:#4aaa4a
    style PORTAL_LAYERS fill:#2a2a4e,color:#e0e0ff,stroke:#6a6aff
    style PORTAL_DB fill:#2e1a2e,color:#ffe0ff,stroke:#aa4aaa
    style PORTAL_INFRA fill:#2e2a1a,color:#fff0e0,stroke:#aaaa4a
```

---

## 2. SSO Authentication Flow

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Portal as DEORIS Portal<br/>(deoris.test)
    participant Module as Module Service<br/>(e.g. enrollease.deoris.test)

    User->>Browser: Navigate to portal
    Browser->>Portal: GET https://deoris.test/login
    User->>Portal: Submit credentials
    Portal->>Portal: Fortify authenticates<br/>Creates encrypted session
    Portal-->>Browser: Set deoris_identity_session cookie<br/>(SameSite=None; Secure; Domain=.deoris.test)
    Browser-->>User: Dashboard loaded

    Note over Browser,Module: User clicks module tab → iframe loads

    Browser->>Module: Load iframe src=https://enrollease.deoris.test?embedded=1
    Module-->>Browser: module-bridge.js loaded

    Browser->>Portal: postMessage REQUEST_SSO {requestId}
    Note over Portal: Validates origin (exact match whitelist)
    Portal->>Portal: GET /api/v1/sso/token<br/>Revoke old SSO tokens<br/>Issue new single-use Sanctum token
    Portal-->>Browser: postMessage SSO_TOKEN {token, requestId}

    Browser->>Module: POST /api/v1/sso/exchange {token}
    Module->>Portal: Validate token (TokenValidator)
    Note over Portal: Verify ability='sso'<br/>DELETE token immediately (single-use)
    Portal-->>Module: {id, name, email, role, student_number, ...}
    Module-->>Browser: window.PORTAL_USER set<br/>module:ready dispatched
    Browser-->>User: Module UI rendered with identity
```

---

## 3. Event Hub Flow

```mermaid
sequenceDiagram
    participant Module as Module Service<br/>(e.g. EnrollEase)
    participant Outbox as event_outbox<br/>(enrolldb)
    participant Hub as DEORIS Event Hub<br/>(deoris.test)
    participant Queue as Redis Queue<br/>(events)
    participant Worker as Queue Worker<br/>(ProcessEcosystemEvent)
    participant DB as deoris_identity_db
    participant Reverb as Reverb WS
    participant Browser as User Browser

    Module->>Outbox: Write event (status=pending)
    Note over Module: Background job picks up outbox

    Module->>Hub: POST /api/events/ingest<br/>Headers: X-DEORIS-Module, X-DEORIS-Timestamp,<br/>X-DEORIS-Nonce, X-DEORIS-Signature (HMAC-SHA256)

    Hub->>Hub: VerifyModuleSignature middleware<br/>• Trusted module?<br/>• Timestamp ±300s?<br/>• Nonce unique? (Redis 5min TTL)<br/>• HMAC valid?

    Hub->>DB: INSERT event_logs (status=received)
    Hub->>Queue: Dispatch ProcessEcosystemEvent job
    Hub-->>Module: 202 Accepted

    Module->>Outbox: Update status=published

    Queue->>Worker: Pick up job
    Worker->>Worker: Idempotency check<br/>Claim processing slot
    Worker->>DB: UPDATE users (admission_status / enrollment_status / clearcheck_passed)
    Worker->>DB: INSERT notifications
    Worker->>Reverb: Broadcast PortalNotificationCreated<br/>Channel: private-users.{id}.notifications
    Worker->>DB: UPDATE event_logs (status=processed)

    Reverb-->>Browser: WebSocket push<br/>Event: notification.created<br/>{notification, unread_count}
    Browser->>Browser: Update bell badge<br/>Refresh notification list
```

---

## 4. Student Progressive Access Flow

```mermaid
flowchart TD
    START([Student Registers on DEORIS]) --> STEP1

    STEP1["🔓 Step 1: Admission Pending\nadmission_status = pending"]
    STEP1 --> ACCESS1["✅ Can access:\n📝 entryEase only"]
    ACCESS1 --> EXAM["Student takes entrance exam\n(entryEase → EventHub → DEORIS)"]

    EXAM --> DECISION1{Admission\nDecision}
    DECISION1 -->|Rejected| REJECTED["❌ admission_status = rejected\nNo module access"]
    DECISION1 -->|Approved| STEP2

    STEP2["🔓 Step 2: Admission Approved\nadmission_status = approved\nenrollment_status = not_enrolled"]
    STEP2 --> ACCESS2["✅ Can access:\n📋 EnrollEase"]
    ACCESS2 --> ENROLL["Student enrolls\n(EnrollEase → EventHub → DEORIS)"]

    ENROLL --> STEP3["🔓 Step 3: Enrolled\nenrollment_status = enrolled\nclearcheck_passed = false"]
    STEP3 --> ACCESS3["✅ Can access:\n📋 EnrollEase\n💰 asssesspay\n✅ ClearCheck"]
    ACCESS3 --> CLEARANCE["Student completes clearance\n(ClearCheck validates 4 modules)"]

    CLEARANCE --> DECISION2{All modules\ncleared?}
    DECISION2 -->|No| PARTIAL["⚠️ Partial clearance\nSame access as Step 3"]
    DECISION2 -->|Yes| STEP4

    STEP4["🔓 Step 4: Fully Cleared\nclearcheck_passed = true"]
    STEP4 --> ACCESS4["✅ Full student access:\n📋 EnrollEase\n📊 gradeTrack\n💰 asssesspay\n📚 LibrarySys\n📌 taskflow\n🏥 MediTrack\n✅ ClearCheck"]

    ELECTION{Election\nActive?}
    ACCESS4 --> ELECTION
    ELECTION -->|Yes| ACCESS5["+ 🗳️ VoteSys"]
    ELECTION -->|No| ACCESS4

    NOTE["⛔ carrerConnect:\nFaculty/Staff only — never student-accessible"]

    style REJECTED fill:#4a1a1a,color:#ffaaaa
    style STEP1 fill:#1a2a4a,color:#aaccff
    style STEP2 fill:#1a3a2a,color:#aaffcc
    style STEP3 fill:#2a3a1a,color:#ccffaa
    style STEP4 fill:#3a2a1a,color:#ffccaa
    style NOTE fill:#3a1a3a,color:#ffaaff
```

---

## 5. ClearCheck Multi-Module Validation Flow

```mermaid
flowchart LR
    STUDENT["👤 Student\nRequests Clearance"]

    subgraph CLEARCHECK["✅ ClearCheck — cleardb"]
        CR["clearance_records\n(status: validating)"]
        MV["module_validations\n(4 rows created)"]
    end

    subgraph VALIDATE["Parallel REST API Calls"]
        V1["📋 EnrollEase\nIs student enrolled?\nNo pending issues?"]
        V2["💰 asssesspay\nAll fees paid?\nNo outstanding balance?"]
        V3["📚 LibrarySys\nAll books returned?\nNo unpaid penalties?"]
        V4["📊 gradeTrack\nAll grades published?\nNo missing grades?"]
    end

    subgraph RESULT["Clearance Result"]
        ALL_CLEAR["✅ All 4 Cleared\nstatus = cleared"]
        PARTIAL["⚠️ Some Failed\nstatus = partially_cleared"]
    end

    subgraph DEORIS["🏛️ DEORIS Portal"]
        USER_UPDATE["users.clearcheck_passed = true"]
        NOTIF["Notification sent to student"]
    end

    STUDENT --> CR
    CR --> MV
    MV --> V1
    MV --> V2
    MV --> V3
    MV --> V4

    V1 -->|cleared| ALL_CLEAR
    V2 -->|cleared| ALL_CLEAR
    V3 -->|cleared| ALL_CLEAR
    V4 -->|cleared| ALL_CLEAR

    V1 -->|failed| PARTIAL
    V2 -->|failed| PARTIAL
    V3 -->|failed| PARTIAL
    V4 -->|failed| PARTIAL

    ALL_CLEAR -->|"EventHub: clearance.cleared"| USER_UPDATE
    USER_UPDATE --> NOTIF
```

---

## 6. Database Topology

```mermaid
graph LR
    subgraph MYSQL["MySQL Server — 127.0.0.1:3306"]
        DB0[("deoris_identity_db\n🏛️ DEORIS Portal")]
        DB2[("enrolldb\n📋 EnrollEase")]
        DB3[("gradetrack\n📊 gradeTrack")]
        DB4[("assespaydb\n💰 asssesspay")]
        DB5[("cleardb\n✅ ClearCheck")]
        DB6[("library\n📚 LibrarySys")]
        DB7[("meditrack_db\n🏥 MediTrack")]
        DB8[("votesys_db\n🗳️ VoteSys")]
        DB9[("deoris_taskflow\n📌 taskflow")]
        DB10[("careerconnect\n🎓 carrerConnect")]
    end

    subgraph SQLITE["SQLite (dev only)"]
        DB1[("database.sqlite\n📝 entryEase")]
    end

    subgraph REDIS["Redis — 127.0.0.1:6379"]
        R0[("DB 0\nQueues\nevents / notifications")]
        R1[("DB 1\nCache")]
        R2[("DB 2\nPub/Sub\ndeoris.events channel")]
    end

    DB0 <-.->|"app-level FK\n(no DB constraint)"| DB2
    DB0 <-.->|"app-level FK"| DB3
    DB0 <-.->|"app-level FK"| DB4
    DB0 <-.->|"app-level FK"| DB5
    DB0 <-.->|"app-level FK"| DB6
    DB0 <-.->|"app-level FK"| DB7
    DB0 <-.->|"app-level FK"| DB8
    DB0 <-.->|"app-level FK"| DB9
    DB0 <-.->|"app-level FK"| DB10
    DB0 <-.->|"app-level FK"| DB1

    DB5 -->|"REST API calls"| DB2
    DB5 -->|"REST API calls"| DB4
    DB5 -->|"REST API calls"| DB6
    DB5 -->|"REST API calls"| DB3

    DB4 -->|"REST API pull\n(ENROLLEASE_API_KEY)"| DB2

    style MYSQL fill:#1a1a2e,color:#e0e0ff,stroke:#4a4aff
    style SQLITE fill:#2e1a1a,color:#ffe0e0,stroke:#aa4a4a
    style REDIS fill:#2e2a1a,color:#fff0e0,stroke:#aaaa4a
```

---

## 7. Technology Stack

```mermaid
graph TD
    subgraph FRONTEND["Frontend Layer"]
        F1["Vanilla JS\n(no SPA framework)"]
        F2["Blade Templates\n(Laravel)"]
        F3["Custom CSS\n(no Tailwind/Bootstrap)"]
        F4["postMessage Bridge\n(SSO iframe comms)"]
        F5["Laravel Echo\n(WebSocket client)"]
    end

    subgraph BACKEND["Backend Layer"]
        B1["Laravel 12\n(all 11 apps)"]
        B2["Fortify + Jetstream\n(auth, 2FA, profile)"]
        B3["Sanctum\n(API tokens, SSO)"]
        B4["Laravel Reverb\n(WebSocket server)"]
        B5["Laravel Queues\n(Redis-backed)"]
        B6["Laravel HTTP Client\n(inter-service calls)"]
    end

    subgraph DATA["Data Layer"]
        D1["MySQL 8.x\n(10 module databases)"]
        D2["SQLite\n(entryEase dev)"]
        D3["Redis\n(queues, cache, pub/sub)"]
    end

    subgraph INFRA["Infrastructure (XAMPP/Windows)"]
        I1["Apache 2.4\n(HTTPS, virtual hosts)"]
        I2["PHP 8.x\n(all apps)"]
        I3["Wildcard SSL\n(*.deoris.test)"]
        I4["Redis Server\n(:6379)"]
    end

    FRONTEND --> BACKEND
    BACKEND --> DATA
    BACKEND --> INFRA
    DATA --> INFRA
```

---

## 8. Role → Module Access Matrix

```mermaid
graph LR
    subgraph ROLES["User Roles"]
        R_ADMIN["👑 admin"]
        R_STUDENT["🎓 student"]
        R_INSTRUCTOR["👨‍🏫 instructor"]
        R_CASHIER["💵 cashier"]
        R_LIBRARIAN["📚 librarian"]
        R_ADMISSION["📝 admission_officer"]
        R_NURSE["🏥 nurse"]
        R_ELECTION["🗳️ election_officer"]
        R_CANDIDATE["🏆 candidate"]
    end

    subgraph MODULES["Modules"]
        M_ENTRY["📝 entryEase"]
        M_ENROLL["📋 EnrollEase"]
        M_GRADE["📊 gradeTrack"]
        M_PAY["💰 asssesspay"]
        M_CLEAR["✅ ClearCheck"]
        M_LIB["📚 LibrarySys"]
        M_MEDI["🏥 MediTrack"]
        M_VOTE["🗳️ VoteSys"]
        M_TASK["📌 taskflow"]
        M_CAREER["🎓 carrerConnect"]
    end

    R_ADMIN --> M_ENTRY
    R_ADMIN --> M_ENROLL
    R_ADMIN --> M_GRADE
    R_ADMIN --> M_PAY
    R_ADMIN --> M_CLEAR
    R_ADMIN --> M_LIB
    R_ADMIN --> M_MEDI
    R_ADMIN --> M_VOTE
    R_ADMIN --> M_TASK
    R_ADMIN --> M_CAREER

    R_STUDENT -->|"progressive unlock"| M_ENTRY
    R_STUDENT -->|"after admission"| M_ENROLL
    R_STUDENT -->|"after clearcheck"| M_GRADE
    R_STUDENT -->|"after enrollment"| M_PAY
    R_STUDENT -->|"after enrollment"| M_CLEAR
    R_STUDENT -->|"after clearcheck"| M_LIB
    R_STUDENT -->|"after clearcheck"| M_MEDI
    R_STUDENT -->|"election active"| M_VOTE
    R_STUDENT -->|"after clearcheck"| M_TASK

    R_INSTRUCTOR --> M_GRADE
    R_INSTRUCTOR --> M_TASK
    R_INSTRUCTOR --> M_CAREER

    R_CASHIER --> M_PAY

    R_LIBRARIAN --> M_LIB

    R_ADMISSION --> M_ENTRY
    R_ADMISSION --> M_ENROLL
    R_ADMISSION --> M_CLEAR

    R_NURSE --> M_MEDI

    R_ELECTION --> M_VOTE
    R_ELECTION --> M_CLEAR

    R_CANDIDATE --> M_VOTE
```

---

## Summary Table

| Component | Technology | Purpose |
|---|---|---|
| **Portal Framework** | Laravel 12 | All 11 apps |
| **Authentication** | Fortify + Jetstream | Login, 2FA, profile |
| **API Tokens** | Sanctum | SSO single-use tokens |
| **SSO Mechanism** | postMessage + Sanctum | iframe identity handoff |
| **WebSockets** | Laravel Reverb | Real-time notifications |
| **Event Bus** | Redis Pub/Sub + HTTP | Cross-module events |
| **Queue** | Redis (DB:0) | Async event processing |
| **Cache** | Redis (DB:1) | Search results, sessions |
| **Main DB** | MySQL `deoris_identity_db` | Users, events, notifications |
| **Module DBs** | MySQL (9) + SQLite (1) | Per-module business data |
| **Web Server** | Apache 2.4 (XAMPP) | HTTPS virtual hosts |
| **Event Security** | HMAC-SHA256 | Signed event envelopes |
| **Identity Pattern** | Cross-DB user ID reference | No shared DB, app-level FK |
| **Clearance** | ClearCheck REST queries | 4-module validation |
