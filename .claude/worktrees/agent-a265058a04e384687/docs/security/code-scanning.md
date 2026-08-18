# Code scanning and finding triage

Convive uses GitHub CodeQL as a source-only static security signal for its
JavaScript and TypeScript code. It complements, but does not replace, the
threat model, dependency review, production dependency audits, TypeScript
checking, tests or human security review.

PHP is intentionally outside CodeQL coverage: GitHub CodeQL does not support
PHP. PHPStan at level 8, Symfony configuration/container validation, Doctrine
schema validation, backend tests and focused review remain Convive's current
PHP assurance baseline. They are not represented as a PHP SAST substitute.
No external PHP SAST provider is enabled or authorised.

## Workflow boundary

[`codeql.yaml`](../../.github/workflows/codeql.yaml) runs CodeQL's default
query suite for JavaScript/TypeScript:

- on pull requests targeting `main` and pushes to the protected `main` branch;
- manually through `workflow_dispatch` when a reviewer needs a fresh analysis;
- every Monday at 04:17 UTC, so query changes can surface findings even when
  Convive source code is unchanged.

The workflow has only `contents: read` and `security-events: write`
permissions. Its Actions are pinned to reviewed commit SHAs. `build-mode: none`
is intentional for this source-only analysis: the workflow does not boot Docker
Compose, load fixtures, access fictional data, deploy an environment or call a
provider other than GitHub code scanning.

## Finding triage

The repository maintainer owns incoming CodeQL alerts until responsibility is
formally delegated:

1. Confirm the query, source location, data-flow path and affected revision.
2. Fix a valid finding in a focused issue and pull request. Critical and high
   findings block unrelated release work unless the maintainer records a
   time-bounded risk decision.
3. Dismiss only a genuine false positive, test-only path or reviewed
   non-exploitable result. Record the alert URL, category, rationale,
   compensating control and re-evaluation trigger.
4. Link the resolving or tracking issue where GitHub permits. Never include
   report content, credentials, access secrets or session identifiers.

Do not generate a baseline, disable broad queries or use unexplained `won't
fix` dismissals to make the dashboard quiet.

## Review boundary

CodeQL identifies known JavaScript/TypeScript source patterns and data flows.
It cannot decide controller identity, privacy notice, retention, safeguarding,
role design, operational procedure or deployment authorisation. Those remain
dedicated product, legal, security and human-review decisions.
