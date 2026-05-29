# ERD — taskflow (deoris_taskflow)

```mermaid
erDiagram
    assignments {
        bigint id PK
        string title
        string subject
        string grade
        string type "written|project|performance|quiz"
        string priority "low|medium|high"
        string quarter "Q1|Q2|Q3|Q4"
        date due_date
        int points
        text description
        string status "pending|active|closed"
        bigint created_by "DEORIS portal user id"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    submissions {
        bigint id PK
        bigint assignment_id FK
        bigint portal_user_id "FK → DEORIS users.id (cross-DB)"
        string file_name
        text comment
        int score
        text feedback
        string status "submitted|graded|returned|late"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    activity_logs {
        bigint id PK
        string action
        string subject_type
        bigint subject_id
        bigint causer_id "DEORIS user id"
        json properties
        string ip_address
        timestamp created_at
        timestamp updated_at
    }

    event_outbox {
        bigint id PK
        string event_id UK
        string event_name
        string source_service
        string schema_version
        string correlation_id
        json payload
        string hmac_signature
        string nonce
        enum status "pending|sent|failed"
        tinyint attempts
        timestamp sent_at
        text last_error
        timestamp created_at
        timestamp updated_at
    }

    assignments ||--o{ submissions : "has"
```

## Database Info
| Property | Value |
|---|---|
| **Database Name** | `deoris_taskflow` |
| **Connection** | MySQL / 127.0.0.1:3306 |
| **App URL** | https://taskflow.deoris.test |
| **Role** | Assignment & Submission Management |

## Cross-DB Links
| Field | References |
|---|---|
| `submissions.portal_user_id` | `deoris_identity_db.users.id` (student submitting) |
| `assignments.created_by` | `deoris_identity_db.users.id` (instructor creating) |
| `event_outbox` → DEORIS | `deoris_identity_db.event_logs` via HTTP POST |

## Notes
- No local users table — fully relies on DEORIS SSO for identity
- `portal_user_id` is the DEORIS user ID stored directly on submissions
- Soft deletes on both `assignments` and `submissions`
