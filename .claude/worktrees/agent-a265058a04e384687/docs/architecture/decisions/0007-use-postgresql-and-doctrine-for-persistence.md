# ADR-0007: Use PostgreSQL and Doctrine for relational persistence

- **Status:** Accepted
- **Date:** 20 July 2026
- **Related issue:** [#1](https://github.com/albertogalvez-dev/Convive/issues/1)
- **Depends on:** [ADR-0001](0001-use-a-monorepository.md), [ADR-0002](0002-use-a-modular-monolith-for-the-backend.md), [ADR-0005](0005-use-docker-compose-for-reproducible-environments.md), [ADR-0006](0006-use-a-resource-oriented-json-http-api-with-an-openapi-contract.md)
- **Clarifies:** the persistence boundary in ADR-0002 and the domain/persistence wording in ADR-0006

## Context

Convive needs to persist structured and related information concerning reports,
cases, professional work, organisations and auditability.

The exact entities, tables, relationships and retention rules have not yet been
designed. This ADR must therefore select a persistence approach without
inventing the complete data model.

ADR-0002 selected one primary relational database for the Symfony modular
monolith. It also established that each module logically owns its data even
though the database is shared.

PostgreSQL and Doctrine have previously been described as planned technologies.
They have not been selected by an accepted persistence decision.

This ADR decides:

- the relational database engine;
- the initial Symfony persistence toolkit;
- the object-relational mapping approach;
- repository and transaction boundaries;
- internal identifier conventions;
- relational naming and type conventions;
- the migration workflow;
- the role of schema documentation.

The detailed conventions in this ADR are initial guardrails. They will be
validated as the first vertical slice is implemented; they do not require every
possible persistence mechanism or test category to be built before that slice
is delivered.

Authentication, final authorisation rules, encryption-key management, retention
periods, backups and the detailed data model remain separate decisions.

## Decision drivers

- Preserve relational integrity for connected and sensitive information.
- Support reliable transactions across related changes.
- Fit the Symfony 7.4 LTS backend selected for Convive.
- Preserve the module boundaries selected in ADR-0002.
- Keep persistence operations out of controllers and API representations.
- Make production schema changes reviewable and reproducible.
- Support constraints, indexes and integration testing against the real engine.
- Retain access to SQL when an ORM is not the clearest tool.
- Avoid unnecessary persistence duplication for one primary developer.
- Support a maintainable single-VPS deployment.

## Options considered

### Option A: PostgreSQL with Doctrine ORM, DBAL and Migrations

Use PostgreSQL as the relational database. Use Doctrine ORM for ordinary
aggregate persistence, Doctrine DBAL for explicit query-oriented access, and
Doctrine Migrations for versioned schema changes.

Benefits:

- conventional and documented Symfony integration;
- object mapping for ordinary persistence operations;
- support for repositories, transactions and optimistic locking;
- access to explicit SQL and PostgreSQL features through DBAL;
- executable and versioned migrations;
- native relational constraints and UUID storage.

Costs:

- Doctrine's Unit of Work and identity map must be understood;
- lazy loading can create hidden queries if used carelessly;
- generated migrations still require human review;
- mapping metadata introduces some persistence coupling;
- complex reporting queries may not fit naturally into ORM entities.

### Option B: PostgreSQL with DBAL and handwritten SQL only

Avoid the ORM. Repositories execute SQL through Doctrine DBAL and map rows
explicitly.

Benefits:

- SQL behaviour remains visible;
- no Unit of Work or lazy-loading behaviour;
- full access to PostgreSQL features;
- a clear fit for complex queries and read projections.

Costs:

- ordinary writes and relationships require more manual code;
- change tracking and object mapping must be implemented explicitly;
- a growing domain model could accumulate substantial persistence boilerplate.

### Option C: PostgreSQL with another PHP ORM

Use an alternative ORM such as Cycle ORM or Eloquent.

Benefits:

- alternative mapping and query models are available;
- some alternatives may require less setup for simple CRUD.

Costs:

- weaker alignment with the standard Symfony ecosystem;
- additional integration and maintenance decisions;
- no confirmed requirement justifies replacing Symfony's conventional Doctrine
  integration.

### Option D: MySQL or MariaDB with Doctrine

Use Doctrine while selecting MySQL or MariaDB as the relational engine.

Benefits:

- mature relational databases;
- widespread hosting support;
- strong Doctrine compatibility.

Costs:

- no confirmed deployment restriction requires either engine;
- changing the planned database would add work without solving a current
  Convive requirement;
- PostgreSQL provides the required relational types, constraints and query
  capabilities.

### Option E: A document database as the primary store

Use a document database such as MongoDB.

Benefits:

- flexible document structures;
- convenient storage for irregular records.

Costs:

- Convive's core information is relational and transactional;
- referential integrity becomes more application-dependent;
- cross-record consistency and reporting become harder;
- flexibility could hide an insufficiently defined domain model.

## Decision

Convive will use PostgreSQL as its primary relational database.

The Symfony backend will use:

- Doctrine ORM for ordinary aggregate persistence;
- Doctrine DBAL for explicit SQL, specialised queries and read projections when
  the ORM is not the clearest tool;
- Doctrine Migrations for every versioned schema change.

This decision does not require every database operation to use Doctrine ORM.

The exact compatible Doctrine package versions will be selected when the
Symfony application is initialised and recorded in `composer.lock`.

The exact PostgreSQL version will also be selected during executable environment
creation. A supported version will be pinned explicitly in the Docker
configuration. The unversioned `latest` image tag will not be used.

A PostgreSQL major-version upgrade requires a separately reviewed operational
plan.

## Persistence boundaries

Persistence operations belong to each module's `Infrastructure` layer.

Controllers must not:

- use the Doctrine Entity Manager directly;
- contain database queries;
- expose mapped entities as API representations;
- implement workflow decisions based on persistence details.

Application use cases coordinate behaviour through repository or persistence
interfaces defined at the appropriate module boundary.

Doctrine implementations of those interfaces remain inside `Infrastructure`.

A typical write flow is:

```text
Symfony controller
    -> application use case
    -> domain behaviour
    -> repository interface
    -> Doctrine repository implementation
    -> PostgreSQL
```

A repository belonging to one module must not become a mechanism for directly
reading or modifying data owned by another module.

Cross-module writes must be coordinated through explicit application
boundaries. Sharing a database does not remove module ownership.

Read-only projections may use Doctrine DBAL and explicit SQL when a joined or
aggregated query would otherwise distort the write model. A projection spanning
modules must have an explicit owning module or application-level reporting
boundary and must preserve authorisation rules; it must not silently bypass
another module's ownership. This is a targeted option, not mandatory CQRS.

## Mapping approach

The default mapping approach will use PHP attributes supported by Doctrine.

Persisted domain entities may carry declarative Doctrine mapping attributes and,
where an ORM association genuinely requires it, Doctrine collection types as a
controlled persistence coupling. Proxy and lazy-loading behaviour must not
become part of domain rules.

This approach avoids maintaining a duplicate persistence object for every
domain object. It permits limited technical mapping dependencies, not
persistence behaviour, queries or transaction management in the domain.

Domain objects must not:

- receive or retrieve an Entity Manager;
- execute database queries;
- call Doctrine repository implementations;
- depend on Symfony services;
- contain HTTP or serialisation behaviour;
- expose unrestricted mutable state only to satisfy the ORM.

Repository implementations, query builders and transaction management remain
inside `Infrastructure`.

API request and response DTOs remain separate from mapped entities, as required
by ADR-0006.

A separate persistence model and explicit mapper may be introduced for a
specific aggregate when:

- ORM requirements would materially distort its domain model;
- its stored representation differs materially from the domain representation;
- a security or integration boundary requires stronger separation;
- it maps to a legacy or externally controlled structure.

Separate persistence models will not be created mechanically without a
demonstrated benefit.

External XML mapping was considered because it would keep Doctrine attributes
out of domain classes. It was not selected initially because it separates
mapping information from the code and adds configuration overhead without a
confirmed proportional benefit for the current project.

ADR-0002 and ADR-0006 describe this controlled coupling without weakening
their module, API or security boundaries.

## Doctrine usage rules

Mapped entities must not be serialised directly as API responses.

Associations must be introduced deliberately. Bidirectional associations will
only be used when navigation is required in both directions.

Lazy loading must not be relied on from controllers, serializers or frontend
representations. Queries required by a use case must be explicit enough to
avoid uncontrolled database access.

Cascade persistence and cascade deletion must not be enabled merely for
convenience.

Destructive cascade deletion is unsuitable when a record may instead require
closure, archival, anonymisation, retention or a legal hold.

Doctrine lifecycle callbacks and event listeners must not contain important
business workflows. Business behaviour belongs in domain objects and
application use cases.

## Identifier options considered

Database-generated integer identifiers offer compact indexes, but require a
database round trip and are less suitable as stable identifiers across module
boundaries. UUIDv4 values can be generated before persistence but are fully
random and have poorer index locality. ULIDs are sortable and
application-generated, but PostgreSQL has no native ULID type. UUIDv7 combines
application generation, time ordering and PostgreSQL's native `uuid` storage.

## Identifier strategy

Aggregate roots and domain entities that require a stable identifier before
persistence or across module boundaries will use application-generated UUIDv7
values.

UUIDv7 provides:

- identifiers that can exist before persistence;
- uniqueness without a database sequence shared across modules;
- better index locality than fully random UUIDv4 values;
- native storage through PostgreSQL's `uuid` type;
- support through Symfony's UID and Doctrine integration.

UUIDv7 is not required for every application-owned or framework-owned table.
Purely technical records may use another identifier when the schema design
documents the reason.

The application will generate domain UUIDs rather than requiring an initial
database round trip. The implementation must use an explicitly configured
UUIDv7 generator and verify the generated version in automated tests; a generic
UUID generator whose version is not guaranteed is insufficient.

UUIDv7 values are non-secret identifiers, not authorisation credentials. They
must never be used as:

- anonymous report access secrets;
- password-reset secrets;
- authentication tokens;
- proof that a caller is authorised.

The anonymous report reference and high-entropy access secret remain separate
product and security concepts.

Internal UUIDv7 values must not be exposed by anonymous reporting endpoints,
because their ordering can reveal and correlate approximate creation time.
Public tracking references and access secrets must use separate,
non-correlatable, high-entropy values selected by the security design.

Other authenticated endpoints may expose an internal identifier only when the
contract requires it and authorisation permits it. API clients must continue
treating exposed identifiers as opaque strings.

## Database naming conventions

PostgreSQL identifiers will use unquoted lowercase `snake_case`.

Table names will use plural or collective nouns. Actual table names will be
defined during domain and data modelling, not by this ADR.

Column names will use singular, descriptive terms.

The following abstract patterns apply:

```text
id
<referenced_concept>_id
<event>_at
<calendar_concept>_date
is_<condition>
has_<condition>
```

Names must:

- be descriptive and consistent;
- avoid unnecessary abbreviations;
- avoid PostgreSQL reserved words;
- avoid redundant prefixes such as `tbl_`;
- begin with a letter and avoid trailing or consecutive underscores;
- use `_id` for foreign keys;
- use `_at` for instants;
- use `_date` for calendar-only dates;
- use `is_` or `has_` for booleans where grammatically appropriate.

Primary-key columns will normally be named `id`. Foreign-key columns will name
the referenced concept using `<referenced_concept>_id`.

PHP properties and JSON fields continue using their ecosystem conventions.
Doctrine mapping must translate between those conventions explicitly where
required.

## Data-type conventions

PostgreSQL-native types will be preferred when Doctrine maps them reliably.

Initial conventions are:

- `uuid` for UUID identifiers;
- `date` for calendar-only dates;
- `timestamptz` for real instants;
- `boolean` for binary state;
- `text` or a length-constrained character type according to validated needs;
- exact numeric types when precision matters;
- relational columns and tables for stable business information.

PHP will represent stored instants with immutable date-time objects. The
compatible Doctrine mapping type will be selected and tested during bootstrap;
for PostgreSQL `timestamptz`, the expected candidate is
`datetimetz_immutable`.

PHP's default time zone and the PostgreSQL connection/session time zone will be
configured as UTC. PostgreSQL normalises `timestamptz` instants and does not
preserve the original named time zone. Displaying an instant in a relevant
local time zone is a presentation concern.

A time zone with independent business meaning must be stored separately rather
than inferred from a UTC timestamp.

## Status and enum values

Stable finite values may use PHP backed enums and string database columns.

PostgreSQL-native enum types will not be introduced initially because changing
their allowed values adds database-specific migration considerations.

Where the set of stored values is stable and database enforcement is valuable,
a reviewed `CHECK` constraint may enforce the allowed strings.

A change to a PHP enum covered by a database `CHECK` constraint must update the
mapping, migration and relevant tests in the same change.

Values intended to be configurable by an organisation must be represented as
data rather than compiled enums.

A stored workflow status remains subject to domain transition rules. A value
permitted by a column constraint is not automatically a permitted transition.

## Relational integrity

Important invariants that PostgreSQL can express safely will be enforced in the
database as well as through domain and Symfony validation.

This includes deliberate use of:

- primary keys;
- foreign keys;
- `NOT NULL` constraints;
- unique constraints;
- check constraints;
- indexes supporting confirmed constraints and access patterns.

Database constraints complement application validation. They do not replace
business rules or authorisation.

Foreign-key deletion behaviour must be selected for each relationship.
Destructive cascading will not be the default for sensitive records.

Indexes will be introduced for actual query, ordering and constraint needs.
They will not be added mechanically without considering write and storage cost.

## JSONB usage

`jsonb` may be used for genuinely variable and bounded metadata whose structure
does not justify a stable relational model.

It must not become the default location for:

- lifecycle state;
- permissions;
- organisation ownership;
- deadlines;
- relationships;
- frequently filtered business information;
- information requiring relational integrity.

Every `jsonb` field must have a documented purpose and expected structure.

Using `jsonb` does not relax validation, authorisation, data minimisation,
retention or audit requirements.

Evidence files will not be embedded as Base64 values in JSON or JSONB. Their
storage provider remains outside this decision.

## Transactions

An application use case should define one clear transaction boundary when its
changes must succeed or fail together.

Doctrine's Entity Manager and Unit of Work may coordinate ordinary writes
inside that boundary.

One ORM flush may atomically persist changes reached through multiple
repositories when they share the same Entity Manager and connection. Explicit
transaction control will be used when an operation mixes ORM and DBAL writes,
requires multiple flushes, uses pessimistic locking, or otherwise cannot rely
on that implicit boundary.

Transactions must not remain open while waiting for user interaction, email
delivery or another remote service.

External side effects must not be assumed to roll back because a database
transaction fails. Reliable asynchronous delivery or an outbox mechanism will
only be selected when a confirmed use case requires it.

## Concurrency

Convive contains long-running professional workflows. Multiple authorised users
may read the same information and later attempt incompatible updates.

Aggregates for which a lost update would be harmful must use an explicit
concurrency strategy.

The concurrency strategy will be selected per aggregate after harmful
concurrent updates and editable operations are identified. Doctrine's numeric
optimistic locking is the default candidate, not a universal requirement.

When optimistic locking spans separate HTTP requests, the version observed by
the client must return through the API as a version field or conditional request
token and be checked on update. That contract must be documented in OpenAPI.

This ADR does not require a version column on every table.

Pessimistic locking will only be used inside a short transaction for a confirmed
concurrency requirement.

## Migration strategy

Every schema change will be represented by a committed Doctrine migration.

Migrations will be stored under:

```text
apps/api/migrations
```

The normal workflow will be:

1. change the reviewed mapping;
2. generate or write a migration;
3. inspect the generated SQL;
4. adjust it where required;
5. run it against a clean PostgreSQL database;
6. run schema validation and relevant tests;
7. commit the mapping and migration together.

Generated migrations are proposals and require review.

Production schema changes must not use automatic schema-update commands such
as:

```text
doctrine:schema:update --force
```

Production deployments will execute reviewed migrations explicitly.

Potentially destructive changes must use a staged approach where necessary:

1. add a compatible structure;
2. deploy code capable of using the transitional structure;
3. migrate or backfill data;
4. remove obsolete structures in a later release.

A destructive migration or data backfill must also be tested from the previous
supported schema with representative fictional data, not only from an empty
database.

A migration rollback method does not guarantee that deleted data can be
restored. Production recovery depends on tested backups and forward corrective
migrations, which belong to later operational documentation.

Fictional fixtures and test data remain separate from production migrations.

## Schema authority and documentation

The persistence artifacts have distinct responsibilities:

- Doctrine mapping describes the schema expected by the current application;
- committed migrations are the normal authorised mechanism for evolving the
  application-owned deployed schema;
- a database created from all migrations must match the current Doctrine
  mapping;
- DBML is derived human-readable documentation and has no independent authority.

Continuous integration should build a clean PostgreSQL database from migrations
and verify its compatibility with the mapping.

A DBML representation will be introduced with the first reviewed domain schema
at:

```text
docs/architecture/data-model.dbml
```

The DBML document will be updated when a change materially affects the reviewed
domain schema. Automation is optional and will only be introduced when it saves
more maintenance than it creates.

This ADR does not invent tables or relationships before domain and data
modelling begins.

## Sensitive-data boundaries

Selecting PostgreSQL and Doctrine does not make Convive ready to process real
student information.

The public demonstration must continue using fictional data.

The future data model must preserve the accepted product requirements for:

- organisation-based access;
- separation of optional reporter contact information;
- restricted evidence access;
- protected audit records;
- configurable retention;
- deletion or anonymisation where applicable.

This ADR does not select:

- field-level encryption algorithms;
- encryption-key management;
- final retention periods;
- legal-hold rules;
- PostgreSQL row-level security;
- a production database role model.

Before any deployment processes real personal data, the applicable retention,
encryption and key-management, backup and restoration, database roles, access
secret, and authorisation decisions must be accepted and implemented. These are
launch blockers for real-data processing, not optional future enhancements.

Application authorisation remains mandatory if database-level protections are
introduced later.

## Testing and verification

The first executable persistence slice must include:

- migrations that build an empty PostgreSQL database;
- compatibility validation between Doctrine mapping and that schema;
- repository integration tests for the implemented vertical slice;
- fixtures containing only fictional data.

Verification will expand with implemented behaviour to cover:

- unit tests for domain behaviour without a database where practical;
- repository integration tests against PostgreSQL;
- migration tests starting from an empty database;
- schema compatibility checks between migrations and Doctrine mapping;
- constraint and transaction tests for important invariants;
- concurrency tests where optimistic locking is introduced;
- data-access isolation tests derived from the separately selected
  authentication and authorisation strategy;
- upgrade tests for destructive migrations and backfills.

SQLite will not replace PostgreSQL in integration tests because its data types,
constraints and SQL behaviour differ from the production engine.

Integration tests will use an isolated PostgreSQL instance compatible with the
deployed major version, never the production database itself. Docker Compose or
another compatible container-based workflow may provide it to CI. The exact
test orchestration will be selected when CI is implemented.

## Explicit exclusions

This decision does not define:

- the complete relational schema;
- every module, entity, table or relationship;
- the authentication or session mechanism;
- the final authorisation model;
- the anonymous access-secret implementation;
- encryption-key management;
- final retention periods;
- production backup tooling;
- file or evidence storage;
- email delivery;
- a message broker or outbox;
- PostgreSQL row-level security;
- live integration with a school information system;
- processing of real student information in the public demonstration.

## Rationale

Convive's core information is relational and workflow-oriented. It requires
explicit relationships, constraints and reliable transactions.

PostgreSQL provides that relational foundation without requiring application
code to compensate for missing integrity mechanisms.

Doctrine integrates with Symfony and reduces repetitive persistence work.
Selecting Doctrine ORM does not prohibit explicit SQL: Doctrine DBAL remains
available for specialised queries and read projections.

PHP attribute mapping is a pragmatic choice for the current team size. It
accepts limited declarative persistence metadata in mapped domain classes while
keeping persistence behaviour, queries and transaction management inside
infrastructure.

Versioned migrations make schema changes visible in Git and reproducible across
development, testing and production.

This combination provides a conventional Symfony workflow while preserving
explicit module, API and security boundaries.

## Consequences

### Positive consequences

- The database technology and persistence toolkit are explicit.
- Symfony uses a conventional and documented integration.
- Relational constraints protect important invariants.
- Related changes can be committed atomically.
- Schema history remains visible in Git.
- Integration tests use an isolated instance of the same database engine as
  production.
- UUIDv7 supports application-generated domain identifiers.
- Complex queries can use DBAL without being forced through the ORM.
- API DTOs remain separate from mapped entities.
- DBML can explain the schema without becoming another migration system.
- The approach fits the modular-monolith and Docker decisions.

### Negative consequences

- The project depends on PostgreSQL-specific behaviour.
- Doctrine ORM introduces concepts and hidden behaviour that must be learned.
- Mapping attributes create limited coupling between domain classes and
  Doctrine.
- Migrations and mappings must remain consistent.
- UUID indexes require more space than integer indexes.
- DBAL queries require explicit result mapping and testing.
- Module boundaries still require development discipline.
- DBML can become stale without a maintained workflow.
- Safe production schema evolution requires operational work.

## Review triggers

Review this decision if:

- Doctrine mapping materially distorts the domain model;
- ORM behaviour creates persistent performance or debugging problems;
- most persistence operations become clearer as explicit SQL;
- PostgreSQL cannot satisfy a confirmed deployment requirement;
- a school integration requires a different database boundary;
- independently deployed module databases become necessary;
- data volume requires a different indexing or partitioning strategy;
- schema documentation repeatedly drifts from executable migrations;
- a real deployment requires database-level tenant isolation;
- selected Symfony, Doctrine or PostgreSQL versions become unsupported or
  incompatible.

A material change to the database engine, ORM strategy or schema authority must
be recorded in a superseding ADR.

## References

- [Symfony: Databases and the Doctrine ORM](https://symfony.com/doc/7.4/doctrine.html)
- [Symfony UID component](https://symfony.com/doc/7.4/components/uid.html)
- [Doctrine ORM architecture](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/architecture.html)
- [Doctrine ORM transactions and concurrency](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/transactions-and-concurrency.html)
- [Doctrine Migrations](https://www.doctrine-project.org/projects/doctrine-migrations/en/current/index.html)
- [PostgreSQL data types](https://www.postgresql.org/docs/current/datatype.html)
- [PostgreSQL UUID type](https://www.postgresql.org/docs/current/datatype-uuid.html)
- [PostgreSQL date and time types](https://www.postgresql.org/docs/current/datatype-datetime.html)
- [PostgreSQL constraints](https://www.postgresql.org/docs/current/ddl-constraints.html)
- [Aircury SQL naming guide](https://github.com/aircury/sql-style-guide/blob/main/naming.md)
