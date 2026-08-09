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
suite creates one screenshot after hiding credential fields. Its textual error
context is captured only after the access secret is no longer present in the
page accessibility tree.

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

Create the dedicated fictional organisation. The statement is idempotent and
does not modify any other development record:

```bash
docker compose \
  -p convive-e2e \
  -f infrastructure/compose/compose.yaml \
  -f infrastructure/compose/compose.development.yaml \
  exec -T database psql \
  --username=convive --dbname=convive --set=ON_ERROR_STOP=1 \
  --command="INSERT INTO organisations (id, name, public_reporting_identifier) \
  VALUES ('00000000-0000-4000-8000-000000000027', 'Convive E2E School', \
  'ORG_E2E0000000000000') ON CONFLICT (public_reporting_identifier) \
  DO UPDATE SET name = EXCLUDED.name;"
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
inserts the same fictional organisation, installs the pinned Chromium build and
runs Playwright. The job always removes its containers and volumes. It does not
upload Playwright artifacts.
