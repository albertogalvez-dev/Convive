# Attachment security boundary and threat model

**Status:** fictional-data backend boundary implemented; reporter UI remains
unimplemented

**Owner:** repository maintainer

**Related issues:** [#36](https://github.com/albertogalvez-dev/Convive/issues/36),
[#37](https://github.com/albertogalvez-dev/Convive/issues/37),
[#38](https://github.com/albertogalvez-dev/Convive/issues/38)

## Purpose and scope

Attachments can contain evidence, identifying metadata and hostile content.
They are therefore a separate security boundary from the existing bounded text
report and follow-up fields. This document sets the non-negotiable design that
must hold before an attachment control is exposed.

It does **not** authorise real safeguarding data or select or procure a
production storage vendor. The current demonstration continues to use only
fictional data. #37 implements a private, application-mediated backend
boundary; #38 remains responsible for any reporter-facing upload control and
genuine transfer-progress behaviour.

The design intentionally favours a narrow evidence format set, private
application-mediated access and unavailable evidence over an unsafe preview or
download. It is based on defence in depth: an allowlist, byte limits, server
format detection, generated storage names, isolation, malware scanning and
authorisation must all hold. No individual control is sufficient.

The [security data-flow and trust-boundary diagram](../architecture/diagrams/security-data-flow.md)
places this private lifecycle beside the separate reporter, professional and
operational credential boundaries; it does not change the fictional-only scope.

## Decisions

### Initial evidence format and capacity policy

The implemented backend accepts only these formats:

| User-visible format | Required detected media type | Rationale                                                              |
| ------------------- | ---------------------------- | ---------------------------------------------------------------------- |
| PDF                 | `application/pdf`            | Common fixed-layout evidence; still scanned and never rendered inline. |
| JPEG                | `image/jpeg`                 | Common camera output; no thumbnail or transformation is performed.     |
| PNG                 | `image/png`                  | Common screenshot format; no thumbnail or transformation is performed. |

The server must reject every other format, including Office documents, SVG,
HTML, archives, executable files, audio and video. It must determine the
format from bounded server-side bytes/signature handling; the multipart
filename and `Content-Type` request header are only advisory and never a
security decision. The original filename is not persisted or used in an object
path. A safe display/download name is generated from the attachment identifier
and the detected extension.

| Limit                                   |   Value | Enforcement point                                              |
| --------------------------------------- | ------: | -------------------------------------------------------------- |
| File bytes                              |   5 MiB | Streaming server limit before durable acceptance               |
| Files in one initial or follow-up write |       3 | Server transaction/request validation                          |
| Files per report                        |      10 | Transactional report-scoped check                              |
| Aggregate attachment bytes per report   |  20 MiB | Transactional report-scoped check, including quarantined bytes |
| Multipart request bytes                 |  16 MiB | Trusted edge/runtime limit and application defence in depth    |
| Concurrent attachment download streams  |       4 | Private-volume permit held for the streamed response lifetime  |
| Filename received from a client         | ignored | Never persisted or used for storage or response headers        |

All limits include files awaiting a scan so that a client cannot exhaust
storage by repeatedly submitting work that is never released. #37 must make
the aggregate checks race-safe. #38 may show the genuine byte count and limits,
but browser validation is only usability feedback; the server is authoritative.

### Storage, identifiers and encryption

- Attachment bytes live in a private attachment store outside the application
  web root and outside the public-site deployment. The existing Restic backup
  repository and its bucket/credentials must never serve attachment bytes.
- Quarantine and available objects use separate private namespaces and,
  where the selected store supports it, separate least-privilege credentials.
  A scanner may read quarantine objects and write a verdict, but may not read
  report data, professional sessions, backup material or available objects.
- The application generates an opaque attachment identifier and storage key;
  no client-supplied filename, path, public report reference or organisation
  name appears in a key. An internal integrity digest may be held with the
  attachment record but is not logged or exposed to reporters.
- Transport to storage and to a scanner uses authenticated encryption in
  transit. The selected private store must encrypt at rest. Provider selection,
  processor terms, key custody and any customer-managed-key decision remain a
  real-data gate; this document does not claim them implemented.
- No attachment gets a public-development URL, public object ACL, CDN route,
  static web-server path or direct client-to-store credential. Downloads are
  authorised and streamed by the application.

### Lifecycle and fail-closed scanning

```text
bounded upload
  -> QUARANTINED (private bytes; no read path)
  -> SCANNING (isolated scanner reads a bounded copy)
  -> AVAILABLE (all validation and scanner checks succeeded)
  -> application-mediated authorised download

scan rejection, timeout or scanner outage
  -> remains unavailable
  -> automatic deletion of raw quarantined bytes within 24 hours
  -> minimal non-content audit outcome only
```

The domain model introduced by #37 makes these states explicit:
`QUARANTINED`, `SCANNING`, `AVAILABLE`, `REJECTED`, `DELETION_PENDING` and
`DELETED`. Only `AVAILABLE` is downloadable. Validation failures that have not
written bytes create no attachment record; failures after a quarantined write
must not create a readable attachment.

Scanning has a bounded 30-minute processing window. A missing, unhealthy or
timed-out scanner is a failed safety condition, not a reason to release the
file: the attachment remains unavailable, a bounded retry may be scheduled,
and raw quarantined bytes are erased within 24 hours if no successful verdict
arrives. There is no professional override and no download-anyway path.

The scanner is an isolated, regularly updated service. It must receive only
the object needed for a scan and return a small verdict; it must not call a
public malware-scanning API with safeguarding evidence.

### Provisioned scanner (#180, 16 August 2026)

The approved implementation is a local ClamAV daemon, pinned by digest,
reachable from the API only over an internal `scan` network that carries no
other traffic. It joins a second network used solely by `freshclam` to refresh
signatures against the official ClamAV endpoint once a day; it shares no
network with the database, Redis or the attachment store, so a compromised
scanner cannot reach persisted data through it.

The API talks to it with the `INSTREAM` command, which carries file bytes and
nothing else: no filename, report reference or professional identity reaches
the daemon. This is asserted directly by a test that compares the exact bytes
the daemon receives.

Every failure mode returns `Unavailable`, never `Clean`. An unset `CLAMAV_DSN`,
an unreachable daemon, a timeout, an `ERROR` reply, an empty reply and an
unrecognised reply all leave the attachment quarantined. Only an explicit `OK`
from a daemon that actually answered releases a file, so an environment without
a provisioned scanner — including local development and CI — fails closed by
construction rather than by configuration discipline. The first release does
not extract archives, create previews/thumbnails, transcode media, run OCR or
perform content-disarm/reconstruction. Those operations expand the parser
attack surface and require their own threat review.

### Metadata and content handling

PDF and image metadata can identify a person, device or location. Convive will
not silently strip or rewrite the original evidence in the first release:
doing so can alter material that a centre needs to assess and cannot prove the
absence of every identifier. The reporter-facing journey must warn that a file
can contain names, location or other identifying data and ask for only
necessary evidence. No EXIF, PDF metadata, extracted text or thumbnail is
displayed by default.

A reporter may optionally associate up to 500 characters of plain-text context
with an attachment. This is stored as report data and is made available only to
the same authorised reporter and to an authorised professional when the
attachment itself is available. It is not a filename, media-derived metadata,
scanner output or storage key. Empty context is stored as absent; the original
client filename remains neither persisted nor returned.

If a later product decision introduces a derived redacted or metadata-stripped
copy, it must preserve the original's private lifecycle, label the derivative,
record provenance, keep both copies subject to the same authorisation and have
its own security/privacy review. It must never silently replace the original.

### Authorisation and safe retrieval

| Actor and operation                           | Required boundary                                                                        | Result                                                                               |
| --------------------------------------------- | ---------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| Reporter adding evidence to an initial report | Existing public submission controls plus the new report-scoped limits                    | May create quarantined evidence only.                                                |
| Reporter adding/fetching evidence later       | Valid, non-revoked capability for that exact report                                      | May see minimal status and download only available evidence for that report.         |
| Professional reading evidence                 | Active professional session, active organisation membership and report/object permission | May access only available evidence in authorised scope; each retrieval is auditable. |
| Scanner/cleanup worker                        | Dedicated least-privilege service identity                                               | May perform only its defined lifecycle operation.                                    |
| Anyone else, including a guessed object key   | No authority                                                                             | Generic denial with no attachment existence disclosure.                              |

The server re-authorises every list and download; Angular never decides access.
Quarantined, rejected, pending-deletion and deleted evidence is unavailable to
both reporters and ordinary professionals. Security/operational diagnostic
detail is not exposed through either journey.

The initial download endpoint must stream the exact private object only after
authorisation and use an application-derived extension. It must send at least:

- `Content-Disposition: attachment` with a generated filename, never the
  client filename;
- a detected, allowlisted `Content-Type`, never the request header value;
- `X-Content-Type-Options: nosniff`;
- `Cache-Control: private, no-store`;
- `Content-Security-Policy: sandbox; default-src 'none'`; and
- `Cross-Origin-Resource-Policy: same-origin`.

It must not provide an inline preview, a public or long-lived signed URL,
cross-origin access, server filesystem path, scanner verdict or object key.
The response size and concurrent-download limits become part of #37's abuse
controls; range requests are unsupported until explicitly designed and tested.

### Retention, deletion and audit responsibility

Attachment bytes are sensitive report data, not ordinary product uploads.
Until an approved controller/DPO retention, deletion, rights and legal-hold
policy exists, no real-data attachment processing is permitted.

For the fictional demonstration:

- rejected, abandoned and scanner-unavailable quarantine bytes are erased no
  later than 24 hours after their terminal outcome;
- available bytes, attachment metadata and any derived copies are deleted
  together when the fictional report is reset or deleted, and in any event no
  later than 30 days after creation;
- a deletion worker must retry failures, surface an operational alert without
  content, and keep a minimal deletion audit outcome for 30 days; and
- backups inherit their approved encrypted-recovery lifecycle, but a future
  real-data policy must make backup expiry and restore deletion continuity
  explicit before evidence is accepted.

The controller/DPO owns purpose, lawful retention, legal-hold and subject-right
decisions for any real deployment. The application owner owns implementation of
the timer, atomic metadata/object/derivative deletion and audit evidence. The
storage operator owns private access, encryption configuration, lifecycle job
operation and incident escalation. A legal hold is not implemented and must
block automatic deletion only after a reviewed product/operational increment.

## Threats, controls and residual work

| ID   | Threat                                                          | Required controls                                                                                               | Residual work/owner                                          |
| ---- | --------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------ |
| A-01 | Malware, active PDF/image content or parser exploit             | Narrow allowlist, signature check, private quarantine, fail-closed scanner port, no inline rendering or transformations | Configure/review an isolated scanner before any real-data use |
| A-02 | Archive/decompression or media-processing resource exhaustion   | Archives/audio/video/extraction/OCR/transcoding are not accepted                                                | Reassess only in a dedicated threat review                   |
| A-03 | Filename/path traversal or object overwrite                     | Ignore original name; generate opaque identifier/key; no web-root storage                                       | #37 persistence and negative tests                           |
| A-04 | Cross-report or cross-organisation disclosure                   | Capability/role/object checks on every read; no public object URLs                                              | #37 authorisation tests; #44 object-policy work              |
| A-05 | Browser execution, cache leakage or content sniffing            | Attachment disposition, detected type, `nosniff`, no-store, sandbox CSP, same-origin resource policy            | #37 HTTP tests; #38 must not add preview                     |
| A-06 | Scanner outage or false operational confidence                  | Fail closed, bounded retry, 24-hour quarantine deletion, no override                                            | #37 worker and unavailable-scanner tests                     |
| A-07 | Personal data in EXIF/PDF metadata                              | Warn before upload; preserve private original; do not extract/render metadata                                   | #38 UX copy and future redaction decision                    |
| A-08 | Storage exhaustion and abusive uploads                          | Per-file/request/report limits, transactional aggregate enforcement, four private download permits and existing abuse controls | Deployment controls remain #63                               |
| A-09 | Evidence disclosure to a third-party scanner or backup provider | No public scanning service; dedicated scanner identity; separate private storage from backup repository         | Provider/processor review before real data                   |
| A-10 | Incomplete deletion or restored evidence after expiry           | Explicit lifecycle, deletion command, backup continuity gate                                                    | Scheduled-operation evidence; controller/DPO real-data policy |

## Delivery contract for dependent issues

### #37 — backend and storage boundary

#37 introduced the attachment metadata/state model, server-side streaming
limits, generated object keys, private storage abstraction, fail-closed
scanner/cleanup interfaces, authorisation, audit events and OpenAPI
documentation. Its tests prove unsupported, spoofed, oversized, excessive,
quarantined, failed-scan and cross-scope requests are denied without leaking
content or paths. It does not select a production provider or send a file to
an external service.

### #38 — reporter experience

#38 may expose only the accepted formats and genuine byte-level upload
progress. It must describe attachment privacy/metadata honestly, make no
synthetic progress claim, show unavailable status without unsafe detail and
never add an inline preview or browser-side security bypass.

## Alternatives rejected

- **Accept every document and block known bad extensions:** a denylist
  cannot safely cover active, archive and parser formats.
- **Store evidence beside the Angular assets or return object URLs:** this
  collapses authorisation and turns a content upload into a public-hosting
  feature.
- **Treat a client MIME header or filename as proof of type:** both are
  attacker-controlled transport metadata.
- **Release an attachment while malware scanning is unavailable:** it trades
  a temporary availability problem for unbounded harm to reporters,
  professionals and the host.
- **Use a public scanning service by default:** uploading safeguarding evidence
  to another provider creates a new disclosure boundary without a reviewed
  processor/retention decision.
- **Silently strip metadata from the only stored evidence:** this can alter
  context and does not eliminate all identity risk; a derivative needs its own
  explicit, traceable design.

## Review triggers

Review this document before adding a format, preview, thumbnail, OCR,
transformation, external scanner, object-store provider, public download URL,
legal hold, real-data deployment or a change to report retention/authorisation.
Update the main threat model and privacy engineering register in the same
change whenever any of those changes becomes implemented.

## References

- [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)
- [OWASP Unrestricted File Upload](https://owasp.org/www-community/vulnerabilities/Unrestricted_File_Upload)
