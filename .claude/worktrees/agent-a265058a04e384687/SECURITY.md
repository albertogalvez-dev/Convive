# Security policy

Convive handles an anonymity-sensitive safeguarding domain. We take
security reports seriously and appreciate responsible disclosure.

## Reporting a vulnerability

**Do not open a public GitHub issue for a security vulnerability.**
Public issues are appropriate for functional bugs; a vulnerability
report filed publicly could be exploited before it can be fixed.

Report vulnerabilities through GitHub's private vulnerability
reporting:

1. Go to the [Security tab](https://github.com/albertogalvez-dev/Convive/security).
2. Select **Report a vulnerability**.
3. Describe the issue, affected component and, if possible, steps to
   reproduce it.

This creates a private draft security advisory visible only to the
repository maintainer and yourself until it is resolved and publicly
disclosed together.

## What to expect

- Convive is currently maintained by one person. Acknowledgement and
  triage timelines are best-effort, not a contractual SLA.
- You will receive a response acknowledging the report, followed by an
  assessment of severity and, where applicable, a fix and coordinated
  disclosure timeline.
- Please do not publicly disclose a vulnerability before a fix has
  shipped or a disclosure timeline has been agreed.

## Scope

Convive is under active development and currently processes only
fictional data. As of this writing:

- **In scope:** the Symfony backend (`apps/api`), the Angular frontend
  (`apps/web`), the CI/CD workflows, and the Docker Compose development
  infrastructure in this repository.
- **Out of scope:** the public demonstration deployment's fictional
  data itself (it is intentionally fictional, not sensitive), and any
  denial-of-service testing against shared infrastructure — report the
  underlying weakness instead of demonstrating it against a live
  service.

Convive does not yet process real student, family, professional or
school data (see [ADR-0008](docs/architecture/decisions/0008-use-server-side-sessions-and-capability-based-anonymous-access.md)
and the [product scope](docs/discovery/product-scope.md) for the gates
that must be satisfied before it does). A vulnerability that would
matter once real data is processed is still worth reporting now.

## Supported versions

Convive does not yet have a stable release line — `main` is the only
supported version. Security fixes land there directly.
