# Architecture

This directory documents the technical architecture of Convive.

It explains the main system components, how they communicate and the reasons behind the most important technical decisions.

## Main components

- **Web application:** the screens and forms used by reporters and school professionals. It will be built with Angular and TypeScript.
- **Backend API:** the business rules, permissions and secure access to data. It will be built with Symfony.
- **Database:** PostgreSQL stores the structured information managed by the
  backend. Doctrine ORM and DBAL provide persistence access, and Doctrine
  Migrations versions schema changes, as selected in
  [ADR-0007](decisions/0007-use-postgresql-and-doctrine-for-persistence.md).
- **Environment:** Docker Compose will provide reproducible development, testing and single-VPS deployment environments.

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
mechanism, expiry, CSRF and browser-storage rules are defined in
[ADR-0008](decisions/0008-use-server-side-sessions-and-capability-based-anonymous-access.md).

## Backend interface

Symfony will expose a resource-oriented HTTP API under the `/api/v1` path, as
selected in
[ADR-0006](decisions/0006-use-a-resource-oriented-json-http-api-with-an-openapi-contract.md).

The interface uses JSON success representations, Problem Details error
representations and an OpenAPI contract. Explicit Symfony controllers and
transport DTOs keep the external contract separate from domain and persistence
models. Symfony remains responsible for validating every request and authorising
every protected operation.
