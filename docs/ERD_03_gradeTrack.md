# ERD — gradeTrack (gradetrack)

```mermaid
erDiagram
    school_years {
        bigint id PK
        string name
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    semesters {
        bigint id PK
        bigint school_year_id FK
        string name
        date start_date
        date end_date
        boolean is_active
        boolean grading_open
        date grading_deadline
        timestamp created_at
        timestamp updated_at
    }

    curricula {
        bigint id PK
        string name
        string code UK
        text description
        int total_units
        timestamp created_at
        timestamp updated_at
    }

    courses {
        bigint id PK
        bigint curriculum_id FK
        string code UK
        string name
        text description
        int units
        string instructor
        timestamp created_at
        timestamp updated_at
    }

    students {
        bigint id PK
        bigint deoris_user_id "FK → DEORIS users.id (cross-DB)"
        bigint portal_user_id "FK → DEORIS users.id (cross-DB)"
        string student_id UK
        string first_name
        string last_name
        string email UK
        string contact
        bigint curriculum_id FK
        enum status "active|inactive|graduated|dropped"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    instructors {
        bigint id PK
        bigint portal_user_id "FK → DEORIS users.id (cross-DB)"
        string employee_id UK
        string first_name
        string last_name
        string email UK
        string department
        string specialization
        enum status "active|inactive|on_leave"
        date hire_date
        text qualifications
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    class_sections {
        bigint id PK
        string section_code UK
        bigint course_id FK
        bigint semester_id FK
        bigint instructor_id FK
        string schedule
        string room
        int max_capacity
        int current_enrollment
        enum status "open|closed|cancelled"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    course_assignments {
        bigint id PK
        bigint course_id FK
        bigint semester_id FK
        bigint instructor_user_id "DEORIS portal user ID"
        string instructor_name
        string instructor_email
        timestamp created_at
        timestamp updated_at
    }

    enrollments {
        bigint id PK
        bigint student_id FK
        bigint course_id FK
        bigint semester_id FK
        bigint section_id FK
        date enrollment_date
        enum status "enrolled|dropped|completed|withdrawn"
        timestamp created_at
        timestamp updated_at
    }

    grades {
        bigint id PK
        bigint enrollment_id FK
        decimal midterm
        decimal finals
        decimal final_grade
        string remarks
        enum status "draft|submitted|published|reopened"
        enum special_status "none|INC|DRP|WDN|NG|EXC"
        decimal gpa_equivalent
        string letter_grade
        bigint submitted_by
        timestamp submitted_at
        bigint published_by
        timestamp published_at
        text reopen_reason
        bigint reopened_by
        timestamp reopened_at
        boolean gpa_bearing
        timestamp created_at
        timestamp updated_at
    }

    grade_components {
        bigint id PK
        bigint course_id FK
        bigint semester_id FK
        string name
        string type
        decimal weight
        int sort_order
        timestamp created_at
        timestamp updated_at
    }

    component_scores {
        bigint id PK
        bigint enrollment_id FK
        bigint component_id FK
        decimal score
        decimal weighted
        timestamp created_at
        timestamp updated_at
    }

    gpa_records {
        bigint id PK
        bigint student_id FK
        bigint semester_id FK
        decimal semester_gpa
        decimal cumulative_gpa
        int units_earned
        int units_enrolled
        enum academic_standing "deans_list|good_standing|satisfactory|probation|dismissed|irregular"
        timestamp computed_at
        timestamp created_at
        timestamp updated_at
    }

    academic_records {
        bigint id PK
        bigint student_id FK
        bigint semester_id FK
        int total_units_enrolled
        int total_units_earned
        int total_units_failed
        decimal semester_gpa
        decimal cumulative_gpa
        enum academic_standing "deans_list|good_standing|satisfactory|probation|dismissed|irregular"
        int subjects_completed
        int subjects_failed
        text remarks
        boolean clearance_eligible
        timestamp computed_at
        timestamp created_at
        timestamp updated_at
    }

    clearance_status_cache {
        bigint id PK
        bigint student_id FK
        bigint semester_id FK
        boolean has_missing_grades
        boolean all_requirements_complete
        boolean cleared_for_release
        int pending_grades_count
        json missing_subjects
        timestamp last_checked_at
        timestamp cached_until
        timestamp created_at
        timestamp updated_at
    }

    attendance {
        bigint id PK
        bigint enrollment_id FK
        date date
        enum status "present|absent|late|excused"
        timestamp created_at
        timestamp updated_at
    }

    grade_audit_logs {
        bigint id PK
        string action
        string entity_type
        bigint entity_id
        bigint actor_user_id
        string actor_name
        string actor_role
        json previous_value
        json new_value
        string reason
        string ip_address
        string user_agent
        timestamp logged_at
    }

    announcements {
        bigint id PK
        string title
        text body
        string posted_by
        timestamp created_at
        timestamp updated_at
    }

    school_years ||--o{ semesters : "has"
    semesters ||--o{ enrollments : "has"
    semesters ||--o{ course_assignments : "has"
    semesters ||--o{ grade_components : "has"
    semesters ||--o{ gpa_records : "has"
    semesters ||--o{ academic_records : "has"
    semesters ||--o{ clearance_status_cache : "has"
    curricula ||--o{ courses : "contains"
    curricula ||--o{ students : "assigned to"
    courses ||--o{ enrollments : "enrolled in"
    courses ||--o{ class_sections : "has"
    courses ||--o{ course_assignments : "assigned"
    courses ||--o{ grade_components : "has"
    instructors ||--o{ class_sections : "teaches"
    class_sections ||--o{ enrollments : "contains"
    students ||--o{ enrollments : "has"
    students ||--o{ gpa_records : "has"
    students ||--o{ academic_records : "has"
    students ||--o{ clearance_status_cache : "has"
    enrollments ||--o{ grades : "has"
    enrollments ||--o{ component_scores : "has"
    enrollments ||--o{ attendance : "has"
    grade_components ||--o{ component_scores : "scored in"
```

## Database Info
| Property | Value |
|---|---|
| **Database Name** | `gradetrack` |
| **Connection** | MySQL / 127.0.0.1:3306 |
| **App URL** | https://gradetrack.deoris.test |
| **Role** | Academic Grading & Records |

## Cross-DB Links
| Field | References |
|---|---|
| `students.deoris_user_id` / `portal_user_id` | `deoris_identity_db.users.id` |
| `instructors.portal_user_id` | `deoris_identity_db.users.id` |
| `course_assignments.instructor_user_id` | `deoris_identity_db.users.id` |
| EnrollEase sync | via ENROLLEASE_API_KEY (pull-based) |
| ClearCheck queries | `clearance_status_cache` via API |

## Views & Procedures
| Object | Type | Purpose |
|---|---|---|
| `v_student_grade_summary` | VIEW | Per-student grade overview |
| `v_course_statistics` | VIEW | Course-level grade stats |
| `sp_calculate_gpa` | PROCEDURE | GPA computation |
| `trg_grade_audit` | TRIGGER | Immutable grade change log |
