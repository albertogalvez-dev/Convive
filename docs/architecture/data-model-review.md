# Data-model documentation review

[`data-model.dbml`](data-model.dbml) and the rendered
[Mermaid view](diagrams/data-model.md) are human-readable views of the
persisted application model. Doctrine mappings and committed migrations remain
authoritative; neither diagram contains fictional records, report narrative,
attachment contents, email addresses, credentials or session identifiers.

## Represented application tables

Both artefacts represent these Doctrine-owned domain tables:

- `organisations`, `reports`, `report_access_grants`,
  `report_follow_up_entries`, `report_attachments` and
  `report_triage_decisions`;
- `professionals` and `organisation_memberships`;
- `managed_cases`, `case_assignments`, `case_involved_people`,
  `case_workflow_source_versions`, `case_tasks`, `case_audit_events` and
  `professional_export_events`.

The diagram records identifiers, relationships, database uniqueness/index
intent and lifecycle-relevant state only. It deliberately omits unbounded
report text and other content values.

## Explicit exclusions

The following PostgreSQL tables are intentionally absent from the domain
diagrams. They are not Doctrine-owned domain entities and are excluded from
Doctrine schema comparison in `apps/api/config/packages/doctrine.yaml`:

| Table | Rationale and authoritative source |
| --- | --- |
| `professional_sessions` | Framework-owned Symfony PDO session storage. Its transient opaque session rows and expiry lifecycle are configured by `PdoSessionHandler`; the access boundary is documented in [ADR-0008](decisions/0008-use-server-side-sessions-and-capability-based-anonymous-access.md). |
| `reporter_email_contacts` | Delivery-support store managed through reviewed DBAL operations, not an ORM aggregate. It can contain optional contact data, remains unavailable to public fictional-demo reporting, and its schema is defined by migration `Version20260811032000`. |
| `reporter_notification_outbox` | Delivery worker queue managed through reviewed DBAL operations, not an ORM aggregate. It is disabled in public fictional-demo production and its schema is defined by migration `Version20260811032000`. |
| `doctrine_migration_versions` | Doctrine migration bookkeeping, owned by the migration tool rather than Convive's domain. |

## Automated consistency check

Run the source-only consistency gate from the repository root:

```bash
bash infrastructure/maintenance/check-architecture-documents.sh
```

The `check-architecture-documents.sh` gate compares the exact table-identifier
inventory in Doctrine ORM attributes,
`data-model.dbml` and the Mermaid data-model view. It also verifies the stable
architecture links that lead reviewers to those three artefacts. The script
does not boot containers, connect to PostgreSQL, read fictional records or
call an external service. Its fixture test proves that missing documented
tables and maintained links fail with the affected artefact in the output.

Backend CI separately generates and compares the OpenAPI contract from the
implemented route controllers, then applies committed migrations and validates
the Doctrine schema. Those checks remain the mechanical boundary for route,
OpenAPI and migration drift; this script deliberately does not duplicate their
framework-aware parsing.

## Human review

Run this checklist whenever a mapping or migration changes, before adding a
new table to a diagram:

```bash
rg --glob '*.php' '#\[ORM\\Table' apps/api/src
rg '^Table ' docs/architecture/data-model.dbml
rg '^    [a-z_]+ \{' docs/architecture/diagrams/data-model.md
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml run --rm --no-deps api php bin/console doctrine:mapping:info
```

Compare the three table inventories, then inspect migration constraints and
foreign keys for every changed table. Confirm each non-ORM persisted table is
either represented or listed above with its owner and rationale. Finally,
preview the Mermaid block in GitHub and the DBML in dbdiagram.io; correct
syntax, links and cardinalities before review. Human review remains necessary
for field semantics, indexes, cardinalities, exclusions and whether a diagram
is an accurate and non-speculative representation of the implemented system.
