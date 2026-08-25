# Privacy engineering register

**Status:** living fictional-demonstration baseline

**Owner:** repository maintainer

**Last reviewed:** 24 August 2026

This is an engineering register, not a record of processing, DPIA or legal
opinion. Those require the actual controller, DPO, deployment, vendors and
procedures. "Anonymous" means no reporter account or stated identity is
required; text, named people, metadata, device use or future evidence may still
identify someone.

## Data-purpose register

| ID   | Data/category and purpose                           | Minimisation/access boundary                                                                                                                                                                                                                                                        | Retention position                                                                                                                                        | Status/owner                              |
| ---- | --------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------- |
| P-01 | Organisation identifier/name: route reports         | Public API exposes only public name; UUID is not a credential                                                                                                                                                                                                                       | Organisation lifecycle not implemented                                                                                                                    | Fictional slice / maintainer              |
| P-02 | Situation description/context: receive a concern    | Required bounded text; four context values; no identity field                                                                                                                                                                                                                       | No approved real-data period/deletion                                                                                                                     | Fictional only / #43/#47                  |
| P-03 | Public reference: receipt/support                   | Random and non-secret; never authentication/recovery                                                                                                                                                                                                                                | Follows report lifecycle                                                                                                                                  | Implemented                               |
| P-04 | Access-secret hash: account-free proof              | Secret shown once; only SHA-256 of 256-bit random value stored                                                                                                                                                                                                                      | No readable recovery                                                                                                                                      | Implemented / ADR-0010                    |
| P-05 | Capability hash/scope/expiry/revocation             | HttpOnly opaque cookie, one report, short lifetime                                                                                                                                                                                                                                  | Restore purge implemented; production exercise pending                                                                                                    | Partial / #63/#66                         |
| P-06 | Follow-up content/time: add information             | Required, whitespace checked, 2,000-char bound; transactional 100-entry cap and bounded reads                                                                                                                                                                                       | Retention period still requires formal approval                                                                                                           | Fictional only / retention gate           |
| P-07 | Client IP: abuse/incident evidence                  | Selected security fields only; no bodies or credentials; alert evidence is redacted                                                                                                                                                                                                 | 30-day fictional-demo default; real period requires approval                                                                                              | Implemented baseline / #65                |
| P-08 | Idempotency key/reference: prevent retry duplicates | Organisation scoped; no secret/response body                                                                                                                                                                                                                                        | Current cache expiry only                                                                                                                                 | Partial / #63                             |
| P-09 | Professional identity/password hash/status          | Fictional administrator-managed accounts; server password hashes and one-time hashed activation/reset credentials; reporter email never account recovery                                                                                                                            | 24-hour credential expiry and session invalidation are implemented; real identity proofing, email delivery and MFA remain gates                           | Implemented fictional boundary / #171     |
| P-10 | Membership/role: organisation authorisation         | Identity, membership and role separate; admin is not blanket content access                                                                                                                                                                                                         | Prompt revocation required                                                                                                                                | Implemented fictional boundary / #30/#31  |
| P-11 | Professional sessions/auth events                   | Server-side session, no browser bearer token, minimised logs and security-revision invalidation                                                                                                                                                                                     | Session expiry and invalidation are implemented; real-data authentication/MFA and operational ownership remain gates                                      | Implemented fictional boundary / #171     |
| P-12 | Attachments/metadata: evidence                      | Fictional-only backend: narrow allowlist, private quarantine, fail-closed scan lifecycle and authorised access in the [attachment threat model](attachment-threat-model.md); no reporter UI                                                                                         | Fictional lifecycle is explicit; real retention remains blocked                                                                                           | Backend boundary / #37; UI #38            |
| P-13 | Optional reporter email/delivery metadata           | Fictional development only; separate verified contact, generic notices, no professional visibility or access recovery under [ADR-0015](../architecture/decisions/0015-use-verified-email-only-for-generic-reporter-notifications.md)                                                | Automated unverified 24h and delivery-evidence 30d cleanup; immediate removal; restore suppresses all contacts/jobs; final-closure cleanup awaits closure | Implemented fictional boundary / #40      |
| P-14 | Case people/actions/assessments                     | Case-local bounded names and neutral roles; active organisation membership plus exact case assignment required; no student-directory import                                                                                                                                         | Protocol, deletion/legal hold and case workflow must be versioned                                                                                         | Modelled for fictional demo / #43/#44     |
| P-15 | Audit trail: protected accountability               | Separate append-only case events contain only case/organisation/actor/action/target/time; no content, secrets, IPs or read tracking; active lead-only audit access under [ADR-0020](../architecture/decisions/0020-protect-case-audit-events-with-minimised-append-only-records.md) | 30-day fictional cleanup; real period, legal hold, rights and export authority remain controller/DPO gates                                                | Implemented fictional boundary / #47      |
| P-16 | Logs/metrics/incidents: operation/security          | JSON health/alert events and security channel exclude report data, secrets and complete request bodies                                                                                                                                                                              | 30-day fictional-demo default; root-only access                                                                                                           | Implemented baseline / #65                |
| P-17 | Backups: recovery                                   | Full sensitive copy; paired encrypted database/object generations, restricted and tested                                                                                                                                                                                            | Rotation implemented; future contact removal requires restore suppression                                                                                 | Implemented fictional baseline / #66/#138 |
| P-18 | Fictional demo records                              | Clearly fictional, isolated and repeatably seeded                                                                                                                                                                                                                                   | Guarded reset to a known state                                                                                                                            | Verified / #70                            |

