# ERD — VoteSys (votesys_db)

```mermaid
erDiagram
    elections {
        bigint id PK
        string name
        string status "draft|open|voting|closed|results_released"
        text description
        string created_by_external_id "DEORIS user id"
        boolean is_active
        timestamp starts_at
        timestamp ends_at
        timestamp voting_starts_at
        timestamp voting_ends_at
        timestamp results_released_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    positions {
        bigint id PK
        bigint election_id FK
        string name
        tinyint max_selections
        timestamp created_at
        timestamp updated_at
    }

    candidates {
        bigint id PK
        bigint position_id FK
        string status "pending|approved|rejected"
        string applicant_external_id "DEORIS user id"
        string approved_by_external_id "DEORIS user id"
        timestamp approved_at
        text rejection_reason
        string name
        string party
        string course
        text bio
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    candidate_profiles {
        bigint id PK
        bigint candidate_id FK
        string tagline
        text platform
        json campaign_links
        timestamp created_at
        timestamp updated_at
    }

    candidate_requirements {
        bigint id PK
        bigint election_id FK
        string title
        text description
        boolean is_required
        timestamp created_at
        timestamp updated_at
    }

    student_voters {
        bigint id PK
        string external_id UK "DEORIS users.id (cross-DB)"
        string email
        string name
        string course
        boolean is_eligible
        timestamp created_at
        timestamp updated_at
    }

    votes {
        bigint id PK
        bigint election_id FK
        bigint position_id FK
        bigint candidate_id FK
        string student_id "DEORIS user id"
        string voter_external_id "DEORIS user id"
        string vote_hash
        boolean is_locked
        timestamp created_at
        timestamp updated_at
    }

    vote_logs {
        bigint id PK
        bigint election_id FK
        bigint vote_id FK
        string voter_external_id "DEORIS user id"
        bigint position_id FK
        string action
        string ip_address
        string user_agent
        json metadata
        timestamp logged_at
        timestamp created_at
        timestamp updated_at
    }

    election_results {
        bigint id PK
        bigint election_id FK
        bigint position_id FK
        bigint candidate_id FK
        int vote_count
        decimal vote_percentage
        smallint rank
        timestamp computed_at
        timestamp created_at
        timestamp updated_at
    }

    election_status_history {
        bigint id PK
        bigint election_id FK
        string from_status
        string to_status
        string changed_by_external_id "DEORIS user id"
        text notes
        timestamp created_at
        timestamp updated_at
    }

    election_officers {
        bigint id PK
        bigint election_id FK
        string external_id "DEORIS user id"
        string email
        string name
        timestamp created_at
        timestamp updated_at
    }

    activity_logs {
        bigint id PK
        string action
        bigint election_id FK
        string actor_external_id "DEORIS user id"
        string subject_type
        bigint subject_id
        string description
        timestamp created_at
        timestamp updated_at
    }

    notifications {
        bigint id PK
        string recipient_external_id "DEORIS user id"
        string type
        string title
        text body
        json data
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }

    event_outbox {
        bigint id PK
        uuid event_id UK
        string event_name
        string source_service
        string schema_version
        uuid correlation_id
        json payload
        string signature
        string nonce
        timestamp occurred_at
        string status
        tinyint attempts
        timestamp published_at
        timestamp created_at
        timestamp updated_at
    }

    elections ||--o{ positions : "has"
    elections ||--o{ votes : "contains"
    elections ||--o{ vote_logs : "logs"
    elections ||--o{ election_results : "has"
    elections ||--o{ election_status_history : "tracks"
    elections ||--o{ election_officers : "managed by"
    elections ||--o{ candidate_requirements : "requires"
    elections ||--o{ activity_logs : "logged in"
    positions ||--o{ candidates : "has"
    positions ||--o{ votes : "voted in"
    positions ||--o{ vote_logs : "logged in"
    positions ||--o{ election_results : "has"
    candidates ||--o{ votes : "receives"
    candidates ||--o{ election_results : "has"
    candidates ||--|| candidate_profiles : "has"
```

## Database Info
| Property | Value |
|---|---|
| **Database Name** | `votesys_db` |
| **Connection** | MySQL / 127.0.0.1:3306 |
| **App URL** | https://votesys.deoris.test |
| **Role** | Student Election & Voting System |

## Cross-DB Links
| Field | References |
|---|---|
| `student_voters.external_id` | `deoris_identity_db.users.id` (SSO identity) |
| `votes.voter_external_id` | `deoris_identity_db.users.id` |
| `candidates.applicant_external_id` | `deoris_identity_db.users.id` |
| `election_officers.external_id` | `deoris_identity_db.users.id` |
| `event_outbox` → DEORIS | `deoris_identity_db.event_logs` via HTTP POST |
| DEORIS `users.election_active` | Synced via EventHub when election opens/closes |
