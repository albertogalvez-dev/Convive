# ADR-0006: Use a resource-oriented JSON HTTP API with an OpenAPI contract

- **Status:** Accepted
- **Date:** 20 July 2026
- **Related issue:** [#1](https://github.com/albertogalvez-dev/Convive/issues/1)
- **Depends on:** [ADR-0002](0002-use-a-modular-monolith-for-the-backend.md), [ADR-0003](0003-use-a-separate-web-frontend.md), [ADR-0004](0004-use-angular-for-the-web-frontend.md)

## Context

Convive uses one Angular web application for its public reporting experience and
its authenticated professional workspace.

Symfony contains the authoritative business rules, validation, authorisation,
workflow operations, auditing and persistence access.

Angular and Symfony therefore require an explicit communication contract.

This interface will support operations including:

- submitting and tracking reports;
- assessing reports;
- creating and managing cases;
- recording actions, communications and evidence;
- managing tasks and deadlines;
- searching authorised reports and cases;
- producing controlled exports.

Many of these operations are workflow-oriented and security-sensitive. They
cannot be treated as unrestricted database CRUD operations.

The interface must remain understandable, testable and documented without
exposing persistence entities or persistence details to the frontend.

A decision is required on:

- the HTTP API style;
- the Symfony implementation approach;
- resource and property naming;
- media types;
- HTTP method conventions;
- status and error responses;
- filtering and pagination;
- contract documentation and verification;
- compatibility and versioning.

Authentication and session mechanics are owned by
[ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md)
and remain outside this API-style decision.

## Decision drivers

- Keep Symfony responsible for authoritative validation and authorisation.
- Preserve the modular-monolith boundaries selected in ADR-0002.
- Avoid exposing persistence entities or database structure as a public contract.
- Represent workflow operations explicitly.
- Give Angular a stable and machine-readable contract.
- Use standard HTTP semantics and interoperable media types.
- Return safe and consistent errors.
- Support accessible frontend validation and error presentation.
- Prevent accidental duplicate submissions where retries are possible.
- Support contract and integration testing.
- Keep implementation understandable for one primary developer.
- Avoid framework automation that obscures security or business behaviour.
- Allow the interface to evolve without silently breaking clients.

## Options considered

### Option A: Explicit Symfony controllers with input and output DTOs

Define HTTP routes explicitly in each backend module.

Controllers map requests to transport DTOs, validate them and delegate to
application use cases. Application and domain behaviour remain independent from
HTTP, Symfony request objects and persistence operations. Domain entities may
carry the controlled Doctrine mapping metadata described in ADR-0007, but those
entities are never transport DTOs.

Use Symfony's request mapping, Validator and Serializer components together with
NelmioApiDocBundle and OpenAPI attributes.

Benefits:

- request handling and authorisation entry points remain explicit;
- workflow operations map clearly to application use cases;
- transport DTOs are separated from domain and persistence models;
- debugging and testing follow a visible execution path;
- Symfony is learned and used directly;
- OpenAPI documentation can be generated from routes and DTOs;
- framework behaviour is introduced only where it provides value.

Costs:

- more controller and DTO code must be written;
- pagination, filtering and error mapping require project conventions;
- OpenAPI annotations and exported contracts must be maintained;
- repetitive CRUD operations receive less automatic implementation.

### Option B: API Platform exposing persistence entities

Mark persistence entities as API resources and use automatically generated CRUD
operations.

Benefits:

- very fast initial CRUD development;
- automatic routes, serialisation, validation, filters and pagination;
- automatically generated OpenAPI documentation;
- reduced initial boilerplate.

Costs:

- the external API becomes coupled to persistence models;
- internal fields may be exposed accidentally;
- database changes can become API changes;
- serialisation groups can become an implicit security boundary;
- domain workflows are easily reduced to direct field updates;
- module boundaries can be bypassed by generic persistence processors;
- the approach is a poor fit for sensitive workflow-oriented operations.

### Option C: API Platform with API resources, DTOs, providers and processors

Use dedicated API resource models rather than exposing persistence entities.

Custom state providers and processors delegate to application use cases.

Benefits:

- API models remain separate from persistence entities;
- OpenAPI, validation, pagination and filtering remain highly automated;
- standard operations require less code;
- custom application behaviour can be introduced through processors.

Costs:

- developers must understand an additional request and state-processing model;
- providers, processors, resource metadata and Symfony application services must
  remain aligned;
- many Convive operations would still require custom behaviour;
- debugging crosses more framework layers;
- metadata can accumulate business and security decisions;
- the benefit becomes smaller when most write operations are workflow-specific.

### Option D: Contract-first OpenAPI with generated server and client code

Write the OpenAPI document before implementation and generate backend interfaces
and Angular client code from it.

Benefits:

- the contract is reviewed before implementation;
- frontend and backend can work from the same schema;
- incompatible changes are visible;
- suitable for separately owned clients and services.

Costs:

- generated backend code adds another maintenance boundary;
- contract editing precedes domain learning;
- generated output may require customisation;
- a solo developer must coordinate generated and handwritten code;
- early contracts may become artificially rigid while the domain is still being
  discovered.

### Option E: GraphQL

Expose a GraphQL schema and allow clients to request selected fields.

Benefits:

- clients can request precise data shapes;
- one schema supports varied query needs;
- strongly typed tooling is available.

Costs:

- no current client requires arbitrary query composition;
- field-level authorisation is more complex;
- query cost and depth require protection;
- HTTP caching and operational monitoring become less direct;
- uploads and workflow commands still require deliberate conventions;
- it adds a new query model without solving a confirmed product problem.

## Decision

Convive will expose a pragmatic resource-oriented HTTP API implemented through
explicit Symfony controllers and dedicated input and output DTOs.

The backend will use:

- Symfony routes and controllers for HTTP entry points;
- request DTOs for accepted input;
- Symfony Validator for boundary validation;
- application use cases for orchestration;
- domain objects and rules for authoritative behaviour;
- response DTOs for public representations;
- Symfony Serializer for JSON serialisation;
- NelmioApiDocBundle and OpenAPI attributes for contract generation.

API Platform will not be included initially.

Persistence entities must not be exposed as request or response models.

A controller must not contain domain workflow logic. It receives transport data,
checks the relevant boundary concerns, delegates to an application use case and
maps the result to an HTTP response.

A typical flow is:

```text
HTTP request
    -> Symfony controller
    -> input DTO and boundary validation
    -> application use case
    -> domain rules
    -> persistence port or other infrastructure
    -> output DTO
    -> HTTP response
```

## API base path and versioning

The initial API will use the following base path:

```text
/api/v1
```

The `/api` prefix separates the backend interface from Angular routes.

The `v1` segment identifies the first public contract version.

Additive compatible changes may remain in `v1`, including:

- adding optional response fields;
- adding optional request fields;
- adding endpoints;
- adding documented error types.

A new version is required when a change would break a supported client, such as:

- removing or renaming a field;
- changing a field's meaning or type;
- changing required request content incompatibly;
- removing an operation;
- changing established workflow semantics incompatibly.

Versions will not be created for ordinary implementation changes that preserve
the documented contract.

## Resource paths

Paths will use plural resource names.

Examples include:

```text
/api/v1/reports
/api/v1/cases
/api/v1/tasks
/api/v1/communications
```

Path segments must:

- use lowercase characters;
- use `kebab-case` when more than one word is required;
- use nouns rather than generic verbs;
- use consistent product terminology;
- avoid exposing database table names as an implementation detail.

Illustrative endpoint names in this ADR do not define the complete endpoint
catalogue. Feature specifications and the OpenAPI contract will define concrete
operations.

## HTTP method conventions

### GET

`GET` retrieves a representation and must not perform a business state change.

Examples:

```text
GET /api/v1/cases
GET /api/v1/cases/{caseId}
```

Security-relevant access logging does not change the safe semantics of the
operation.

### POST

`POST` creates a resource or requests an explicit domain operation.

Examples:

```text
POST /api/v1/reports
POST /api/v1/cases/{caseId}/transitions
```

A workflow transition must pass through an application use case. The frontend
must not bypass workflow rules by changing a status field directly.

Successful resource creation should return `201 Created` and a `Location`
header when a stable location is available.

### PATCH

`PATCH` may be used for genuine partial updates to independently editable
fields.

JSON Merge Patch will use:

```text
application/merge-patch+json
```

For a merge-patch request:

- an omitted property remains unchanged;
- `null` clears a property only when that property is nullable;
- protected or immutable properties are rejected;
- workflow transitions must not be represented as unrestricted field patches.

### PUT

`PUT` will not be introduced initially because Convive does not currently
require complete resource replacement.

It may be added later for a resource whose complete replacement semantics are
clear and useful.

### DELETE

`DELETE` will only be provided when deletion is a valid domain and regulatory
operation.

Sensitive records must not receive a generic delete operation when their
lifecycle requires closure, archival, anonymisation, retention or a legal hold.

## Success representations

Successful representations will use:

```text
application/json
```

JSON property names will use `camelCase`.

Example:

```json
{
  "reference": "CV-2026-0001",
  "status": "received",
  "createdAt": "2026-07-20T10:30:00Z"
}
```

Class names and DTO type names will use `PascalCase`.

PHP and TypeScript identifiers will follow their ecosystem conventions.

Database naming is a separate persistence concern.

[ADR-0007](0007-use-postgresql-and-doctrine-for-persistence.md) selects
PostgreSQL, Doctrine ORM and DBAL, Doctrine Migrations, and the `snake_case`
convention for relational identifiers.

The selected persistence adapter must translate between persistence and
application naming explicitly where required.

The API must not expose a persistence naming decision accidentally.

## Dates and times

Calendar-only values will use the ISO date form:

```text
2026-07-20
```

Instants will use RFC 3339-compatible ISO 8601 values with an explicit offset,
normally normalised to UTC:

```text
2026-07-20T10:30:00Z
```

The frontend may display an instant in the user's relevant time zone, but it must
not reinterpret or discard its original temporal meaning.

## Identifiers

Identifiers exposed through the API will be opaque strings from the client's
perspective.

Angular must not assume that identifiers are sequential numbers or derive
permissions, ordering or meaning from their format.

ADR-0007 selects UUIDv7 as the default internal identifier for aggregate roots
and domain entities that require stable pre-persistence or cross-module
identity. Anonymous endpoints do not expose those internal identifiers.

A report's public tracking reference and access secret are separate security and
product concepts. They must not be treated as ordinary database identifiers.

An identifier format does not replace authorisation.

## Collection responses

Collection endpoints will use explicit pagination.

The initial query parameters will be:

```text
page
pageSize
```

Initial defaults:

- `page` starts at `1`;
- `pageSize` defaults to `25`;
- `pageSize` has a maximum of `100`.

An illustrative response is:

```json
{
  "items": [],
  "pagination": {
    "page": 1,
    "pageSize": 25,
    "totalItems": 0,
    "totalPages": 0
  }
}
```

The backend must apply organisation and user access restrictions before
calculating collection results or totals.

Ordering must be deterministic and include a stable tie-breaker when necessary.

The initial pagination strategy is suitable for the expected school-level
volumes and administrative interfaces. Cursor pagination may be introduced for
a measured access pattern that requires it.

## Filtering, search and sorting

Simple collection filters will use documented `GET` query parameters.

Example:

```text
GET /api/v1/cases?status=active&assignedTo=me&sort=-updatedAt
```

Each endpoint must explicitly allow:

- supported filters;
- supported sorting fields;
- permitted page sizes;
- any search behaviour.

Arbitrary database fields must not become filterable or sortable automatically.

Filtering and search must apply exactly the same authorisation rules as direct
resource access.

Complex client-defined database queries are not part of the API.

The HTTP `QUERY` method described by RFC 10008 will not be used initially. Its
ecosystem support is too recent for Convive's required deployment period.
Ordinary `GET` filters are sufficient for the initial product.

## Error responses

Errors intended for API consumers will use Problem Details for HTTP APIs and the
media type:

```text
application/problem+json
```

The baseline structure may contain:

- `type`;
- `title`;
- `status`;
- `detail`;
- `instance`;
- documented extension members.

Validation failures may include an `errors` extension:

```json
{
  "type": "https://convive.example/problems/validation-failed",
  "title": "Request validation failed",
  "status": 422,
  "detail": "One or more fields are invalid.",
  "instance": "/api/v1/reports",
  "errors": [
    {
      "field": "description",
      "code": "too_short",
      "message": "The description is too short."
    }
  ]
}
```

Clients must use stable machine-readable fields such as `type`, `code` and
`field`. They must not parse human-readable `detail` text to determine
behaviour.

Error responses must never expose:

- stack traces;
- SQL fragments;
- filesystem paths;
- server topology;
- source-code locations;
- secrets or credentials;
- access tokens or report access secrets;
- unnecessary personal or case information.

## Status-code conventions

The API will use HTTP status codes according to their semantics.

Initial conventions include:

- `200 OK` for a successful response with content;
- `201 Created` for successful resource creation;
- `202 Accepted` only when processing genuinely continues asynchronously;
- `204 No Content` for a successful operation with no response representation;
- `400 Bad Request` for malformed JSON or structurally invalid HTTP input;
- `401 Unauthorized` when valid authentication is required but absent;
- `403 Forbidden` when an authenticated principal is not permitted;
- `404 Not Found` when a resource is absent or its existence must not be
  disclosed;
- `409 Conflict` when an operation conflicts with current state or idempotency;
- `422 Unprocessable Content` for semantically invalid input;
- `429 Too Many Requests` when rate limits are exceeded;
- `500 Internal Server Error` for unexpected server failures.

The authentication mechanism itself belongs to the authentication ADR.

## Idempotency and retries

Operations that could create harmful duplicates when retried should support an
idempotency mechanism.

Public report submission is an initial candidate.

A client may provide an idempotency key:

```text
Idempotency-Key: <opaque-random-value>
```

For an operation that supports this mechanism:

- the key is scoped to the operation and relevant security context;
- retrying an equivalent request returns an equivalent result;
- reusing the key with materially different input produces a conflict;
- keys are retained only for a documented period;
- the key must not contain personal or sensitive information.

Idempotency support will be documented per operation rather than assumed for
every `POST`.

## File uploads

Evidence files will use dedicated authorised upload operations and
`multipart/form-data`.

Binary files must not be embedded as Base64 values inside normal JSON requests.

Upload operations must support:

- allowed type validation;
- size limits;
- filename and metadata sanitisation;
- authorisation before access;
- safe storage boundaries;
- malware scanning where technically available;
- audit-relevant events;
- safe failure responses.

The storage implementation is deferred to a later infrastructure decision.

## Security and privacy boundaries

Every protected operation must be authorised by Symfony.

The frontend must not be trusted to enforce:

- organisation boundaries;
- case assignments;
- professional roles;
- workflow transitions;
- evidence access;
- export permissions.

Using an opaque identifier does not grant access.

Sensitive API responses should use restrictive cache policies such as
`Cache-Control: no-store` unless a specific safe caching strategy is documented.

Public write endpoints must support appropriate rate limiting and abuse
protection.

The same-origin production boundary selected in ADR-0003 remains preferred.
Cross-origin access must not be enabled broadly without a confirmed client and a
reviewed security configuration.

Authentication, secure cookies, CSRF, session expiry and anonymous tracking
credentials are defined in
[ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md).

## Request correlation

The backend should assign or propagate a safe request identifier.

A response may expose it through a header such as:

```text
X-Request-ID
```

The identifier may be used to correlate API failures with internal structured
logs.

It must not encode personal data, credentials or sensitive domain information.

Operational logs and the protected domain audit trail are separate concerns.
Request correlation does not replace case auditing.

## OpenAPI contract

Convive will publish a machine-readable OpenAPI 3.1.x contract.

OpenAPI 3.1.x is selected as the compatibility-first target for the initial
Symfony and Angular toolchain.

OpenAPI 3.2 will not be claimed until the selected generation and client tools
are verified to support it correctly.

The contract will describe:

- paths and HTTP methods;
- request parameters and headers;
- request bodies;
- success representations;
- Problem Details responses;
- pagination and filters;
- security requirements;
- file uploads;
- examples where useful.

In accordance with ADR-0008, the contract will represent the professional
session cookie and anonymous capability cookie as separate cookie security
schemes and apply only the expected scheme to each protected operation. It will
also document the required XSRF header for applicable state-changing requests.

The Symfony routes, controller metadata and transport DTOs will be the
implementation source used to generate the contract.

The exported contract should be stored at:

```text
docs/api/openapi.yaml
```

Once generation is available, continuous integration should regenerate the
contract and fail when the committed representation is stale.

Interactive API documentation may be enabled in development.

A production interactive console must be disabled or appropriately restricted.
Security must never depend on hiding the OpenAPI document.

Generating Angular types or a client from OpenAPI may be introduced after the
generated output has been reviewed. Generated code must not become an
unreviewed source of business behaviour.

## Verification strategy

The API boundary will be verified through:

- unit tests for mapping and pure validation where valuable;
- integration tests for controllers and application use cases;
- authorisation tests for protected operations;
- contract tests for important success and error representations;
- OpenAPI generation and drift checks;
- end-to-end tests for a small number of critical user journeys.

## Explicit exclusions

This decision does not introduce:

- direct exposure of persistence entities;
- automatically generated unrestricted CRUD;
- API Platform in the initial implementation;
- GraphQL;
- JSON-LD or Hydra as the primary representation;
- JSON:API;
- strict HATEOAS requirements;
- HTTP `QUERY` in the initial release;
- arbitrary client-defined database queries;
- unrestricted cross-origin access;
- a complete authentication strategy;
- a database schema or identifier implementation;
- a file-storage provider;
- a general message broker;
- generated Angular code without review.

## Rationale

Convive requires a stable interface, but its most important operations represent
sensitive workflows rather than simple database CRUD.

Explicit Symfony controllers and transport DTOs make each security and
application boundary visible. They align with the modular-monolith structure and
allow domain rules to remain independent from HTTP and persistence behaviour.
The controlled mapping metadata permitted by ADR-0007 does not change that
separation.

API Platform with dedicated resource DTOs would be a valid alternative and
would reduce some repetitive code. However, its additional state-provider and
processor model is not currently justified when many write operations already
require explicit use cases.

Plain JSON and standard HTTP semantics provide broad interoperability without
introducing JSON-LD, GraphQL or another query model.

OpenAPI gives Angular, tests and reviewers a machine-readable contract while
NelmioApiDocBundle reduces manual duplication.

This combination prioritises explicit behaviour, security, maintainability and
learning while preserving a review path if repetitive API work becomes a
measured problem.

## Consequences

### Positive consequences

- Angular receives a stable and documented interface.
- Symfony remains the authoritative security and business boundary.
- Persistence entities remain private implementation details.
- Workflow operations are explicit.
- Error handling is consistent and machine-readable.
- API changes can be reviewed through OpenAPI.
- Contract drift can be detected automatically.
- Standard HTTP tooling remains usable.
- The implementation fits the existing modular-monolith structure.
- Individual requests are easier to trace and test.
- API Platform remains available as a future reviewed option.

### Negative consequences

- More controllers and DTOs must be maintained.
- Pagination and filters require explicit implementation.
- OpenAPI metadata adds code and review effort.
- Contract generation must be kept deterministic.
- Angular and Symfony can still drift until automated verification exists.
- Developers must apply the HTTP conventions consistently.
- Versioned paths create a compatibility commitment.
- Idempotency and request correlation require additional infrastructure work.

## Review triggers

Review this decision if:

- controller and DTO repetition materially delays delivery;
- most operations prove to be conventional CRUD;
- OpenAPI metadata becomes harder to maintain than API Platform resources;
- contract generation cannot represent the required interface reliably;
- a second independently developed client becomes important;
- the Angular client requires a different compatibility strategy;
- GraphQL solves a measured query problem that HTTP filters cannot;
- cursor pagination becomes necessary for measured data volumes;
- cross-origin clients become a confirmed requirement;
- API Platform demonstrates a clear reduction in total complexity;
- the selected toolchain cannot support OpenAPI 3.1.x reliably.

If the API implementation strategy changes materially, a later ADR must
supersede this decision and describe the migration path.

## References

- [HTTP Semantics — RFC 9110](https://www.rfc-editor.org/rfc/rfc9110.html)
- [Problem Details for HTTP APIs — RFC 9457](https://www.rfc-editor.org/rfc/rfc9457.html)
- [JSON Merge Patch — RFC 7396](https://www.rfc-editor.org/rfc/rfc7396.html)
- [HTTP QUERY Method — RFC 10008](https://www.rfc-editor.org/rfc/rfc10008.html)
- [OpenAPI Specification](https://spec.openapis.org/oas/)
- [Symfony controllers](https://symfony.com/doc/current/controller.html)
- [Symfony Serializer](https://symfony.com/doc/current/serializer.html)
- [Symfony Validator](https://symfony.com/doc/current/validation.html)
- [NelmioApiDocBundle](https://symfony.com/doc/current/NelmioApiDocBundle/index.html)
- [API Platform operations](https://api-platform.com/docs/core/operations/)
- [API Platform state processors](https://api-platform.com/docs/core/state-processors/)
- [API Platform OpenAPI support](https://api-platform.com/docs/core/openapi/)
- [Aircury SQL naming guide](https://github.com/aircury/sql-style-guide/blob/main/naming.md)
