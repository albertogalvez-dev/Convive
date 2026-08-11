# ADR-0010: Use a single secret for anonymous report access

- **Status:** Accepted
- **Date:** 5 August 2026
- **Related issue:** [#21](https://github.com/albertogalvez-dev/Convive/issues/21)
- **Depends on:** [ADR-0006](0006-use-a-resource-oriented-json-http-api-with-an-openapi-contract.md), [ADR-0007](0007-use-postgresql-and-doctrine-for-persistence.md), [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md), [ADR-0009](0009-use-public-organisation-reporting-links.md)
- **Changes:** The anonymous follow-up verification requirements of [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md)
- **Amended by:** [ADR-0011](0011-allow-the-reporter-browser-password-manager-to-store-the-access-secret.md), which permits the reporter's own credential manager to store the access secret while keeping application-controlled storage prohibited

## Context

Convive generates two values after an anonymous report is submitted:

1. a public tracking reference;
2. a separate high-entropy access secret.

ADR-0008 currently requires the reporter to provide both values to enter the
private follow-up area.

This ADR changes only that verification requirement. The remaining professional
session, anonymous capability, cookie, expiry, revocation and security
boundaries selected in ADR-0008 remain applicable.

Requiring two values increases the information that the reporter must preserve
and re-enter. The access secret already contains 256 bits of cryptographically
secure random entropy and can identify and authorise access to one report
without using the public reference as an additional credential.

The public reference remains useful as a receipt and non-secret identifier, but
it does not need to participate in authentication.

This decision concerns only the initial verification used to unlock anonymous
follow-up. It does not change report submission, professional authentication or
the short-lived report-scoped capability issued after successful verification.

## Decision drivers

- Reduce the information that an anonymous reporter must preserve.
- Keep anonymous follow-up understandable on mobile devices.
- Preserve a non-secret reference as a receipt and support identifier.
- Prevent report enumeration and credential leakage.
- Keep the access grant limited to one report.
- Avoid storing readable access secrets.
- Preserve the capability-cookie boundary selected in ADR-0008.

## Options considered

### Option A: Require the public reference and access secret

The reporter provides both values before entering the private follow-up area.

### Option B: Require only the access secret

The reporter provides the high-entropy access secret. The backend derives its
one-way representation and locates the corresponding report directly.

The public reference remains available as a receipt but is not required to
unlock the report.

### Option C: Use the public reference as the only credential

The reporter provides only the public reference.

This option is not acceptable because the reference is a non-secret identifier
and must not grant access by itself.

## Decision

Convive will require only the high-entropy access secret to unlock anonymous
follow-up.

The public tracking reference will remain a separate non-secret value used as a
receipt and support identifier. It will not be required as an authentication
factor.

The access secret will continue to be returned only once after report
submission and stored by the backend only through a deterministic one-way
representation.

The current secret profile remains 32 cryptographically secure random bytes
encoded as 64 lowercase hexadecimal characters. The lookup representation is
the lowercase hexadecimal SHA-256 digest of that canonical secret. This
unkeyed deterministic digest is appropriate here because the input is a
uniformly random 256-bit secret rather than a human-selected password. It must
not be reused for low-entropy credentials.

The backend will locate the report through an indexed unique representation of
the submitted secret. It must not scan stored reports or compare the submitted
secret against every stored value.

After successful verification, the backend will issue the short-lived,
report-scoped capability selected in ADR-0008. The access secret will not be
sent with every subsequent request.

## Security constraints

- The access secret must contain at least 128 bits of cryptographically secure
  random entropy; the selected initial profile contains 256 bits.
- The submitted value must be exactly 64 lowercase hexadecimal characters and
  must not be normalised into alternative representations.
- The stored SHA-256 representation must be exactly 64 lowercase hexadecimal
  characters.
- The stored one-way representation must have a unique database constraint.
- Secret collisions must fail safely.
- Verification attempts must be rate limited before database or capability
  work.
- Invalid, unknown and revoked secrets must produce the same external failure.
- The secret must never appear in URLs, logs, analytics, emails, browser
  persistent storage or error details.
- Successful verification must rotate into a separate short-lived capability.
- Losing the secret must not create a staff-controlled recovery bypass.

## Consequences

### Positive

- The reporter only needs to preserve one access credential.
- The follow-up form becomes simpler and less error-prone.
- The public reference can be displayed and used without being mistaken for a
  password.
- Anonymous access remains limited to one report.

### Negative

- Possession of the secret alone grants access to the report.
- The secret lookup requires a unique indexed representation.
- Existing persistence and repository behaviour will require a migration and
  implementation changes.
- Losing the secret may mean losing anonymous access.

## Deferred work

This ADR does not implement:

- the anonymous follow-up API;
- the capability-cookie lifecycle;
- optional email notifications, whose notification-only boundary is selected
  in [ADR-0015](0015-use-verified-email-only-for-generic-reporter-notifications.md);
- secret recovery; ADR-0015 explicitly rejects email-only and manual-support
  recovery for the initial release;
- attachment access;
- the complete rate-limiting policy;
- professional report access.

These concerns must be delivered and tested through separate issues.

## Review triggers

Review this decision if:

- the access-secret entropy or encoding changes;
- an approved recovery mechanism is introduced;
- anonymous access must support a non-browser client;
- operational evidence shows unacceptable secret loss or compromise;
- a regulatory or threat-model review requires an additional factor.
