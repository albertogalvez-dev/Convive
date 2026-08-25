# Architecture diagram catalogue

Each diagram has one audience and an implementation source of truth. Update a
diagram in the same change as the behaviour or infrastructure it represents;
do not add a decorative diagram whose nodes are not implemented.

| Diagram                                                                       | Audience                                | Source of truth                                                               | Verification                                                                      | Status                                      |
| ----------------------------------------------------------------------------- | --------------------------------------- | ----------------------------------------------------------------------------- | --------------------------------------------------------------------------------- | ------------------------------------------- |
| [System architecture](initial-system-architecture.md)                         | Contributors and reviewers              | Angular routes, Symfony modules and Compose boundaries                        | API integration tests and Compose configuration check                             | Maintained logical component view           |
| [Reporting sequence](reporting-sequence.md)                                   | API/frontend contributors               | Current anonymous capability flow and professional response endpoints         | Reporting controller integration tests and OpenAPI route coverage                 | Maintained implemented behaviour            |
| [Single-VPS deployment](single-vps-deployment.md)                             | Operators and security reviewers        | ADR-0029 and `infrastructure/production/compose.production.yaml`              | `infrastructure/production/check-api-environment.sh` and production Compose check | Maintained fictional deployment topology    |
| [Recovery flow](recovery-flow.md)                                             | Operators and release reviewers         | ADR-0013, `infrastructure/backup/` and restore evidence contract              | `infrastructure/backup/test-recovery.sh`                                          | Maintained implemented recovery path        |
| [Data model](data-model.md)                                                   | Developers and technical report readers | Doctrine mappings, migrations and `data-model.dbml`                           | `infrastructure/maintenance/check-architecture-documents.sh`                      | Maintained schema view                      |
| [Case lifecycle](case-lifecycle.md)                                           | Anyone asking what Convive does         | `ReportTriageOutcome`, `CaseStatus` and ADR-0017                              | Triage and managed-case domain and integration tests                              | Maintained: a report is not a case          |
| [Authorisation model](authorisation-model.md)                                 | Contributors and security reviewers     | `ProfessionalRole`, `CaseAssignmentRole` and ADR-0018                         | `AuthoriseCaseAccessTest` and professional-case controller tests                  | Maintained: role ≠ case access              |
| [Territorial protocol model](territorial-protocol-model.md)                   | Contributors and evaluators             | `WorkflowSourceVersion`, the territorial migrations and their isolation tests | Territorial migration and isolation tests                                         | Maintained: citation, not decision          |
| [Translation pipeline](translation-pipeline.md)                               | Contributors and content reviewers      | `i18n-completeness.ts`, `translation-sync.ts` and ADR-0026/0027               | `npm run i18n:confirm` and frontend translation tests                             | Maintained: two guarantees on purpose       |
| [Sessions and capabilities](access-sessions-and-capabilities.md)              | Security reviewers and contributors     | ADR-0008/0010/0011 and Symfony authenticators                                 | Session and reporter-capability integration tests                                 | Maintained separate credential contexts     |
| [Attachment lifecycle](attachment-lifecycle.md)                               | Security reviewers and operators        | Attachment lifecycle scripts and threat model                                 | `infrastructure/maintenance/test-attachment-lifecycle.sh`                         | Maintained fail-closed private boundary     |
| [Observability and incident response](observability-and-incident-response.md) | Maintainer and operator                 | Observability scripts, systemd units and incident runbook                     | `infrastructure/observability/exercise-failure.sh`                                | Maintained redacted signal-to-response path |
| [Fictional-demo retention](fictional-demo-retention.md)                       | Privacy and recovery reviewers          | Cleanup commands, recovery runbook and privacy register                       | Attachment lifecycle and encrypted-recovery checks                                | Maintained fictional-only deletion boundary |

The initial system diagram is intentionally logical rather than a second
deployment diagram. The single-VPS diagram is the authoritative trust-boundary
view for the selected deployment. No speculative diagram is presented as implemented; an area without behaviour
and a source of truth stays in its issue until both exist. Case management,
authorisation and the territorial and translation models now have both, which
is why they appear above.

The four newest diagrams each state, at the top, **the property they exist to
make obvious**. That is the acceptance test for them: an authorisation diagram
where "administrator does not imply case access" is not the first thing a
reader takes away has failed, however correct its arrows.

## Update rule

When a migration changes the schema, update `data-model.dbml` and
`data-model.md` in the same pull request. When a route, trust boundary or
release/recovery contract changes, update the affected diagram and its related
ADR/runbook together. A superseded view is either corrected in place or
labelled historical with a link to its replacement; it is never silently left
to contradict the code.

## Executable validation

Run `npm run docs:check` from `apps/web` to validate every tracked relative
Markdown link, parse every Mermaid fence with pinned `mermaid@11.17.0`, and
compare the catalogue with the maintained diagram files. The check reads only
the working tree; it does not fetch credentials, public data or remote links.
`npm run docs:check:test` exercises failing-link, invalid-Mermaid and
unindexed-diagram fixtures.
