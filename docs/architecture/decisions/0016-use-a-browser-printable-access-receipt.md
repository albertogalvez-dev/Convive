# ADR-0016: Use a browser-printable anonymous access receipt

- **Status:** Accepted
- **Date:** 11 August 2026
- **Related issue:** [#42](https://github.com/albertogalvez-dev/Convive/issues/42)
- **Depends on:** [ADR-0010](0010-use-a-single-secret-for-anonymous-report-access.md), [ADR-0011](0011-allow-the-reporter-browser-password-manager-to-store-the-access-secret.md), [ADR-0014](0014-separate-public-website-and-application-domains.md)

## Context

Convive reveals the anonymous access secret once. Copy and the reporter's own
credential manager remain available, but some reporters need a tangible receipt.
A plain-text download exposes the credential without context, while an
application-generated PDF adds a document generator, download endpoint and
metadata surface without making the resulting file inherently secure.

## Decision drivers

- Preserve a simple option on desktop and mobile without server-side secret handling.
- State clearly that possession of the secret grants access.
- Keep the receipt to the minimum information needed to return.
- Keep credentials out of filenames, URLs, document titles, analytics and logs.
- Preserve copy and the browser credential-manager option.
- Produce accessible source markup and a legible paper/PDF layout.

## Options considered

### Browser print view

Render a dedicated semantic receipt and invoke the operating system's print
dialog. The reporter may print it or use the browser's built-in “Save as PDF”.
No file is generated, named or transmitted by Convive.

### Application-generated PDF

Generate and download a PDF in the browser or backend. This gives Convive more
control over layout but adds code, metadata and possibly server processing while
providing no protection once the file is stored on the device.

### Plain-text download

Download the secret as text. This is easy to implement but strips away the risk
warning, is easily indexed or previewed and encourages unsafe filenames.

### Wallet or passkey

Enroll a device-bound credential. This may become a stronger recovery option,
but changes the authentication model and requires a separate security decision.

### No downloadable or printable option

Keep only copy and the credential manager. This avoids a new artefact but does
not meet the confirmed need for an understandable tangible receipt.

## Decision

Convive uses a browser-printable receipt. The application opens the native print
dialog and does not generate or download a file itself.

The printable content contains only:

- the canonical follow-up location `app.conviveaula.com/seguimiento`;
- the one-time disclosed access secret;
- a warning that anyone holding the secret can enter the communication.

The public reference remains visible in the on-screen confirmation as a
non-secret receipt/support identifier, but it is excluded from the printable
access receipt because ADR-0010 no longer requires it for authentication.

The page title, URL and any application-controlled filename or document metadata
must not include the reference or secret. Convive does not persist, upload or log
the receipt. Copy and permitted credential-manager storage remain independent
options; opening or cancelling the print dialog does not suppress the warning
shown before leaving an unpreserved secret.

## Consequences

### Positive

- The browser owns paper/PDF destination, filename and local storage choice.
- No PDF dependency, backend endpoint or new secret-bearing network request exists.
- The printed artefact carries its risk warning and only the required credential.
- The same source works with keyboard, mobile print sharing and assistive technology.

### Negative

- Browser print dialogs and PDF tagging vary by platform.
- Convive cannot prevent a reporter from choosing an unsafe destination or filename.
- A printed or saved receipt is a bearer credential and must be protected physically.

## Verification

- Component tests assert the print action, minimum fields, absence of the public
  reference and credential-free document title.
- Print-media browser review checks legibility and that normal page controls,
  report content and evidence are absent from the output.
- Desktop and mobile review confirms that copy remains available and the warning
  is visible before invoking print.

## Review triggers

Review this decision if Convive adopts a separately approved passkey/recovery
factor, browser print behaviour no longer preserves usable semantics, or evidence
shows the receipt materially increasing credential exposure.
