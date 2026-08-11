# Architecture

This directory documents the technical architecture of Convive.

It explains the main system components, how they communicate and the reasons behind the most important technical decisions.

Cross-cutting security and privacy boundaries are maintained in the
[threat model and privacy engineering register](../security/README.md).

## Architecture diagram

The [diagram catalogue](diagrams/README.md) records each diagram's audience,
implementation source and maintenance status. It is the index to the current
logical, sequence, deployment, recovery and data-model views.

The [initial system architecture diagram](diagrams/initial-system-architecture.md)
summarises the logical request flow between the application's users, Angular,
Symfony and PostgreSQL. Its accompanying text records the development routing
and initial Docker Compose and single-VPS deployment context.

The [single-VPS deployment diagram](diagrams/single-vps-deployment.md) records
the production trust boundaries between Cloudflare Tunnel, the private gateway,
Symfony, PostgreSQL and Redis. The accompanying
[deployment runbook](../operations/deployment-release-and-rollback.md) defines
preflight, smoke-test and rollback decisions for the fictional demonstration.

The [implemented reporting sequence](diagrams/reporting-sequence.md) covers
the current anonymous and professional conversation paths. The [recovery
flow](diagrams/recovery-flow.md) covers the encrypted off-host backup gate and
isolated restoration exercise. ADR-0015 defines the future optional-email
boundary without claiming an implemented delivery path. Future case-management,
email-delivery and analytics behaviour is deliberately not drawn until its
implementation exists.

## Data model

The [data model diagram](diagrams/data-model.md) and its human-readable schema
source [`data-model.dbml`](data-model.dbml) show the persisted domain model.
The [data-model review](data-model-review.md) records the table inventory,
explicit infrastructure/DBAL exclusions and repeatable review command. The
authoritative sources remain the Doctrine mappings and the committed migrations.

## Main components

- **Web application:** the screens and forms used by reporters and school
  professionals, built with Angular and TypeScript.
- **Backend API:** the business rules, permissions and secure access to data,
  implemented with Symfony.
- **Database:** PostgreSQL stores the structured information managed by the
  backend. Doctrine ORM and DBAL provide persistence access, and Doctrine
  Migrations versions schema changes, as selected in
  [ADR-0007](decisions/0007-use-postgresql-and-doctrine-for-persistence.md).
- **Environment:** Docker Compose provides the reproducible development and
  testing environment and is the selected foundation for the future single-VPS
  deployment environment.
- **Public ingress:** a named Cloudflare Tunnel reaches a Convive-owned private
  gateway without publishing a VPS port or sharing ProjectX infrastructure, as
  selected in [ADR-0012](decisions/0012-use-cloudflare-tunnel-for-the-single-vps-deployment.md).
- **Shared security state:** production Redis provides restart-resistant,
  shared idempotency and rate-limit state and is reachable only by Symfony.
  Professional sessions remain in PostgreSQL as selected in ADR-0008.

## Basic communication flow

1. A user interacts with the Angular web application.
2. Angular sends an HTTPS request to the Symfony backend interface.
3. Symfony validates the request and applies the business and security rules.
4. Symfony reads or modifies information in PostgreSQL through module-owned
   Doctrine persistence adapters.
5. Symfony returns a response that Angular presents to the user.

The frontend never accesses the database directly. All protected operations pass through the Symfony backend.

## Access boundaries

Professional users authenticate through stateful Symfony sessions stored in
PostgreSQL. Anonymous reporters do not receive professional accounts or a
second framework session. After the reporter proves possession of a report's
access secret, Symfony issues a short-lived opaque capability limited to that
report and returns it in a protected cookie. The public reference remains a
non-secret receipt and support identifier and is not required for access.

Professional sessions and anonymous capabilities use separate authenticator
and route boundaries. Every protected operation accepts only its declared
access context and remains subject to backend authorisation. The complete
access mechanism, expiry, CSRF constraints and required integration verification
are defined in
[ADR-0008](decisions/0008-use-server-side-sessions-and-capability-based-anonymous-access.md)
as amended by
[ADR-0010](decisions/0010-use-a-single-secret-for-anonymous-report-access.md)
and
[ADR-0011](decisions/0011-allow-the-reporter-browser-password-manager-to-store-the-access-secret.md).

The reporter may keep the access secret in their own browser credential
manager, chosen through the browser's own prompt. Convive never writes the
secret or the capability handle to storage the application controls, and never
invokes a credential-retrieval API in the background; it consumes an autofilled
secret only when the reporter deliberately submits the access form.

## Public organisation routing

Each organisation has a dedicated persisted public reporting identifier,
separate from its internal UUID. The identifier uses the `ORG_` prefix followed
by 16 random Crockford Base32 characters, providing 80 random bits in a
20-character canonical representation.

Input parsing is case-insensitive and normalises the common `O`/`0` and
`I`/`L`/`1` ambiguities. Invalid lengths, prefixes, symbols, whitespace and
Unicode homoglyphs are rejected. PostgreSQL enforces uniqueness and Doctrine
can resolve an organisation through the canonical public identifier.

The backend resolves the organisation's minimal public reporting profile before
accepting anonymous report submissions addressed by this identifier. The
identifier may be distributed through a stable link, QR code or readable
manual-entry fallback; QR generation and poster design remain under development.

The public identifier is not authentication, proof of organisation membership
or an anonymous report follow-up credential. The initial product does not
require a shared or rotating centre access code and will not publish a complete
organisation directory. Abuse prevention remains an explicit API and
operational responsibility.

The routing decision and identifier boundary are defined in
[ADR-0009](decisions/0009-use-public-organisation-reporting-links.md).

## Public website and application boundary

The future public product website and the application have separate
responsibilities. `conviveaula.com` is for product, blog, demonstration and
contact content, while `app.conviveaula.com` hosts reporter and professional
routes. The QR route stays direct on the application host and never passes
through commercial onboarding. These are architecture targets, not evidence of
public DNS or deployment. Canonical routes, wordmark destinations, indexing,
cookie and referrer rules are defined in
[ADR-0014](decisions/0014-separate-public-website-and-application-domains.md).

## Backend interface

Symfony exposes a resource-oriented HTTP API under the `/api/v1` path, as
selected in
[ADR-0006](decisions/0006-use-a-resource-oriented-json-http-api-with-an-openapi-contract.md).

The interface uses JSON success representations, Problem Details error
representations and an OpenAPI contract. Explicit Symfony controllers and
transport DTOs keep the external contract separate from domain and persistence
models. Symfony remains responsible for validating every request and authorising
every protected operation.

The implemented operations are a service health check, public organisation
profile resolution and anonymous report submission:

```text
GET  /api/v1/public/organisations/{publicReportingIdentifier}
POST /api/v1/public/organisations/{publicReportingIdentifier}/reports
```

The profile response contains only the organisation's public name. Malformed and
unknown identifiers produce the same `404 Not Found` Problem Details response,
allowing the Angular journey to distinguish an invalid link from a recoverable
connectivity failure without exposing internal organisation data.

A successful submission returns `201 Created` with the report's public reference
and a one-time access secret. The generated contract is committed at
[`docs/api/openapi.yaml`](../api/openapi.yaml) and continuous integration fails
when the implementation and the contract drift apart.
