# ADR-0002: Use a modular monolith for the backend

- **Status:** Accepted
- **Date:** 17 July 2026
- **Related issue:** [#1](https://github.com/albertogalvez-dev/Convive/issues/1)
- **Depends on:** [ADR-0001](0001-use-a-monorepository.md)

## Context

Convive requires a backend that manages reports, bullying cases, users, educational organisations, notifications, evidence and audit records.

PHP with Symfony is an explicit technical constraint selected for Convive. This
ADR therefore decides how the Symfony backend will be structured; it does not
compare Symfony with other backend frameworks.

These areas are related but have different responsibilities and rules. The backend must preserve those boundaries while supporting transactions, authorisation, traceability and secure handling of sensitive information.

The product is initially developed by one primary developer, will run on a single controlled VPS and does not currently require independently deployed backend services.

An application architecture must be selected before the Symfony source structure and domain model are created.

## Decision drivers

- Keep business rules understandable and testable.
- Preserve the distinction between an incoming report and a professionally managed case.
- Apply security and authorisation consistently.
- Support reliable database transactions.
- Keep deployment and local development manageable.
- Avoid distributed-system complexity without a demonstrated need.
- Allow the domain model to evolve without creating an unstructured codebase.
- Make architectural boundaries visible to reviewers and future contributors.
- Retain the possibility of extracting a module in the future if a real requirement appears.

## Options considered

### Option A: Traditional layered monolith

Build one Symfony application organised primarily by technical type, such as controllers, entities, repositories and services.

Benefits:

- familiar Symfony structure;
- simple initial setup;
- one application and one deployment;
- straightforward database transactions;
- low operational overhead.

Costs:

- business concepts become distributed across technical folders;
- dependencies between areas can grow without clear boundaries;
- a generic service layer can become difficult to understand;
- maintaining the distinction between reports and cases requires additional discipline;
- the codebase may become increasingly coupled as functionality grows.

### Option B: Modular monolith

Build one Symfony application and one deployment unit, but organise the code around explicit business modules.

Each module contains the code required for its own use cases and exposes controlled ways for other modules to interact with it.

Benefits:

- one deployment and one transactional boundary;
- business areas remain visible in the source structure;
- module-level testing and ownership are clearer;
- lower operational complexity than microservices;
- easier coordinated changes across related business areas;
- individual modules may be extracted later if justified.

Costs:

- module boundaries depend on architectural discipline;
- careless database access can bypass module boundaries;
- some shared concerns must be designed carefully;
- the structure requires more thought than a basic Symfony application;
- a module cannot be deployed independently without later architectural work.

### Option C: Microservices

Build reports, cases, identity, notifications and other areas as independently deployed services.

Benefits:

- services can be deployed and scaled independently;
- teams can own services separately;
- failures may be isolated;
- technology and storage can vary by service.

Costs:

- distributed authentication and authorisation;
- network communication and partial failures;
- more complex transactions and consistency;
- additional deployment, monitoring and logging infrastructure;
- service versioning and contract management;
- substantially higher testing and local development complexity;
- excessive operational overhead for one developer and one VPS.

## Decision

Convive will implement its Symfony backend as a modular monolith.

The backend will remain one application, one codebase and one primary deployment unit. It will use explicit modules to represent major business capabilities.

Initial module candidates are:

- `Reporting`;
- `CaseManagement`;
- `IdentityAccess`;
- `Organisations`;
- `Notifications`;
- `Audit`.

These names are initial boundaries, not permanent assumptions. They may be refined when the domain and data models are designed.

## Internal structure

The source code will be organised by business module before technical layer.

A module may contain:

- `Domain` for business concepts, rules and domain behaviour;
- `Application` for use cases and orchestration;
- `Infrastructure` for persistence and external technical integrations;
- `Presentation` for HTTP entry points and input/output mapping.

As selected in ADR-0007, persisted domain entities may contain declarative
Doctrine mapping attributes and, where an association requires it, Doctrine
collection types as controlled technical coupling. Persistence operations,
queries, transaction management, proxy behaviour and lazy-loading decisions
remain outside domain rules and inside `Infrastructure`.

A possible initial structure is:

- `src/Reporting/Domain`;
- `src/Reporting/Application`;
- `src/Reporting/Infrastructure`;
- `src/Reporting/Presentation`;
- `src/CaseManagement/Domain`;
- `src/CaseManagement/Application`;
- `src/CaseManagement/Infrastructure`;
- `src/CaseManagement/Presentation`.

This structure will be introduced only where the code requires it. Empty layers and placeholder classes will not be created merely to reproduce the architecture.

## Module interaction rules

- A module owns its business rules.
- Other modules must not modify its internal state directly.
- Cross-module operations should use explicit application services, contracts or events.
- One module should not query or update another module's tables directly.
- Shared code must contain genuinely generic concepts and must not become a location for unrelated business logic.
- Module dependencies should be testable and documented.
- Symfony remains responsible for enforcing authorisation at protected entry points and within sensitive use cases.

## Persistence and transactions

The initial modular monolith will use one primary relational database.

PostgreSQL, Doctrine ORM and DBAL, and Doctrine Migrations are selected in
[ADR-0007](0007-use-postgresql-and-doctrine-for-persistence.md).

Each module will logically own its data even though the tables share the same database.

A shared relational database allows the backend persistence layer to use transactions for operations that must succeed or fail together. This is particularly valuable when a report is assessed, a case is created and an audit event must be recorded consistently.

Using one database does not permit direct reads or writes across module
boundaries. Persistence access and cross-module projections must continue to
respect explicit module responsibilities.

## Background processing

Notifications and other asynchronous operations may run through Symfony Messenger workers.

A worker using the same application code is not considered a separate microservice. It is another runtime process belonging to the same modular monolith.

A dedicated message broker or independently deployed notification service will only be introduced if a concrete operational requirement justifies it.

## Explicit exclusions

This decision does not introduce:

- independently deployed microservices;
- a database per module;
- event sourcing;
- mandatory CQRS for every use case;
- distributed transactions;
- empty architectural layers without working code;
- abstraction solely for hypothetical future requirements.

Patterns such as domain events or command/query separation may be used selectively when they simplify a concrete use case.

## Rationale

Convive needs strong internal boundaries because reports, cases, identity, notifications and audit records have different rules and security implications.

At the same time, its current team size, deployment target and expected workload do not justify the operational complexity of microservices.

A modular monolith provides the most appropriate balance:

- the operational simplicity and transactional reliability of one application;
- the conceptual clarity of domain-oriented modules;
- enough structure for a growing product;
- a possible path to later extraction without paying the distributed-system cost today.

A traditional layered monolith would be simpler at the beginning, but it would make the product's business boundaries less visible and easier to erode.

## Consequences

### Positive consequences

- Symfony remains simple to run, test and deploy.
- Business areas are visible in the repository structure.
- Reports and cases can remain separate domain concepts.
- Related operations can use reliable database transactions.
- Security and auditing can be applied consistently.
- Local development does not require multiple backend services.
- Architectural diagrams can map directly to code modules.
- Future contributors can locate code by business capability.
- A module can be considered for extraction if future evidence justifies it.

### Negative consequences

- The developer must actively protect module boundaries.
- One backend release deploys all modules together.
- A failure affecting the Symfony application may affect all backend capabilities.
- The shared database can encourage accidental coupling.
- Cross-module interactions require deliberate design.
- Independent scaling is not available without later architectural changes.
- The module structure adds some initial learning and organisational cost.

## Review triggers

This decision should be reviewed if:

- independent teams require ownership and deployment of separate capabilities;
- one module requires substantially different scaling characteristics;
- an institutional integration requires stronger technical isolation;
- availability requirements demand independent failure boundaries;
- the shared database prevents required autonomy;
- deployment frequency differs significantly between modules;
- the modular boundaries cannot be maintained effectively inside one application.

If the architecture changes, a later ADR must supersede ADR-0002 and explain the migration path.
