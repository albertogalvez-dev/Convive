# Data model

Entity–relationship view of Convive's first domain schema (`organisations` and
`reports`). GitHub renders this diagram from its Mermaid source. It is kept in
sync with the Doctrine mappings, the committed migration and
[`data-model.dbml`](../data-model.dbml).

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#E2E8F0', 'primaryTextColor': '#0F172A', 'primaryBorderColor': '#64748B', 'lineColor': '#64748B'}}}%%
erDiagram
    organisations {
        uuid id PK
        varchar name
    }
    reports {
        uuid id PK "UUIDv7, application-generated"
        uuid organisation_id FK
        text situation_description
        varchar situation_context "in_person | digital | mixed | unknown"
        varchar status "received"
        varchar public_reference UK "non-sequential"
        varchar access_secret_hash "one-way hash; secret never stored"
        timestamptz created_at "immutable UTC"
    }
    organisations ||--o{ reports : "receives"
```
