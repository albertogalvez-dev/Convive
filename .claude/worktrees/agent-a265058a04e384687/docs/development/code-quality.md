# Static analysis and code-quality baseline

The repository has no generated analysis baseline. Every finding from the
commands below is a merge blocker until it is fixed or receives a narrowly
documented exception. The contributor making a change owns its local quality
evidence; CI enforces the same commands before merge.

## Commands

Run these commands from the repository root with the Compose development stack
running:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec -T api composer analyse
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec -T web npm run typecheck
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec -T web npm run format:check
```

`composer analyse` runs PHPStan at level 8 over application source, tests and
migrations. Its Symfony, Doctrine and PHPUnit extensions model the framework
and test APIs used by Convive; the configuration is
`apps/api/phpstan.neon`. `npm run typecheck` checks both the Angular application
and test projects with TypeScript strict mode and Angular strict templates.
Prettier remains the formatter for TypeScript, templates and styles. The
Angular production build and the backend test suite remain separate required
checks because they exercise compilation and behaviour rather than replacing
static analysis.

The `Backend` and `Frontend` CI jobs run `composer analyse` and
`npm run typecheck` respectively. A local green result is therefore directly
comparable to the corresponding CI gate.

## Findings and exceptions

Fix the code, test or type declaration first. Do not generate a PHPStan
baseline, weaken the configured level, add a broad `ignoreErrors` rule or use a
global TypeScript suppression to make a change pass.

An exception is allowed only when a framework or generated-code limitation is
reproducible and the warning is demonstrably false. It must be scoped to the
smallest file and rule identifier, explain why it is safe in a nearby comment,
and reference a tracking issue with an expiry or removal condition. Reviewers
must reject exceptions that hide a new application, migration or test defect.

There is no versioned Qodana configuration and no accepted IDE-inspection
baseline. PhpStorm and Qodana findings are useful review input, but are not
evidence by themselves: verify an apparent Symfony or Doctrine false positive
with the committed PHPStan configuration, Symfony container lint, Doctrine
schema validation and the relevant tests before suppressing anything.

## PhpStorm setup

Use the Compose `api` service as the PHP CLI interpreter. Configure these
Compose files in this order:

```text
C:\Convive\convive\infrastructure\compose\compose.yaml
C:\Convive\convive\infrastructure\compose\compose.development.yaml
```

Map `C:\Convive\convive\apps\api` to `/app`, select
`/app/phpstan.neon` as the PHPStan configuration, and run PHPUnit through the
same interpreter with `/app/phpunit.dist.xml`. For frontend changes, run the
repository `typecheck` and `format:check` npm scripts through the Compose `web`
service rather than relying on a host Node.js installation. This keeps editor,
local and CI tool versions aligned.
