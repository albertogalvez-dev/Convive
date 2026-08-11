# Convive threat model

**Status:** living fictional-demonstration baseline

**Owner:** repository maintainer

**Last reviewed:** 11 August 2026

## Safety boundary

Convive may contain safeguarding communications that identify children and
other school-community members. The public demonstration is restricted to
fictional data. This model neither authorises real reporting nor claims GDPR,
ENS or real-school deployment compliance.

Real data remains blocked until the controller and DPO approve purposes,
notices, retention, rights handling, processor arrangements and operational
responsibilities, and every applicable technical gate below has evidence.

## Trust boundaries

```text
Untrusted reporter browser
  -> public HTTPS / same-origin Angular and API
  -> trusted edge and request controls (#63, not implemented)
  -> Symfony validation, authentication and authorisation boundary
  -> PostgreSQL
  -> encrypted backup and restore boundary (#66, implementation in progress)

Professional browser -> professional session (#30) -> Symfony API
```

The browser, network metadata and all submitted content are untrusted. Angular
is not an authorisation boundary. Symfony is authoritative. PostgreSQL and its
backups require separate least-privilege operational access. Email, identity
and monitoring services become new trust boundaries if introduced. Fictional
evidence has a backend boundary: private quarantine storage,
application-mediated retrieval and a fail-closed scanner port, as defined in
the [attachment security boundary](attachment-threat-model.md). No reporter
attachment UI is exposed until #38 implements and verifies that experience.

Anonymous capability and future professional cookies may share an origin but
are mutually exclusive security contexts for an operation. No endpoint may
upgrade anonymous possession into professional authority.

## Assets

| Asset                                    | Required property                                                         |
| ---------------------------------------- | ------------------------------------------------------------------------- |
| Report/follow-up content                 | Confidentiality, integrity, availability, organisation isolation          |
| Reporter access secret                   | One-time disclosure, hashed server storage, no logging or recovery bypass |
| Anonymous capability                     | Short lifetime, one-report scope, revocation, no JavaScript access        |
| Professional identity/session            | Strong authentication, CSRF protection, invalidation, least privilege     |
| Memberships and roles                    | Integrity, freshness and server-side enforcement                          |
| Case, evidence and audit data            | Confidentiality, traceability and controlled retention                    |
| Logs                                     | Operational integrity without credentials or excessive personal data      |
| Deployment secrets, database and backups | Confidentiality, recovery and controlled deletion                         |
| Demo boundary                            | Fictional data and no mistaken real reporting channel                     |

## Actors and plausible attackers

- legitimate reporters, including young people using shared devices;
- authorised professionals and configuration administrators;
- unauthenticated scanners and abusive/spamming reporters;
- people with physical or browser-profile access to a reporter device;
- compromised professional accounts and malicious insiders;
- supply-chain, VPS, database, backup and deployment attackers;
- operators making accidental configuration, release, restore or seed errors.

## Misuse cases

1. Exhaust storage or bury work through reports or follow-up entries.
2. Guess, steal or autofill a reporter secret on a shared device.
3. Reuse a capability after closure, expiry or database restoration.
4. Cross report, organisation or case boundaries by changing identifiers.
5. Trigger state changes through CSRF or the wrong security context.
6. Execute active content or malicious evidence in a professional workspace.
7. Leak content, credentials or IPs through logs, URLs, email, CI or backups.
8. Abuse a professional account, stale membership or administrator role.
9. deploy weak secrets, missing migrations or an unrecoverable database.
10. Present the demo as monitored and receive real safeguarding information.

## Prioritised register

`Blocked` permits only the stated fictional demonstration, not real data.

