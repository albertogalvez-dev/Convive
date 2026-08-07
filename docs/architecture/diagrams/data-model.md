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
        varchar status "received"
        varchar public_reference UK "non-sequential"
        varchar access_secret_hash UK "64-char lowercase hex SHA-256; secret never stored"
        timestamptz created_at "immutable UTC"
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
    professionals ||--o{ organisation_memberships : "holds"
    organisations ||--o{ organisation_memberships : "grants"
```
