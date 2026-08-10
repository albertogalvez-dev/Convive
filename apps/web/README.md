# Convive web application

Angular frontend for Convive's public reporting and professional case-management experiences.

## Current status

The frontend contains Convive's public product entry, direct reporter journey,
private follow-up and protected professional workspace.

It currently:

- runs Angular 22 with TypeScript;
- uses standalone Angular components;
- uses the Angular Router;
- sends relative `/api/**` requests through the development proxy;
- renders the public product homepage at `/`;
- keeps the planned public website and application hosts separate while making
  both paths available on local development hosts;
- presents clear, non-operational public information pages until their blog,
  interactive demonstration and contact journeys are implemented;
- exposes `/r/:publicReportingIdentifier` for anonymous report submission;
- guides the reporter through description, context and review steps;
- displays the returned reference and one-time access secret without persisting
  either credential in browser storage or the URL;
- handles expected public API errors without exposing internal details;
- uses Vitest for frontend tests;
- produces static assets through the Angular production build.

Attachments, optional email, public contact collection, blog publication and
interactive demonstrations remain separate product increments. All current
demonstration data is fictional.

## Public host boundary

The registered public product host is `https://conviveaula.com`; the sensitive
application host is `https://app.conviveaula.com`. The former owns product,
blog, demonstration and contact routes. The latter owns direct reporting,
private follow-up and the professional workspace. Local development hosts
intentionally serve both areas so the complete application remains testable.

The root page does not route QR reporters through product content. On the
application host, `/r/:publicReportingIdentifier` remains the direct reporter
entry point. The host, cookie, navigation and indexing consequences are defined
in [ADR-0014](../../docs/architecture/decisions/0014-separate-public-website-and-application-domains.md).

## Canonical development environment

The frontend runs as the `web` service in the project's Docker Compose development environment.

From the repository root, start the complete environment with:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml up --build
```

The Angular application is then available at:

```text
http://127.0.0.1:4200
```

Source files are mounted into the container. Angular watches for changes and rebuilds the application automatically.

## Runtime versions

The development image uses:

- Node.js `24.15.0`;
- npm `11.12.1`.

The supported versions are also declared in `package.json`.

Node.js and npm do not need to be installed directly on the host when using the canonical Docker Compose environment.

## API proxy

Browser requests use the relative `/api/v1` path.

During development, `proxy.conf.json` forwards `/api/**` requests to the Compose `api` service:

```text
Browser -> Angular development server -> Symfony API
```

The browser does not communicate with the internal Docker service name directly. This preserves the same-origin browser contract without introducing development CORS configuration.

## Install dependencies

The Docker image installs the exact dependencies recorded in `package-lock.json` with:

```bash
npm ci
```

The development environment stores Linux `node_modules` in a Docker volume instead of mixing them with host dependencies.

## Frontend tests

With the Compose environment running, execute the frontend tests from the repository root:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec web npm test -- --watch=false
```

The current test suite verifies:

- root routing and host-boundary behaviour;
- public product and public-information destinations;
- the reporting journey, API request and response states;
- context-selection rules and safe public error messages;
- credential copy behaviour and leave protection;
- keyboard interaction and focus management in the help dialog.

The isolated Playwright suite also covers the fictional reporter-to-professional
journey and the public homepage's accessibility, responsive layout and direct
reporter separation. Follow [the Playwright guide](../../docs/testing/playwright.md)
before running the full browser suite locally.

## Formatting

Check the frontend source formatting with:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec web npm exec prettier -- --check "src/**/*.{ts,html,scss}" "*.{json,md}"
```

## Type checking

Check the Angular application and test projects with the repository's strict
TypeScript and template configuration:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec web npm run typecheck
```

## Production build

Create the Angular production build with:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec web npm run build
```

The generated static assets are written under:

```text
apps/web/dist/convive-web
```

The production deployment will serve these static assets through the selected public web server or reverse proxy. It will not use the Angular development server as a production server.

## Angular CLI

Project-local Angular CLI commands can be executed inside the running `web` service. For example:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec web npm run ng -- generate component component-name
```

See the [Angular CLI documentation](https://angular.dev/tools/cli) for available commands and schematics.
