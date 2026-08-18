# ADR-0020: Protect case audit events with minimised append-only records

- **Status:** Accepted
- **Date:** 11 August 2026
- **Related issue:** [#47](https://github.com/albertogalvez-dev/Convive/issues/47)
- **Depends on:** [ADR-0017](0017-model-triage-as-append-only-decisions.md),
  [ADR-0018](0018-require-case-assignments-for-case-content.md) and
  [ADR-0019](0019-version-case-workflow-sources-and-require-explicit-task-resolution.md)

## Context

Case actions need accountability for safeguarding work without turning Convive
into employee surveillance or duplicating security logs. The derived activity
shown in the case workspace is useful operational context, but it is not a
tamper-resistant record and must not be represented as one.

The demonstration uses only fictional data. A controller, DPO, legal basis,
production retention period, rights process and legal-hold procedure do not yet
exist and cannot be invented by this repository.

## Decision

Convive stores a separate append-only case audit event for significant
case-relevant actions only. Each event contains the managed-case, organisation,
authorised professional actor, action, target type, target identifier and
occurred time. It contains no report or task text, decision reason, attachment
description, access secret, session identifier, IP address or content snapshot.

The first event vocabulary is deliberately small:

- case creation and report-to-case linkage;
- assignment creation and revocation;
- task creation, completion and not-applicable resolution;
- authorised evidence-download authorisation; and
- audit export.

No read/view event is recorded. Opening a page is not evidence of a meaningful
professional action and recording it would create surveillance data.

PostgreSQL rejects updates and ordinary deletes from the application. The only
allowed deletion path is the fictional-data retention job, which sets a
transaction-local purge capability and removes events older than 30 days. This
is a bounded demonstration lifecycle, not a real safeguarding retention rule.

Only an active `lead` assignment has the explicit `view_audit` permission. It
can read the ordered case audit trail and request a no-store CSV export of its
minimal fields. The export intentionally excludes report content, evidence,
case-person names, reasons and target identifiers. Creating that export appends
an audit event before the export is generated. There is no global audit search,
self-service data-subject export or staff-performance analytics.

For real data, audit retention, deletion, legal hold, rights handling, export
authority, incident-investigation access and restoration reconciliation remain
disabled gates requiring the controller/DPO and documented procedures.

## Consequences

### Positive

- Significant case actions gain a durable, permission-scoped accountability
  record without copying sensitive content.
- The database prevents accidental application updates or deletes.
- Audit reads and exports are least-privilege actions rather than a side effect
  of ordinary case viewing.
- The fictional lifecycle is explicit and testable.

### Negative

- The record cannot reconstruct deleted content or every screen interaction.
- A lead may need an approved future process to share an export with an
  investigator; Convive does not automate that disclosure.
- The retention job is intentionally unsuitable as a real-data policy.

## Verification

- Migration tests prove PostgreSQL rejects audit mutation and ordinary delete.
- Persistence tests prove significant events are stored atomically with case
  changes and contain no content payload.
- API tests prove exact-case `view_audit` permission, indistinguishable denial,
  ordered reads, no-store responses and minimal export behaviour.
- The fictional cleanup job is tested with a transaction-local purge boundary.

## Review triggers

Review before any real-data deployment, role-matrix change, audit-search or
analytics proposal, legal-hold requirement, export integration, retention
approval, controller/DPO appointment or restoration-policy change.
