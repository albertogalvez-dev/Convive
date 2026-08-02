# ADR-0009: Use public organisation reporting links without mandatory access codes

- **Status:** Accepted
- **Date:** 1 August 2026
- **Related issue:** [#8](https://github.com/albertogalvez-dev/Convive/issues/8)
- **Depends on:** [ADR-0003](0003-use-a-separate-web-frontend.md), [ADR-0004](0004-use-angular-for-the-web-frontend.md), [ADR-0006](0006-use-a-resource-oriented-json-http-api-with-an-openapi-contract.md), [ADR-0007](0007-use-postgresql-and-doctrine-for-persistence.md), [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md)

## Context

A person submitting a public report must reach the reporting channel for the
correct educational organisation.

The organisation currently has an internal UUIDv7 identifier. That identifier
belongs to the persistence model and must not become the public entry mechanism
merely because it already exists.

A shared or rotating centre access code was proposed as a possible way to route
reporters and reduce abuse. A QR code displayed by the educational centre was
also proposed as a possible entry point.

These concerns must remain distinct:

1. routing determines which organisation receives a report;
2. authentication establishes a user's identity;
3. anonymous follow-up grants access to one existing report;
4. abuse prevention limits malicious or excessive use of a public channel.

A shared centre code cannot reliably authenticate an individual reporter. It
may be copied, forwarded, forgotten or unavailable to a person who needs to
report a situation.

ADR-0008 deliberately left the centre-routing mechanism undecided.

## Decision drivers

- Keep public reporting accessible without requiring an account.
- Minimise barriers for students and other legitimate reporters.
- Route every report to exactly one organisation.
- Avoid exposing internal persistence identifiers in public URLs.
- Avoid presenting a shared code as authentication or proof of membership.
- Support QR distribution and a practical manual-entry fallback.
- Keep report follow-up credentials separate from organisation routing.
- Apply abuse controls explicitly at the API and operational boundaries.
- Preserve a future path for identifier revocation or rotation.
- Avoid publishing a complete directory of participating organisations before
  that product and privacy decision is required.

## Options considered

### Option A: Stable public reporting link per organisation

Give each organisation a dedicated public reporting identifier and use it in a
stable reporting link. The link can be encoded in a QR code and the identifier
can also support manual entry.

Benefits:

- low-friction access from a poster, website or other trusted communication;
- no account or shared password is required;
- the organisation is determined before the report is submitted;
- public routing remains separate from internal identifiers and report secrets;
- the same link supports mobile and desktop browsers;
- the identifier can be revoked or rotated if operationally necessary.

Costs:

- the identifier requires a lifecycle and uniqueness rules;
- printed QR codes and links must be replaced if the identifier is rotated;
- possession of the link does not prove membership of the organisation;
- separate abuse-prevention controls remain necessary.

### Option B: Mandatory shared or rotating centre access code

Require reporters to enter a code distributed by the educational centre.

Benefits:

- may reduce casual submissions from people who have never received the code;
- a compromised code can be rotated;
- can be printed or distributed through existing school channels.

Costs:

- the code can be copied and shared;
- it does not authenticate or identify an individual;
- distribution and recovery add operational work for every centre;
- rotation can invalidate posters and instructions;
- reporters may be blocked when the code is unavailable;
- it can create a false impression of stronger access control;
- it does not replace rate limiting, validation or monitoring.

### Option C: Public organisation directory and manual selection

Publish a searchable list of organisations and ask the reporter to select one.

Benefits:

- no code or prior link is required;
- familiar interaction for users;
- a person can find a centre from the main application.

Costs:

- publishes the participating-organisation inventory;
- introduces search, ambiguity and wrong-centre submissions;
- requires decisions about naming, duplicates and geographical information;
- expands the first reporting slice without being necessary for QR access.

### Option D: Expose the internal organisation UUIDv7

Use the existing internal identifier directly in the route or request.

Benefits:

- requires no additional organisation identifier;
- UUIDv7 values are globally unique.

Costs:

- couples the public contract to persistence identity;
- exposes an internal identifier without a product reason;
- makes later rotation or revocation difficult;
- encourages clients to submit internal foreign keys;
- conflicts with the separation between internal and public identifiers.

## Decision

Convive will use a stable public reporting link for each organisation.

The link will contain a dedicated public reporting identifier. This identifier:

- belongs to the organisation's public reporting entry point;
- is distinct from the organisation's internal UUIDv7;
- is unique and non-sequential;
- is safe to expose in URLs, QR codes and printed instructions;
- routes a reporter to one organisation;
- is not authentication or proof of organisation membership;
- is not the report's public tracking reference;
- is not the report follow-up access secret;
- can have an explicit activation, revocation and rotation lifecycle.

The identifier format, alphabet and length are selected during implementation
and recorded in the implementation note below. Manual-entry usability must be
considered, but guess resistance must not be presented as an access-control
boundary because the identifier is public.

The initial product slice will not require a mandatory shared or rotating
centre access code.

The initial product slice will not require a public directory of all
organisations. A directory can be evaluated later if a demonstrated discovery
need justifies its product and privacy costs.

The organisation will be resolved from the public reporting entry point. A
report-submission payload must not be allowed to override that resolution by
supplying an arbitrary internal organisation identifier.

A QR code may encode the public reporting link. A readable identifier or URL
may be printed alongside it as a fallback.

Public-channel abuse prevention will be designed explicitly through controls
such as:

- server-side request validation;
- maximum payload lengths;
- request-rate limits;
- safe technical logging;
- monitoring and operational response;
- additional anti-automation controls if evidence justifies them.

These controls are separate from organisation routing and must not rely on the
public identifier remaining unknown.

## Consequences

### Positive

- Reporters receive a direct and low-friction route to the correct centre.
- Internal organisation identifiers remain private implementation details.
- Routing, authentication, anonymous follow-up and abuse prevention remain
  separate concepts.
- The design supports posters and mobile-first QR access.
- The first reporting journey does not depend on distributing a shared secret.
- Future revocation or rotation remains possible.

### Negative

- Organisations require an additional persisted identifier and lifecycle.
- QR material becomes operational configuration that must remain accurate.
- Anyone with the public link can reach the reporting form.
- Abuse controls must be implemented and monitored independently.
- Manual-entry usability requires testing and may justify a future format
  revision.

## Implementation note — 2 August 2026

The first persisted public organisation reporting identifier has been
implemented as the `ORG_` prefix followed by 16 random Crockford Base32
characters. The canonical representation is therefore 20 characters long and
contains 80 random bits.

Identifiers are generated with a cryptographically secure random source.
Parsing is case-insensitive and normalises `O` to `0` and `I` or `L` to `1`.
Other invalid characters, incorrect lengths, whitespace and Unicode homoglyphs
are rejected.

The identifier is represented by a domain value object, persisted separately
from the internal organisation UUID and protected by a PostgreSQL unique
constraint. The organisation repository can resolve an organisation through
the canonical public identifier.

The migration backfills existing organisations before applying the `NOT NULL`
and uniqueness constraints. This resolves the previously deferred identifier
format, alphabet and length. Public URL structure, activation, rotation and
revocation remain deferred.

## Deferred decisions

This ADR does not define:

- the final public URL structure;
- QR-code generation or poster design;
- identifier activation, rotation and redirect grace periods;
- a public organisation directory;
- the complete rate-limiting policy;
- CAPTCHA or another anti-automation provider;
- report-submission request and response representations;
- the report follow-up capability-cookie lifecycle.

These concerns require separate implementation or decision issues.

## Review triggers

Review this decision if:

- educational centres require a verified membership gate before submission;
- legitimate reporters cannot reliably obtain the public link;
- link sharing creates demonstrated safeguarding or operational harm;
- abuse cannot be controlled through explicit API and operational measures;
- a public directory becomes necessary;
- identifier rotation makes printed material operationally unmanageable;
- legal or educational-authority requirements impose another access mechanism.

## References

- [OWASP API Security — Unrestricted Resource Consumption](https://owasp.org/API-Security/editions/2023/en/0xa4-unrestricted-resource-consumption/)
- [NCSC password guidance — Managing shared access](https://www.ncsc.gov.uk/collection/passwords/updating-your-approach)
