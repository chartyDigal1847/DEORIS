# ERD — EnrollEase (enrolldb)

```mermaid
erDiagram
    rooms {
        bigint id PK
        string name
        tinyint grade_level
        string section
        string adviser
        smallint capacity_male
        smallint capacity_female
        timestamp created_at
        timestamp updated_at
    }

    academic_terms {
        bigint id PK
        string name
        string school_year
        enum semester "1st|2nd|summer"
        date enrollment_start
        date enrollment_end
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    students {
        bigint id PK
        string student_name
        string first_name
        string last_name
        string middle_name
        date date_of_birth
        enum gender "male|female"
        string nationality
        string email UK
        string contact_number
        string address
        string lrn UK
        string previous_school
        tinyint last_grade_completed
        decimal average_grade
        string student_name_alias
        timestamp created_at
        timestamp updated_at
    }

    enrollments {
        bigint id PK
        bigint student_id FK
        bigint academic_term_id FK
        string student_name
        string first_name
        string last_name
        string middle_name
        date date_of_birth
        enum gender "male|female"
        string nationality
        string email
        string contact_number
        string address
        tinyint grade_level
        string school_year
        string previous_school
        tinyint last_grade_completed
        decimal average_grade
        string lrn
        string guardian_name
        string guardian_relationship
        string guardian_contact
        string guardian_email
        string guardian_occupation
        string psa_path
        string photo_path
        string report_card_path
        bigint room_id FK
        enum status "pending|reviewing|approved|rejected|enrolled|cancelled"
        text remarks
        timestamp created_at
        timestamp updated_at
    }

    guardians {
        bigint id PK
        bigint student_id FK
        string name
        string relationship
        string contact_number
        string email
        string occupation
        timestamp created_at
        timestamp updated_at
    }

    student_documents {
        bigint id PK
        bigint student_id FK
        enum document_type "psa|photo|report_card|other"
        string file_path
        string file_name
        timestamp created_at
        timestamp updated_at
    }

    enrollment_status_logs {
        bigint id PK
        bigint enrollment_id FK
        string from_status
        string to_status
        string changed_by_role
        string changed_by_id
        string changed_by_name
        text remarks
        string ip_address
        timestamp created_at
        timestamp updated_at
    }

    activity_logs {
        bigint id PK
        string action
        string subject_type
        bigint subject_id
        string actor_id
        string actor_name
        string actor_role
        json context
        string ip_address
        string user_agent
        timestamp created_at
        timestamp updated_at
    }

    event_outbox {
        bigint id PK
        string event_id UK
        string event_name
        string source_module
        string correlation_id
        json payload
        string schema_version
        enum status "pending|published|failed|cancelled"
        tinyint attempts
        text last_error
        timestamp published_at
        timestamp created_at
        timestamp updated_at
    }

    notifications {
        uuid id PK
        string recipient_id
        string recipient_role
        string type
        string title
        text body
        json data
        string action_url
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }

    sso_tokens {
        bigint id PK
        string token UK
        json payload
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    rooms ||--o{ enrollments : "assigned to"
    academic_terms ||--o{ enrollments : "belongs to"
    students ||--o{ enrollments : "has"
    students ||--o{ guardians : "has"
    students ||--o{ student_documents : "has"
    enrollments ||--o{ enrollment_status_logs : "tracks"
```

## Database Info
| Property | Value |
|---|---|
| **Database Name** | `enrolldb` |
| **Connection** | MySQL / 127.0.0.1:3306 |
| **App URL** | https://enrollease.deoris.test |
| **Role** | Student Enrollment Management |

## Cross-DB Links
| Field | References |
|---|---|
| `enrollments.student_id` | `enrolldb.students.id` (local) |
| `event_outbox` → DEORIS | `deoris_identity_db.event_logs` via HTTP POST |
| DEORIS `users.enrollease_enrollment_id` | `enrolldb.enrollments.id` (application-level) |
| asssesspay pulls enrollment data | via REST API with API key |
| gradeTrack syncs enrollment | via ENROLLEASE_API_KEY webhook |

## Views & Procedures
| Object | Type | Purpose |
|---|---|---|
| `v_enrollment_stats` | VIEW | Enrollment counts by school year & grade |
| `v_section_capacity` | VIEW | Room capacity vs enrolled counts |
| `sp_enrollment_summary` | PROCEDURE | Summary by school year |
| `trg_enrollment_status_audit` | TRIGGER | Auto-log status changes |
