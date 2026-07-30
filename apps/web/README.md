# Convive web application

Angular frontend for Convive's public reporting and professional case-management experiences.

## Current status

The frontend is part of Convive's operational application walking skeleton.

It currently:

- runs Angular 22 with TypeScript;
- uses standalone Angular components;
- uses the Angular Router;
- requests `GET /api/v1/health` through `HttpClient`;
- sends relative `/api/**` requests through the development proxy;
- renders the health response returned by Symfony;
- uses Vitest for frontend tests;
- produces static assets through the Angular production build.

This is a technical foundation, not the final product interface.

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

- creation of the root Angular component;
- rendering of the Convive application name;
- the relative health request and rendered API status.

End-to-end browser testing is not configured yet.

## Formatting

Check the frontend source formatting with:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec web npm exec prettier -- --check "src/**/*.{ts,html,scss}" "*.{json,md}"
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
