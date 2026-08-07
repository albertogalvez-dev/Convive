# Dependency update management

Dependabot is configured in [`.github/dependabot.yml`](../../.github/dependabot.yml) for all three ecosystems in use: Composer (`apps/api`), npm (`apps/web`) and GitHub Actions.

## Cadence and grouping

- **Weekly, Mondays.** Frequent enough to keep advisories from piling up, infrequent enough not to bury the single reviewer in daily noise.
- **Minor and patch updates are grouped** into one PR per ecosystem per week. These are the routine, low-risk updates; reviewing them as a batch is proportionate to their risk.
- **Major updates are never grouped.** Each major-version bump arrives as its own PR, so it gets individual attention rather than being nodded through inside a batch of otherwise-routine changes.
- **GitHub Actions updates are grouped together** (`patterns: ["*"]`) since Actions versions rarely introduce breaking changes and reviewing them individually would be disproportionate.
- `open-pull-requests-limit: 5` per ecosystem keeps the queue bounded; it is a ceiling, not a target.

## Review expectations

Before merging any Dependabot PR:

1. **Read what actually changed.** For a grouped minor/patch PR, skim the linked changelogs/release notes for each package; don't merge on trust alone.
2. **Major updates require reading the upgrade guide**, not just the diff. Check for deprecations, removed APIs and required code changes before merging — these are exactly the updates most likely to need follow-up work in this repository.
3. **Let CI decide, don't override it.** The full `Backend`, `Frontend` and `Infrastructure` GitHub Actions checks (`.github/workflows/ci.yaml`) must pass on the PR before merging, same as any other change.
4. **Verify the lockfile, not just the manifest.** `composer.lock` and `package-lock.json` changes should be exactly what the ecosystem's own tooling produced (`composer update <package>` / `npm update <package>` locally reproduces the same diff) — never hand-edit a lockfile to make a PR pass.

## What stays visible rather than auto-resolved

- **Production audits remain a required CI gate.** `composer audit --locked` (backend) and `npm audit --omit=dev` (frontend) already fail the build on a production advisory; Dependabot updates that would silence one are still expected to pass this gate honestly, not by suppressing the check.
- **Development-only advisories stay visible and risk-assessed individually**, following the same pattern already established for #5 (a transitive `@hono/node-server` advisory in the Angular dev toolchain, deliberately monitored rather than papered over with a forced override). Dependabot proposing an update that resolves a tracked dev-only advisory should reference and close the corresponding tracking issue; it must not be assumed resolved just because a PR exists.

## Explicitly out of scope

- Automatic merging of any Dependabot PR, regardless of update type or ecosystem.
- Forced npm dependency overrides to silence an advisory whose direct consumer hasn't declared compatibility.
- `npm audit fix --force`, which has previously been rejected in this repository (#5) for proposing a breaking Angular CLI downgrade.
