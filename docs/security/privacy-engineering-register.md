# Privacy engineering register

**Status:** living fictional-demonstration baseline

**Owner:** repository maintainer

**Last reviewed:** 10 August 2026

This is an engineering register, not a record of processing, DPIA or legal
opinion. Those require the actual controller, DPO, deployment, vendors and
procedures. "Anonymous" means no reporter account or stated identity is
required; text, named people, metadata, device use or future evidence may still
identify someone.

## Data-purpose register

| ID   | Data/category and purpose                           | Minimisation/access boundary                                                | Retention position                              | Status/owner                 |
| ---- | --------------------------------------------------- | --------------------------------------------------------------------------- | ----------------------------------------------- | ---------------------------- |
| P-01 | Organisation identifier/name: route reports         | Public API exposes only public name; UUID is not a credential               | Organisation lifecycle not implemented          | Fictional slice / maintainer |
| P-02 | Situation description/context: receive a concern    | Required bounded text; four context values; no identity field               | No approved real-data period/deletion           | Fictional only / #43/#47     |
| P-03 | Public reference: receipt/support                   | Random and non-secret; never authentication/recovery                        | Follows report lifecycle                        | Implemented                  |
| P-04 | Access-secret hash: account-free proof              | Secret shown once; only SHA-256 of 256-bit random value stored              | No readable recovery                            | Implemented / ADR-0010       |
| P-05 | Capability hash/scope/expiry/revocation             | HttpOnly opaque cookie, one report, short lifetime                          | Restore purge implemented; production exercise pending | Partial / #63/#66       |
| P-06 | Follow-up content/time: add information             | Required, whitespace checked, 2,000-char bound; transactional 100-entry cap and bounded reads | Retention period still requires formal approval | Fictional only / retention gate |
| P-07 | Client IP: abuse/incident evidence                  | Selected security fields only; no bodies or credentials; alert evidence is redacted | 30-day fictional-demo default; real period requires approval | Implemented baseline / #65 |
| P-08 | Idempotency key/reference: prevent retry duplicates | Organisation scoped; no secret/response body                                | Current cache expiry only                       | Partial / #63                |
| P-09 | Professional identity/password hash/status          | Fictional fixtures/demo seed; reporter email never account recovery         | Invitation/reset/account lifecycle absent       | Model only / #30/#70         |
| P-10 | Membership/role: organisation authorisation         | Identity, membership and role separate; admin is not blanket content access | Prompt revocation required                      | Model only / #30/#31/#44     |
| P-11 | Professional sessions/auth events                   | Server session, no browser bearer token, minimal logs                       | Expiry/invalidation not implemented             | Planned / #30/#65            |
| P-12 | Attachments/metadata: evidence                      | Not collected; allowlist, limits, isolation and scanning required           | Lifecycle must precede collection               | Blocked / #36-#38            |
| P-13 | Optional reporter email/delivery metadata           | Not collected; never identity or access recovery                            | Provider retention/verification undecided       | Blocked / #39-#40            |
| P-14 | Case people/actions/assessments                     | Not implemented; only necessary fields/assignments                          | Protocol, deletion/legal hold must be versioned | Blocked / #43-#49            |
| P-15 | Audit trail: protected accountability               | Separate from logs; exclude secrets/gratuitous content                      | Controller-approved access/period needed        | Blocked / #47/#66            |
| P-16 | Logs/metrics/incidents: operation/security          | JSON health/alert events and security channel exclude report data, secrets and complete request bodies | 30-day fictional-demo default; root-only access | Implemented baseline / #65 |
| P-17 | Backups: recovery                                   | Full sensitive copy; encrypt, inventory, restrict and test                  | Rotation/deletion and stale-access invalidation | Blocked / #66                |
| P-18 | Fictional demo records                              | Clearly fictional, isolated and repeatably seeded                           | Guarded reset to a known state                   | Verified / #70               |

## Prioritised privacy risks

| ID   | Priority | Risk and decision                                                                                                                 | Owner/gate       |
| ---- | -------- | --------------------------------------------------------------------------------------------------------------------------------- | ---------------- |
| R-01 | Critical | Demo mistaken for monitored reporting: label it fictional and never solicit real concerns                                         | #70              |
| R-02 | Critical | Cross-organisation/excess professional access: server object policy and negative tests; UI filtering is insufficient              | #30/#31/#44      |
| R-03 | High     | Text identifies people despite “anonymous”: explain account-free rather than absolute anonymity and discourage unnecessary detail | Controller/DPO   |
| R-04 | High     | Shared browser exposes saved secret: saving is explicit/browser-controlled; warn and preserve capability closure                  | ADR-0011 trigger |
| R-05 | High     | Indefinite reports/logs/backups: real data blocked until periods, deletion, legal hold and restore continuity exist               | #47/#65/#66      |
| R-06 | High     | Email/third party links identity to report: disabled until purpose, verification and provider boundary are reviewed               | #39/#40          |
| R-07 | High     | Evidence malware/metadata harms people: attachments disabled pending lifecycle/threat decision                                    | #36-#38          |
| R-08 | Medium   | Monitoring captures excessive IP/device data: the implemented checks use fixed redacted fields; real deployment still needs approved purpose, access and period | #65 / controller gate |
| R-09 | Medium   | Test artifacts retain credentials/content: fictional data; E2E removes context and redacts failure screenshot                     | Verified #27     |
| R-10 | Medium   | Restore resurrects deleted/revoked access: reconcile sessions, grants, tokens, memberships, deletion and audit                    | #66              |

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

Absence is the current privacy control for attachments, email, analytics and
case management. Review this register whenever a PR changes a data field,
purpose, actor, permission, endpoint, log, retention, export, third party,
backup, environment or real-data status, and after incidents or restore tests.
