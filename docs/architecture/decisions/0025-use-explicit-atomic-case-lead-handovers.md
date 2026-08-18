# ADR-0025: Use explicit atomic case-lead handovers

- Status: Accepted
- Date: 2026-08-13
- Diagram: [Authorisation: organisation role versus case assignment](../diagrams/authorisation-model.md)

## Context and problem

Each managed case needs one explicit active primary responsible person while
retaining limited, explicit collaboration. A planned absence or continuity
change must not create a period with two active leads, a case without a lead,
or a broad automatic access grant. The operational decision also requires a
minimised reason without exposing it in ordinary case-workspace responses.

ADR-0018 owns exact-case authorisation and ADR-0023 owns the mapping from
visible fictional responsibilities to technical grants. This record does not
add a public professional role, an automatic absence workflow, or emergency
access.

## Decision drivers

- Preserve one explicit active lead per case.
- Make a handover deliberate, attributable and auditable.
- Preserve the established one historical assignment record per case and
  professional until a separately reviewed re-entry model is needed.
- Keep assignment reasons minimised and out of ordinary case responses.
- Avoid automatic reassignments, returns after absences and centre-wide access.

## Considered alternatives

1. Revoke the former lead and create the next lead as independent operations.
2. Keep a permanent unique `(case_id, professional_id)` record.
3. Grant management access to every active organisation member.
4. Use an explicit atomic lead handover while retaining the established
   assignment-history uniqueness constraint.

## Selected option and rationale

Convive uses option 4. A lead change creates the new lead and revokes the
former lead in one persistence transaction, with one mandatory, bounded reason
recorded on both historical decisions. The established unique case-professional
record is retained so the audited access history remains unambiguous. A future
re-entry model needs its own migration and review before allowing a revoked
professional to receive a second historical assignment.

Only the exact-case assignment manager may add a contributor or observer,
change a non-lead assignment between those two roles, revoke a non-lead
assignment, or hand over the lead. A role change requires the same bounded,
minimised reason and creates its own audit event. The API returns active
same-organisation candidates only to that manager. Assignment, role-change
and revocation reasons are stored for the protected record but are not returned
in the normal workspace representation.

## Consequences

- A final lead cannot be removed: it must be handed over explicitly.
- Existing historical assignments remain meaningful; re-entry after a prior
  revocation is deliberately deferred to a separate reviewed model.
- There is no automatic reassignment, automatic return after absence,
  break-glass access or newly invented Direction capability.
- A later absence-management increment must record dates and use this handover
  mechanism; it cannot silently select a responsible person.

## Review triggers

Review this decision before adding a real professional role model, absence
management, a formal delegation workflow, cross-organisation transfer,
emergency access, or any change to protected audit-record retention.
