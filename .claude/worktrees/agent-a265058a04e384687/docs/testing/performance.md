# Performance baseline and budgets

## Purpose and limits

These budgets protect the fictional Convive demonstration against obvious
regressions while its critical journeys, API and single-VPS deployment model
are still evolving. They are deliberately small in number, run only against
the isolated fictional E2E dataset and are not a promise of public-production
latency, capacity or availability.

Measurements run through the same Compose-based stack that powers the Chromium
E2E journey and exercise the Angular development proxy and Symfony API. They
are useful as a regression signal but are not a substitute
for representative VPS, tunnel and internet measurements before release.

## Baseline (10 August 2026)

The production Angular build reported an initial raw transfer of **438.66 kB**
(**106.84 kB** estimated transfer) before this baseline was added. The existing
component-style limits are retained. The first complete isolated CI run
(`31411206898`, 10 August 2026) measured:

| Path | Median | Maximum or readiness |
| --- | ---: | ---: |
| Health endpoint | 9.4 ms | 23.1 ms maximum |
| Fictional public reporting profile | 20.7 ms | 21.9 ms maximum |
| Public reporting ready | — | 276.1 ms |
| Professional dashboard ready | — | 568.2 ms |

The API and render values are printed by the isolated CI journey for every pull
request because runner timing is variable; use their median and maximum,
rather than a single historical number, when investigating a change.

All measurements use only the reserved fictional organisation
`ORG_DEM0000000000000`, its fictional public profile and the fictional triage
professional. No report content, secrets or real identities are collected or
written to performance output.

## Initial-route headroom (13 August 2026)

Immediately before issue #169, the production build's initial payload was
**500.01 kB** raw (**118.35 kB** estimated transfer), which exceeded the
500 kB warning threshold by 10 bytes. The root route eagerly imported reporter
and professional application screens even when a visitor only opened the
public home page.

The delivered route boundary keeps the public home eager and lazy-loads public
information, reporter, professional-access, professional-workspace and
not-found screens. The same production build now reports **101.34 kB** raw
(**26.47 kB** estimated transfer) for the initial payload: a **398.67 kB**
(79.7%) raw reduction. Those route chunks remain covered by the no-retry
isolated browser journey; this is not a claim that a complete reporting or
professional route costs only the initial payload.

The target is to retain at least the new 150 kB warning boundary and 175 kB
error boundary for the initial shell. Any future eager dependency or public
home addition must report its measured initial impact in its pull request and
be lazy-loaded where it is not required for first render. Do not consume this
headroom by raising a budget without an evidence-backed, separately reviewed
exception.

## Enforced budgets

| Risk | Measurement | Budget | Gate |
| --- | --- | --- | --- |
| Slow initial web payload | Angular production initial bundle | warning at 150 kB; error at 175 kB | Frontend build |
| API/proxy regression | five requests each to health and the fictional public profile | median <= 250 ms; every sample <= 750 ms | Isolated Chromium E2E |
| Public-reporting readiness | navigation until the fictional organisation name is visible | <= 3,000 ms | Isolated Chromium E2E |
| Professional-dashboard readiness | login navigation until the dashboard heading is visible | <= 3,000 ms | Isolated Chromium E2E |

The API probe uses five requests so one sample cannot hide a persistent
regression. Its maximum guard is intentionally wider than the median guard to
avoid treating ordinary shared-runner noise as a product failure while still
detecting a stalled request. The two page budgets include the actual route data
needed for the user to begin, not just an HTML response.

## How the gate runs

The `End-to-end` CI job creates an empty isolated Compose project, applies
migrations, seeds the reserved fictional data and runs Playwright without
retries. The performance test is part of that same journey and prints its
measured values in the CI log. It does not require, read or modify a local
developer database.

For local discovery only, from `C:\Convive\convive\apps\web` with a
verification-only fictional value, run:

```powershell
$env:E2E_PROFESSIONAL_PASSWORD = 'verification-only-fictional-password'
npm run e2e -- --list
```

Run the full journey only against the dedicated isolated E2E Compose stack; do
not tear down a developer's active stack or load Doctrine fixtures to obtain a
measurement.

## Investigating a regression

1. Confirm the failing path and metric in the CI output; do not rerun solely to
   obtain a pass because critical E2E tests have zero retries.
2. Compare the production build output, API query/serialisation work and route
   dependencies with the last green run using the same fictional dataset.
3. Fix the cause or reduce the changed scope. An exception needs a linked issue,
   a written reason, a bounded expiry and explicit remeasurement; it must not be
   implemented as a global test suppression or a silent budget increase.
4. Before public release, repeat the relevant checks on the provisioned
   single-VPS/tunnel route and record the environment, date and fictional input
   separately. Those operational results refine planning; they do not turn the
   CI budget into a public performance guarantee.
