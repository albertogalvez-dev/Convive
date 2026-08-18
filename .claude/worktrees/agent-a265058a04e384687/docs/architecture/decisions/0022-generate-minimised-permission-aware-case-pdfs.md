# ADR-0022: Generate minimised permission-aware case PDFs

- **Status:** Accepted
- **Date:** 11 August 2026
- **Related issue:** [#49](https://github.com/albertogalvez-dev/Convive/issues/49)
- **Depends on:** [ADR-0018](0018-require-case-assignments-for-case-content.md),
  [ADR-0020](0020-protect-case-audit-events-with-minimised-append-only-records.md)
  and [ADR-0021](0021-use-permission-preserving-operational-case-views.md)

## Context

An authorised professional may need a portable operational record, but an export
is a disclosure boundary rather than a convenience feature. A PDF must not turn
direct case access into an unrestricted data extraction path or create a public
analytics surface.

## Decision

Convive generates two server-side, in-memory PDFs only for an authenticated,
active professional:

- a **case record** requires the exact-case `export` permission, held by the
  active lead assignment only; and
- an **operational overview** contains aggregate counts derived solely from the
  requesting professional's exact active assignments. It contains no case,
  reporter, student, organisation or staff identity.

The case record contains only the case UUID, organisation name, status,
modality, creation time, current assignment role, task operational fields and
the already lead-authorised minimised audit events. It excludes report text,
triage reasons, involved-person names, attachment metadata/content, secrets,
session data, IP addresses and target identifiers. It is not an institutional
submission or a data-subject export.

PDFs are rendered in memory from controlled Spanish-language templates and
returned with `no-store`, attachment disposition, a neutral filename, PDF
content type, `X-Content-Type-Options: nosniff` and no-index metadata. No
generated document is persisted on the server. A successful case-record
generation appends the existing minimised case audit event before the response
is sent. Aggregate exports append only an actor, export kind and time to the
separate append-only `professional_export_events` record; it is subject to the
same bounded fictional-demo retention process and does not contain any export
contents or counts. Unauthorised and unknown case records share the existing
unavailable response.

## Consequences

- Contributors and observers cannot export their readable case view.
- A future institutional, legal-hold or subject-access export needs a separate
  privacy, retention and approval decision.
- The renderer becomes a reviewed application dependency and must be tested for
  both PDF structure and visual layout.

## Verification

- Authorisation tests prove lead-only case export and non-identifying aggregate
  output.
- Rendering tests inspect PDF type, metadata, headers and excluded content.
- A rendered fictional PDF is visually checked and programmatically parsed.
- Every successful export adds exactly one appropriate audit event.
