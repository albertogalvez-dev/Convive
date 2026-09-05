# Architecture Decision Records

This directory contains the Architecture Decision Records (ADRs) for Convive.

An ADR documents a significant technical decision, the alternatives that were considered, the reasons for the selected option and its consequences.

## Status meanings

- **Proposed:** the decision is still being evaluated.
- **Accepted:** the decision has been reviewed and will be applied.
- **Superseded:** a later ADR has replaced the decision.

## Required content

Each ADR should include:

- context and problem;
- decision drivers;
- considered alternatives;
- selected option and rationale;
- positive and negative consequences;
- review triggers.

## Acceptance checklist

Before changing an ADR from **Proposed** to **Accepted**:

1. Read the current product scope, architecture overview and every related ADR.
2. Confirm that the new ADR has one clear decision area and does not silently
   select a concern owned by another ADR.
3. Record its dependencies and keep undecided concerns explicitly deferred.
4. Update the decision index and every affected overview in the same change.
5. Check terminology, repository paths, runtime boundaries and security
   boundaries against the current files.
6. Check relative Markdown links, run `git diff --check` and inspect both the
   working-tree and staged diffs.

**Accepted** means selected and reviewed; it does not mean implemented.

## Decision index

The **Owns** column identifies the subject controlled by each ADR. This prevents
later records from silently selecting the same concern again.

