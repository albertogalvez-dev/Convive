# ADR-0032: Separate SaaS 2.0 environments by purpose, with config-gated feature flags

- **Status:** Accepted
- **Date:** 5 September 2026
- **Related issue:** [#509](https://github.com/albertogalvez-dev/Convive/issues/509)
- **Depends on:** [ADR-0007](0007-use-postgresql-and-doctrine-for-persistence.md), [ADR-0013](0013-use-restic-with-off-host-object-storage-for-database-recovery.md), [ADR-0029](0029-use-the-platform-caddy-per-project-edge-for-public-ingress.md), [ADR-0030](0030-adopt-a-free-tier-eu-resident-minimal-footprint-infrastructure-model-for-saas-2.0.md), [ADR-0031](0031-enforce-saas-2.0-tenant-isolation-at-the-organisation-boundary-with-a-mandatory-query-filter.md)
- **Does not decide:** the migration workflow itself (ADR-0007), the backup/restore mechanism (ADR-0013), the release runbook's operator procedure for the demo (`docs/operations/deployment-release-and-rollback.md`, unchanged), or the real-data pilot go/no-go decision (#538 — this ADR only prepares the environment and flag mechanism that decision would use, it does not pre-empt it)

## Context

`docs/product/saas-2.0-charter.md` INV-16 requires the stable fictional demo and
SaaS 2.0 to run as separate deployments with separate configuration, accounts,
data stores, backups and domains, sharing no datastore and only reviewed source
code and visual direction. ADR-0030 already decided *where* SaaS 2.0 runs
(its own isolated VPS project). This issue is about what happens inside that
one SaaS 2.0 deployment as it grows from nothing to, eventually, real centres —
without ever quietly blurring "fictional" and "real", or forcing every
in-progress SaaS surface to be finished before it can be merged.

Today Convive has exactly two environment purposes, both demo-scoped: local
development (`apps/api/.env.dev`) and CI (`apps/api/.env.test`), plus the one
production deployment governed by `docs/operations/deployment-release-and-
rollback.md` and `.github/workflows/release.yaml` — a manual, reviewed
`workflow_dispatch` against the single demo VPS project. There is no
feature-flag mechanism anywhere in the codebase (`grep` across `apps/api/src`
and `apps/web/src` finds none) — every merged change to `main` has so far been
complete and immediately live in the demo. SaaS 2.0 will not have that luxury:
#508 alone spans several issues (#508, #526, #527, …) building one coherent
workspace, and charter DR-1 requires an owner-approved first working version
before an issue closes, not before every related issue closes.

## Decision drivers

- Make INV-16's separation a property of configuration and pipeline, not just
  of the VPS project boundary ADR-0030 already drew.
- Let SaaS 2.0 issues merge to `main` and go through the same real CI as
  everything else, without exposing an unfinished surface to whichever
  environment is "live" at that moment.
- Never let sandbox operation drift into processing real data by a config
  mistake instead of the deliberate #538 decision INV-11 requires.
- Keep the minimal-footprint, one-operator model from ADR-0030 — no new
  environment needs its own standing infrastructure until it is actually used.

## Decision

**1. Five environment purposes, four of them real today.**

| Purpose | What runs here | May process |
|---|---|---|
| `local` | A developer's own machine, either codebase | Fictional only |
| `test` | CI (`ci.yaml`), both codebases | Fictional/synthetic only |
| `demo-production` | The existing, unchanged demo deployment (ADR-0029) | Fictional only, always |
| `saas-sandbox` | SaaS 2.0's own deployment (ADR-0030), every centre before its own #537 gate | Minimum necessary adult account data (name, work email) under charter §4.2; **never** real safeguarding-domain data |
| `saas-pilot` | Reserved name only — no infrastructure exists for it yet | Real safeguarding-domain data, and only for a centre with its own passed #537 gate, after #538's written go decision |

`saas-pilot` is named now so that when #538 approves a real pilot, the work is
promoting an existing, already-governed environment purpose rather than
inventing a fifth thing under pressure. Naming it does not authorise standing
it up; ADR-0030's infrastructure and this ADR's config model extend to it only
when #538 says so.

**2. One environment-purpose setting, read once, gating everything else.**
Each deployment sets a single `APP_ENVIRONMENT_PURPOSE` value (one of the five
above) alongside the existing `APP_ENV`/`DATABASE_URL` configuration
(`apps/api/.env*`, extended the same way for `apps/web`). Every place in the
codebase that currently assumes "the demo" or "sandbox" reads this one value
rather than inferring purpose from a hostname, a feature flag, or which
database happens to be connected. `saas-sandbox` and `saas-pilot` never share a
value with `demo-production` on the same running instance — this is the
concrete, testable form of INV-16's "separate configuration" for this
dimension.

**3. Feature flags are config, not a runtime toggle.** A SaaS 2.0 surface not
yet ready for `saas-sandbox` is gated by a boolean environment variable read
through Symfony's existing parameter mechanism (`%env(bool:FEATURE_X)%`,
mirroring how `apps/api/.env*` already differ per purpose), set per deployment
in the same reviewed, version-controlled Compose/environment files ADR-0029's
release runbook already treats as the single source of truth — never an
admin-UI switch flipped live without a code review. This keeps a flag exactly
as auditable as any other config value: a change to it is a diffable,
reviewed commit, not an unreviewed action taken from inside the running
product. A flag is removed once the surface it gates has shipped everywhere it
gates — flags do not accumulate as permanent branches in the code.

