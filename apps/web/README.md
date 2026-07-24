# Convive web application

Angular frontend for Convive's public reporting and professional case-management
experiences.

## Requirements

- Node.js `^24.15.0`
- npm `11.12.1`

The supported versions are declared in `package.json`. The complete project will
use Docker Compose as its canonical development environment once the walking
skeleton infrastructure is in place.

## Install dependencies

```bash
npm ci
```

`npm ci` installs the exact dependency versions recorded in `package-lock.json`.

## Development server

```bash
npm start
```

The application is then available at `http://localhost:4200/` and reloads when
source files change.

## Production build

```bash
npm run build
```

The generated static assets are written under `dist/`.

## Unit tests

```bash
npm test
```

The initial test suite uses [Vitest](https://vitest.dev/). End-to-end testing is
not configured yet and no E2E command is currently provided.

## Angular CLI

Project-local Angular CLI commands can be run through npm:

```bash
npm run ng -- generate component component-name
```

See the [Angular CLI documentation](https://angular.dev/tools/cli) for the
available commands and schematics.
