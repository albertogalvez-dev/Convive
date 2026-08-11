# Data model

Entity–relationship view of Convive's domain schema. GitHub renders this
diagram from its Mermaid source. It is kept in sync with the Doctrine
mappings, the committed migrations and [`data-model.dbml`](../data-model.dbml).

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#E2E8F0', 'primaryTextColor': '#0F172A', 'primaryBorderColor': '#64748B', 'lineColor': '#64748B'}}}%%
erDiagram
    organisations {
        uuid id PK
        varchar name
        varchar public_reporting_identifier UK "ORG_ + 16 Crockford Base32 characters"
    }
    reports {
        uuid id PK "UUIDv7, application-generated"
        uuid organisation_id FK
        text situation_description
        varchar situation_context "in_person | digital | mixed | unknown"
        varchar status "received | reviewed; independent from triage"
        varchar public_reference UK "non-sequential"
        varchar access_secret_hash UK "64-char lowercase hex SHA-256; secret never stored"
        timestamptz created_at "immutable UTC"
        text review_reason "nullable until reviewed"
        uuid reviewed_by_professional_id FK "nullable until reviewed"
        timestamptz reviewed_at "nullable; immutable UTC"
        int attachment_count "reserved slots"
        int attachment_bytes "reserved bytes"
        int version "optimistic lock"
    }
    report_access_grants {
        uuid id PK "UUIDv7, application-generated"
        uuid report_id FK
        varchar capability_hash UK "64-char lowercase hex SHA-256"
        timestamptz issued_at "immutable UTC"
        timestamptz last_used_at "drives 15-minute idle timeout"
        timestamptz absolute_expires_at "issued_at + 2 hours"
        timestamptz revoked_at "nullable; set once"
    }
    report_follow_up_entries {
        uuid id PK "UUIDv7, application-generated"
        uuid report_id FK
        varchar author_type "reporter | professional"
        uuid professional_author_id "nullable; private audit metadata"
        text content "bounded to 2000 characters"
        timestamptz created_at "immutable UTC; append-only"
    }
    managed_cases {
        uuid id PK "UUIDv7; minimal case identity"
        uuid organisation_id FK
        uuid created_by_professional_id FK
        timestamptz created_at "immutable UTC"
    }
    report_triage_decisions {
        uuid id PK "UUIDv7; append-only"
        uuid report_id FK
        uuid organisation_id FK
        uuid decided_by_professional_id FK
        varchar outcome "keep | redirect | dismiss | link_to_case"
        text reason "trimmed 10-1000 characters"
        timestamptz decided_at "immutable UTC"
        uuid terminal_report_id UK "null for keep; report id when terminal"
        uuid case_id UK "only for link_to_case"
    }
    professionals {
        uuid id PK "UUIDv7, application-generated"
        varchar name
        varchar email UK "normalised lowercase; no auth until #30"
        timestamptz created_at "immutable UTC"
    }
    organisation_memberships {
        uuid id PK "UUIDv7, application-generated"
        uuid professional_id FK
        uuid organisation_id FK
        varchar role "triage | administrator"
        timestamptz granted_at "immutable UTC"
        timestamptz revoked_at "nullable; row persists after revocation"
    }
    organisations ||--o{ reports : "receives"
    professionals o|--o{ reports : "reviews"
    reports ||--o{ report_access_grants : "grants access to"
    reports ||--o{ report_follow_up_entries : "accumulates"
    reports ||--o{ report_triage_decisions : "receives decisions"
    reports o|--o| report_triage_decisions : "has terminal decision"
    organisations ||--o{ managed_cases : "owns"
    organisations ||--o{ report_triage_decisions : "scopes"
    professionals ||--o{ managed_cases : "creates"
    professionals ||--o{ report_triage_decisions : "decides"
    managed_cases o|--o| report_triage_decisions : "is linked by"
    professionals ||--o{ organisation_memberships : "holds"
    organisations ||--o{ organisation_memberships : "grants"
```
