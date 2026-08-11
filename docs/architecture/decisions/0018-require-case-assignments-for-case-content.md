# ADR-0018: Require explicit assignments for case content

- **Status:** Accepted
- **Date:** 11 August 2026
- **Related issue:** [#44](https://github.com/albertogalvez-dev/Convive/issues/44)
- **Depends on:** [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md), [ADR-0017](0017-model-triage-as-append-only-decisions.md)

## Context

A managed case contains operational safeguarding information that is more
sensitive and longer-lived than the incoming report from which it may
originate. Organisation membership alone is too broad: administrators must be
able to manage professional accounts without automatically reading every case,
and a professional assigned to one case must not gain access to another.

Convive also needs enough structure for a fictional demonstration without
becoming a general student-information system or assigning institutional job
titles that the product does not yet manage.

## Decision drivers

- Apply least privilege at both organisation and case scope.
- Keep administrative account management separate from case content.
- Record the minimum operational identities needed for one managed case.
- Avoid importing academic, enrolment or complete student records.
- Give the report-to-case transition a safe, usable initial owner.

## Options considered

### Grant case access to every triage member

This makes organisation membership an unbounded content grant and cannot
express a deliberately restricted case.

### Use school job titles as permissions

Titles such as director, counsellor or inspector vary by organisation and do
not state what the holder may do in a particular case.

### Require an organisation membership and a case assignment

Organisation membership establishes the tenant boundary. A separate,
revocable assignment establishes the case boundary and carries an operational
permission profile.

## Decision

Every managed case has an explicit status and modality. A new case begins in
`assessment`; future reviewed transitions may move it to `active` or `closed`.
Its modality is one of `in_person`, `digital`, `mixed` or `unknown` and is
initially derived from the source report without changing that report.

An involved person contains only a case-local UUID, a bounded operational name
and one neutral role: `affected`, `alleged_actor`, `witness`, `guardian` or
`other`. `alleged_actor` records an allegation, not a finding. The model does
not contain academic records, dates of birth, addresses, enrolment data or
external institutional identifiers.

A professional assignment is unique for one case and professional, records its
assigner and time, and may be revoked without deletion:

| Assignment | View | Manage case content | Manage assignments |
| --- | --- | --- | --- |
| `lead` | yes | yes | yes |
| `contributor` | yes | yes | no |
| `observer` | yes | no | no |

Case access requires all of the following at evaluation time:

1. an active professional account;
2. an active membership in the case organisation;
3. an active assignment to that exact case;
4. the requested permission in the assignment profile.

An `administrator` membership satisfies none of the case-specific requirements
by itself. A professional who needs administrative and case responsibilities
holds them separately.

When `link_to_case` creates a case, the authorised triage actor receives the
initial `lead` assignment in the same transaction. Existing minimal cases are
backfilled identically by the migration.

## Consequences

### Positive

- Cross-organisation and cross-case access are independently denied.
- Administrative support can remain blind to safeguarding content.
- Permissions use operational capabilities rather than invented job titles.
- The fictional demonstration has deterministic case data without a student
  database.

### Negative

- Reassignment and last-lead rules require later explicit services and audit.
- Losing organisation membership removes access even while an assignment row
  remains active.
- Case listing must join both membership and assignment scope.

## Verification

- Domain tests cover state, modality, bounded people and the permission matrix.
- Authorisation tests cover administrator-only, cross-organisation and
  cross-case denial.
- PostgreSQL tests prove report linking creates one lead assignment atomically.
- The migration backfills existing cases and constrains all enum-like values.
- The fictional demo seed repeatably creates one case, one lead assignment and
  two minimal fictional people.

## Review triggers

Review before adding institutional directory imports, inspection access,
organisation-wide emergency access, case transfer, reopening or break-glass
access.
