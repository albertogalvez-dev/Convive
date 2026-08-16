# Release record — YYYY-MM-DD — `<release identifier>`

Copy this file to `YYYY-MM-DD-<release>.md` and fill it **during** the release.
Leave a field empty rather than guessing; an empty field is information, an
invented one is not. Never paste secrets, reporter data or raw logs — see the
[directory README](README.md).

## Outcome

| | |
| --- | --- |
| **Result** | completed / rolled back / abandoned before deployment |
| **Operator** | |
| **Start (UTC)** | |
| **End (UTC)** | |
| **Maintenance window** | |

## What was deployed

| | |
| --- | --- |
| **Release identifier** | |
| **Git revision** | full commit SHA |
| **Pull request** | |
| **`api` image digest** | `sha256:…` |
| **`gateway` image digest** | `sha256:…` |
| **Previous release identifier** | |
| **Previous `api` digest** | needed to roll back |
| **Previous `gateway` digest** | needed to roll back |

Every image must be pinned by digest. A tag is not a record of what ran.

## Migrations

| | |
| --- | --- |
| **Migrations applied** | versions, or "none" |
| **Compatibility** | backward-compatible / requires ordered rollout / irreversible |
| **Reversible?** | yes / no — if no, say what makes it irreversible |

An irreversible migration changes the rollback plan: say so here, before the
release, not after.

## Verification

| Check | Result | Notes |
| --- | --- | --- |
| CI green on the merged revision | | run URL |
| Public host smoke test | | |
| Application host smoke test | | |
| Health endpoint | | |
| Security headers and HTTPS | | |
| Host and indexing boundary | | |
| Backup taken before release | | backup identifier |
| Restore test evidence | | reference, not contents |
| Monitoring received the release identifier | | |

## Operational acceptance

The role-based scenarios from the
[acceptance pack](../../testing/operational-acceptance.md), when this release
runs them.

| Scenario | Run by | Outcome | Classification | Priority | Owner | Follow-up |
| --- | --- | --- | --- | --- | --- | --- |
| | | | | | | |

A blocking finding stops the release unless the owner accepts it **in writing**,
recorded below with their name. Closeness to a deadline is not an acceptance.

## What went wrong

Anything that did not go to plan, including things that were recovered from.
A release with an empty section here is either unusually clean or incompletely
recorded; prefer writing the small things down.

| Time (UTC) | What happened | What was done | Follow-up issue |
| --- | --- | --- | --- |
| | | | |

## Rollback

| | |
| --- | --- |
| **Rollback triggered?** | yes / no |
| **Decision made by** | |
| **Trigger** | what was observed, not what was suspected |
| **Target release** | |
| **Result** | |
| **Data implications** | anything written during the failed release |

If no rollback was needed, state how long the previous release stays
recoverable and who is watching until then.

## Sign-off

| | |
| --- | --- |
| **Release accepted by** | |
| **Date (UTC)** | |
| **Outstanding follow-ups** | issue numbers |

A release is accepted when the checks above passed, the previous release remains
recoverable for the stated window, and every open follow-up has an issue. Not
when it merely appears to work.
