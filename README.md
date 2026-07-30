# Convive

An open-source web application for secure student reporting and internal school case management.

## Overview

Convive addresses two connected problems:

1. Students may avoid reporting possible bullying situations because of fear, retaliation, or lack of trust in existing channels.
2. School staff need a secure and traceable way to assess reports, document decisions, manage actions, and follow up on internal cases.

Convive separates an initial student report from an internal case. A report does not automatically become a confirmed bullying case. It must first be reviewed by the responsible school staff.

## Project status

The initial application walking skeleton is operational.

The current development environment runs:

- an Angular 22 web application;
- a Symfony 7.4 API on PHP 8.5;
- PostgreSQL 18.4 with Doctrine;
- the complete stack through Docker Compose;
- automated backend, frontend and infrastructure checks through GitHub Actions.

The Angular application currently requests `GET /api/v1/health` through its development proxy and renders the response returned by Symfony.

Convive is under active development and is not ready for use in a real school environment. Development and demonstrations must use fictional data only.

## Requirements

The canonical development environment requires:

- Git;
- Docker Desktop with Docker Compose;
- a web browser.

PhpStorm is the primary development IDE, but it is not required to run the application.

The application runtimes and dependencies execute inside containers. PHP, Composer, Node.js and PostgreSQL do not need to be installed directly on the host.

## Start the development environment

From the repository root, start the common and development Compose configurations together:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml up --build
```

In PhpStorm, the same environment can be started with a Docker Compose run configuration that uses these files in this order:

1. `infrastructure/compose/compose.yaml`
2. `infrastructure/compose/compose.development.yaml`

The environment starts three services:

- `web`: Angular development server with automatic reload;
- `api`: Symfony development server;
- `database`: PostgreSQL.

## Local addresses

Once the services are running:

- Angular application: `http://127.0.0.1:4200`
- Symfony health endpoint: `http://127.0.0.1:8000/api/v1/health`
- PostgreSQL: `127.0.0.1:5432`

The Angular page should display:

```text
API status: ok
```

This confirms the complete development request path:

```text
Browser -> Angular -> development proxy -> Symfony -> Angular
```

PostgreSQL readiness and the Doctrine connection are verified separately.

## Stop the development environment

Stop the running Compose process with `Ctrl+C`, or use the Stop action in PhpStorm.

To remove the development containers and networks while preserving the database volume:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml down
```

## Verification

Verify that Doctrine can connect to PostgreSQL:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec api php bin/console doctrine:migrations:status --no-interaction
```

Execute the backend tests:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec api php bin/phpunit
```

Execute the frontend tests:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec web npm test -- --watch=false
```

Check frontend formatting:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec web npm exec prettier -- --check "src/**/*.{ts,html,scss}" "*.{json,md}"
```

Create the Angular production build:

```bash
docker compose -f infrastructure/compose/compose.yaml -f infrastructure/compose/compose.development.yaml exec web npm run build
```

GitHub Actions repeats the backend, frontend and infrastructure checks for every pull request and for changes pushed to `main`.

## Development data and secrets

The committed development database credentials are fictional and must never be reused in production.

Do not commit:

- real personal or school data;
- production credentials;
- private keys;
- `.env.local` or environment-specific local override files.

The public demonstration and all development environments must use fictional data until the necessary legal, privacy, security and operational conditions for real use have been formally validated.

## Documentation

- [Product scope](docs/discovery/product-scope.md)
- [Regulatory context](docs/discovery/regulatory-context.md)
- [Architecture overview](docs/architecture/README.md)
- [Architecture decision records](docs/architecture/decisions/README.md)
- [Brand assets and usage](docs/brand/README.md)

## License

This project is released under the MIT License. See [LICENSE](LICENSE).

## Acknowledgements

Convive is being developed as part of the Aircury Summer of Code 2026 programme, with mentoring and financial support from Aircury SL.
