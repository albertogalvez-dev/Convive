# Operations

These runbooks describe the implemented fictional-data demonstration. They
are not a real-school service contract, an uptime SLA or permission to load
real safeguarding data.

## Local-to-release path

1. Start the reproducible development Compose stack using the commands in the
   [root README](../../README.md).
2. Apply development and `convive_test` migrations; run the backend, frontend,
   infrastructure and browser checks locally. The test-only professional
   session DSN is injected by Compose, so tests do not touch development
   sessions.
3. Open a focused branch and pull request. CI must pass Backend, Frontend,
   Infrastructure, Encrypted recovery, End-to-end and Dependency review.
4. Merge only the reviewed pull request into `main`. A push to `main` repeats
   the executable checks; Dependency review is intentionally pull-request
   scoped.
5. Start the [controlled release workflow](controlled-release-workflow.md)
   manually for a reviewed `main` commit. Its deployment job remains behind
   the `convive-demo` environment and explicit operator secrets.
6. The VPS reconciliation script pulls immutable image digests, runs the
   declared migration class, performs internal/public smoke tests and retains
   the previous generation for rollback.

## Runbooks

| Runbook | Audience | Purpose |
|---|---|---|
| [Controlled release workflow](controlled-release-workflow.md) | Maintainer/reviewer | CI gates, manual release inputs and deployment boundary |
| [Deployment, release and rollback](deployment-release-and-rollback.md) | VPS operator | Preflight, migration classes, smoke tests and rollback |
| [Encrypted backup and recovery](backup-and-recovery.md) | VPS operator | Restic/R2 backup, retention and isolated restoration |
| [Incident response and observability](incident-response.md) | Maintainer/on-call | Redacted signals, alert publication and triage |
| [Private attachment lifecycle](attachment-lifecycle.md) | VPS operator | Bounded scan retries and fictional-evidence cleanup |
| [Supported maintenance through 2027](maintenance-and-support.md) | Maintainer/transfer owner | Ownership, cadence, renewals and retirement |
| [Fictional demonstration data](fictional-demo-data.md) | Maintainer | Safe deterministic demo seed and reset boundaries |

## Evidence boundary

Runbooks refer to root-owned, secret-free evidence on the target host. They do
not require or permit copying environment files, tokens, report bodies,
capabilities or personal contact data into GitHub, issues, chat or screenshots.
