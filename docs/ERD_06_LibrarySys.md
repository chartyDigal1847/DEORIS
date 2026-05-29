# ERD — LibrarySys (library)

```mermaid
erDiagram
    books {
        bigint id PK
        string title
        string author
        string isbn UK
        string category
        string publisher
        year year_published
        text description
        int quantity
        int available_copies
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    visits {
        bigint id PK
        bigint deoris_user_id "FK → DEORIS users.id (cross-DB)"
        string user_name "denormalized from DEORIS"
        string user_email "denormalized from DEORIS"
        date visit_date
        time time_in
        time time_out
        string purpose
        timestamp created_at
        timestamp updated_at
    }

    transactions {
        bigint id PK
        bigint deoris_user_id "FK → DEORIS users.id (cross-DB)"
        string user_name "denormalized from DEORIS"
        string user_email "denormalized from DEORIS"
        bigint book_id FK
        date borrow_date
        date due_date
        date return_date
        string status "borrowed|returned|overdue|lost|renewed"
        int renewal_count
        decimal penalty_amount
        text notes
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    penalties {
        bigint id PK
        bigint transaction_id FK
        bigint deoris_user_id "FK → DEORIS users.id (cross-DB)"
        string user_name
        string user_email
        decimal amount
        string reason
        enum status "unpaid|paid|waived"
        date issued_date
        date paid_date
        text notes
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    inventory_logs {
        bigint id PK
        bigint book_id FK
        bigint deoris_user_id "DEORIS user id"
        string action "added|removed|borrowed|returned|adjusted"
        int quantity_before
        int quantity_after
        int available_before
        int available_after
        string notes
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

    notifications {
        bigint id PK
        string recipient_id "DEORIS user id"
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

    books ||--o{ transactions : "borrowed in"
    books ||--o{ inventory_logs : "tracked in"
    transactions ||--o{ penalties : "generates"
```

## Database Info
| Property | Value |
|---|---|
| **Database Name** | `library` |
| **Connection** | MySQL / 127.0.0.1:3306 |
| **App URL** | https://librarysys.deoris.test |
| **Role** | Library Book Management & Borrowing |

## Cross-DB Links
| Field | References |
|---|---|
| `visits.deoris_user_id` | `deoris_identity_db.users.id` (migrated from local users table) |
| `transactions.deoris_user_id` | `deoris_identity_db.users.id` |
| `penalties.deoris_user_id` | `deoris_identity_db.users.id` |
| ClearCheck queries | `transactions` & `penalties` via REST API |
| `event_outbox` → DEORIS | `deoris_identity_db.event_logs` via HTTP POST |

## Notes
- Local `users` table was **dropped** in migration `2026_05_26_000010` — identity fully delegated to DEORIS
- User name/email are **denormalized** into `visits` and `transactions` for query performance
