# Architecture diagram catalogue

Each diagram has one audience and an implementation source of truth. Update a
diagram in the same change as the behaviour or infrastructure it represents;
do not add a decorative diagram whose nodes are not implemented.

| Diagram | Audience | Source of truth | Status |
|---|---|---|---|
| [Initial system architecture](initial-system-architecture.md) | Contributors and reviewers | Angular routes, Symfony modules and Compose boundaries | Maintained logical component view |
| [Reporting sequence](reporting-sequence.md) | API/frontend contributors | Current anonymous capability flow and professional response endpoints | Maintained implemented behaviour |
| [Single-VPS deployment](single-vps-deployment.md) | Operators and security reviewers | ADR-0012 and `infrastructure/production/compose.production.yaml` | Maintained fictional deployment topology |
| [Recovery flow](recovery-flow.md) | Operators and release reviewers | ADR-0013, `infrastructure/backup/` and restore evidence contract | Maintained implemented recovery path |
| [Data model](data-model.md) | Developers and technical report readers | Doctrine mappings, migrations and `data-model.dbml` | Maintained schema view |

The initial system diagram is intentionally logical rather than a second
deployment diagram. The single-VPS diagram is the authoritative trust-boundary
view for the selected deployment. No speculative school-role, email, upload,
case-management or analytics diagram is presented as implemented; those areas
remain in their respective issues until their behaviour and source of truth
exist.

## Update rule

When a migration changes the schema, update `data-model.dbml` and
`data-model.md` in the same pull request. When a route, trust boundary or
release/recovery contract changes, update the affected diagram and its related
ADR/runbook together. A superseded view is either corrected in place or
labelled historical with a link to its replacement; it is never silently left
to contradict the code.
