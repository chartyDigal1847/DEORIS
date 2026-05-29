# ERD — DEORIS Main Portal (deoris_identity_db)

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        string student_number UK
        timestamp email_verified_at
        string password
        text two_factor_secret
        text two_factor_recovery_codes
        timestamp two_factor_confirmed_at
        enum role "admin|student|instructor|cashier|librarian|admission_officer|nurse|election_officer|candidate"
        enum admission_status "pending|approved|rejected|under_review"
        enum enrollment_status "not_enrolled|enrolled"
        boolean clearcheck_passed
        bigint enrollease_enrollment_id
        timestamp enrollment_status_synced_at
        string previous_enrollment_status
        bigint current_team_id
        string profile_photo_path
        timestamp created_at
        timestamp updated_at
    }

    password_reset_tokens {
        string email PK
        string token
        timestamp created_at
    }

    sessions {
        string id PK
        bigint user_id FK
        string ip_address
        text user_agent
        longtext payload
        int last_activity
    }

    passkeys {
        bigint id PK
        bigint user_id FK
        string credential_id
        text public_key
        bigint sign_count
        timestamp created_at
        timestamp updated_at
    }

    personal_access_tokens {
        bigint id PK
        string tokenable_type
        bigint tokenable_id
        text name
        string token UK
        text abilities
        timestamp last_used_at
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    notifications {
        uuid id PK
        string type
        string notifiable_type
        bigint notifiable_id
        json data
        string source_module
        string event_name
        string title
        text body
        string action_url
        timestamp read_at
        timestamp broadcast_at
        string correlation_id
        timestamp created_at
        timestamp updated_at
    }

    event_logs {
        bigint id PK
        string event_id UK
        string event_name
        string source_module
        string correlation_id
        json payload
        string status
        text error
        timestamp received_at
        timestamp processed_at
        timestamp created_at
        timestamp updated_at
    }

    service_registry {
        bigint id PK
        string service_key UK
        string label
        string url
        string api_version
        enum status "active|inactive|degraded|maintenance"
        json allowed_roles
        json environment_config
        string health_check_url
        timestamp last_health_check_at
        boolean health_ok
        timestamp created_at
        timestamp updated_at
    }

    users ||--o{ sessions : "has"
    users ||--o{ passkeys : "has"
    users ||--o{ notifications : "receives (polymorphic)"
    users ||--o{ personal_access_tokens : "owns (polymorphic)"
```

## Database Info
| Property | Value |
|---|---|
| **Database Name** | `deoris_identity_db` |
| **Connection** | MySQL / 127.0.0.1:3306 |
| **App URL** | https://deoris.test |
| **Role** | Central Identity & Event Hub |

## Key Relationships to Modules
| Module | Link Field | Direction |
|---|---|---|
| entryEase | `users.id` ↔ `applicants.deoris_user_id` | DEORIS → entryEase |
| EnrollEase | `users.enrollease_enrollment_id` ↔ `enrollments.id` | EnrollEase → DEORIS |
| gradeTrack | `users.id` ↔ `students.portal_user_id` | DEORIS → gradeTrack |
| asssesspay | `users.id` ↔ `students.portal_user_id` | DEORIS → asssesspay |
| LibrarySys | `users.id` ↔ `visits/transactions.deoris_user_id` | DEORIS → LibrarySys |
| taskflow | `users.id` ↔ `submissions.portal_user_id` | DEORIS → taskflow |
| VoteSys | `users.id` ↔ `votes.voter_external_id` | DEORIS → VoteSys |
| MediTrack | `users.id` ↔ `students.external_id` | DEORIS → MediTrack |
| ClearCheck | `users.id` ↔ `students.user_id` | DEORIS → ClearCheck |
| carrerConnect | `users.id` ↔ `faculty_users.sso_id` | DEORIS → carrerConnect |
| All Modules | EventHub HTTP POST `/api/events/ingest` | Bidirectional |
