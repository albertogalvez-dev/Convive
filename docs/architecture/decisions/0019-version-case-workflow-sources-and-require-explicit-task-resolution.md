# ADR-0019: Version case workflow sources and require explicit task resolution

- **Status:** Accepted
- **Date:** 11 August 2026
- **Related issue:** [#45](https://github.com/albertogalvez-dev/Convive/issues/45)
- **Depends on:** [ADR-0018](0018-require-case-assignments-for-case-content.md)
- **Diagram:** [Territorial protocol model](../diagrams/territorial-protocol-model.md)

## Context

Case work needs owners, target dates and traceable protocol stages. Those
targets do not all have the same authority. The Andalusian Order of 20 June
2011 establishes the applicable protocol, the 2026 national framework is a
reference for autonomous communities, and a centre may also set internal
operational targets.

A due date must not be presented as an automatic legal conclusion. Likewise,
creating or expiring a task cannot prove that a family, the Education
Inspectorate or another external recipient was contacted.

## Decision drivers

- Preserve the authority, territory and reviewed version behind each target.
- Keep binding rules distinct from recommendations and internal operations.
- Make overdue calculation reproducible without a background state mutation.
- Require a named, authorised professional for every terminal transition.
- Never infer completion of an external communication.

## Options considered

### Store a free-text source on each task

This is easy to enter but cannot reliably distinguish versions, territories or
authority and would make later regulatory review impractical.

### Encode every target directly in application code

This makes regulatory changes deployment changes, obscures provenance and risks
universalising one territorial profile.

### Reference immutable source versions from explicit case tasks

This keeps the task lifecycle small while preserving the reviewed basis for a
target. New source versions can be added without rewriting historical tasks.

## Decision

Every task belongs to one managed case and records an assigned professional,
protocol stage, bounded title, target date, task kind, creator and immutable
workflow source version.

A source version records a stable code and version, title, optional internal or
required official URI, territory, publication date, review date and one
authority classification:

| Authority | Meaning inside Convive |
| --- | --- |
| `binding` | A reviewed, territorially applicable official rule or instruction. |
| `recommended` | Official guidance or a reference framework that is not represented as an applicable legal deadline. |
| `internal` | An organisation or Convive demonstration target with no claim of legal authority. |

The initial catalogue versions the Andalusian 2011 protocol, the Andalusian
2017 cyberbullying instructions, the Ministry's 2026 reference framework and a
fictional Granada demonstration profile. The national 24-hour, 10-day and
six-month targets remain recommendations; they are not labelled as binding
Andalusian deadlines.

Task status is `pending`, `completed` or `not_applicable`. Overdue is derived
only when a task is still pending and the evaluation time is strictly later
than its target date. It is not persisted as a fourth status. A task marked not
applicable requires a bounded reason.

Completion and not-applicable transitions are terminal and require an explicit
professional actor with `manage` permission for the exact case at evaluation
time. An assigned owner must retain at least `view` access when a task is
created. Database constraints mirror the resolution-state invariants.

`external_communication` is an explicit task kind. It remains pending until an
authorised professional confirms completion. Convive does not infer delivery
from task creation, due-date passage, another task, a case transition or a
future integration attempt.

### Controlled catalogue maintenance

Approved task-planning templates are reviewed, source-versioned catalogue
entries. They provide a selectable starting point for the fictional Andalusian
profile, but a professional must still explicitly choose the template, adapt
the task title, select the owner and set the target date. They neither select
or apply a protocol nor calculate a legal deadline.

Neither the normal professional API nor the workspace provides free-text
editing of regulatory sources, authority, territory or source version. Adding
or retiring a source or template is a deliberately reviewed, versioned
implementation change: it records the official or internal provenance and
review date, adds a new immutable source version where needed, and does not
rewrite historical tasks. A retired template is made unavailable for new work;
already-created tasks retain their immutable source reference.

## Consequences

### Positive

- Historical tasks retain the exact reviewed source version.
- Overdue and not-applicable behaviour is deterministic and auditable.
- The model supports the Andalusian profile without claiming support elsewhere.
- External communications cannot silently appear completed.

### Negative

- Regulatory sources require deliberate review and new immutable versions.
- Due dates still require professional judgement where an applicable source
  provides no numeric target.
- A later UI must explain source authority without presenting legal advice.

## Verification

- Domain tests cover source classification, overdue boundaries, explicit
  completion and terminal not-applicable transitions.
- Application services apply the case-assignment permission boundary before
  creation or resolution.
- PostgreSQL constraints cover source identity, dates, enum values and coherent
  resolution evidence.
- A persistence test round-trips a source-aware resolved task.

## Review triggers

Review when an applicable Andalusian protocol changes, the national framework
changes legal status, another territory is added, targets become configurable
through a user interface or an external communication integration is proposed.