| ADR                                                                                        | Decision                                                                 | Owns                                                                                                                                                  | Status   |
| ------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| [ADR-0001](0001-use-a-monorepository.md)                                                   | Use a monorepository                                                     | Repository topology                                                                                                                                   | Accepted |
| [ADR-0002](0002-use-a-modular-monolith-for-the-backend.md)                                 | Use a modular monolith for the backend                                   | Backend structure and module boundaries                                                                                                               | Accepted |
| [ADR-0003](0003-use-a-separate-web-frontend.md)                                            | Use a separate web frontend                                              | Frontend/backend separation and responsibilities                                                                                                      | Accepted |
| [ADR-0004](0004-use-angular-for-the-web-frontend.md)                                       | Use Angular for the web frontend                                         | Frontend framework and initial rendering model                                                                                                        | Accepted |
| [ADR-0005](0005-use-docker-compose-for-reproducible-environments.md)                       | Use Docker Compose for reproducible environments                         | Environment orchestration                                                                                                                             | Accepted |
| [ADR-0006](0006-use-a-resource-oriented-json-http-api-with-an-openapi-contract.md)         | Use a resource-oriented JSON HTTP API with an OpenAPI contract           | HTTP API style, representations, method conventions and contract generation                                                                           | Accepted |
| [ADR-0007](0007-use-postgresql-and-doctrine-for-persistence.md)                            | Use PostgreSQL and Doctrine for relational persistence                   | Database engine, persistence toolkit, identifiers, relational conventions and migration strategy                                                      | Accepted |
| [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md)         | Use server-side sessions and capability-based anonymous access           | Professional credentials, account authentication and session lifecycle; report-scoped anonymous continuity; real-data authentication assurance gate   | Accepted |
| [ADR-0009](0009-use-public-organisation-reporting-links.md)                                | Use public organisation reporting links without mandatory access codes   | Public organisation routing for report submission                                                                                                     | Accepted |
| [ADR-0010](0010-use-a-single-secret-for-anonymous-report-access.md)                        | Use a single secret for anonymous report access                          | Initial anonymous follow-up verification credential and the non-secret role of the public reference                                                   | Accepted |
| [ADR-0011](0011-allow-the-reporter-browser-password-manager-to-store-the-access-secret.md) | Allow the reporter's browser password manager to store the access secret | Where the reporter's access secret may be retained in the browser, and the boundary between application-controlled storage and the credential manager | Accepted |
| [ADR-0012](0012-use-cloudflare-tunnel-for-the-single-vps-deployment.md)                    | Use Cloudflare Tunnel for the single-VPS deployment                      | Historical public-ingress decision                                                                                                                     | Superseded |
| [ADR-0013](0013-use-restic-with-off-host-object-storage-for-database-recovery.md)          | Use Restic with off-host storage for persistent-data recovery            | Paired encrypted database/object generations, consistency, retention, isolated restore and credential invalidation                                    | Accepted |
| [ADR-0014](0014-separate-public-website-and-application-domains.md)                        | Separate public website and application domains                          | Public-site/application host roles, canonical routes, navigation and indexing boundaries                                                              | Accepted |
| [ADR-0015](0015-use-verified-email-only-for-generic-reporter-notifications.md)             | Use verified email only for generic reporter notifications               | Optional reporter contact, verification, safe message content, retention and the prohibition on email/manual recovery                                 | Accepted |
| [ADR-0016](0016-use-a-browser-printable-access-receipt.md)                                 | Use a browser-printable anonymous access receipt                          | Reporter-controlled paper/PDF receipt format, minimum fields, warnings and credential-safe print metadata                                             | Accepted |
| [ADR-0017](0017-model-triage-as-append-only-decisions.md)                                  | Model report triage as append-only decisions                              | Triage outcomes, terminal semantics, actor attribution and idempotent report-to-case linking                                                          | Accepted |
| [ADR-0018](0018-require-case-assignments-for-case-content.md)                              | Require explicit assignments for case content                             | Case state, minimum involved people, assignments and organisation-plus-case authorisation                                                             | Accepted |
| [ADR-0019](0019-version-case-workflow-sources-and-require-explicit-task-resolution.md)     | Version workflow sources and require explicit task resolution              | Source-aware case tasks, deterministic deadlines and manual external-communication confirmation                                                       | Accepted |
| [ADR-0020](0020-protect-case-audit-events-with-minimised-append-only-records.md)            | Protect case audit events with minimised append-only records                | Case-audit event boundary, minimisation, explicit audit access, fictional retention and controlled export                                            | Accepted |
| [ADR-0021](0021-use-permission-preserving-operational-case-views.md)                         | Use permission-preserving operational case views                             | Exact-assignment operational queues, filters, cursor ordering and non-analytic dashboard boundaries                                                  | Accepted |
| [ADR-0022](0022-generate-minimised-permission-aware-case-pdfs.md)                             | Generate minimised permission-aware case PDFs                                | Lead-only case record, non-identifying operational overview, in-memory rendering and auditable safe delivery                                         | Accepted |
| [ADR-0023](0023-map-andalusian-centre-responsibilities-to-least-privilege-grants.md)           | Map Andalusian centre responsibilities to least-privilege grants             | Visible centre responsibilities, technical grants, exact-case assignment boundary and deliberately unavailable authority                              | Accepted |
| [ADR-0024](0024-use-a-versioned-neutral-triage-taxonomy.md)                                    | Use a versioned neutral triage taxonomy                                      | Reporter and professional structured triage fields, versioning and the prohibition on automatic conclusions                                           | Accepted |
| [ADR-0025](0025-use-explicit-atomic-case-lead-handovers.md)                                     | Use explicit atomic case-lead handovers                                      | Exact-case responsible continuity, assignment/revocation reasons and no automatic access                                                              | Accepted |
| [ADR-0026](0026-use-transloco-for-runtime-internationalisation.md)                              | Use Transloco for runtime internationalisation                               | Frontend i18n library, locale completeness gating, runtime language switching                                                                         | Accepted |
| [ADR-0027](0027-derive-protocol-translation-keys-and-fall-back-to-spanish.md)                    | Derive protocol translation keys and fall back to Spanish                    | Derived template keys, Spanish fallback for protocol content and why it is not gated like the public path                                              | Accepted |
| [ADR-0028](0028-generate-qr-posters-at-build-time-with-a-zero-dependency-encoder.md)             | Generate QR posters at build time with a zero-dependency encoder             | Build-time poster generation, encoder choice and supply-chain surface, and decode-verification before printing                                         | Accepted |
| [ADR-0029](0029-use-the-platform-caddy-per-project-edge-for-public-ingress.md)                    | Use the platform Caddy per-project edge for public ingress                    | Public ingress, Caddy/edge ownership, production trust boundary and release coordination                                                               | Accepted |
| [ADR-0030](0030-adopt-a-free-tier-eu-resident-minimal-footprint-infrastructure-model-for-saas-2.0.md) | Adopt a free-tier, EU-resident, minimal-footprint infrastructure model for Convive SaaS 2.0 | SaaS 2.0 hosting on the shared OVH VPS, external EU object storage and email subprocessors, minimal footprint and portability | Accepted |
| [ADR-0031](0031-enforce-saas-2.0-tenant-isolation-at-the-organisation-boundary-with-a-mandatory-query-filter.md) | Enforce SaaS 2.0 tenant isolation at the organisation boundary with a mandatory query filter | Tenant/centre identity mapping to `Organisation`, cross-tenant query enforcement, tenant-scoping migration checklist, tenant-aware recovery boundary | Accepted |
| [ADR-0032](0032-separate-saas-2.0-environments-by-purpose-with-config-gated-feature-flags.md) | Separate SaaS 2.0 environments by purpose, with config-gated feature flags | Environment-purpose taxonomy (local/test/demo-production/saas-sandbox/saas-pilot), feature-flag mechanism, per-purpose release/rollback scope, demo/SaaS pipeline separation | Accepted |
