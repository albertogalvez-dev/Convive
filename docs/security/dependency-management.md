# Dependency update management

Dependabot is configured in [`.github/dependabot.yml`](../../.github/dependabot.yml) for all four ecosystems in use: Composer (`apps/api`), npm (`apps/web`), GitHub Actions and the Docker base images in both apps' `Dockerfile`s.

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
3. **Let CI decide, don't override it.** The full `Backend`, `Frontend`,
   `Infrastructure`, `Dependency review`, `Encrypted recovery` and
   `End-to-end` checks must pass on the PR before merging, same as any other
   change. `Dependency review` is intentionally pull-request-only and is
   skipped on a post-merge push to `main`.
4. **Verify the lockfile, not just the manifest.** `composer.lock` and `package-lock.json` changes should be exactly what the ecosystem's own tooling produced (`composer update <package>` / `npm update <package>` locally reproduces the same diff) — never hand-edit a lockfile to make a PR pass.

## What stays visible rather than auto-resolved

- **Production audits remain a required CI gate.** `composer audit --locked` (backend) and `npm audit --omit=dev` (frontend) already fail the build on a production advisory; Dependabot updates that would silence one are still expected to pass this gate honestly, not by suppressing the check.
- **Development-only advisories stay visible and risk-assessed individually**, following the same pattern already established for #5 (a transitive `@hono/node-server` advisory in the Angular dev toolchain, deliberately monitored rather than papered over with a forced override). Dependabot proposing an update that resolves a tracked dev-only advisory should reference and close the corresponding tracking issue; it must not be assumed resolved just because a PR exists.

## GitHub Actions supply chain

- **Every third-party Action referenced by the checked-in workflows is pinned
  to an immutable commit SHA**, not a mutable version tag. A tag like `@v6`
  can be moved by its publisher to point at different code without any change
  appearing in this repository's history; a SHA cannot. Each pin carries a
  trailing `# vX.Y.Z` comment purely for human readability; Dependabot's
  `github-actions` ecosystem update recognises this convention and bumps both
  the SHA and the comment together when a new release ships.
- **`Dependency review`** (`actions/dependency-review-action`) runs as its own CI job on every pull request, failing on any newly introduced dependency with a `high` or above severity advisory. It only runs on `pull_request` events — a diff against the base ref is meaningless on a push to `main`.
- **Required on pull requests.** `Protect main` requires `Dependency review`
  alongside Backend, Frontend, Infrastructure, Encrypted recovery and
  End-to-end. The post-merge push correctly reports it as skipped because it
  has no pull-request diff to inspect.
- **Actions permissions** (`Settings → Actions → General`) currently allow any
  published Action with no required repository-level SHA pinning. Checked-in
  workflows use full SHA pins, but that convention does not enable the GitHub
  setting. Its owner, rationale and review trigger are recorded in
  [GitHub security governance](github-governance.md); restricting it remains a
  separate reviewed setting change because an incomplete allowlist could break
  CI or release workflows.

## Update ownership

| Ecosystem | Path(s) | Owner |
|---|---|---|
| Composer | `apps/api` | Backend maintainer |
| npm | `apps/web` | Frontend maintainer |
| GitHub Actions | `.github/workflows/` | Whoever touches CI, reviewed by the maintainer |
| Docker base images | `apps/api/Dockerfile`, `apps/web/Dockerfile` | Whoever owns that app's Dockerfile |

Convive is currently a solo-maintained project, so today every row resolves to the same person — this table exists so ownership is explicit if that changes, not because it currently needs dividing.

## Emergency security handling

A dependency advisory rated critical or actively exploited does not wait for the weekly Monday schedule:

1. Trigger an out-of-band update immediately — either accept Dependabot's proposed PR the moment it opens (Dependabot itself raises high-severity alerts outside its configured schedule) or patch manually if no automated proposal exists yet.
2. The full CI checklist still applies; an emergency fix skips the *schedule*, never the *verification*.
3. If the fix requires deviating from this document's normal review expectations (e.g. merging without reading a full changelog because exploitation is active), record why in the PR description rather than silently skipping the usual process.

## Explicitly out of scope

- Automatic merging of any Dependabot PR, regardless of update type or ecosystem.
- Forced npm dependency overrides to silence an advisory whose direct consumer hasn't declared compatibility.
- `npm audit fix --force`, which has previously been rejected in this repository (#5) for proposing a breaking Angular CLI downgrade.
- Enabling every available supply-chain control without assessing its maintenance cost — restricting Actions permissions at the repository-settings level is deliberately deferred, not silently skipped (see above).
