# ERD — ClearCheck (cleardb)

```mermaid
erDiagram
    students {
        bigint id PK
        bigint user_id "FK → DEORIS users.id (cross-DB, was local)"
        string reg_no UK
        enum grade_level "7|8|9|10|11|12"
        string section
        string program
        enum clearance_status "pending|in_progress|completed|rejected"
        int completed_steps
        int total_steps
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    clearance_checkers {
        bigint id PK
        bigint user_id "FK → DEORIS users.id (cross-DB)"
        string department
        int documents_reviewed
        int documents_approved
        int documents_rejected
        timestamp created_at
        timestamp updated_at
    }

    document_uploads {
        bigint id PK
        bigint student_id FK
        string document_type
        string file_path
        enum status "pending|approved|rejected"
        text rejection_reason
        bigint reviewed_by "DEORIS user id"
        timestamp reviewed_at
        timestamp created_at
        timestamp updated_at
    }

    clearance_records {
        bigint id PK
        bigint student_id FK
        enum status "pending|validating|partially_cleared|cleared|disputed|expired"
        tinyint progress_percentage
        tinyint modules_cleared
        tinyint modules_total
        timestamp last_validated_at
        timestamp cleared_at
        timestamp expires_at
        string correlation_id
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    module_validations {
        bigint id PK
        bigint clearance_record_id FK
        bigint student_id FK
        string module_key "enrollease|assesspay|librarysys|gradetrack"
        string module_name
        enum status "pending|cleared|failed|error|timeout"
        json response_payload
        text unresolved_issues
        timestamp validated_at
        smallint response_time_ms
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    validation_statuses {
        bigint id PK
        bigint clearance_record_id FK
        bigint student_id FK
        enum previous_status "pending|validating|partially_cleared|cleared|disputed|expired"
        enum new_status "pending|validating|partially_cleared|cleared|disputed|expired"
        string triggered_by
        text notes
        timestamp created_at
        timestamp updated_at
    }

    clearance_requests {
        bigint id PK
        bigint student_id FK
        bigint clearance_record_id FK
        enum type "initial|refresh|dispute"
        enum status "queued|processing|completed|failed"
        string requested_by
        text notes
        timestamp processed_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    clearance_analytics {
        bigint id PK
        string metric_key
        json metric_data
        date period_date
        timestamp created_at
        timestamp updated_at
    }

    validation_logs {
        bigint id PK
        bigint module_validation_id FK
        string log_level
        string message
        json context
        timestamp created_at
        timestamp updated_at
    }

    clearcheck_notifications {
        bigint id PK
        bigint user_id FK
        string type
        string title
        text body
        json data
        boolean is_read
        timestamp read_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    activity_logs {
        bigint id PK
        string action
        string subject_type
        bigint subject_id
        string actor_id
        json properties
        string ip_address
        timestamp created_at
        timestamp updated_at
    }

    event_outbox {
        bigint id PK
        string event_id UK
        string event_name
        string source_module
        json payload
        enum status "pending|published|failed|cancelled"
        int attempts
        text last_error
        timestamp published_at
        timestamp created_at
        timestamp updated_at
    }

    students ||--o{ document_uploads : "uploads"
    students ||--o{ clearance_records : "has"
    students ||--o{ clearance_requests : "submits"
    students ||--o{ module_validations : "validated in"
    students ||--o{ validation_statuses : "has"
    students ||--o{ clearcheck_notifications : "receives"
    clearance_records ||--o{ module_validations : "contains"
    clearance_records ||--o{ validation_statuses : "tracks"
    clearance_records ||--o{ clearance_requests : "linked to"
    module_validations ||--o{ validation_logs : "has"
```

## Database Info
| Property | Value |
|---|---|
| **Database Name** | `cleardb` |
| **Connection** | MySQL / 127.0.0.1:3306 |
| **App URL** | https://clearcheck.deoris.test |
| **Role** | Student Clearance Validation (multi-module) |

## Cross-DB Links
| Field | References |
|---|---|
| `students.user_id` | `deoris_identity_db.users.id` (migrated from local) |
| `module_validations.module_key = 'enrollease'` | Queries `enrolldb` via REST API |
| `module_validations.module_key = 'assesspay'` | Queries `assespaydb` via REST API |
| `module_validations.module_key = 'librarysys'` | Queries `library` via REST API |
| `module_validations.module_key = 'gradetrack'` | Queries `gradetrack` via REST API |
| `event_outbox` → DEORIS | `deoris_identity_db.event_logs` via HTTP POST |
| DEORIS `users.clearcheck_passed` | Updated via EventHub when clearance = 'cleared' |
