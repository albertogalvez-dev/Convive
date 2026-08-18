# ADR-0024: Use a versioned neutral triage taxonomy

- Status: Accepted
- Date: 2026-08-13
- Issue: #177
- Diagram: [Case lifecycle and the report/case boundary](../diagrams/case-lifecycle.md)

## Context and problem

An initial report needs a small amount of structured context without converting
the reporter's account into a risk score, a diagnosis or an automated
conclusion. Professionals also need to record their separate initial
assessment. Future territorial profiles must be able to evolve the vocabulary
without silently changing the meaning of historic reports.

## Decision drivers

- preserve an anonymous reporter's ability to say that information is unknown;
- keep reporter input separate from a professional's assessment;
- avoid automatic safeguarding, protocol or urgency conclusions;
- retain the meaning of structured fields across future profile versions;
- support a clear, accessible and bounded interface.

## Considered alternatives

1. Keep only free text and situation context.
2. Calculate a severity score from reporter answers.
3. Use a fixed, unversioned list of categories.
4. Store a small neutral taxonomy with a version on every report.

## Decision

Convive stores taxonomy version `andalucia-v1` on each report. The reporter may
optionally record recurrence (`single`, `repeated`, `ongoing`, `unknown`) and
an attention cue (`needs_prompt_attention`, `no_prompt_attention_indicated`,
`unknown`). Both fields default to `unknown` for compatibility and to avoid
forcing an inference.

`andalucia-v1` identifies this bounded product vocabulary; it is not a legal
classification, a statement of institutional applicability or legal advice.

During the initial review, an authorised professional separately records a
concern category, recurrence and attention cue. The category is one of peer
interaction, digital interaction, exclusion or isolation, harmful language or
conduct, safety or wellbeing concern, other, or unknown. This assessment is
not shown in the reporter-facing follow-up representation.

No value creates a case, sends a notification, assigns a protocol, establishes
a legal duty, calculates a deadline or determines an emergency response.
Those actions remain explicit, authorised and auditable in their own flows.

## Consequences

The professional workspace gains bounded, reviewable context while the public
reporting flow remains neutral. Historic rows and direct seed inserts receive
database defaults for the reporter fields and taxonomy version. A later
territorial profile change requires a new reviewed version and an explicit
migration/presentation decision; it must not reinterpret `andalucia-v1` data.

## Review triggers

- an approved territorial profile needs additional or different vocabulary;
- evidence shows that a label pressures reporters into unsupported conclusions;
- a new workflow proposes automatic action from a taxonomy value;
- privacy or accessibility review identifies a minimisation or comprehension
  issue.
