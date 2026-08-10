# ADR-0014: Separate public website and application domains

**Status:** Accepted

**Date:** 10 August 2026

## Context

Convive has two distinct public audiences. Prospective centres need
discoverable product, blog, demonstration and contact content. Reporters and
professionals need the application: a direct QR entry, private follow-up and
an authenticated workspace. A QR reporter must never be diverted through a
commercial landing page.

This decision defines architecture targets only. It does not provision DNS,
publish a hostname or enable a public deployment.

## Decision

When public hosts are provisioned, Convive uses two subdomains:

| Host | Responsibility | Indexing |
| --- | --- | --- |
| `https://convive.es` | Product website, blog, demonstration request and contact information | Public website only |
| `https://app.convive.es` | Anonymous reporting, private follow-up and professional workspace | Sensitive application routes excluded |

The canonical initial routes are:

| Audience | URL |
| --- | --- |
| Product home | `https://convive.es/` |
| Blog | `https://convive.es/blog/` |
| Demonstration request | `https://convive.es/demostracion/` |
| Contact | `https://convive.es/contacto/` |
| Direct reporter entry | `https://app.convive.es/r/:publicReportingIdentifier` |
| Private follow-up | `https://app.convive.es/seguimiento` |
| Professional sign-in | `https://app.convive.es/profesionales/acceso` |
| Professional workspace | `https://app.convive.es/profesionales` |

`app.convive.es/` has no commercial-home responsibility. It is an intentional
application route or a safe not-found response, never a duplicate public site.

### Navigation and wordmarks

- A public-website wordmark links to `https://convive.es/`.
- In the direct QR form and follow-up entry, the Convive wordmark remains
  non-navigating: it must not discard form context or redirect to marketing.
- The authenticated professional wordmark links to `/profesionales`, matching
  the implemented dashboard behaviour. The professional access wordmark stays
  non-navigating until a safe in-app destination exists.
- A public-site link to sign-in is explicitly labelled `Área profesional` and
  targets the application sign-in route. It does not imply a demo account.

### Privacy, security and SEO

- Application cookies remain host-only for `app.convive.es`; they are never
  shared with `convive.es`.
- Report references, secrets, capabilities, organisation identifiers and
  report content never reach public-site links, query strings, analytics or
  forms. Application analytics stay disabled pending #56.
- `/r/:publicReportingIdentifier`, `/seguimiento` and professional routes are
  excluded from sitemaps and use `noindex`. Direct QR routing remains available
  to people who receive the link.
- The release/security configuration must prevent identifier-bearing URLs from
  being sent as referrers to another origin. Its exact header implementation is
  deferred to deployment configuration.
- Cross-domain links have visible purpose and accessible keyboard semantics;
  the wordmark alone never communicates a sensitive destination.

## Consequences

This preserves a short, mobile-first reporter journey while allowing a public
site to gain content and SEO without indexing the application. It also makes
cookie, analytics and referrer boundaries explicit. Deployment later needs two
hostnames, tunnel routing, canonical redirects and security headers.

## Deferred work

- #51 implements the public site; #52 implements blog and technical SEO.
- #53 and #54 own interactive fictional demonstrations; #55 owns contact and
  demonstration-request behaviour.
- #56 decides whether privacy-preserving public-site analytics are justified.
- DNS, tunnel hostnames, edge headers and live smoke tests remain authorised
  release/operator work.

## Review triggers

Review before real data, third-party content or analytics, shared
authentication, an organisation directory, QR-route changes or hostname
provisioning.

## References

- [Product scope](../../discovery/product-scope.md)
- [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md)
- [ADR-0009](0009-use-public-organisation-reporting-links.md)
- [ADR-0010](0010-use-a-single-secret-for-anonymous-report-access.md)
- [ADR-0011](0011-allow-the-reporter-browser-password-manager-to-store-the-access-secret.md)
- [ADR-0012](0012-use-cloudflare-tunnel-for-the-single-vps-deployment.md)
