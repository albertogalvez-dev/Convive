# Operational acceptance pack (fictional centre)

A repeatable set of role-based scenarios run against the fictional demonstration
before a release. It exists because automated tests prove technical boundaries
one at a time, while a centre needs to see that the whole journey holds together
for each role, including the paths where the product must say no.

**This pack uses fictional data only.** The accounts and the centre come from
`FictionalDemoDataset`; no scenario introduces real personal data, and none is
evidence that a real-data pilot may proceed.

## How to run it

Each scenario states its role, its starting point, the steps and the expected
outcome. Record the outcome verbatim — including anything unexpected — and
classify every deviation using the taxonomy below before deciding what to do
about it.

Scenarios marked **automated** already run in CI; the reference names the test
that covers them, so a reviewer can read the assertion rather than repeat the
click-through. Scenarios marked **manual** need a person: they involve judgement
about wording, comprehension or assistive-technology behaviour that an assertion
cannot settle.

### Fictional actors

| Actor | Account | Role |
| --- | --- | --- |
| Reporter | none — anonymous | — |
| Lucía | `lucia.demo@convive.example` | Triage professional |
| Carlos | `carlos.demo@convive.example` | Administrator |

Case-level roles (lead, contributor, observer) are assigned per scenario and are
independent of the organisation role, per ADR-0008.

### Classifying an outcome

Every deviation is exactly one of these, and the distinction decides who owns it:

- **Product defect** — the software does something other than what the recorded
  decision says. Owner: engineering. Becomes an issue with reproduction steps.
- **Policy decision** — the software behaves as built, but the behaviour itself
  needs a decision that has not been made. Owner: the person who can make that
  decision. Becomes a comment on the relevant issue, never a silent fix.
