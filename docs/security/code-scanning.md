# Code scanning and finding triage

Convive uses GitHub CodeQL as a source-only static security signal for its PHP
and JavaScript/TypeScript code. It complements, but does not replace, the
threat model, dependency review, production dependency audits, PHPStan,
TypeScript checking, tests or human security review.

## Workflow boundary

[`codeql.yaml`](../../.github/workflows/codeql.yaml) runs the CodeQL default
query suite independently for `php` and `javascript-typescript`:

- on pull requests targeting `main` and pushes to the protected `main` branch;
- manually through `workflow_dispatch` when a reviewer needs a fresh analysis;
- every Monday at 04:17 UTC, so changes in CodeQL queries can surface findings
  even when Convive source code is unchanged.

The workflow has only `contents: read` and `security-events: write`
permissions. Its `checkout`, CodeQL initialization and analysis Actions are
pinned to reviewed commit SHAs, with readable release comments; Dependabot
keeps those pins in the established GitHub Actions update path.

`build-mode: none` is intentional. PHP and TypeScript do not require a native
compiled-language build capture for this repository's CodeQL analysis. The
workflow checks out source only: it does not boot Docker Compose, load
fixtures, access the fictional demonstration dataset, deploy an environment or
call a provider other than GitHub's code-scanning service.

## Finding triage

The repository maintainer owns incoming CodeQL alerts until responsibility is
formally delegated. Triage every new alert in GitHub's **Security > Code
scanning alerts** view before treating a PR as complete:

1. Confirm the alert's query, source location, data-flow path and affected
   revision. Reproduce the relevant code path or test where practical.
2. Fix a valid finding in a focused issue/PR. Critical and high-severity
   findings block unrelated release work until they are resolved or an
   explicitly documented, time-bounded risk decision is made by the
   maintainer. Medium and low findings are fixed promptly or tracked in a
   labelled security issue with owner, scope and review trigger.
3. A dismissal is permitted only for a genuine CodeQL false positive,
   test-only path, or reviewed non-exploitable result. Record the alert URL,
   dismissal category, technical rationale, compensating control and the
   source or dependency change that requires re-evaluation. Never use a
   generated baseline, broad query disablement or an unexplained `won't fix`
   dismissal merely to make a dashboard quiet.
4. Link the resolving or tracking issue from the alert where GitHub permits;
   keep the issue free of report content, credentials, access secrets, session
   identifiers and other sensitive operational data.

Initial review is part of enabling this workflow: every alert produced by the
first successful PHP and JavaScript/TypeScript analyses must be fixed,
explicitly dismissed with the rationale above, or tracked before issue #161 is
closed. Later scheduled alerts follow the same process.

## Review boundary

CodeQL can identify known source-pattern and data-flow risks. It cannot decide
whether a fictional demonstration has the right controller identity, privacy
notice, retention decision, safeguarding wording, role design, operational
procedure or deployment authorization. Those remain product, legal, security
and human architecture review decisions under their dedicated issues.
