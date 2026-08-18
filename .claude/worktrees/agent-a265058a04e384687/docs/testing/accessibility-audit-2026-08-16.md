# Manual accessibility audit — 16 August 2026

**Auditor:** repository maintainer, assisted review.
**Scope:** the public website, the public information set, the fictional
demonstrations and the anonymous reporting entry state.
**Method:** the manual verification matrix in
[accessibility.md](accessibility.md), executed against a locally running
development stack with fictional data only.
**Standard:** WCAG 2.2 AA as an objective, not as a conformance claim. See the
limitations section: this audit does not establish conformance.

## What was executed

| Row of the matrix | Viewport or setting | Result |
|---|---|---|
| Reading order, landmarks, heading structure | 1280 x 720 | Pass. One `h1` per page, no heading-level skips, `banner` / `navigation` / `main` / `contentinfo` present, helplines exposed as a list |
| Accessible names on labelled regions | 1280 x 720 | Pass. Every `aria-labelledby` resolves to an existing element |
| Reflow, no two-dimensional scrolling | 640 x 360 (1280 x 720 at 200%) | Pass on every public page checked |
| Mobile layout and overflow | 375 x 812 | Pass, no horizontal overflow |
| Compact layout | 320 x 568 | Pass, no horizontal overflow |
| Target size, WCAG 2.2 SC 2.5.8 | 375 x 812 | Pass by the spacing exception — see the note below |
| Text contrast, SC 1.4.3 | 375 x 812 and 1280 x 720 | **One failure found and fixed** — see F-01 |
| Form control labelling | 1280 x 720 | Pass. No unlabelled control found |
| Status and error announcement, SC 4.1.3 | 1280 x 720 | **One gap found and fixed** — see F-02 |
| Reduced motion | `prefers-reduced-motion: reduce` | Pass with a caveat — see the observation below |

Pages covered: the public home, `/blog/`, `/demostracion/`,
`/demostracion/profesional/`, the five public information documents, `/contacto/`
and the reporting entry at `/r/{identifier}` in its invalid-link state.

## Findings

### F-01 — Step number failed contrast (fixed)

The numbered badge on the current and completed steps of both guided
demonstrations rendered white text on `#1ca2db` at 12.8 px. Measured ratio
**2.9:1** against the 4.5:1 required for text at that size.

Fixed by moving the badge to `#176f9c`, the accent already used for links, which
measures **5.54:1** against white. Applied to `public-demo.scss` and
`professional-demo.scss`, and re-measured as passing.

### F-02 — Reporting entry error states were silent (fixed)

The reporting form resolves an organisation profile and then replaces the page
content with one of several terminal states. In the invalid-link, unavailable
and disabled states the content swapped with no announcement and without moving
focus, so a screen-reader user who followed a broken link would not learn that
the form had failed to open.

Fixed by marking the three error states as `role="alert"` and the
fictional-demonstration state as `role="status"`, matching the pattern the same
component already uses for field validation. Verified in the browser: the
invalid-link state is now exposed as an alert.

## Notes on things that looked like findings and were not

**Target size.** Several standalone links measure 16–18 px tall on mobile
(`Área profesional`, `Volver al inicio`, the footer mail address), which is below
the 24 px minimum of SC 2.5.8. They pass through the spacing exception: nearest
neighbouring target centres measured 85 px, 208 px and 337 px away, all far
beyond the 24 px the exception requires. Recorded so a future reviewer does not
re-open it, and so a future layout change that tightens the spacing is known to
put these at risk.

**Region names.** An accessibility-tree reader used during the audit displayed
several landmark regions without names. Checking the DOM directly showed every
`aria-labelledby` resolving to an existing element with text. The tool did not
resolve `aria-labelledby`, only `aria-label`. No defect.

## Observation, not a finding

The global reduced-motion rule in `styles.scss` sets `animation-duration` and
`transition-duration` to `0.01ms !important` rather than `animation: none`. This
is the widely used pattern and is imperceptible in practice, but it shortens
motion rather than removing it. Worth a deliberate decision if a future criterion
is written as removal, since components that set `animation: none` locally are
overridden by the global `!important`.

## Limitations — what this audit does not establish

**No screen-reader verification was performed.** Every check above is structural,
measured or visual. Name, role and value as actually announced by NVDA, JAWS or
VoiceOver, the usability of the reading order in practice, and whether the alert
roles added in F-02 announce usefully rather than merely fire, all remain
unverified. Those rows of the matrix require a human with assistive technology
and are listed in [the screen-reader checklist](accessibility-screen-reader-checklist.md).

**The authenticated professional workspace was not manually audited in this
pass.** It is covered by the automated Axe pass in the End-to-end job — the
professional dashboard and, since #235, the workspace introduction dialog — but
its manual rows at 200% zoom, 320 px and keyboard-only operation were not
executed here.

**The complete reporting journey beyond the entry state was not audited.**
Submitting a fictional report requires the seeded demonstration dataset, which
was not present in the local stack used. The End-to-end job exercises the ready
form, its keyboard operation, its validation alert and a 390 x 844 viewport on
every run.

Because of those three gaps, **this audit does not establish WCAG 2.2 AA
conformance**, and the public accessibility notice continues to say so.
