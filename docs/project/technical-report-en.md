# Convive technical project report

**Status:** verified fictional-demo project report  
**Last reviewed:** 25 August 2026

## Executive summary

Convive is an open-source web application for receiving school-community communications and organising their internal assessment. A communication never becomes a case automatically: an authorised professional assesses it, records the decision and, where appropriate, works the case with tasks, linked people, history and an applicable territorial source.

This report describes the product and code in the repository. The demonstration uses fictional data only. It does not enable real safeguarding intake, is not an emergency channel and makes no legal, institutional or operational compliance claim for real data.

## Product and journeys

| Entry | Purpose | Boundary |
| --- | --- | --- |
| Public website | Explains Convive, help resources and the guided demonstration | Never replaces emergency services or a real school |
| Public reporting | Walks through a form without an account | Follow-up uses a one-time secret and a capability scoped to that communication |
| Professional area | Shows inbox, cases, tasks, evidence and settings | The demo exposes prepared non-persistent journeys where designed |

The public demonstration provides one empty journey and one fictional completed example. The static example has no capability, contacts no API and cannot be mistaken for saving a communication. Visitors see exactly two professional entries: **Case management** and **Administration**. Lead, contributor and observer permissions remain internal per-case rules.

The seventeen autonomous communities, Ceuta and Melilla are modelled with versioned territorial sources. Convive cites document, authority and version; it neither invents deadlines nor turns a protocol into an automatic decision.

## Architecture

| Component | Responsibility |
| --- | --- |
| Angular 22 | Public website, form, follow-up and professional workspace |
| Symfony 7.4 / PHP 8.5 | Validation, sessions, anonymous capabilities, authorisation and audit |
| PostgreSQL 18.4 | Fictional organisations, communications, cases, tasks and audit |
| Redis 8.2 | Abuse limits and authenticated transient state |
| Private storage + ClamAV | Evidence quarantine and scanning |
| Caddy gateway | Compiled Angular and same-origin API routing |
| Platform Caddy | Sole approved VPS public HTTP(S) ingress |

The [context view](../architecture/diagrams/c4-context.md), [container view](../architecture/diagrams/c4-container.md) and [security data-flow](../architecture/diagrams/security-data-flow.md) are the maintained map. Design rationale is maintained in the [ADRs](../architecture/decisions/README.md).

## Security and privacy

The interface does not authorise. Symfony requires an active professional session, organisation membership and exact case permission. Possession of a reporter secret cannot become a professional session.

Evidence remains in private storage, passes quarantine and scanning, and is previewed in memory only after an authorised safe-format read. There is no public storage URL and no fabricated video evidence. Operational secrets are restrictive-permission files outside Git; logs avoid communication content and credentials.

The [threat model](../security/threat-model.md), [privacy register](../security/privacy-engineering-register.md) and [attachment threat model](../security/attachment-threat-model.md) document both controls and limits.

## Fictional data and verification

The deterministic demonstration is created by an explicit guarded command, never by Doctrine fixtures during startup. It prepares communications, cases at different stages, assignments, tasks, history and purpose-created context evidence without real data. It is idempotent; reset requires confirmation and affects only the reserved space. The procedure is maintained in [fictional demonstration data](../operations/fictional-demo-data.md).

Changes are checked through Symfony and Angular tests, types/format, OpenAPI, Compose, encrypted recovery and isolated Chromium journeys. E2E creates an ephemeral fictional database and removes its stack. The [testing strategy](../testing/strategy.md) and delivery traceability define the required evidence.

## Operations and status

Release first prepares a healthy generation with immutable digest-pinned images, reviewed migrations and recovery evidence. Only then is the exact Caddy route activated and HTTPS, hostname, API, headers, demo label and unintended ports are checked. Failure keeps the candidate non-public or restores only Convive's route and generation.

The [release and rollback runbook](../operations/deployment-release-and-rollback.md), [sequence](../architecture/diagrams/release-rollback-sequence.md) and [ADR-0029](../architecture/decisions/0029-use-the-platform-caddy-per-project-edge-for-public-ingress.md) are the operational source. This report does not claim an active public hostname, configured DNS, a real school or authorisation to process personal data. Those assertions belong to the controlled release and public-boundary review.

See the [README](../../README.md), [operations index](../operations/README.md) and [Spanish report](technical-report-es.md).
