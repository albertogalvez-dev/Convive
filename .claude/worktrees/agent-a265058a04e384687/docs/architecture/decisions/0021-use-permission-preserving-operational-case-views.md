# ADR-0021: Use permission-preserving operational case views

- **Status:** Accepted
- **Date:** 11 August 2026
- **Related issue:** [#48](https://github.com/albertogalvez-dev/Convive/issues/48)
- **Depends on:** [ADR-0018](0018-require-case-assignments-for-case-content.md),
  [ADR-0019](0019-version-case-workflow-sources-and-require-explicit-task-resolution.md)
  and [ADR-0020](0020-protect-case-audit-events-with-minimised-append-only-records.md)

## Context

Professionals need a bounded way to find work assigned to them, including
actions that are overdue, upcoming or recently changed. A dashboard cannot
weaken the exact-case assignment boundary, infer safeguarding conclusions or
become staff-performance monitoring.

## Decision

The professional workspace provides four permission-preserving views over
active exact-case assignments only:

- **Assigned:** all accessible cases, ordered by case creation time descending;
- **Overdue:** cases whose earliest pending task is before the request time,
  ordered by that due time ascending;
- **Upcoming:** cases whose earliest pending task is at or after the request
  time, ordered by that due time ascending; and
- **Recent:** cases ordered by their latest explicit case, assignment or task
  lifecycle activity descending.

The views support exact state and modality filters plus a bounded reference
search. Search matches the accessible source-report public reference or a
complete case UUID; it does not search report text, task titles, people,
reasons, audit payloads or attachments.

Every view uses a bounded page and an opaque cursor containing only the selected
view, its ordering instant and a UUID tie-breaker. A cursor is rejected when
used with a different view. Ordering always adds the case UUID, so pages remain
deterministic when timestamps match. Empty, unauthorised and partial-data
responses are intentionally distinct only where the professional already has
the list endpoint; direct case access retains its indistinguishable unavailable
response.

`operational_updated_at` records only explicit case, assignment and task
lifecycle changes. It is not a read event, replacement for ADR-0020's formal
audit trail, risk score, priority score or measure of professional performance.
Passive evidence download and audit viewing/export do not change it.

Dashboard aggregates are derived from those exact same authorised result sets.
They expose counts for operational questions only and never identify reporters,
students, other organisations or unassigned cases.

## Consequences

### Positive

- Operational queues are stable, paginated and no broader than direct case
  access.
- Overdue and upcoming status remains a reproducible task-time calculation.
- The interface can handle empty and partial result sets without mock metrics.

### Negative

- The reference search deliberately does not behave like full-text search.
- A later activity type needs an explicit decision about whether it updates the
  operational ordering.
- Cross-case performance analytics and predictive prioritisation remain absent.

## Verification

- API tests prove exact assignment and organisation boundaries for every view,
  filter and cursor.
- Tests prove deterministic ordering, cursor continuation and overdue/upcoming
  boundaries.
- Frontend and accessibility checks cover filters, pagination and empty states.
- The data-model diagram and OpenAPI contract document the added field and
  response semantics.
