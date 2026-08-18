# ADR-0023: Map Andalusian centre responsibilities to least-privilege grants

- **Status:** Accepted
- **Date:** 13 August 2026
- **Related issue:** [#170](https://github.com/albertogalvez-dev/Convive/issues/170)
- **Depends on:** [ADR-0017](0017-model-triage-as-append-only-decisions.md),
  [ADR-0018](0018-require-case-assignments-for-case-content.md) and
  [ADR-0021](0021-use-permission-preserving-operational-case-views.md)

## Context

Convive's initial fictional Andalusian centre profile needs product language that
is recognisable in a school without turning an institutional job title into an
unbounded data-access grant. The existing technical boundary is intentionally
small: organisation memberships are `triage` or `administrator`, and every
case-content operation requires an active exact-case assignment of `lead`,
`contributor` or `observer`.

The product must be able to show familiar responsibilities while preserving
that boundary. It must not claim statutory authority, institutional
integration, inspection access, emergency access or a real-centre workflow.

## Decision drivers

- Use terms that a fictional Andalusian educational centre recognises.
- Keep organisation administration separate from report and case content.
- Require explicit, revocable exact-case access for every case-content action.
- Make high-risk actions and unavailable capabilities reviewable.
- Avoid implying a legal conclusion or authority from the product model.

## Decision

The visible responsibility is separate from the technical grant. A person may
hold more than one visible responsibility only when each corresponding grant is
made explicitly and is auditable.

Visible responsibility labels are product terminology for the fictional
Andalusian profile. They do not attest an appointment, qualification or legal
authority in a real educational centre.

| Visible responsibility | Initial organisation grant | Exact-case grant | Explicit boundary |
| --- | --- | --- | --- |
| Dirección del centro | `administrator` when centre configuration or account administration is required | None by default; a separate assignment is required | Does not automatically read, export, close or reopen any case. |
| Coordinación de bienestar y protección | `triage` | May receive `lead`, `contributor` or `observer` when explicitly assigned | Principal coordination responsibility for relevant communications; it does not gain every managed case. |
| Orientación educativa | None by default | May receive `lead`, `contributor` or `observer` when explicitly assigned | Works only within the assigned case and permitted actions. |
| Tutoría | None by default | May receive `contributor` or `observer` when explicitly assigned | Has no default access to all cases involving a course, group or learner. |
| Profesorado | None by default | May receive `contributor` or `observer` when explicitly assigned | May communicate a fictional internal concern but does not acquire case access by doing so. |
| Administración y servicios | `administrator` only when a content-blind administrative task requires it | None by default; a separate assignment is required | Never receives report, case, evidence, audit or export access from administration alone. |

`triage` remains an organisation-scoped report-review grant, not a managed-case
grant. Exact case access requires an active professional account, active
membership in the same organisation and an active exact-case assignment:

| Exact-case assignment | May view | May manage case content | May manage assignments |
| --- | --- | --- | --- |
| `lead` | yes | yes | yes |
| `contributor` | yes | yes | no |
| `observer` | yes | no | no |

Only an active `lead` may create or revoke an assignment under the current
technical model. A later assignment workflow may add a Direction-initiated
reassignment flow only with an explicit reason, audit evidence and no implicit
content access. Second-person approval, conflict-of-interest delegation,
inspection, cross-organisation transfer and emergency or break-glass access
are intentionally unavailable.

The initial product also intentionally withholds automatic legal/protocol
conclusions, automatic family contact, automatic deadlines, universal case
search, public professional account creation and public professional mutation.

Organisation membership administration may grant, change, suspend, resume or
remove an explicit technical grant only within the administrator's own centre.
It records a minimised account-audit action and invalidates the affected
professional's stale session. It neither creates, revokes nor alters a case
assignment: an active membership remains only one of the three independent
requirements for access to a managed case.

## Consequences

### Positive

- The interface uses familiar centre responsibilities without encoding them as
  legal claims or blanket permissions.
- Direction and administration can perform content-blind duties without a
  hidden safeguarding-data grant.
- Case management remains attributable to an exact assigned person.
- Future account, assignment and dashboard work has a stable approval boundary.

### Negative

- A visible responsibility alone is insufficient to answer every workflow
  question; explicit grants and assignments must be shown clearly.
- Some workflows, including absence cover and Direction-led reassignment, need
  a later implementation that preserves the same boundary.
- The matrix is not a substitute for a centre's own protocol or legal advice.

## Verification

- Domain and authorisation tests retain the organisation-plus-exact-case
  boundary for each assignment capability.
- Administrator-only, cross-organisation and cross-case access remain denied.
- Product documentation exposes the visible-responsibility and technical-grant
  distinction.
- Future permission-changing endpoints and UI must cite this ADR and add
  focused authorisation tests.

## Review triggers

Review this matrix before a centre protocol change, organisational-role change,
new high-risk action, absence/reassignment implementation, inspection request,
accessibility finding, territorial-profile change or any real-data pilot.
