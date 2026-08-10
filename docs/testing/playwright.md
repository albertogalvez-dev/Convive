# Playwright end-to-end tests

Convive uses plain Playwright Test for a small number of critical browser
journeys. The initial Chromium test submits a fictional anonymous report, opens
its follow-up area, reads the submitted state, appends information and closes
the report-scoped access grant.

TestDino, Gherkin and other abstraction layers are intentionally not installed.

## Credential safety

The test reads the one-time report access secret only into process memory. It
checks that the value is absent from the URL, local storage, session storage,
IndexedDB database names and browser cookies. The access input is cleared as
soon as the secret has been exchanged for the protected capability cookie.

Playwright traces, automatic screenshots and video are disabled because they
could retain a secret shown by the credential-result page. On failure, the
suite creates one screenshot after hiding anonymous and professional credential
fields. Global teardown removes Playwright's textual accessibility context.

Promotional screenshots or recordings are a separate, explicitly sanitised
demo workflow. They must use stable fictional content and must never record a
real report access secret.

## Isolated local preparation

Stop any development stack currently using ports 4200, 8000 or 5432. From the
repository root, install backend dependencies and start an isolated Compose
project:

```bash
docker compose \
  -f infrastructure/compose/compose.yaml \
  -f infrastructure/compose/compose.development.yaml \
  run --rm --build --no-deps api composer install \
  --prefer-dist --no-interaction --no-progress

docker compose \
  -p convive-e2e \
  -f infrastructure/compose/compose.yaml \
  -f infrastructure/compose/compose.development.yaml \
  up --detach --build
```

Apply migrations without loading or purging fixtures:

```bash
docker compose \
  -p convive-e2e \
  -f infrastructure/compose/compose.yaml \
  -f infrastructure/compose/compose.development.yaml \
  exec -T api php bin/console doctrine:migrations:migrate --no-interaction
```

Create a strong ephemeral professional password in the shell without printing
it, then run the same isolated fictional-demo command used by releases. The
example uses Bash; an equivalent secret-safe environment assignment is required
in other shells:

```bash
export DEMO_PROFESSIONAL_PASSWORD="$(openssl rand -hex 32)"
export E2E_PROFESSIONAL_PASSWORD="$DEMO_PROFESSIONAL_PASSWORD"

docker compose \
  -p convive-e2e \
  -f infrastructure/compose/compose.yaml \
  -f infrastructure/compose/compose.development.yaml \
  exec -T \
  -e APP_ENV=prod \
  -e APP_DEMO_MODE=1 \
  -e APP_SECRET=fictional-e2e-only \
  -e DEMO_PROFESSIONAL_PASSWORD \
  api php bin/console app:demo:seed --env=prod --no-debug
```

From `apps/web`, install the pinned browser and execute the test:

```bash
npm ci
npm run e2e:install
npm run e2e
```

Remove the complete isolated environment and its fictional database afterwards:

```bash
docker compose \
  -p convive-e2e \
  -f infrastructure/compose/compose.yaml \
  -f infrastructure/compose/compose.development.yaml \
  down --volumes --remove-orphans
```

Do not add `--volumes` when stopping the ordinary development project unless
deleting its database is explicitly intended.

## Continuous integration

The `End-to-end` job creates an ephemeral Compose project, applies migrations,
generates and masks a fresh professional password, runs the reviewed demo seed,
installs the pinned Chromium build and runs Playwright. The job always removes
its containers and volumes. It does not upload Playwright artifacts.
