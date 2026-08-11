# ADR-0015: Use verified email only for generic reporter notifications

- **Status:** Accepted
- **Date:** 11 August 2026
- **Related issue:** [#39](https://github.com/albertogalvez-dev/Convive/issues/39)
- **Depends on:** [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md), [ADR-0010](0010-use-a-single-secret-for-anonymous-report-access.md), [ADR-0011](0011-allow-the-reporter-browser-password-manager-to-store-the-access-secret.md)

## Context

Convive's account-free reporting journey is complete without an email address.
The reporter receives one high-entropy access secret that Convive stores only as
a hash. Losing that secret may permanently remove access, but staff cannot
identify the legitimate anonymous reporter well enough to recover it safely.

An optional address can reduce the need to poll for updates. It also creates a
direct link between a mailbox and a sensitive communication, exposes delivery
metadata to another system and may create the false impression that control of
the mailbox proves ownership of the report. The public reference is deliberately
non-authenticating and the access secret must never be sent by email.

This decision selects the product, privacy and recovery boundary. It does not
select a production delivery provider or enable real-data email.

## Decision drivers

- Keep the no-email journey complete and equally capable.
- Notify without disclosing whether a message concerns bullying, a named person
  or any report content.
- Prevent email, the public reference and support staff from becoming access
  recovery shortcuts.
- Obtain explicit, provable consent before linking a mailbox to a report.
- Minimise who can see contact and delivery data and how long it exists.
- Fail independently: report updates must succeed when email delivery fails.

## Options considered

### Option A: Do not collect reporter email

This has the smallest privacy and operational surface, but reporters must poll
and may miss a time-sensitive professional response. It remains the default
journey, not the selected optional enhancement.

### Option B: Verified email for generic notifications, without recovery

The reporter explicitly adds an address, proves mailbox control and receives
only a generic prompt to open Convive with the secret they retained separately.
The address is not exposed in report or case views and cannot authenticate
follow-up access.

This adds a bounded contact and provider trust boundary while preserving the
existing anonymous capability model. This is the selected option.

### Option C: Email link or support verification recovers report access

Mailbox control proves neither that the same person submitted the report nor
that a requester is entitled to read it. Staff questions, the public reference,
names, report details and school knowledge are all guessable or socially
engineerable. Sending or resetting the secret would also contradict its
one-way-storage boundary. This option is rejected.

### Option D: Enrolled passkey as a separate recovery factor

A passkey enrolled while the reporter already holds access could provide
cryptographic proof independent of email. It adds browser/device compatibility,
shared-device, synchronisation, revocation and lost-device decisions and is not
needed for the initial fictional demonstration. It is deferred to a new ADR and
threat review if evidence shows that credential-manager storage is insufficient.

## Decision

Reporter email is optional, verified and used only for generic report-update
notifications. Convive remains fully usable without it. Email does not identify
the reporter to professionals, authenticate anonymous access, recover or reset
the access secret, or change the public reference into a credential.

The implementation owned by issue #40 must use a separate contact persistence
boundary. Ordinary report, conversation, case, professional list and audit
representations must contain neither the address nor provider message
identifiers or raw responses. Only the contact/notification application
component and narrowly authorised operators investigating delivery may read
them. Application logs and security events must use internal identifiers and
bounded outcomes, never addresses.

### Verification and consent

- The reporter deliberately opts in; submission and follow-up never preselect
  or require email.
- The consent notice explains that adding an address makes the reporter more
  identifiable and that the delivery system will learn that the mailbox uses
  Convive, without receiving report content.
- Convive records the purpose-specific consent time and notice version.
- A new or changed address remains unverified and receives no update notices.
- Verification uses a random, single-use, hashed bearer token with at least 128
  bits of entropy and a maximum 24-hour lifetime.
- The verification route contains no report reference or access secret, loads
  no third-party resources, emits `Referrer-Policy: no-referrer` and returns a
  generic success or failure that does not reveal a report.
- Verification issuance and attempts are rate limited. Reissuing invalidates
  earlier outstanding tokens.
- Verifying email proves control of that mailbox only. It grants no report
  capability and does not reveal whether a report has been updated.

The verification message may contain only the short-lived verification URL and
generic mailbox-control instructions. It contains no report reference, status,
content, organisation, access secret or follow-up link. Verification delivery
therefore cannot be mistaken for a report update.

### Safe update-notification content

The subject and body may say only that there is an update in Convive and direct
the recipient to the canonical application entry point
`https://app.conviveaula.com/seguimiento`. Update notifications must not contain
a report reference, access secret, capability, verification token, report/case
status, message text, names, organisation, evidence, professional identity or
deep link that identifies a report. The application URL is not an
authentication link.

Notification work is queued only after the report transaction commits. A queue,
provider or delivery failure never rolls back, hides or delays the report update.
Retries are bounded and idempotent; provider responses and local evidence are
redacted. A provider must not receive an internal report identifier as custom
metadata.

### Retention, removal and restoration

- Unverified contact and token records expire and are deleted within 24 hours.
- Verification tokens are deleted immediately after successful use or
  replacement.
- Opt-out or contact removal takes effect immediately for future deliveries and
  cancels queued/retry work before removing the address from active contact
  storage; suppression must use a non-reversible scoped digest if one is
  operationally required.
- Verified contact is deleted with its report or after the final closure notice
  reaches a delivered or terminal-failure state, no later than seven days after
  closure once closure exists. Delivery-attempt metadata has a 30-day maximum
  in the fictional demonstration.
- A real-data deployment remains blocked until the controller/DPO approves the
  exact periods, provider processing and rights procedure, and restore handling
  prevents an old backup from reviving removed contact or consent.

No production delivery vendor, DNS record, sender identity, processor or
subprocessor is selected by this ADR. Development and CI must use reserved
`.example` addresses and an isolated mail catcher; they must not send internet
email.

## Recovery position

The initial release has no email recovery and no manual support recovery. A
person who loses the access secret uses the existing guidance to submit a new
communication. Staff must not disclose, replace or bypass the secret based on
the public reference, mailbox access, personal details, report knowledge or
school records.

Any future recovery mechanism requires prior proof enrolled while the reporter
has an authorised capability, a separate ADR, negative abuse tests, revocation
and loss handling, and an updated privacy/threat review. Email alone is not such
proof.

## Consequences

### Positive

- Reporters can receive update prompts without putting sensitive content or
  credentials in email.
- No-email reporting and follow-up remain first-class and provider-independent.
- Mailbox compromise does not directly grant report access.
- Contact data is separated from professional report handling and has explicit
  deletion rules.

### Negative

- The mailbox and future delivery provider still learn that the recipient uses
  Convive.
- Lost-secret recovery remains deliberately unavailable.
- Verification, queues, removal, retry limits and backup-restoration suppression
  increase implementation and operational work.
- A real deployment requires controller/DPO and processor decisions that the
  fictional demonstration cannot make.

## Implementation status

Issue #40 implements the decision for fictional development data with a
PostgreSQL transactional outbox and a pinned Mailpit service. Production keeps
the feature disabled and uses the null transport unless an independently
reviewed runtime configuration enables it. The worker retains no plaintext
verification token, logs only internal delivery identifiers and bounded
outcomes, removes expired pending contacts within 24 hours, removes completed
delivery evidence after 30 days and suppresses every reporter contact and job
during isolated restoration. Reporters may opt in again after recovery.

## Deferred work

- Selecting and contracting a production email provider.
- Production sender-domain DNS and reputation controls.
- A passkey or other independently enrolled recovery factor.
- Real-data retention, rights and restore-reconciliation approval.

## Review triggers

Review this decision before adding recovery, another notification channel,
message customisation, professional visibility of contact data, a production
provider, real-data processing or materially different retention requirements.