- **Training gap** — the software behaves correctly and the tester could not
  tell. Owner: whoever writes the onboarding material (#193). Becomes a note in
  that material, not a code change.

Classifying a training gap as a defect wastes engineering time; classifying a
defect as a training gap ships the defect. When a scenario is ambiguous, record
it as a policy decision and let the owner reclassify.

### Prioritising a finding

Priority follows from what the deviation exposes, not from how hard it is to
fix:

- **Blocking** — a boundary failed: data reached someone who should not have it,
  a denial did not deny, or a safeguarding-relevant action was lost. The release
  does not proceed until it is resolved or explicitly accepted in writing by the
  owner.
- **High** — a journey cannot be completed by a role that needs it, or the
  interface states something untrue about what the product does.
- **Normal** — friction, unclear wording or a missing convenience that does not
  block the journey or misstate behaviour.

A blocking finding is never downgraded because the release date is close. That
trade is the owner's to make explicitly, in writing, on the issue — not an
implementer's to make quietly.

## Reporter journey

### R1 — Submit a report and return with the access code · automated

**Start:** the public reporting page for the fictional centre.
**Steps:** submit a report, keep the access code, return through the follow-up
route and add a further message.
**Expect:** the report is accepted without asking for identity; the code opens
the same conversation; the professional side shows the follow-up.
**Covered by:** `e2e/public-reporting.spec.ts`, "completes the fictional
reporter-professional conversation loop".

### R2 — A lost access code cannot be recovered · automated

**Start:** the follow-up route with no code.
**Steps:** attempt to reach a report without the code, and with an invalid one.
**Expect:** no route reveals whether a report exists. The limitation is stated
before the code is issued, not after it is lost.
**Covered by:** report access capability tests in `apps/api/tests/Reporting`.

### R3 — The anonymity limits are understood, not just displayed · manual

**Start:** the public reporting page.
**Steps:** ask a reader unfamiliar with the product what the service knows about
them, and what happens if they lose the code.
**Expect:** they answer correctly from the page alone.
**Why manual:** this measures comprehension. An assertion can only prove the
words are present.

## Triage professional (Lucía)

### T1 — Work an assigned case end to end · automated

**Start:** signed in as Lucía with a lead assignment.
**Steps:** open the case, plan a task from a reviewed template, record a
communication, transition the lifecycle with a reason, generate a document.
**Expect:** each step succeeds and appears in the audit trail.
**Covered by:** `ProfessionalCaseControllerTest`, task, communication, lifecycle
and document tests.

### T2 — A case she is not assigned to does not exist for her · automated

**Start:** signed in as Lucía.
**Steps:** request a case in her centre that she holds no assignment for.
**Expect:** the same "not available" response as a case that does not exist —
the denial does not confirm the case is real.
**Covered by:** `ProfessionalCaseControllerTest` unavailable-state tests.

### T3 — Search returns nothing she could not already reach · automated

**Start:** signed in as Lucía.
**Steps:** filter by a colleague whose cases she does not share, and search a
term appearing only in another professional's note.
**Expect:** empty results, not a hint that matches exist elsewhere.
**Covered by:** `testTheResponsibleFilterNarrowsToSharedCasesAndNeverRevealsOthers`
and `testTheNoteSearchNeverMatchesAnotherProfessionalsNote`.

### T4 — Declaring an absence does not hand over her cases · automated

**Start:** signed in as Lucía with an active case.
**Steps:** record a planned absence covering today, then reopen the case.
**Expect:** the case is still hers; nothing was transferred and no access was
removed.
**Covered by:** `testRecordingAnAbsenceNeitherMovesTheCaseNorRemovesOwnAccess`.

## Contributor and observer

### C1 — A contributor works the case but cannot take the evidence · automated

**Start:** signed in as a professional holding a contributor assignment.
**Steps:** open the case, then attempt to download an evidence file.
**Expect:** the case and the evidence metadata are visible; retrieval is
refused, and the interface says who may retrieve it rather than offering a link
that fails.
**Covered by:** `testEvidenceMetadataIsVisibleToAContributorWhoCannotDownloadIt`.

### O1 — An observer reads and nothing more · automated

**Start:** signed in as a professional holding an observer assignment.
**Steps:** open the case, then attempt to generate a document and download
evidence.
**Expect:** reading succeeds; both extractions are refused.
**Covered by:** `testAnObserverCannotGenerateADocumentFromACaseTheyCanRead` and
`testAnObserverCannotDownloadEvidenceEither`.

## Administrator (Carlos)

### A1 — Administer accounts and see what needs a continuity decision · automated

**Start:** signed in as Carlos.
**Steps:** create a fictional account, change a membership, then read the
continuity list.
**Expect:** account administration works, and the continuity list names cases
needing a decision with reference, status, responsible and reason.
**Covered by:** `CaseContinuityControllerTest` and the account administration
tests.

### A2 — Administration does not open case content · automated

**Start:** signed in as Carlos, who administers the centre but holds no case
assignment.
**Steps:** from the continuity list, attempt to open one of the named cases.
**Expect:** refused. The list tells him a case needs reassignment; it does not
let him read it. Restoring continuity means assigning it, which is audited.
**Covered by:** `testTheContinuityListNamesACaseTheAdministratorStillCannotOpen`,
written while assembling this pack — the scenario had no test until then, which
is exactly the kind of gap a role-based walkthrough is meant to expose.

### A3 — Another centre's cases are invisible · automated

**Start:** signed in as an administrator of a different fictional centre.
**Steps:** request the first centre's continuity list.
**Expect:** empty — not an error that would confirm the centre has cases.
**Covered by:** `testAnAdministratorOfAnotherCentreReadsNothing`.

## Cross-cutting

### X1 — Critical journeys are usable by keyboard and screen reader · manual

**Start:** the reporting journey and the professional workspace.
**Steps:** complete both without a pointer, then with a screen reader.
**Expect:** every action is reachable, focus is visible and never trapped, and
errors are announced.
**Why manual:** assistive-technology behaviour needs a person. The automated
Axe pass in `public-reporting.spec.ts` covers only detectable violations.
**Instrument:** see the checklist in #167.

### X2 — An overdue task is visible to the people who can act · automated

**Start:** a fictional case with a task past its due date.
**Steps:** read the overdue view as the lead, then the continuity list as the
administrator.
**Expect:** both surface it, with the wording describing work rather than
judging the case.
**Covered by:** `testAnOverdueTaskRaisesTheCaseEvenWithAPresentResponsible`.

### X3 — Nothing in the demonstration claims to be official · manual

**Start:** the public pages and a generated document.
**Steps:** read them as an outsider.
**Expect:** the fictional-demonstration status is unmistakable, and no document
or page reads as an official form or an emergency channel.
**Why manual:** the automated check asserts the marker is present; whether it
*reads* as unmistakable is a judgement.

## Recording a run

Copy this table into the release record (#166) and fill one row per scenario.

| Scenario | Run by | Date | Outcome | Classification | Priority | Owner | Follow-up |
| --- | --- | --- | --- | --- | --- | --- | --- |
| R1 | | | | | | | |

A run is complete when every scenario has an outcome and every deviation has a
classification, a priority, a named owner and a linked follow-up. An incomplete run is reported as incomplete rather
than summarised as a pass.
