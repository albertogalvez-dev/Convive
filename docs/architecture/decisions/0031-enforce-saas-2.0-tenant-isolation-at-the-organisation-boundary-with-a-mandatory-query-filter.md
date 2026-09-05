# ADR-0031: Enforce SaaS 2.0 tenant isolation at the organisation boundary with a mandatory query filter

- **Status:** Accepted
- **Date:** 5 September 2026
- **Related issue:** [#506](https://github.com/albertogalvez-dev/Convive/issues/506)
- **Depends on:** [ADR-0007](0007-use-postgresql-and-doctrine-for-persistence.md), [ADR-0009](0009-use-public-organisation-reporting-links.md), [ADR-0013](0013-use-restic-with-off-host-object-storage-for-database-recovery.md), [ADR-0030](0030-adopt-a-free-tier-eu-resident-minimal-footprint-infrastructure-model-for-saas-2.0.md)
- **Does not decide:** internal identifier format (ADR-0007 — UUIDv7 stands), the public organisation-routing identifier (ADR-0009 — `PublicReportingIdentifier` stands), the general migration workflow (ADR-0007), the backup/restore mechanism (ADR-0013), or SaaS 2.0's infrastructure/deployment split from the demo (ADR-0030)

## Context

`docs/product/saas-2.0-charter.md` §3 fixes **tenant ↔ centre ↔ centre workspace**
as one object, and INV-1 requires that no tenant can discover, read, mutate,
export or recover another tenant's data through any path — API, URL or
identifier guessing, session, membership, attachment, export, search, backup or
recovery — with cross-tenant access denied by default and proven by negative
tests. The charter defers the technical design to this issue and to #510/#514/#535.

Convive already has a working single-tenant-shaped concept: the `Organisation`
entity (`apps/api/src/Organisations/Domain/Organisation.php`), linked to
`Professional` through `OrganisationMembership`, carrying its own public routing
identifier (`PublicReportingIdentifier`, ADR-0009) distinct from its internal
UUIDv7 primary key. Today exactly one organisation exists (the fictional demo),
so nothing has ever exercised isolation *between* organisations. Cross-tenant
access control is enforced only by hand-written comparisons inside
application/domain services — for example `AuthoriseCaseAccess::require()`
(`apps/api/src/Cases/Application/AuthoriseCaseAccess.php`), which checks that the
professional holds an active `OrganisationMembership` matching the case's
organisation before checking the specific `CaseAssignment` permission. There is
no Symfony Voter layer and no Doctrine query-level scoping (`SQLFilter` or
equivalent) anywhere in the codebase — every scoping check is a manually written
`Uuid::equals()` comparison, repeated per service.

SaaS 2.0 turns this from a decorative concept into a real security boundary:
many real centres, each administering its own members, cases and evidence, on
one shared database (§4.4's minimal-footprint constraint rules out a
database-per-tenant or schema-per-tenant model — see "Considered alternatives").
The number of organisation-scoped entities and services will grow quickly across
#511–#525. A single missed `Uuid::equals()` check in a new service is exactly
the failure mode INV-1 exists to close, and relying solely on hand-written
per-service checks gives no second line of defence when one is missed.

The demo and SaaS 2.0 share only reviewed source code and visual direction
(charter §4.1); they run as separate deployments with separate databases
(ADR-0030). That structural separation is what actually prevents the demo's
single fictional organisation from ever becoming a SaaS tenant — this ADR does
not re-decide that boundary, only states the one additional code-level rule it
depends on (see "Decision", point 5).

## Decision drivers

- Meet INV-1 with a mechanism that survives a missed check in an individual
  service, not only correct application code.
- Keep the minimal-footprint, free-tier-first infrastructure model from
  ADR-0030 — no per-tenant database or schema.
- Reuse `Organisation` and `PublicReportingIdentifier` rather than introducing a
  parallel "Centre" concept that would fork the demo/SaaS shared codebase.
- Keep the existing migration and recovery tooling (ADR-0007, ADR-0013) rather
  than building tenant-specific tooling the current scope and budget do not
  justify.
- Make a missing tenant scope a build-time/test-time failure, not a
  production incident.

## Considered alternatives

**Database-per-tenant or schema-per-tenant.** Rejected. Every new centre would
need its own migration run, backup generation and connection pool entry,
directly contradicting the minimal-footprint model (ADR-0030 §4.4) and adding
operational surface with no engineering capacity to run it. It also does not
remove the need for row-level checks, since some data (accounts, which may hold
memberships in more than one centre per charter §4.2) is not tenant-exclusive.

**Hand-written checks only (status quo, scaled up).** Rejected as the sole
mechanism. It is what the demo already does, and it is exactly the pattern that
fails silently: a new endpoint or repository query that forgets one
`Uuid::equals()` comparison ships a cross-tenant leak with no test failure to
catch it, because nothing outside the individual service enforces the boundary.

**A new "Centre" entity, `Organisation` retired.** Rejected. It would fork the
one concept the demo and SaaS 2.0 are meant to keep sharing (charter §4.1) for
no technical gain — `Organisation` already carries what a centre is.

## Decision

**1. Tenant = Organisation, no new entity.** SaaS 2.0's centre is the existing
`Organisation` entity. #512 extends it with the `Sandbox` / `Activation under
review` / `Activated` lifecycle state (charter §4.2); it does not introduce a
parallel tenant concept. Every reference in SaaS 2.0 code and documentation to
"tenant" or "centre" means this same row.

**2. Every tenant-scoped table carries a non-nullable `organisation_id`.**
Any entity holding data that belongs to one centre (cases, memberships,
attachments, audit events, exports, and everything added under #511–#525) has a
non-nullable foreign key to `organisations`. An entity with no legitimate single
owner (for example a truly global reference taxonomy) is exempt only by an
explicit, reviewed comment on the entity recording why, so the exemption is a
decision, not an omission.

**3. A mandatory, deny-by-default Doctrine query filter is the second
enforcement layer.** SaaS 2.0 registers a Doctrine `SQLFilter` scoped to the
authenticated request's organisation id, enabled on every request by default
for every organisation-scoped entity from point 2. This does not replace the
existing per-service authorisation pattern (`AuthoriseCaseAccess` and its
siblings keep deciding *what a member of this organisation may do*); it adds an
independent layer that keeps a query from ever returning another organisation's
rows even if a service-level check is missing, disabled for the wrong branch, or
added later without the original author's context. The filter is part of the
request boot sequence, not something a controller opts into.

**4. Migrations stay single, shared and atomic — with a new checklist item.**
Doctrine's existing migration workflow (ADR-0007) is unchanged: one migration
history, applied and rolled back together, never per tenant. What SaaS 2.0 adds
is a review requirement — a migration introducing an organisation-scoped table
adds its `organisation_id` column and filter tag in the same migration, and the
test suite exercises it against fixtures spanning at least two organisations, so
a missing column or filter tag fails CI rather than surfacing as a production
leak. Schema rollback remains whole-database; there is no partial-tenant
rollback, and none is being built.

**5. Recovery stays whole-database; "tenant-aware" means verifying isolation
survived it, not selective restore.** SaaS 2.0's own database is backed up and
restored with the same Restic mechanism as the demo (ADR-0013), into its own
separate repository (ADR-0030) — restoring SaaS 2.0 never touches the demo's
database and no tooling holds credentials for both at once, which is the one
additional rule this ADR adds on top of ADR-0030's deployment split: no script
or migration in either codebase accepts two database connection strings at
once. Beyond that, #535's tenant-aware recovery drill confirms two things after
a restore: every `organisation_id` foreign key and filter tag is intact, and
every session and capability across every organisation is invalidated — the
existing ADR-0013 requirement, now checked across more than one organisation
instead of the demo's single one. Per-tenant selective export or restore is an
explicit non-goal of this ADR; a real-data pilot's single-centre offboarding or
erasure need is a separate, later decision, not solved here.

**6. Public identifiers are unchanged.** Centres keep using
`PublicReportingIdentifier` (ADR-0009) for public routing. This ADR does not
add a second identifier scheme.

## Consequences

### Positive

- INV-1 gets two independent enforcement layers instead of one; a missed
  service-level check is caught by the filter instead of leaking data.
- No new infrastructure, database, or identifier scheme — the minimal-footprint
  model and the demo/SaaS shared-code model both hold.
- The migration checklist turns a class of bug (forgotten tenant scope) into a
  CI failure instead of a production incident.

### Negative

- Every organisation-scoped repository query now depends on request-scoped
  filter state being correctly bound; a bug in the filter's own boot sequence
  is a single point of failure for the whole layer, which is why it is
  additive to, not a replacement for, service-level checks.
- The two-organisation fixture requirement adds test-authoring overhead to
  every new tenant-scoped entity.
- No selective per-tenant restore or export exists; a future real-data
  single-centre offboarding request will need its own design, not reuse this
  one.

## Review triggers

Review before removing or bypassing the query filter for any endpoint, before
introducing an entity that stores organisation-scoped data without the
`organisation_id` column or an explicit exemption comment, before any tooling
is given simultaneous access to more than one deployment's database connection
string, and before any per-tenant selective export or restore capability is
proposed.