## Prioritised privacy risks

| ID   | Priority | Risk and decision                                                                                                                                                                                                      | Owner/gate                |
| ---- | -------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------- |
| R-01 | Critical | Demo mistaken for monitored reporting: label it fictional and never solicit real concerns                                                                                                                              | #70                       |
| R-02 | Critical | Cross-organisation/excess professional access: server object policy and negative tests; UI filtering is insufficient                                                                                                   | #30/#31/#44               |
| R-03 | High     | Text identifies people despite “anonymous”: explain account-free rather than absolute anonymity and discourage unnecessary detail                                                                                      | Controller/DPO            |
| R-04 | High     | Shared browser exposes saved secret: saving is explicit/browser-controlled; warn and preserve capability closure                                                                                                       | ADR-0011 trigger          |
| R-05 | High     | Indefinite reports/logs/backups: real data blocked until periods, deletion, legal hold and restore continuity exist                                                                                                    | #47/#65/#66               |
| R-06 | High     | Email/third party links mailbox use to a report: ADR-0015 limits purpose, content, access and retention; isolated fictional Mailpit delivery is implemented, while production remains disabled pending provider review | Provider / real-data gate |
| R-07 | High     | Evidence malware/metadata harms people: backend fails closed on scanner outage; no reporter UI or real-data use is allowed pending an isolated scanner and the reviewed reporter journey                               | #38 / real-data gate      |
| R-08 | Medium   | Monitoring captures excessive IP/device data: the implemented checks use fixed redacted fields; real deployment still needs approved purpose, access and period                                                        | #65 / controller gate     |
| R-09 | Medium   | Test artifacts retain credentials/content: fictional data; E2E removes context and redacts failure screenshot                                                                                                          | Verified #27              |
| R-10 | Medium   | Restore resurrects deleted/revoked access: reconcile sessions, grants, tokens, memberships, deletion and audit                                                                                                         | #66                       |

## Authorised-access rules

- Secret possession authorises only one report's anonymous operations.
- Professional identity also requires active organisation membership, role and
  object/action policy.
- Administrator duties do not imply report/case content access.
- Database, backup, log and incident access require named purpose, least
  privilege and traceability.
- Development/demo access never authorises copied real school data.

## Real-data gate and review

The demonstration stays fictional until applicable critical/high entries have
an accountable owner, implemented evidence and approved residual decision. At
minimum: controller/DPO and notices; professional authentication/MFA and
authorisation; retention/deletion/rights; production security, incident and
recovery evidence; vendor contracts; and named maintenance ownership.

Real-data attachment and case use are still forbidden. Absence remains the
current privacy control for analytics; case management is only a fictional-data
model and ADR-0015 email delivery remains disabled in production. Review this
register whenever a PR changes a data field,
purpose, actor, permission, endpoint, log, retention, export, third party,
backup, environment or real-data status, and after incidents or restore tests.

## Implemented-boundary evidence

- [Sessions and capabilities diagram](../architecture/diagrams/access-sessions-and-capabilities.md)
  and ADR-0008/0010/0011 define the separate professional-session and
  reporter-capability contexts.
- `ManageProfessionalAccount`, `ManageOrganisationMembership` and their
  focused integration tests prove fictional account lifecycle, credential
  expiry and session invalidation.
- [Recovery flow](../architecture/diagrams/recovery-flow.md) and
  `infrastructure/backup/test-recovery.sh` prove the fictional recovery
  exercise without asserting a deployed or real-data operation.
- [Attachment lifecycle](../architecture/diagrams/attachment-lifecycle.md) and
  [observability and incident response](../architecture/diagrams/observability-and-incident-response.md)
  record the implemented fail-closed and redacted operational boundaries.
- The [security data-flow and trust-boundary diagram](../architecture/diagrams/security-data-flow.md)
  connects these controls to the reviewed public edge, credential contexts,
  private evidence lifecycle and encrypted fictional recovery path.