| ID   | Priority | Threat                                                              | Current evidence                                                                                                                                                                                                               | Residual risk / owner                                                                                                                                                                                                                                      |
| ---- | -------- | ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| T-01 | Critical | Real submissions enter an unattended demo                           | Repository/product rules require fictional data                                                                                                                                                                                | Unmistakable demo boundary and repeatable seed/reset are gates in [#70](https://github.com/albertogalvez-dev/Convive/issues/70)                                                                                                                            |
| T-02 | Critical | Unauthorised or cross-organisation professional access              | Membership/role domain model only                                                                                                                                                                                              | Authentication, sessions, API authorisation and negative isolation tests: [#30](https://github.com/albertogalvez-dev/Convive/issues/30)-[#31](https://github.com/albertogalvez-dev/Convive/issues/31)                                                      |
| T-03 | Critical | VPS/database/deployment-secret compromise                           | Development Compose; secrets excluded from Git                                                                                                                                                                                 | Edge, least privilege, secret injection, release and patching: [#63](https://github.com/albertogalvez-dev/Convive/issues/63), [#64](https://github.com/albertogalvez-dev/Convive/issues/64), [#67](https://github.com/albertogalvez-dev/Convive/issues/67) |
| T-04 | High     | Backup theft or restore revives revoked access                      | ADR-0008 requires invalidation                                                                                                                                                                                                 | Encryption, off-host control and restore exercises: [#66](https://github.com/albertogalvez-dev/Convive/issues/66)                                                                                                                                          |
| T-05 | High     | Reporter secret leaks                                               | 256-bit secret, SHA-256 lookup, body-only exchange, E2E storage/artifact checks                                                                                                                                                | Credential-manager/shared-profile and XSS risk remain; review ADR-0011 on new evidence                                                                                                                                                                     |
| T-06 | High     | Capability theft/reuse/cross-report access                          | Hashed opaque handle, HttpOnly cookie, scope, expiry and revocation tests                                                                                                                                                      | Cleanup, restore invalidation and mutation abuse controls remain deployment gates                                                                                                                                                                          |
| T-07 | High     | Public/follow-up resource exhaustion                                | Submission, verification and capability/IP-scoped follow-up limits; transactional 100-entry cap; bounded deterministic reads                                                                                                   | Shared, restart-resistant limiter state remains the production gate owned by #63                                                                                                                                                                           |
| T-08 | High     | Malicious attachments or metadata                                   | [Backend boundary](attachment-threat-model.md): private quarantine, fail-closed scanning lifecycle, authorised retrieval and cleanup tests                                                                                     | Reporter UX: [#38](https://github.com/albertogalvez-dev/Convive/issues/38); isolated-scanner/provider and real-data review remain gates                                                                                                                    |
| T-09 | High     | CSRF or confused access context                                     | Same-origin, SameSite capability and distinct cookie                                                                                                                                                                           | Explicit mutation/context tests required in #30 and [#33](https://github.com/albertogalvez-dev/Convive/issues/33)-[#35](https://github.com/albertogalvez-dev/Convive/issues/35)                                                                            |
| T-10 | High     | Insider/stale membership/excess admin reads data                    | Least-privilege product rule                                                                                                                                                                                                   | Object policy, invalidation and audit: #31, [#44](https://github.com/albertogalvez-dev/Convive/issues/44), [#47](https://github.com/albertogalvez-dev/Convive/issues/47)                                                                                   |
| T-11 | Medium   | Logs/monitoring retain content, credentials or IPs                  | Structured security logger excludes bodies/secrets                                                                                                                                                                             | Collection, redaction, access, retention and incident process: [#65](https://github.com/albertogalvez-dev/Convive/issues/65)                                                                                                                               |
| T-12 | Medium   | Limits/idempotency bypassed by restart, replica or equivalent input | Per-IP limits and optional key                                                                                                                                                                                                 | Filesystem state, canonical binding and concurrent race require #63 review                                                                                                                                                                                 |
| T-13 | Medium   | Dependency or CI compromise ships code                              | Pinned Actions, dependency review, audits, Dependabot                                                                                                                                                                          | Dev advisory [#5](https://github.com/albertogalvez-dev/Convive/issues/5); provenance/release evidence #64/#67                                                                                                                                              |
| T-14 | Medium   | Errors/rendering disclose internals or execute content              | Problem Details and escaped Angular interpolation                                                                                                                                                                              | Generic error boundary, CSP/security headers and body limits: #63                                                                                                                                                                                          |
| T-15 | Medium   | Email links a mailbox to Convive or becomes a recovery bypass       | [ADR-0015](../architecture/decisions/0015-use-verified-email-only-for-generic-reporter-notifications.md); separate contacts, hashed single-use verification, bounded outbox, generic-content tests, immediate opt-out and restore suppression are implemented for fictional Mailpit delivery | Production provider, sender-domain and real-data approval remain gates                                                                                                                                                                                       |

## Existing evidence

- Tests verify secret format/hash, generic denial, capability scope, expiry and
  revocation.
- Submission and secret verification have independent pre-work rate limits.
- Security-log tests exclude submitted content.
- CI checks OpenAPI, migrations/schema, dependencies, apps and Compose.
- Playwright uses an ephemeral fictional database and checks application state
  and retained artifacts for secret leakage.

This proves only the implemented slice, not future production security.

## Real-data blockers

- controller/DPO decisions, notices, rights and processor arrangements;
- professional MFA or institutional authentication and object authorisation;
- production TLS/edge headers, limits, secrets, least privilege and patching;
- encrypted backup, restoration and access-invalidation exercises;
- approved logging/monitoring retention and incident response;
- data retention, deletion, legal hold and audit continuity;
- reviewed boundaries before attachments, email or third parties are enabled;
- named operational ownership through the supported maintenance period.

## Review triggers

Update this model in the same increment when authentication, roles, anonymous
credentials, report/case data, evidence, third parties, logging, deployment,
backup, retention or real-data status changes, or after an incident or material
vulnerability invalidates an assumption.
