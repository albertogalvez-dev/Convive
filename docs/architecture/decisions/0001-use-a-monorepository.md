# ADR-0001: Use a monorepository

- **Status:** Accepted
- **Date:** 17 July 2026
- **Related issue:** [#1](https://github.com/albertogalvez-dev/Convive/issues/1)

## Context

Convive will contain a backend application, a separate web frontend, infrastructure definitions and technical documentation.

The product is initially developed by one primary developer and its components will be delivered and deployed in a coordinated way. A repository strategy is required before the application structure is created.

## Decision drivers

- Keep product work and technical documentation traceable in one place.
- Allow a feature to change frontend and backend coherently.
- Keep local setup and contribution simple.
- Avoid unnecessary coordination overhead for a solo developer.
- Preserve clear boundaries between application components.
- Support automated testing and deployment as the repository grows.

## Options considered

### Option A: Monorepository

Store the backend, frontend, infrastructure definitions and documentation in the same Git repository.

Benefits:

- one source of truth for the complete product;
- coordinated changes can be reviewed in a single pull request;
- simpler local setup and issue tracking;
- documentation and implementation evolve together.

Costs:

- continuous integration must handle different application areas;
- repository boundaries require discipline;
- unrelated changes could be mixed if commits are not kept focused.

### Option B: Multiple repositories

Store the backend, frontend, infrastructure definitions and documentation in separate Git repositories.

Benefits:

- independent permissions, versioning and release cycles;
- stronger physical separation between applications;
- smaller repositories for specialised teams.

Costs:

- cross-application features require coordination across repositories;
- compatible versions must be tracked explicitly;
- issues, pull requests and documentation become fragmented;
- additional setup and maintenance for a solo developer.

## Decision

Convive will use a monorepository.

The initial repository structure will separate the main areas into:

- `apps/api` for the Symfony backend;
- `apps/web` for the web frontend;
- `infrastructure` for deployment and environment definitions;
- `docs` for lasting product and technical documentation.

Each application will retain its own dependencies, configuration and test suite. Sharing a repository does not mean sharing application responsibilities or runtime code.

## Rationale

Convive is one product developed by one primary developer, and its frontend and backend will frequently evolve as part of the same feature.

A monorepository keeps issues, pull requests, documentation and coordinated changes in one place while avoiding the versioning and maintenance overhead of multiple repositories.

Clear directory boundaries and focused commits will be used to reduce the main risks of this approach.

## Consequences

### Positive consequences

- Product documentation and implementation remain versioned together.
- Cross-application changes can be reviewed in the same pull request.
- Issues, milestones and releases remain centralised.
- A contributor only needs to clone one repository.
- Local and deployment configuration can describe the complete system.
- The complete engineering process remains visible from one GitHub repository.

### Negative consequences

- Continuous integration must detect and validate different application areas.
- The repository will become larger as the product grows.
- Frontend and backend boundaries must be maintained through conventions and review.
- Commits and pull requests must remain focused to avoid mixing unrelated work.
- Repository access cannot be separated by application area.

## Review triggers

This decision should be reviewed if:

- independent teams take ownership of the frontend and backend;
- components require separate access permissions;
- applications develop incompatible release cycles;
- repository size significantly affects development or continuous integration;
- an institutional deployment requires stronger repository separation.

If this decision changes, a new ADR must supersede ADR-0001 instead of rewriting its history.
