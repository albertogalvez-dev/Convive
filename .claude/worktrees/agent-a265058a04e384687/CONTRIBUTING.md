# Contributing to Convive

Convive is currently maintained by one primary developer. External
contributions are welcome, but review capacity is limited — a pull
request may take a while to receive attention, and not every proposal
will fit the project's current direction or delivery order.

## Before you start

- Read the [README](README.md) for architecture and local setup.
- Check [open issues](https://github.com/albertogalvez-dev/Convive/issues)
  and the [roadmap tracking issue](https://github.com/albertogalvez-dev/Convive/issues/71)
  before proposing new work, to avoid duplicating something already
  planned or in progress.
- For anything touching security, privacy or the anonymous-access
  model, read the relevant [architecture decision records](docs/architecture/decisions/README.md)
  first — Convive's threat model treats these as deliberate, reviewed
  decisions, not implementation details.
- Development and demonstration data must always be fictional. Never
  submit a change that includes real student, family, professional or
  school information, in code, fixtures, tests or examples.
- Read the [layered testing strategy](docs/testing/strategy.md) before changing
  a test boundary or deciding what evidence a behaviour change needs.
- Read the [accessibility baseline](docs/testing/accessibility.md) before
  changing an interface, keyboard interaction, responsive layout or motion.

## Workflow

1. **Open or claim an issue** before starting non-trivial work, so the
   scope is agreed before code is written.
2. **Branch from `main`**, named `<issue-number>-<short-slug>` (for
   example `41-add-rate-limiting-idempotency-and-public-report-anti-abuse-controls`).
3. **Keep commits coherent.** Split commits by concern rather than by
   chronology — a commit should be independently reviewable. Commit
   messages explain *why*, not just what changed.
4. **Verify locally before opening a pull request**:
   - Backend (`apps/api`): `composer validate --strict`, `composer audit --locked`,
     `composer analyse`,
     `php bin/console lint:yaml config`, `php bin/console lint:container`,
     the OpenAPI contract diff (`php bin/console nelmio:apidoc:dump --format=yaml`
     compared against `docs/api/openapi.yaml`), `doctrine:migrations:migrate`
     against a clean test database, `doctrine:schema:validate`, and
     `vendor/bin/phpunit`.
   - Frontend (`apps/web`): `npm run format:check`, `npm run typecheck`,
     `npm audit --omit=dev`, `npm test`, `npm run build`.
   - Browser, recovery and infrastructure changes: run the relevant isolated
     exercise documented in `docs/testing/strategy.md` and record only
     redacted fictional-data evidence.
   - All three commands run inside the Docker Compose development
     stack described in the README — no host PHP, Composer or Node.js
     installation is required.
5. **Open a pull request** against `main` describing what changed and
   why, referencing the issue it closes. Fill in the pull request
   template's verification checklist honestly — an unchecked item is
   better than a false claim.
6. **Continuous integration must pass.** The `Backend`, `Frontend`,
   `Infrastructure`, `Dependency review`, `Encrypted recovery` and
   `End-to-end` GitHub Actions checks are required status checks on `main`; a
   pull request cannot merge until all six succeed. `Dependency review` is
   intentionally pull-request-only and is skipped on the post-merge push.
   The additional `PR traceability` workflow must also pass and validates the
   closing issue reference plus its evidence sections.
7. Once merged, the source branch is deleted. If the merged issue has
   an acceptance checklist, it should be checked off against the
   actual merged evidence, not assumed complete because a pull request
   exists.

## Code style

- Backend: strict types everywhere, immutable value objects for
  validated primitives, domain code free of framework and HTTP
  concerns. Match the structure of the module you're editing rather
  than introducing a new pattern for a single change.
- Frontend: Prettier is the formatting authority; TypeScript strict mode and
  Angular strict templates are required. Run the documented checks before
  committing rather than hand-formatting.
- Comments explain *why*, not *what* — well-named code should make the
  *what* obvious on its own.

## Reporting a security issue

See [SECURITY.md](SECURITY.md). Do not open a public issue for a
security vulnerability.
