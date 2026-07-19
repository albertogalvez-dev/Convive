# ADR-0005: Use Docker Compose for reproducible environments

- **Status:** Accepted
- **Date:** 19 July 2026
- **Related issue:** [#1](https://github.com/albertogalvez-dev/Convive/issues/1)
- **Depends on:** [ADR-0001](0001-use-a-monorepository.md), [ADR-0002](0002-use-a-modular-monolith-for-the-backend.md), [ADR-0003](0003-use-a-separate-web-frontend.md), [ADR-0004](0004-use-angular-for-the-web-frontend.md)

## Context

Convive will contain:

- an Angular web frontend;
- a Symfony backend;
- one primary relational database, currently planned as PostgreSQL;
- infrastructure required to connect and operate those components.

Development takes place primarily on Windows, while continuous integration will
run independently and the public demonstration will be deployed to a Linux VPS.

Installing PHP, Composer, Node.js, PostgreSQL and their system dependencies
directly on every machine would allow runtime versions and configuration to
drift. Convive therefore needs a reproducible environment strategy before its
applications are initialised.

This ADR decides environment orchestration. It does not select the database
engine, API contract, authentication mechanism, public reverse proxy, backup
implementation or monitoring platform.

## Decision drivers

- Reproduce supported runtime versions across development, CI and production.
- Reduce differences between Windows and the Linux VPS.
- Start the complete application through a documented workflow.
- Isolate application and database processes.
- Keep secrets outside the repository and built frontend assets.
- Support development-specific behaviour without copying it into production.
- Build production images without development dependencies.
- Remain understandable and operable by one primary developer.
- Support the initial single-VPS deployment without distributed orchestration.
- Preserve a practical fallback if Windows filesystem performance is inadequate.

## Options considered

### Option A: Install every dependency directly on each host

Install the complete stack on the Windows development computer, CI runner and
Linux VPS.

Benefits:

- direct execution and debugging from the IDE;
- no container runtime;
- potentially faster filesystem access during Windows development.

Costs:

- separate manual setup for every environment;
- greater risk of runtime and extension drift;
- harder onboarding and recovery;
- production depends more heavily on undocumented host state.

### Option B: Containerise only supporting infrastructure

Run the database and optional supporting services in containers while Angular
and Symfony execute directly on the host.

Benefits:

- simpler than containerising the complete stack;
- good local filesystem performance;
- direct IDE debugging;
- reproducible database setup.

Costs:

- PHP, Composer and Node.js still require supported host installations;
- application runtimes may differ between development, CI and production;
- the VPS still needs a separate deployment strategy;
- more than one supported setup must be maintained.

### Option C: Use Docker Compose as the canonical environment

Describe application services, networks, volumes and runtime configuration with
Dockerfiles and Docker Compose. Adapt the common service model through separate
development and production Compose files.

Benefits:

- runtime and system dependencies are defined as code;
- the stack starts through a consistent workflow;
- development, CI and production can reuse container definitions;
- application images can be built reproducibly;
- internal services can remain isolated;
- contributors require less host-specific setup.

Costs:

- Docker and Docker Compose must be installed and understood;
- containers consume additional resources;
- Windows filesystem access may be slower;
- IDE debugging requires container-aware configuration;
- development and production still need distinct configuration;
- Docker does not provide backups, security or observability automatically.

### Option D: Use Kubernetes

Describe and run Convive through Kubernetes.

Benefits:

- declarative orchestration across multiple nodes;
- service scheduling and recovery;
- independent scaling for distributed services.

Costs:

- disproportionate complexity for one developer and one VPS;
- additional networking, storage and security responsibilities;
- more difficult local operation;
- no current scaling or availability requirement justifies it.

## Decision

Convive will use Docker Compose as its canonical environment orchestration
mechanism.

The common model will initially contain three logical services:

- `web` for the Angular frontend;
- `api` for the Symfony backend;
- `database` for the primary relational database.

PostgreSQL is the planned implementation of `database`, but ADR-0007 owns the
selection of PostgreSQL, Doctrine and the migration strategy. ADR-0005 does not
make that persistence decision.

Additional services will only be introduced when a confirmed requirement
justifies them.

## Repository layout

Compose files will be stored under the existing infrastructure area selected in
ADR-0001:

```text
infrastructure/compose/
|-- compose.yaml
|-- compose.development.yaml
`-- compose.production.yaml
```

Application Dockerfiles will remain close to the applications they build:

```text
apps/api/Dockerfile
apps/web/Dockerfile
```

The files will be implemented when the Symfony and Angular applications are
initialised. This ADR fixes their intended responsibilities, not their complete
future contents.

## Environment model

### Common configuration

`compose.yaml` will describe configuration shared by the supported environments,
including logical service names, build contexts, internal networks, dependencies
and persistent volumes where required.

It must not contain real credentials or environment-specific production values.

### Development configuration

`compose.development.yaml` may provide:

- source-code mounts or synchronisation;
- automatic reload;
- development commands and ports;
- debugger configuration;
- safe local defaults;
- development-only tools.

PhpStorm, Git and the source repository will remain on the Windows host.
Containers will execute the supported application runtimes.

The development environment may be started with this cross-platform command:

```text
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml up --build
```

### Production configuration

`compose.production.yaml` may provide or override:

- production image targets;
- restart policies;
- internal-only service exposure;
- production configuration references;
- health checks;
- logging settings;
- immutable application code.

Production must not bind-mount editable application source code from the VPS.

A production environment may be started with:

```text
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.production.yaml up -d
```

Image delivery, migrations, rollback and recovery commands will be defined in a
later deployment ADR and operational documentation.

## Frontend runtime boundary

The logical `web` service does not have identical development and production
behaviour:

- during development, it may run the Angular development server and automatic
  reload tooling;
- during production builds, Node.js compiles the Angular application;
- in production, the resulting static files are served by the selected web
  server or reverse proxy.

The production architecture will not introduce a permanent Node.js application
server. This preserves the rendering and runtime decision made in ADR-0004.

## Local performance fallback

The canonical supported workflow is the complete Docker Compose environment.

Because the repository currently resides on the Windows filesystem, real
Symfony and Angular workloads must be measured before assuming that bind mounts
perform adequately.

If measured filesystem performance prevents practical development, a documented
hybrid fallback may run Angular and Symfony directly on the host while the
database and supporting infrastructure remain in Docker Compose.

That fallback must not replace the container definitions used for CI,
production builds and the supported deployment without a later review of this
decision.

## Container and image rules

- Each service must have one clear responsibility.
- Angular, Symfony and the database must not run inside one container.
- Application data must not be stored only in a container writable layer.
- Production images should use multi-stage builds where appropriate.
- Runtime images must exclude development dependencies and debugging tools.
- Base images must use explicit supported version tags instead of `latest`.
- Images must be rebuilt and reviewed for security updates during the supported
  deployment period.
- Exact PHP, Node.js and operating-system image versions will be pinned when the
  applications are initialised.

## Networking and readiness

Services will communicate through private Compose networks and service names,
not hard-coded container IP addresses.

In production:

- the database must not expose a public host port;
- internal application services must not be published unnecessarily;
- only the required public entry point should be externally reachable;
- HTTPS must terminate at the public entry point.

The public reverse-proxy technology and final network topology belong to the
deployment ADR.

Startup order does not prove service readiness. Stateful dependencies should
provide meaningful health checks, and applications must handle temporary
dependency failure appropriately. `depends_on` is not a replacement for
application-level error handling or retry behaviour.

## Configuration and secrets

Runtime configuration will use environment variables where appropriate.

The repository may include `.env.example` containing variable names and safe
example values. Local secret files such as `.env.local` must remain ignored by
Git.

Real secrets must never be:

- committed to the repository;
- embedded in Dockerfiles or images;
- included in Angular bundles;
- printed in logs.

The production secret-management mechanism will be selected during deployment
design.

## Persistent data

The development database may use a named volume so recreating an application
container does not remove local data. Development data must remain fictional.

A Docker volume is not a backup. Production backup frequency, encrypted backup
storage, retention, restoration testing and recovery procedures will be defined
and verified separately.

## Development-only services

A mail catcher, debugger or database administration interface may later be
enabled through development configuration or Compose profiles when it solves a
confirmed need.

Development-only services must remain disabled in production, must not receive
real personal data and must not become dependencies of normal production
behaviour.

This ADR does not currently select Mailpit, Redis, RabbitMQ, MinIO or another
supporting service.

## Continuous integration and deployment boundary

CI should use the same Dockerfiles or equivalent pinned runtime versions. It
does not need to start the complete production topology for every check.

For example, frontend and backend tests may use their relevant build stages,
while integration tests may start the database through Compose.

Docker Compose is appropriate for the initial single-VPS deployment. Selecting
it does not mean that development configuration can be copied unchanged to
production.

The later deployment decision will define the public reverse proxy, TLS,
application image delivery, migrations, backups, monitoring, rollback and
recovery procedures.

## Explicit exclusions

This decision does not introduce:

- Kubernetes or Docker Swarm;
- multiple production nodes;
- a database engine selection;
- a final public reverse proxy;
- a production mail service;
- a message broker or object-storage service;
- production source-code bind mounts;
- real secrets in Compose files;
- a backup or monitoring implementation.

## Rationale

Docker Compose provides the most appropriate balance between reproducibility,
isolation, learning value and operational complexity for a coordinated Angular
and Symfony application running on one VPS.

A host-only environment would be simpler to execute initially but harder to
reproduce across Windows, CI and Linux. A hybrid environment remains a measured
fallback rather than the default. Kubernetes solves distributed-orchestration
problems that Convive does not currently have.

Development and production will share a service model without pretending that
their security and runtime configuration are identical.

## Consequences

### Positive consequences

- Supported runtimes and system dependencies are described as code.
- Environment differences become explicit and reviewable.
- Another contributor can reproduce the stack more easily.
- CI and production builds can reuse application images.
- The database and application processes remain separated.
- The single-VPS deployment has a documented orchestration mechanism.

### Negative consequences

- Docker knowledge and local resources are required.
- Windows filesystem performance may affect development.
- Debugging requires IDE and container integration.
- Compose files and base images require maintenance.
- File permissions and persistent volumes require care.
- Compose alone does not provide secure deployment, backups or monitoring.

## Review triggers

Review this decision if:

- Windows performance prevents practical development;
- Docker Desktop cannot be used in the supported development environment;
- the VPS cannot run the required containers reliably;
- an institutional environment prohibits the selected container runtime;
- multiple production nodes or independent service scheduling become necessary;
- a managed platform replaces the single-VPS deployment model;
- maintaining the container environment becomes disproportionate to the product.

If the decision changes, a later ADR must supersede ADR-0005 and describe the
migration path.

## References

- [Docker Compose overview](https://docs.docker.com/compose/)
- [Using Compose in production](https://docs.docker.com/compose/how-tos/production/)
- [Multi-stage builds](https://docs.docker.com/build/building/multi-stage/)
- [Docker build best practices](https://docs.docker.com/build/building/best-practices/)
- [Docker Compose Watch](https://docs.docker.com/compose/how-tos/file-watch/)
