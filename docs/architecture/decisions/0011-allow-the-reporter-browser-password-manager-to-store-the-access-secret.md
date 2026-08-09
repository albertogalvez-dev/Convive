# ADR-0011: Allow the reporter's browser password manager to store the access secret

- **Status:** Accepted
- **Date:** 9 August 2026
- **Related issue:** [#26](https://github.com/albertogalvez-dev/Convive/issues/26)
- **Depends on:** [ADR-0004](0004-use-angular-for-the-web-frontend.md), [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md), [ADR-0010](0010-use-a-single-secret-for-anonymous-report-access.md)
- **Changes:** The browser-storage prohibition of [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md) and [ADR-0010](0010-use-a-single-secret-for-anonymous-report-access.md), for the reporter's own credential manager only

## Context

ADR-0010 requires the reporter to preserve a single 64-character hexadecimal
access secret, returned once and never recoverable. ADR-0008 and ADR-0010 then
prohibit that secret from appearing in "frontend persistent storage" and
"browser persistent storage".

Those prohibitions were written against storage the application controls:
`localStorage`, `sessionStorage`, IndexedDB and JavaScript-readable cookies. In
that setting the application decides to persist a credential, the reporter is
not asked, and any cross-site scripting flaw reads it directly.

The reporter's own credential manager is a different mechanism. It stores the
value outside the page, encrypted under the browser profile, only after the
person accepts an explicit prompt. When the browser later autofills the access
form, the value becomes available to that page like any other populated form
field; this decision therefore reduces application-managed persistence but does
not make the secret immune to compromised page JavaScript or cross-site
scripting.

The first follow-up journey built in #26 made the practical consequence
concrete: a value of this length cannot be memorised or reliably transcribed,
so the realistic failure is not credential theft but permanent loss of access
to one's own report, with no staff-controlled recovery by design.

This ADR narrows the storage prohibition. It does not introduce recovery, does
not change what authenticates the reporter and does not change the capability
lifecycle.

## Decision drivers

- Reduce permanent loss of anonymous follow-up access.
- Keep the application from deciding, on its own, to persist a credential.
- Avoid application-managed persistence and retrieval of the secret.
- Preserve the account-free, identity-free nature of anonymous follow-up.
- Keep the public reference non-authenticating.
- Keep shared-device exposure visible and reversible.
- Use standard browser mechanisms rather than a bespoke storage scheme.

## Options considered

### Option A: Keep the prohibition unchanged

The reporter must copy the secret to a place they choose, outside the browser.

Benefits: nothing is stored in the browser profile; the shared-device risk is
unchanged.

Costs: the most common realistic outcome remains losing the only credential;
reporters paste the secret into whatever is at hand, including places with
weaker protection than a credential manager.

### Option B: Allow the reporter's credential manager, never application storage

The submission receipt and the follow-up access form present the report
reference and access secret as a standard credential pair, so the browser can
offer to save them and later autofill them. The application never writes the
secret to storage it controls and never reads stored credentials silently.

Benefits: the secret survives in an encrypted, user-controlled store; saving
requires an explicit human decision; autofill removes the transcription error
that currently produces failed verification attempts; the mechanism is the one
people already use for credentials. Once autofilled, the value has the normal
exposure of a password field in the active page.

Costs: on a shared device profile, a saved secret is available to whoever uses
that profile; support depends on the browser; the accepted decisions must be
amended rather than silently contradicted.

### Option C: Application-managed retention or recovery

Convive stores the secret itself, or introduces a recovery channel.

This option is not acceptable. It contradicts ADR-0010's "losing the secret
must not create a staff-controlled recovery bypass" and would make Convive hold
a readable credential for an anonymous report.

## Decision

Convive will allow the reporter's browser credential manager to store the
report access secret, and will not store it in any storage the application
controls.

The prohibition in ADR-0008 and ADR-0010 is restated as follows: the access
secret and the capability handle must never be written to `localStorage`,
`sessionStorage`, IndexedDB, JavaScript-readable cookies or any other
application-controlled persistence, and must never appear in URLs, logs,
analytics, emails, referrer data or error details. Storage inside the browser's
own credential manager, chosen by the reporter through the browser's prompt, is
permitted and is not application-controlled persistence.

To make that possible:

- the submission receipt presents the public reference and the access secret as
  a credential pair, and offers an explicit action that asks the browser to
  save them;
- the follow-up access form marks the reference field as the credential
  identifier and the secret field as the credential secret, so the browser can
  autofill both;
- the reference field remains optional and is never sent to the API.

The public reference keeps the role assigned in ADR-0010: a non-secret receipt
and support identifier. Using it as the credential label does not make it an
authentication factor. Verification continues to accept only the access secret,
and the backend must not begin accepting the reference as a credential.

## Security constraints

- The application must not write the secret to application-controlled storage.
- Saving must require an explicit action by the reporter; it must never happen
  as a side effect of loading a page or submitting the report.
- Application code must not call a credential-retrieval API to obtain the
  secret in the background. The browser may populate the form through its
  normal autofill interaction, and Convive consumes that field only when the
  reporter deliberately submits the access form.
- Autofill does not protect a populated field from compromised same-page
  JavaScript. Convive's normal cross-site-scripting controls remain part of the
  credential boundary and must not be weakened on the assumption that the
  credential manager makes the value unreadable.
- The follow-up journey must keep asking for the secret after a page reload
  rather than resuming from the capability cookie, so a saved credential still
  requires a deliberate action to open the report.
- Explicit revocation ("cerrar acceso") must remain available and must continue
  to invalidate the server-side capability.
- The interface must state that the secret can be saved in the browser and warn
  that a shared device profile makes it available to other users of that
  profile.
- Nothing in this decision permits transmitting the secret anywhere other than
  the verification endpoint, and it must remain absent from logs and analytics.

## Consequences

### Positive

- The realistic failure mode, losing the only credential, becomes far less
  likely.
- The secret is kept in an encrypted, user-controlled store instead of an
  arbitrary note or message.
- Autofill removes transcription errors that consume the verification rate
  limit.
- The application's own storage boundary becomes stricter and easier to test,
  because it is now stated as an explicit list rather than a general phrase.

### Negative

- On a shared browser profile, a saved secret is reachable by other users of
  that profile.
- Credential-manager behaviour and the availability of an explicit save action
  differ between browsers, so the interface must degrade to copying the secret
  manually.
- Convive must keep two adjacent concepts distinct in documentation and review:
  permitted credential-manager storage and prohibited application storage.

## Deferred work

This ADR does not introduce:

- secret recovery or a support-operated bypass;
- passkeys or any other authenticator for anonymous follow-up;
- optional email notifications or an email-based reminder of the reference;
- professional credential storage guidance, which remains owned by ADR-0008.

## Review triggers

Review this decision if:

- an approved recovery mechanism is introduced;
- the credential profile in ADR-0010 changes;
- evidence shows saved secrets being exposed on shared school devices;
- browsers change how explicit credential storage is requested or consented;
- a threat-model or regulatory review requires an additional factor.
