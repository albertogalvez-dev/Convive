# Architecture

This directory documents the technical architecture of Convive.

It explains the main system components, how they communicate and the reasons behind the most important technical decisions.

## Architecture diagram

The [initial system architecture diagram](diagrams/initial-system-architecture.md)
summarises the logical request flow between the application's users, Angular,
Symfony and PostgreSQL. Its accompanying text records the development routing
and initial Docker Compose and single-VPS deployment context.

## Data model

The [data model diagram](diagrams/data-model.md) shows the entity–relationship
view of the first domain schema (`organisations` and `reports`). Its
human-readable schema source is [`data-model.dbml`](data-model.dbml). The
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
public reference and access secret, Symfony issues a short-lived opaque
capability limited to that report and returns it in a protected cookie.

Professional sessions and anonymous capabilities use separate authenticator
and route boundaries. Every protected operation accepts only its declared
access context and remains subject to backend authorisation. The complete
access mechanism, expiry, CSRF constraints and required integration verification
are defined in
[ADR-0008](decisions/0008-use-server-side-sessions-and-capability-based-anonymous-access.md).

## Public organisation routing

Each organisation has a dedicated persisted public reporting identifier,
separate from its internal UUID. The identifier uses the `ORG_` prefix followed
by 16 random Crockford Base32 characters, providing 80 random bits in a
20-character canonical representation.

Input parsing is case-insensitive and normalises the common `O`/`0` and
`I`/`L`/`1` ambiguities. Invalid lengths, prefixes, symbols, whitespace and
Unicode homoglyphs are rejected. PostgreSQL enforces uniqueness and Doctrine
can resolve an organisation through the canonical public identifier.

The backend already accepts anonymous report submissions addressed by this
identifier. The identifier may be distributed through a stable link, QR code or
readable manual-entry fallback; QR generation, poster design and the Angular
reporting journey remain under development.

The public identifier is not authentication, proof of organisation membership
or an anonymous report follow-up credential. The initial product does not
require a shared or rotating centre access code and will not publish a complete
organisation directory. Abuse prevention remains an explicit API and
operational responsibility.

The routing decision and identifier boundary are defined in
[ADR-0009](decisions/0009-use-public-organisation-reporting-links.md).

## Backend interface

Symfony exposes a resource-oriented HTTP API under the `/api/v1` path, as
selected in
[ADR-0006](decisions/0006-use-a-resource-oriented-json-http-api-with-an-openapi-contract.md).

The interface uses JSON success representations, Problem Details error
representations and an OpenAPI contract. Explicit Symfony controllers and
transport DTOs keep the external contract separate from domain and persistence
models. Symfony remains responsible for validating every request and authorising
every protected operation.

The implemented operations are a service health check and the public anonymous
report submission endpoint:

```text
POST /api/v1/public/organisations/{publicReportingIdentifier}/reports
```

A successful submission returns `201 Created` with the report's public reference
and a one-time access secret. The generated contract is committed at
[`docs/api/openapi.yaml`](../api/openapi.yaml) and continuous integration fails
when the implementation and the contract drift apart.
