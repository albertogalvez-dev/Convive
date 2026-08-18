# ADR-0017: Model report triage as append-only decisions

- **Status:** Accepted
- **Date:** 11 August 2026
- **Related issue:** [#43](https://github.com/albertogalvez-dev/Convive/issues/43)
- **Depends on:** [ADR-0002](0002-use-a-modular-monolith-for-the-backend.md), [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md)
- **Diagram:** [Case lifecycle and the report/case boundary](../diagrams/case-lifecycle.md)

## Context

An anonymous report is an incoming communication, not a confirmed bullying
case. The existing initial review records that an authorised professional has
read and assessed the report, but it does not record the operational outcome of
that assessment. Convive needs an explicit transition without rewriting the
original report or making dismissal equivalent to deletion.

## Decision drivers

- Preserve the report and its original content independently from case work.
- Attribute every decision to an active triage professional and organisation.
- Retain the reason and decision time for safeguarding accountability.
- Make report-to-case creation safe to retry.
- Establish only the case identity needed by this transition; assignments,
  people, tasks and workflow belong to later case-management increments.

## Options considered

### Replace the report status with a case status

This collapses two lifecycles and makes an incoming communication appear to be
a confirmed case. It also makes redirection or dismissal difficult to audit.

### Store only the latest triage outcome on the report

This is simple to query but overwrites earlier professional judgement and mixes
mutable operational state into the immutable source record.

### Append triage decisions and link a separate case

Each decision is an immutable record. A separate case and link are created only
for the case outcome, while the report remains independently addressable.

## Decision

Convive records report triage as append-only decisions with four outcomes:

- `keep`: retain the report in active triage because a final routing decision
  has not yet been made;
- `redirect`: retain the report but end local triage because another route is
  appropriate;
- `dismiss`: retain the report but end triage because no managed case will be
  opened from it;
- `link_to_case`: create and link the minimal managed-case identity.

Every decision records its report, organisation, professional actor, bounded
reason and immutable UTC time. `keep` is non-terminal and may be followed by
another decision. `redirect`, `dismiss` and `link_to_case` are terminal. A
terminal decision cannot be replaced by a later one.

The `link_to_case` operation creates one minimal case in the report's
organisation and one unique report-to-case link. Retrying that same operation
returns the existing decision and case rather than creating duplicates. A retry
does not replace the original actor, reason or time. Database uniqueness and a
single transaction enforce the invariant under concurrent requests.

The case was initially introduced with only identity, organisation, creator and
creation time. [ADR-0018](0018-require-case-assignments-for-case-content.md)
now defines its lifecycle baseline, involved people and assignment boundary.
Tasks and deadlines remain deferred to issue #45.

The initial `new`/`reviewed` report status remains separate. Triage requires the
existing initial review first, but no triage outcome mutates report content,
follow-up history or reporter access. Redirection and dismissal never delete the
report, evidence or conversation.

The HTTP boundary is an organisation-scoped professional report subresource.
It accepts one outcome and reason, requires same-origin CSRF protection and an
active `triage` membership, and returns a credential-free representation of the
recorded decision. Foreign and unknown report identifiers remain
indistinguishable.

## Consequences

### Positive

- Report and case semantics remain distinct and auditable.
- Professional judgement is not silently overwritten.
- Retried case creation cannot produce duplicate cases.
- Later case-management work starts from an explicit source link.

### Negative

- Reading current triage state requires selecting the latest decision.
- Terminal corrections require a future, explicitly audited correction model.
- The initial case record is intentionally not yet a usable case workspace.

## Verification

- Domain tests cover bounded reasons and allowed outcomes.
- Persistence tests cover ordering, terminal state and unique linking.
- HTTP tests cover review preconditions, actor attribution, idempotent retries,
  CSRF, role and cross-organisation denial.
- Migration, Doctrine schema and OpenAPI checks remain green.

## Review triggers

Review this decision if legislation or an approved safeguarding workflow
requires correction/reopening semantics, multi-report case linking, transfer
between organisations or a separately authorised role for case creation.
