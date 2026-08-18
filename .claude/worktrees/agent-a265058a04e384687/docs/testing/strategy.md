# Layered testing strategy

Convive uses a small set of complementary test layers. Each test protects the
nearest boundary that can demonstrate the risk; a higher layer is added only
when the risk crosses that boundary. This keeps feedback useful without
repeating every assertion in every suite.

## Test layers and responsibilities

| Layer | Protects | Primary evidence | CI responsibility |
| --- | --- | --- | --- |
| PHP domain and application tests | Validation, state transitions, value objects and use-case rules | PHPUnit unit tests with explicit collaborators | `Backend` |
| PHP persistence and HTTP integration tests | Doctrine mappings, migrations, API contracts, cookies, authorisation and organisation isolation | PHPUnit against the dedicated test PostgreSQL database | `Backend` |
| API contract validation | Published OpenAPI remains aligned with controllers and serializers | Generated OpenAPI diff | `Backend` |
| Angular component tests | Rendering, user input, client-side state and safe error feedback | Vitest component tests | `Frontend` |
| Browser end-to-end journey | Reporter-to-professional conversation, credential safety and protected cross-role behaviour | Playwright against an ephemeral Compose project | `End-to-end` |
| Operational exercises | Development topology, release boundary, encrypted recovery and fictional demo readiness | Compose smoke checks and reviewed recovery exercise | `Infrastructure` and `Encrypted recovery` |

Static analysis, formatting and dependency audits are preventive quality gates,
not substitutes for behaviour tests. Their exact commands are defined in the
[code-quality baseline](../development/code-quality.md).

## Critical boundaries

Every change must identify the narrowest affected layer. The following risks
always require automated evidence before merge:

- anonymous reporting, access-secret exchange, report capability scope and
  access revocation;
- organisation and professional-role isolation, including a negative access
  assertion where a protected route changes;
- reporter and professional conversation history, including what is visible to
  each party;
- migration, Doctrine mapping and OpenAPI changes;
- the public fictional reporter-to-professional journey and its redacted
  credential handling when a browser-visible flow changes;
- backup/recovery and release boundaries when their automation changes.

Accessibility is a quality boundary too. Component and browser tests must use
semantic roles, labels and keyboard interaction where a changed control has an
accessible behaviour. The [accessibility baseline](accessibility.md) defines
the project-wide automated checks and manual matrix.

The [performance baseline and budgets](performance.md) defines bounded bundle,
API and critical-route regression gates for the isolated fictional demo.

## Fictional data and isolation

Tests never use real student, family, professional or school data. Unit tests
construct their own fictional values. Backend integration tests run with
`APP_ENV=test` and the dedicated `convive_test` database; their setup either
uses test transactions or removes only records it created. They must not load
Doctrine fixtures, because fixtures can purge development data.

The browser suite creates the separate `convive-e2e` Compose project, applies
migrations to its own volumes and seeds only the guarded deterministic fictional
dataset with a fresh masked professional password. It removes that project and
its volumes in an `always()` cleanup step. Recovery exercises use their own
isolated storage and database targets. No test job shares a demonstration or
production data store.

## Coverage as diagnostic evidence

Convive does not enforce a repository-wide line-coverage percentage. The local
and CI PHP runtimes deliberately run without a coverage driver, and a single
percentage would reward shallow tests while hiding browser, security and
operational gaps. Instead, a behaviour-changing pull request must show the
relevant risk in the appropriate layer and add a focused regression test for a
fixed defect.

Coverage reporting can be introduced for a stable, owned decision only when it
answers a specific question (for example, a critical module whose untested
branch is demonstrably risky). Any future threshold must state its scope,
owner, collection cost and the behaviour it is intended to protect. It must not
be used as a merge target by itself.

## Flaky-test handling

Playwright has zero retries in local and CI execution. A failure is therefore
visible to the pull request and cannot be silently converted into a pass. Do
not respond by rerunning until green, increasing timeouts without evidence, or
adding an untracked skip. Investigate isolation, ordering, time dependency and
real application failure first; preserve only redacted diagnostic material.

A temporary skipped test requires a linked issue, a narrowly documented reason
and a removal condition. It does not make the underlying acceptance criterion
complete. The issue owner must either repair the test in the same increment or
record the follow-up before the pull request can claim the journey is covered.

## Local and CI workflow

Run the relevant local commands before opening a pull request. The
`Backend`, `Frontend`, `Infrastructure`, `Dependency review`, `Encrypted
recovery` and `End-to-end` CI jobs then reproduce their respective boundary on
a clean runner. Treat a
local/CI mismatch as a defect in configuration or isolation, not as evidence to
be ignored. Contributor commands and the required PR evidence are maintained
in [CONTRIBUTING.md](../../CONTRIBUTING.md).
