# Convive SaaS 2.0 delivery template

This is the reusable, fill-in template `docs/product/saas-2.0-charter.md`
promises at #507. It generalises the charter's §6.0 expectation format and its
DR-1 delivery rule into one document so that writing a SaaS 2.0 issue, and
later the pull request that closes it, is copying a template rather than
re-deriving the shape each time.

It does not replace the charter. Every section below points back at the
charter clause it operationalises; where this document and the charter
disagree, the charter is the authority and this document is out of date.

## How to use this

1. Before opening or picking up a SaaS 2.0 issue, read the charter's own
   instructions in its introduction ("Using this charter to implement an
   issue"): §3 for terms, the issue's §10 row, each cited `R/T/C/P-*` entry in
   §6, each cited `INV-*` in §5.
2. Copy **section A** below into the issue body (or confirm it is already
   there) and fill it in.
3. If the issue's §10 row lists a `DR-1 artifact`, or the issue otherwise
   touches the `[owner decision]` surface set (charter §3.7), copy **section
   B** into the issue and do not start coding until it is complete.
4. When opening the pull request, copy **section C**'s evidence menu into
   `.github/PULL_REQUEST_TEMPLATE.md`'s existing "Verification evidence"
   section, keeping only the evidence types the change actually touches.

## A. Expectation record (generalises charter §6.0)

For each expectation the issue delivers, record:

```
### <ID, e.g. R-9> — <short title>

`[rule]` and/or `[owner decision]` · depends on <INV-N, …> · implements <#issue>

- **+** GIVEN <state>, WHEN <action>, THEN <observable outcome>.
- **−** WHEN <the failure or denial condition>, THEN <the safe outcome — a
  denial, an error state, or nothing observable changes>.
```

Rules for filling this in, carried over unchanged from charter §6.0:

- An expectation is an **observable outcome** — verifiable by inspection or a
  test, never by production analytics or user tracking.
- Every entry needs at least one negative case. A feature with no way to
  observe it failing safely is not yet specified.
- Tag honestly: `[rule]` if no owner discretion remains, `[owner decision]` if
  DR-1 governs the exact shape, both if a rule bounds an owner-decided detail.
- Cite the `INV-*` invariants this expectation would violate if it regressed —
  this is what a reviewer checks the negative case against.
- An `[owner decision]` entry does not ship until section B is complete for it.

## B. DR-1 checklist (generalises charter DR-1)

Copy this checklist onto the issue for every `[owner decision]` surface it
touches. The charter's DR-1 section is the authoritative rule text; this is
the fill-in-the-blanks form of it.

```
### DR-1 — <surface name>

- [ ] **Owner intent statement recorded** — the owner's direction in their own
      words: what the surface looks like, what it does and does not do (data
      shown, where each action leads, empty and error states). For a surface
      with a demo counterpart: what to keep / change / drop.
- [ ] **Approved artifact attached** — wireframe, sketch, marked-up demo
      screenshot, or literal strings in order for a text-only surface. If
      options were proposed, the owner's pick and a one-line reason the others
      were set aside are recorded.
- [ ] **Owner approval comment** — an explicit comment from the owner account
      approving the artifact (with changes, if any). A reaction or unrecorded
      chat approval does not count.
- [ ] **Completeness check** — someone other than the artifact's author could
      build this without guessing any owner-facing choice.
```

Checkpoints to keep on the issue, not just in this file:

- **Proposal** — owner reviews the artifact before any code is written.
- **First working version** — owner uses the running surface with fictional
  sandbox data (not a screenshot) before the issue closes and before any
  dependent validation issue runs.
- Divergence from the approved artifact is corrected before close, or the
  artifact is revised with fresh owner approval — silent drift blocks release.
- **Unanticipated decision mid-build?** Stop. It goes to the owner, not an
  implementer default. Add it to charter §3.7's `[owner decision]` surface set
  and §10 once resolved, so the tagged set stays complete (this is how a gap
  found during #504–#544's implementation gets folded back into the charter).

## C. Evidence menu (generalises INV-17/INV-18 and the PR template)

Pick every type the change actually exercises; drop the rest. These feed
`.github/PULL_REQUEST_TEMPLATE.md`'s "Verification evidence" section directly
— do not invent a second format there.

- **Visual** — screenshot or short recording of the built surface against its
  approved DR-1 artifact (section B), or against the relevant expectation's
  positive case if no artifact was needed.
- **Accessibility** — result against `docs/testing/accessibility.md`'s
  automated WCAG 2 A/AA and 2.1 AA rules, full keyboard operation, and
  screen-reader labelling (INV-17). A regression on a critical journey blocks
  release.
- **Language** — for any surface reaching a supported locale, confirmation
  against `docs/content/plain-language-standard.md` and the INV-18 completeness
  gate: a locale is never announced complete while partially or
  machine-translated.
- **Contract** — OpenAPI diff, migration file, or other schema/API evidence
  for a change with an external or cross-service contract.
- **Operational** — the required GitHub checks already listed in the PR
  template (`Backend`, `Frontend`, `Infrastructure`, `Dependency review`,
  `Encrypted recovery`, `End-to-end`), plus anything issue-specific: a
  recovery-drill log for a backup/recovery change, a negative cross-tenant
  test for anything touching INV-1, and so on.

## Changelog

- 2026-09-05 — created, closing #507. Charter references at §1 (last
  paragraph), §3.7, DR-1's origin note, §6.0 and §5's INV-18 now point here.
