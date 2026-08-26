# Engineering-quality audit — 26 August 2026

**Scope:** the deployed fictional-data demonstration at reviewed revision
`70f4592996380997db140419ed57624e31dd3ebc` and the matching `main` source.

**Method:** this is an evidence audit, not a checklist of fashionable tools.
Each conclusion is reconciled against the executable code, automated checks,
architecture decisions, operational runbooks and the controlled deployment.
It does not claim GDPR, ENS, WCAG or penetration-test certification, and it
does not authorise real-data processing.

## Result

Convive has a strong, proportionate engineering baseline for a single-VPS,
fictional-data demonstration. No P0 or P1 engineering change is justified by
the current evidence. The remaining partial items are deliberately bounded
review work rather than release blockers.

| Area | Status | Evidence | Material gap | Recommended action | Priority |
| --- | --- | --- | --- | --- | --- |
| Architecture and maintainability | Proven | `docs/architecture/`, ADR-0029, `docs/development/code-quality.md`, PHPStan level 8 in CI | No demonstrated layering regression | Retain existing architecture/document checks | Do not implement |
| Risk-directed testing | Proven | `docs/testing/strategy.md`, `apps/api/tests`, `apps/web/e2e`, CI Backend/Frontend/E2E jobs | No meaningful critical-path branch was found without a negative test | Keep focused regression tests instead of a global coverage target | Do not implement |
| API-contract robustness | Proven | `docs/api/openapi.yaml`, `apps/api/bin/check-openapi-route-coverage.php`, CI OpenAPI diff/route coverage | Generated-client duplication would not add a distinct control | Keep OpenAPI drift and route coverage checks | Do not implement |
| Security and misuse resistance | Proven | `docs/security/threat-model.md`, `attachment-threat-model.md`, capability/session tests, public-boundary review #168 | Real-data operational ownership remains intentionally out of scope | Reassess only before a real-data pilot | Blocked / external |
| Supply chain and release integrity | Proven | pinned Actions in `.github/workflows/ci.yaml`, digest-pinned production Compose, release workflow and release record | No demonstrated benefit from signing or SBOM machinery for this small controlled path | Retain dependency review, audits and immutable digests | Do not implement |
| Observability and reliability | Proven | `infrastructure/observability/`, health checks, incident response and controlled release checks | No user-facing metrics dashboard; operational signals are intentionally bounded | Add metrics only after a measured operational need | Do not implement |
| Recovery, rollback and incident readiness | Proven | encrypted recovery workflow, `docs/operations/backup-and-recovery.md`, release/rollback runbook, deployed release record | RPO/RTO are operational commitments requiring a future owner | Keep restore evidence and rollback generation | Blocked / external |
| Performance and capacity | Proven | `docs/testing/performance.md`, bounded pagination, production gateway checks | No load test evidence | A synthetic load test is not proportionate before real traffic or capacity symptoms | Do not implement |
| Accessibility | Partial | `docs/testing/accessibility.md`, `accessibility-audit-2026-08-16.md`, semantic component/browser tests | A current screen-reader pass is still a manual review activity | Complete it as part of visual/interaction audit #351 | P2 |
| Data minimisation and privacy engineering | Proven for fictional mode | privacy register, attachment boundary, retention/governance drafts, fictional-only server guard | A controller must decide real-data retention/rights/processing | Keep real-data gates closed | Blocked / external |
| Developer experience and reproducibility | Proven | `CONTRIBUTING.md`, Compose topology, isolated E2E/recovery checks and production-boundary CI | No demonstrated hidden prerequisite in the supported path | Keep local instructions aligned with CI | Do not implement |
| Repository hygiene | Proven | `.gitignore`, secret examples, dependency/secret governance and CI documentation checks | No tracked secret or accidental generated artefact found in reviewed `main` | Continue normal review and dependency governance | Do not implement |

## Evidence by area

### 1. Architecture enforcement and maintainability — Proven

The codebase separates Domain, Application, Infrastructure and Presentation
modules, while the tests exercise the boundaries nearest to each risk. PHPStan
level 8 has no generated baseline and is a CI merge gate; the quality policy
explicitly forbids broad suppressions. Architecture diagrams and their
catalogue are checked by `npm run docs:check`.

No coupling finding warranted a file-size rule, a generic dependency linter or
a framework rewrite. Such controls would duplicate PHPStan, code review and
the existing architecture-document consistency check without evidence of a
missed defect.

### 2. Test effectiveness — Proven

