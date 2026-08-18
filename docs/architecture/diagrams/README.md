# Architecture diagram catalogue

Each diagram has one audience and an implementation source of truth. Update a
diagram in the same change as the behaviour or infrastructure it represents;
do not add a decorative diagram whose nodes are not implemented.

| Diagram | Audience | Source of truth | Status |
|---|---|---|---|
| [System architecture](initial-system-architecture.md) | Contributors and reviewers | Angular routes, Symfony modules and Compose boundaries | Maintained logical component view |
| [Reporting sequence](reporting-sequence.md) | API/frontend contributors | Current anonymous capability flow and professional response endpoints | Maintained implemented behaviour |
| [Single-VPS deployment](single-vps-deployment.md) | Operators and security reviewers | ADR-0012 and `infrastructure/production/compose.production.yaml` | Maintained fictional deployment topology |
| [Recovery flow](recovery-flow.md) | Operators and release reviewers | ADR-0013, `infrastructure/backup/` and restore evidence contract | Maintained implemented recovery path |
| [Data model](data-model.md) | Developers and technical report readers | Doctrine mappings, migrations and `data-model.dbml` | Maintained schema view, enforced by `check-architecture-documents.sh` |
| [Case lifecycle](case-lifecycle.md) | Anyone asking what Convive does | `ReportTriageOutcome`, `CaseStatus` and ADR-0017 | Maintained: a report is not a case |
| [Authorisation model](authorisation-model.md) | Contributors and security reviewers | `ProfessionalRole`, `CaseAssignmentRole` and ADR-0018 | Maintained: role ≠ case access |
| [Territorial protocol model](territorial-protocol-model.md) | Contributors and evaluators | `WorkflowSourceVersion`, the territorial migrations and their isolation tests | Maintained: citation, not decision |
| [Translation pipeline](translation-pipeline.md) | Contributors and content reviewers | `i18n-completeness.ts`, `translation-sync.ts` and ADR-0026/0027 | Maintained: two guarantees on purpose |

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
