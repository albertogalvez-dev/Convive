# Visual and interaction audit — 26 August 2026

## Scope and method

This is a route-by-route review of the public fictional demonstration at the
published `conviveaula.com` and `app.conviveaula.com` hostnames. It used a
desktop viewport and a 390×844px mobile viewport, semantic DOM inspection,
visible interactive controls and browser-console checks. It uses fictional
data only and stores no screenshots, credentials or sessions in Git.

This evidence is a product review, not a claim of WCAG conformance or a
substitute for testing with assistive-technology users.

## Route results

| Journey | Desktop | 390px mobile | Keyboard/semantic state | Result |
| --- | --- | --- | --- | --- |
| Public home | Main landmark, hierarchy, locale selector, footer links and professional entry loaded without console errors. | No horizontal overflow. | Navigation and language controls expose semantic links/select. | Pass |
| Public demonstration | Both reporter and professional paths, completed-example link and poster content loaded. | No horizontal overflow. | Links expose their destinations. | Pass |
| Public information (`/privacidad`) | Heading hierarchy and public information loaded without errors. | No horizontal overflow. | Page title and main heading match the route. | Pass |
| Reporter (`/r/ORG_DEM0000000000000`) | Only the safety notice rendered; no journey could be inspected. | Same behaviour, no overflow. | Safe status was announced but the required controls were absent. | Finding — #454 |
| Professional access | Role selector and entry action are labelled and a demonstration session reaches the dashboard. | Desktop session was retained while checking mobile; no overflow on the resulting dashboard. | Combobox and entry button have accessible names. | Pass |
| Professional dashboard and communications | Seeded recent communications, counts and labelled navigation loaded without errors. | No horizontal overflow. | Links expose distinct case/report destinations. | Pass |
| Case list | Eight seeded cases, filters and state labels loaded without errors. | Not separately re-run after desktop check; responsive coverage is deferred to the next visual pass. | Inputs and filters have labels. | Pass with deferred mobile visual review |
| Case detail | Tasks, people, activity and one private evidence record loaded. Raw enum labels and an English source title were visible. | Not separately re-run after desktop check; responsive coverage is deferred to the next visual pass. | Section headings are present. | Finding — #456 |
| Settings and notices | Session controls, account metadata and settings heading loaded without errors. | No horizontal overflow; session action is visible and named. | Buttons and headings have accessible names. | Pass |

## Findings and disposition

1. **#454 — reporter journey absent in fictional mode.** The route correctly
   avoided persistence but was not a usable demonstration. The dedicated
   change restores the full client-side flow while preventing report,
   attachment and access-grant requests. Its tests must pass before the
   finding is considered resolved.
2. **#456 — case-detail labels leaked implementation vocabulary.** The
   dedicated change maps communication enums through the published locale and
   corrects the source wording stored for the Spanish fictional demo. Its
   migration and tests must pass before the finding is considered resolved.

## Deferred review

- A fresh manual screen-reader pass remains required after the two corrections
  above are deployed. This is intentionally not called a conformance
  certification.
- Alberto’s final subjective visual review remains the correct place for
  brand, spacing and editorial taste decisions; this audit records functional
  and reproducible evidence rather than replacing that review.