The testing strategy assigns domain/application rules, database/HTTP contracts,
Angular rendering, browser journeys and operational exercises to separate
layers. Critical negative paths are covered for capability scope/revocation,
professional and organisation isolation, CSRF, role transitions, attachment
scan states, rate limits and fictional-demo read-only behaviour. Playwright
runs with zero retries, so a flaky journey cannot silently turn green.

Repository-wide coverage or mutation testing is not recommended: it would add
collection cost without identifying a current unprotected high-risk decision.
A narrowly scoped mutation experiment can be reconsidered if a future defect
shows a stable domain rule repeatedly escaping focused tests.

### 3. API contracts — Proven

The CI backend job regenerates OpenAPI and diffs it against the committed
contract, then exercises route coverage. HTTP integration tests protect
Problem Details, cookie/session boundaries and authorisation responses.
Generating a second client would duplicate the TypeScript, OpenAPI and E2E
controls rather than protect a demonstrated independent consumer.

### 4. Security assurance — Proven for the fictional demonstration

The threat model maps high-risk assets to concrete controls: server-side
professional sessions, opaque reporter capabilities, same-origin CSRF,
organisation/exact-case checks, Redis-backed limits and idempotency, private
quarantine plus scanner lifecycle, minimised logs and immutable release
materials. The post-release review in #168 confirmed the real hostname,
headers, host boundary and absence of exposed Convive database/cache/API ports.

The unresolved risks are not code gaps: institutional identity, accountable
operations, controller/DPO decisions and real-data governance remain explicit
gates. They must not be solved by pretending the fictional demo is compliant.

### 5. Supply chain and release integrity — Proven

GitHub Actions are SHA-pinned; Composer/npm audits and PR dependency review
run in CI. Production images are digest-pinned, and the guarded release records
the reviewed revision and immutable images. The deployed release record for
`70f4592996380997db140419ed57624e31dd3ebc` has a successful outcome.

SBOM publication, image signing and provenance attestation are reasonable
future controls only when Convive has additional deployers, consumers or a
compliance requirement. They are not a proportionate replacement for the
currently verified release contract.

### 6. Observability and reliability — Proven

Health checks, resource/backup/incident exercises and bounded operational
scripts cover the single-VPS topology. The release workflow verifies the real
gateway-to-PHP-FPM path; it does not mistake static configuration for runtime
health. Caddy is the sole public listener and Convive services are isolated in
the `px-convive-*` boundary.

Full distributed tracing or an always-on metrics stack would add data,
maintenance and privacy surface before a demonstrated operational demand.

### 7. Recovery and rollback — Proven

The encrypted backup/recovery workflow verifies a restoration in isolated
storage and clears session/capability material during recovery. The controlled
release keeps a compatible prior generation and records backup evidence,
migration class and outcome. The August release required no rollback; that is
not represented as a successful emergency exercise.

Future RPO/RTO promises require a named controller/operator and measured
operational commitments, so they remain outside the fictional-demo release.

### 8. Performance and capacity — Proven for present scale

The application uses pagination and bounded reads, and the performance
baseline guards bundles, key API paths and critical routes. No N+1 or unbounded
production hot path was demonstrated by the reviewed code and checks.

A load test is not recommended merely to manufacture a number for a
fictional-data demo. It becomes justified when traffic, a latency regression or
an onboarding plan provides an explicit workload to model.

### 9. Accessibility — Partial, P2

Automated semantic and keyboard coverage, responsive review guidance and the
manual August audit are meaningful controls. They do not replace a fresh
screen-reader pass after the current public/professional visual refinements.
Issue #351 owns that route-by-route review; it should record pass/finding/
deferred outcomes without claiming WCAG certification.

### 10. Privacy and data minimisation — Proven for fictional mode

The public and production boundaries are fictional-only. Attachments stay
private and scanner-gated; reports/secrets are excluded from logs; recovery
and retention material documents real-data decisions as unresolved rather than
inventing approval. This is the correct boundary for the current product.

### 11. Developer experience and reproducibility — Proven

`CONTRIBUTING.md`, the versioned Compose files, CI jobs and isolated E2E/recovery
stacks define the supported paths. CI runs the same static-analysis, contract,
schema, build and browser categories expected locally. The repository avoids
Doctrine fixtures because they could purge development data.

### 12. Repository hygiene — Proven

The reviewed source contains examples rather than populated credentials, and
the deployment contract keeps secrets/runtime evidence outside Git. The audit
found no evidence requiring history rewrite or credential rotation. Normal
review, dependency governance and repository checks remain sufficient.

## Decision

No implementation follow-up is opened from this audit. The only active quality
work that materially remains is the route-by-route visual and screen-reader
review already owned by #351, followed by its truthful README captures (#334/
#354). The project should prioritise those review outcomes and real product
feedback over additional engineering tooling.
