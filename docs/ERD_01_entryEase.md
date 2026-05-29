# ERD — entryEase (SQLite / entry_ease_db)

```mermaid
erDiagram
    applicants {
        bigint id PK
        bigint deoris_user_id "FK → DEORIS users.id (cross-DB)"
        string grade_level
        enum status "Pending|Under Review|Approved|Rejected"
        text additional_info
        text admin_notes
        bigint reviewed_by "DEORIS user id"
        string documents "JSON paths"
        bigint exam_schedule_id FK
        enum admission_status "pending|approved|rejected"
        string seat_assignment
        string portal_student_number
        string portal_name
        string portal_email
        timestamp created_at
        timestamp updated_at
    }

    exam_schedules {
        bigint id PK
        string title
        date exam_date
        time start_time
        time end_time
        string venue
        string batch
        int slots
        text instructions
        enum exam_type "written|online"
        enum status "upcoming|ongoing|completed|cancelled"
        timestamp created_at
        timestamp updated_at
    }

    exam_scores {
        bigint id PK
        bigint applicant_id FK
        bigint exam_schedule_id FK
        decimal score
        decimal total_items
        text remarks
        string recorded_by
        timestamp recorded_at
        timestamp created_at
        timestamp updated_at
    }

    exam_questions {
        bigint id PK
        bigint exam_schedule_id FK
        text question_text
        json choices
        string correct_answer
        int points
        int sort_order
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    exam_attempts {
        bigint id PK
        bigint applicant_id FK
        bigint exam_schedule_id FK
        enum status "in_progress|submitted"
        decimal score
        decimal total_items
        timestamp started_at
        timestamp submitted_at
        timestamp created_at
        timestamp updated_at
    }

    exam_attempt_answers {
        bigint id PK
        bigint exam_attempt_id FK
        bigint exam_question_id FK
        string selected_answer
        boolean is_correct
        timestamp created_at
        timestamp updated_at
    }

    activity_logs {
        bigint id PK
        string action
        string subject_type
        bigint subject_id
        bigint causer_id
        json properties
        timestamp created_at
        timestamp updated_at
    }

    sso_tokens {
        bigint id PK
        bigint user_id
        string token UK
        json payload
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    deoris_event_outbox {
        bigint id PK
        string event_id UK
        string event_name
        string source_module
        string correlation_id
        json payload
        string schema_version
        enum status "pending|published|failed"
        int attempts
        text last_error
        timestamp published_at
        timestamp created_at
        timestamp updated_at
    }

    applicants ||--o{ exam_scores : "has"
    applicants ||--o{ exam_attempts : "takes"
    exam_schedules ||--o{ exam_scores : "has"
    exam_schedules ||--o{ exam_questions : "contains"
    exam_schedules ||--o{ exam_attempts : "has"
    exam_attempts ||--o{ exam_attempt_answers : "contains"
    exam_questions ||--o{ exam_attempt_answers : "answered in"
    applicants }o--|| exam_schedules : "assigned to"
```

## Database Info
| Property | Value |
|---|---|
| **Database Name** | `SQLite` (local file, dev) / `entry_ease_db` (MySQL commented) |
| **Connection** | SQLite (default) |
| **App URL** | https://entryease.deoris.test |
| **Role** | Admission & Entrance Exam Management |

## Cross-DB Links
| Field | References |
|---|---|
| `applicants.deoris_user_id` | `deoris_identity_db.users.id` (application-level, no FK) |
| `applicants.reviewed_by` | `deoris_identity_db.users.id` (admission officer) |
| EventHub outbox → | `deoris_identity_db.event_logs` via HTTP POST |
