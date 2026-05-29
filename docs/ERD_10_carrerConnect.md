# ERD — carrerConnect / CareerConnect (careerconnect)

```mermaid
erDiagram
    faculty_users {
        bigint id PK
        string sso_id UK "DEORIS users.id (cross-DB)"
        string email UK
        string name
        string role "instructor|cashier|librarian|admission_officer|admin"
        string department
        json permissions
        string profile_picture
        boolean is_active
        text bio
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    departments {
        bigint id PK
        string name UK
        string code UK
        text description
        bigint head_id FK
        string contact_email
        string phone
        string location
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    announcements {
        bigint id PK
        string title
        longtext content
        bigint author_id FK
        bigint department_id FK
        enum priority "low|normal|high|urgent"
        enum visibility "all|department|role"
        json target_roles
        datetime published_at
        datetime expires_at
        int views_count
        boolean is_pinned
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    communication_boards {
        bigint id PK
        string name
        text description
        bigint creator_id FK
        bigint department_id FK
        enum visibility "all|department|role"
        json allowed_roles
        boolean is_moderated
        boolean is_active
        int posts_count
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    board_posts {
        bigint id PK
        bigint board_id FK
        bigint author_id FK
        string title
        longtext content
        boolean is_pinned
        boolean is_moderated
        enum status "pending|approved|rejected"
        int comments_count
        int views_count
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    board_comments {
        bigint id PK
        bigint post_id FK
        bigint author_id FK
        longtext content
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    resource_categories {
        bigint id PK
        string name UK
        string slug UK
        text description
        string icon
        int resources_count
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    career_resources {
        bigint id PK
        string title
        longtext description
        bigint category_id FK
        bigint author_id FK
        string resource_type "pdf|link|video|document|guide"
        string file_path
        string external_url
        string thumbnail
        int downloads_count
        int views_count
        boolean is_featured
        boolean is_approved
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    message_threads {
        bigint id PK
        string subject
        bigint creator_id FK
        json participants "array of faculty_user IDs"
        int messages_count
        datetime last_message_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    messages {
        bigint id PK
        bigint thread_id FK
        bigint sender_id FK
        longtext content
        json read_by
        timestamp created_at
        timestamp updated_at
    }

    notifications {
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
    }

    activity_logs {
        bigint id PK
        string action
        string subject_type
        bigint subject_id
        bigint causer_id FK
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
        enum status "pending|published|failed"
        int attempts
        text last_error
        timestamp published_at
        timestamp created_at
        timestamp updated_at
    }

    access_attempts {
        bigint id PK
        string ip_address
        string user_agent
        string attempted_route
        int attempt_count
        timestamp last_attempt_at
        timestamp created_at
        timestamp updated_at
    }

    departments }o--|| faculty_users : "headed by"
    announcements }o--|| faculty_users : "authored by"
    announcements }o--|| departments : "belongs to"
    communication_boards }o--|| faculty_users : "created by"
    communication_boards }o--|| departments : "belongs to"
    board_posts }o--|| communication_boards : "posted in"
    board_posts }o--|| faculty_users : "authored by"
    board_comments }o--|| board_posts : "on"
    board_comments }o--|| faculty_users : "authored by"
    career_resources }o--|| resource_categories : "categorized in"
    career_resources }o--|| faculty_users : "authored by"
    message_threads }o--|| faculty_users : "created by"
    messages }o--|| message_threads : "in"
    messages }o--|| faculty_users : "sent by"
    notifications }o--|| faculty_users : "sent to"
```

## Database Info
| Property | Value |
|---|---|
| **Database Name** | `careerconnect` |
| **Connection** | MySQL / 127.0.0.1:3306 |
| **App URL** | https://careerconnect.deoris.test |
| **Role** | Faculty Communication & Career Resources |

## Cross-DB Links
| Field | References |
|---|---|
| `faculty_users.sso_id` | `deoris_identity_db.users.id` (SSO identity) |
| `event_outbox` → DEORIS | `deoris_identity_db.event_logs` via HTTP POST |

## Notes
- `faculty_users` is a local mirror of DEORIS users with role = instructor/admin/etc.
- Synced via SSO token validation on login
- No student-facing tables — this module is faculty/staff only
