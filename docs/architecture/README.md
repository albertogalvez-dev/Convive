# Architecture

This directory documents the technical architecture of Convive.

It explains the main system components, how they communicate and the reasons behind the most important technical decisions.

## Main components

- **Web application:** the screens and forms used by reporters and school professionals. It will be built with Angular and TypeScript.
- **Backend API:** the business rules, permissions and secure access to data. It will be built with Symfony.
- **Database:** the persistent structured information managed by the backend. PostgreSQL is the planned database, subject to its persistence ADR.
- **Environment:** Docker Compose will provide reproducible development, testing and single-VPS deployment environments.

## Basic communication flow

1. A user interacts with the Angular web application.
2. Angular sends an HTTPS request to the Symfony backend interface.
3. Symfony validates the request and applies the business and security rules.
4. Symfony reads or modifies information in the planned PostgreSQL database through its persistence layer.
5. Symfony returns a response that Angular presents to the user.

The frontend never accesses the database directly. All protected operations pass through the Symfony backend.

## Backend interface

Symfony will expose a resource-oriented HTTP API under the `/api/v1` path, as
selected in
[ADR-0006](decisions/0006-use-a-resource-oriented-json-http-api-with-an-openapi-contract.md).

The interface uses JSON success representations, Problem Details error
representations and an OpenAPI contract. Explicit Symfony controllers and
transport DTOs keep the external contract separate from domain and persistence
models. Symfony remains responsible for validating every request and authorising
every protected operation.
