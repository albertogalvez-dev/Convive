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
| Reporter (`/r/ORG_DEM0000000000000`) | The full fictional journey and its completed example render locally without a persistence request. | No horizontal overflow. | Form controls and local completion state expose their labels. | Pass after #454 release |
| Professional access | Role selector and entry action are labelled and a demonstration session reaches the dashboard. | Desktop session was retained while checking mobile; no overflow on the resulting dashboard. | Combobox and entry button have accessible names. | Pass |
| Professional dashboard and communications | Seeded recent communications, counts and labelled navigation loaded without errors. | No horizontal overflow. | Links expose distinct case/report destinations. | Pass |
| Case list | Eight seeded cases, filters and state labels loaded without errors. | Not separately re-run after desktop check; responsive coverage is deferred to the next visual pass. | Inputs and filters have labels. | Pass with deferred mobile visual review |
| Case detail | Tasks, people, activity and private evidence records loaded with Spanish status and source labels. | Not separately re-run after desktop check; responsive coverage is deferred to the next visual pass. | Section headings are present. | Pass after #456 release |
| Settings and notices | Session controls, account metadata and settings heading loaded without errors. | No horizontal overflow; session action is visible and named. | Buttons and headings have accessible names. | Pass |

## Findings and disposition

1. **#454 — reporter journey absent in fictional mode.** This was repaired by
   the dedicated local-only journey and is closed. The public release keeps
   report, attachment and access-grant requests non-persistent.
2. **#456 — case-detail labels leaked implementation vocabulary.** This was
   repaired by the published Spanish label mapping and is closed. The next
   visual pass should still check translations in every public locale.

## Deferred review

- A fresh manual screen-reader pass remains required after the deployed
  corrections. This is intentionally not called a conformance
  certification.
- Alberto’s final subjective visual review remains the correct place for
  brand, spacing and editorial taste decisions; this audit records functional
  and reproducible evidence rather than replacing that review.
