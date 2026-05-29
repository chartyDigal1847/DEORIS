# ERD — MediTrack (meditrack_db)

```mermaid
erDiagram
    students {
        bigint id PK
        string external_id UK "DEORIS users.id (cross-DB)"
        string student_number UK
        string first_name
        string last_name
        string email
        string grade_level
        string section
        date birthdate
        string guardian_name
        string guardian_contact
        string emergency_contact
        json medical_flags
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    nurses {
        bigint id PK
        string external_id UK "DEORIS users.id (cross-DB)"
        string name
        string email
        string license_number
        string status
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    clinic_visits {
        bigint id PK
        bigint student_id FK
        bigint nurse_id FK
        string visit_code UK
        string chief_complaint
        string visit_type "walk_in|referral|emergency"
        string status "pending_checkup|in_progress|completed|emergency"
        string severity "low|moderate|high|critical"
        decimal temperature
        string blood_pressure
        smallint pulse_rate
        smallint respiratory_rate
        decimal weight_kg
        text notes
        timestamp checked_in_at
        timestamp checked_out_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    diagnoses {
        bigint id PK
        bigint clinic_visit_id FK
        bigint student_id FK
        bigint nurse_id FK
        string code
        string title
        text description
        text treatment_plan
        string status
        timestamp diagnosed_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    medical_records {
        bigint id PK
        bigint student_id FK
        bigint nurse_id FK
        string record_type
        string title
        text summary
        text sensitive_notes
        json attachments
        string status
        timestamp approved_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    prescriptions {
        bigint id PK
        bigint clinic_visit_id FK
        bigint student_id FK
        bigint nurse_id FK
        string medication_name
        string dosage
        string frequency
        string duration
        text instructions
        string status
        timestamp issued_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    health_reports {
        bigint id PK
        bigint student_id FK
        bigint nurse_id FK
        string report_type
        string title
        date period_start
        date period_end
        longtext summary
        json metrics
        string status
        timestamp generated_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    emergency_alerts {
        bigint id PK
        bigint student_id FK
        bigint clinic_visit_id FK
        bigint nurse_id FK
        string alert_code UK
        string severity
        string title
        text message
        string status
        timestamp issued_at
        timestamp resolved_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    student_concerns {
        bigint id PK
        bigint student_id FK
        string external_student_id "DEORIS user id"
        string title
        text description
        string severity
        string status
        timestamp submitted_at
        timestamp reviewed_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    medical_audit_logs {
        bigint id PK
        string actor_external_id "DEORIS user id"
        string actor_name
        string actor_role
        string action
        string auditable_type
        bigint auditable_id
        string ip_address
        string user_agent
        json before
        json after
        uuid correlation_id
        timestamp created_at
        timestamp updated_at
    }

    notifications {
        bigint id PK
        string recipient_external_id "DEORIS user id"
        string recipient_role
        string type
        string title
        text message
        json data
        timestamp read_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    event_outbox {
        bigint id PK
        uuid event_id UK
        string event_name
        string source_service
        json payload
        string signature
        string nonce UK
        string schema_version
        uuid correlation_id
        string status
        timestamp published_at
        int attempts
        text last_error
        timestamp created_at
        timestamp updated_at
    }

    deoris_event_inbox {
        bigint id PK
        uuid event_id UK
        string event_name
        string source_module
        json payload
        string signature
        string nonce
        bigint timestamp
        uuid correlation_id
        string status
        timestamp processed_at
        text error_message
        timestamp created_at
        timestamp updated_at
    }

    students ||--o{ clinic_visits : "has"
    students ||--o{ diagnoses : "has"
    students ||--o{ medical_records : "has"
    students ||--o{ prescriptions : "has"
    students ||--o{ health_reports : "has"
    students ||--o{ emergency_alerts : "has"
    students ||--o{ student_concerns : "submits"
    nurses ||--o{ clinic_visits : "handles"
    nurses ||--o{ diagnoses : "makes"
    nurses ||--o{ medical_records : "creates"
    nurses ||--o{ prescriptions : "issues"
    nurses ||--o{ health_reports : "generates"
    nurses ||--o{ emergency_alerts : "issues"
    clinic_visits ||--o{ diagnoses : "results in"
    clinic_visits ||--o{ prescriptions : "generates"
    clinic_visits ||--o{ emergency_alerts : "triggers"
```

## Database Info
| Property | Value |
|---|---|
| **Database Name** | `meditrack_db` |
| **Connection** | MySQL / 127.0.0.1:3306 |
| **App URL** | https://meditrack.deoris.test |
| **Role** | School Clinic & Health Records |

## Cross-DB Links
| Field | References |
|---|---|
| `students.external_id` | `deoris_identity_db.users.id` (SSO identity) |
| `nurses.external_id` | `deoris_identity_db.users.id` (SSO identity) |
| `deoris_event_inbox` | Receives events from `deoris_identity_db.event_logs` |
| `event_outbox` → DEORIS | `deoris_identity_db.event_logs` via HTTP POST |

## Views & Procedures
| Object | Type | Purpose |
|---|---|---|
| `clinic_visit_analytics` | VIEW | Daily visit stats by status/severity |
| `diagnosis_trends` | VIEW | Monthly diagnosis frequency |
| `sp_student_health_statistics` | PROCEDURE | Per-student health summary |
| `trg_clinic_visit_emergency` | TRIGGER | Auto-create emergency alert on critical visit |
