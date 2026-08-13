# GitHub security governance

This document records the effective GitHub security and supply-chain controls
reviewed on **2026-08-13**. GitHub remains authoritative for live settings;
the repository records the result, ownership and review method so that a
future maintainer can detect drift without treating this file as a settings
API.

Convive is a fictional-data demonstration. These controls protect repository
and delivery integrity; they do not authorise a real-data service or claim a
security certification.

## Ownership and change rule

The **repository maintainer** owns every control below until the role is
formally delegated. Review this register monthly, before a public release and
after a material GitHub, Actions, dependency or incident change.

Changing a GitHub setting is not implied by this inventory. A setting change
needs its own reviewed issue and pull request, an explicit before/after record
and the applicable CI evidence. Do not silently enable a control, relax a
rule, or replace an unsupported scanner merely to make this table greener.

## Reproduce the review

An administrator can inspect the current state without reading application
data or secrets. Run these commands from a trusted authenticated `gh` session;
they return repository metadata, not deployment credentials:

```bash
gh api repos/albertogalvez-dev/Convive/rulesets/20046802
gh api repos/albertogalvez-dev/Convive --jq '.security_and_analysis'
gh api repos/albertogalvez-dev/Convive/actions/permissions
gh api repos/albertogalvez-dev/Convive/vulnerability-alerts --include
gh api 'repos/albertogalvez-dev/Convive/secret-scanning/alerts?state=open'
gh api 'repos/albertogalvez-dev/Convive/code-scanning/alerts?state=open'
```

Compare the result with [`.github/dependabot.yml`](../../.github/dependabot.yml)
and the workflow files in [`.github/workflows/`](../../.github/workflows/).
Some secret-scanning configuration endpoints are unavailable to this API
credential; in that case use the repository Security settings and record the
observed state rather than guessing.

## Protected main branch

The active `Protect main` ruleset targets the default branch and prevents
deletion and non-fast-forward updates. It requires a pull request and a
strictly up-to-date successful result from all of these checks before merge:

| Required check | Boundary it protects |
| --- | --- |
| `Backend` | Composer/audit, Symfony configuration, PHPStan, OpenAPI, migrations, schema and backend tests |
| `Frontend` | formatting, strict TypeScript, production dependency audit, frontend tests and build |
| `Infrastructure` | Compose and development/production-boundary checks |
| `Dependency review` | newly introduced dependency risk on pull requests |
| `Encrypted recovery` | configured encrypted backup and isolated recovery exercise |
| `End-to-end` | isolated fictional browser journey and its cleanup |

`Dependency review` intentionally has no useful comparison on a push to
`main`, so it is skipped after merge; it is nevertheless required on the pull
request that reaches `main`. `PR traceability` validates the closing issue
reference and evidence headings on pull requests. It is an additional
observed workflow, not a substitute for the six protected-branch checks.

The ruleset currently permits merge, squash and rebase methods and requires no
approving review count. That matches the documented solo-maintainer workflow;
adding reviewer, CODEOWNER or merge-method restrictions is a separate owner
decision, not an undocumented assumption.

## Effective control register

| Control | Observed state on 2026-08-13 | Rationale and boundary | Owner and review trigger |
| --- | --- | --- | --- |
| Dependency alerts | Enabled | Alerts keep production and development findings visible; production audits still fail CI. | Repository maintainer; weekly advisory review and any critical alert. |
| Dependabot version updates | Configured weekly for Composer, npm, GitHub Actions and both Dockerfiles | Source-controlled update PRs are reviewed with lockfiles and CI; they are never auto-merged. | Repository maintainer; every Monday and after a framework/runtime update. |
| Dependabot security updates | Disabled | This setting is not a substitute for dependency alerts or reviewed update PRs. Its enablement and resulting PR workflow need a separate owner decision. | Repository maintainer; monthly and before public release. |
| Automated security fixes | Disabled | Automatic dependency mutations are outside Convive's intentional PR review policy. | Repository maintainer; monthly and after an actively exploited advisory. |
| Private vulnerability reporting | Enabled | [SECURITY.md](../../SECURITY.md) directs reporters to GitHub's private advisory path rather than public issues. | Repository maintainer; on every private report and quarterly. |
| Secret scanning | Enabled; no open alerts observed | Detects committed supported secrets in the public repository. | Repository maintainer; on every alert and monthly. |
| Secret-scanning push protection | Enabled | Prevents supported secrets from being pushed; it does not replace review or credential rotation. | Repository maintainer; monthly and after a blocked push. |
| Secret-scanning validity checks and non-provider patterns | Disabled | No additional validation service or custom-pattern set has been approved. Do not imply coverage beyond GitHub-supported patterns. | Repository maintainer; before adding a provider, pattern set or public release. |
| Actions policy | Actions enabled; all actions/workflows allowed; repository SHA-pin enforcement disabled | Checked-in workflows use immutable full-SHA action references and least-privilege workflow permissions. Repository-level allowlisting or mandatory pinning could disrupt release/CI and needs an inventory and separate review. | Repository maintainer; before adding an Action and quarterly. |
| Code scanning | No CodeQL workflow on `main`; no open code-scanning alerts observed | Issue [#161](https://github.com/albertogalvez-dev/Convive/issues/161) remains blocked: CodeQL supports the TypeScript analysis but not the required PHP analysis. A zero-alert inventory is not evidence of PHP coverage. | Repository maintainer; when GitHub adds PHP support or an approved SAST decision exists. |

## Workflow supply-chain boundary

`ci.yaml`, `release.yaml` and `pr-traceability.yaml` declare the permissions
they need. CI uses only `contents: read`; PR traceability uses only
`pull-requests: read`; the manually dispatched release workflow additionally
uses `actions: read`, `checks: read` and `packages: write` for its controlled
release contract. Every third-party Action currently referenced by the
checked-in workflows is pinned to a full commit SHA with a human version
comment. This repository convention limits mutable-tag drift even though
GitHub's repository-wide SHA-pin setting is not enabled.

Review every new or changed Action for provenance, full-SHA pin, required
permission and whether it can execute untrusted pull-request input. A workflow
that needs a broader token, a new external service, a scheduled privileged
action or a repository-policy relaxation requires a dedicated security review.

## Evidence and follow-up

This review reconciles the six current protected-branch checks with the PR
template, contributor guidance, testing strategy and dependency guidance. It
does not resolve the PHP SAST limitation in #161, remediate the development
toolchain advisory in #5, enable a provider, or change any GitHub setting.