**4. Migrations and rollback stay ADR-0007's mechanism, run per deployment.**
No change to the migration workflow itself. What changes is scope: `saas-
sandbox`'s migration history is its own (ADR-0030's separate database), applied
and rolled back independently of `demo-production`'s. A migration is written
once and applied to whichever deployments need it — `test` always, `saas-
sandbox` as SaaS 2.0 issues land, `demo-production` only for the security/
reliability/factual-correction changes charter §4.1 already permits there.

**5. Release and rollback reuse the demo's runbook shape, not its instance.**
SaaS 2.0 gets its own `workflow_dispatch` release action and its own record
template, following `docs/operations/deployment-release-and-rollback.md`'s
existing preflight/record/rollback structure (release identifier, image
digests, migration classification, backup/restore evidence, operator and
smoke-test result) against the `saas-sandbox` deployment instead of the demo's.
A `saas-sandbox` release never touches `demo-production`'s Compose project,
secrets, or VPS registration, and vice versa — the same "narrow, reversible
blast radius" ADR-0029 already established for the demo's ingress applies here
by having genuinely separate release actions, not a shared one parameterised
by target.

**6. No co-mingling is a structural guarantee, not a policy reminder.** Because
`saas-sandbox`/`saas-pilot` and `demo-production` never share a database
(ADR-0030), never share `APP_ENVIRONMENT_PURPOSE`, and are released by separate
actions against separate VPS projects, there is no code path or operator
action that could move data between them without deliberately connecting to
both — the same "no simultaneous credentials" rule ADR-0031 already states for
tenant isolation extends naturally to the demo/SaaS boundary at the pipeline
level too.

## Consequences

### Positive

- INV-16 becomes a concrete, checkable property (one config value, one
  database, one release action per purpose) rather than a description of
  intent.
- SaaS 2.0 issues can merge to `main` and pass real CI continuously, with
  incomplete surfaces gated by a reviewed config value rather than left
  unmerged on long-lived branches.
- `saas-pilot` has a name and a governance path (#537/#538) before it has any
  infrastructure, so the real-data transition is "turn on what's already
  designed" rather than a rushed new decision.

### Negative

- A `FEATURE_X` flag left in place after its surface ships is dead
  configuration if nobody removes it — code review has to catch this, since
  nothing enforces flag lifecycle automatically.
- Five named purposes (even with only four live) is one more thing a new
  contributor has to learn before touching SaaS 2.0 configuration.
- Running a second, independent release action doubles the release runbook
  surface an operator maintains, though each individual run stays as narrow as
  the demo's.

## Review triggers

Review before adding a sixth environment purpose, before `saas-sandbox` and
`demo-production` (or `saas-pilot`) ever share a database, config file, or
release action, before a feature flag is toggled anywhere other than a
reviewed configuration file, and before standing up any infrastructure for
`saas-pilot` ahead of #538's go decision.
