# ADR-0012: Use Cloudflare Tunnel for the single-VPS deployment

- **Status:** Superseded by [ADR-0029](0029-use-the-platform-caddy-per-project-edge-for-public-ingress.md)
- **Date:** 9 August 2026
- **Related issue:** [#63](https://github.com/albertogalvez-dev/Convive/issues/63)
- **Depends on:** [ADR-0005](0005-use-docker-compose-for-reproducible-environments.md), [ADR-0007](0007-use-postgresql-and-doctrine-for-persistence.md), [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md)

## Context

Convive needs a reproducible fictional-data demonstration on Alberto's existing
single VPS. The host already runs an unrelated ProjectX installation whose
Caddy process owns ports 80 and 443. Convive must coexist without joining
ProjectX networks, changing its proxy configuration or exposing its database
and application containers.

The VPS was inspected read-only before this decision. It has sufficient current
capacity, Docker Engine and Docker Compose, but capacity at inspection time is
not a guarantee and must be checked before every release.

ADR-0005 selected Docker Compose and deferred public ingress, TLS, production
secrets, image delivery, migrations and rollback. This ADR owns those deployment
boundaries. Backup implementation and observability remain owned by issues #66
and #65.

## Decision drivers

- Isolate Convive from every existing project on the host.
- Avoid opening another public host port or coupling Convive to ProjectX Caddy.
- Preserve same-origin Angular and `/api/v1` routing.
- Keep PHP, PostgreSQL and security-sensitive shared state unreachable from the
  host network and public Internet.
- Make releases reproducible, inspectable and reversible where database
  compatibility permits.
- Keep all credentials outside Git, images, frontend assets and deployment logs.
- Remain operable by one maintainer on one VPS.
- Make the fictional-demo boundary unmistakable; this architecture is not
  approval to process real safeguarding data.

## Options considered

### Reuse the existing ProjectX Caddy

This would avoid another ingress provider, but it would attach Convive to
ProjectX infrastructure and require changes to an unrelated live deployment.
It would also make ownership and rollback less independent.

### Bind a second public reverse proxy to another host port

This would preserve project isolation but expose a non-standard public port or
require host-level routing and firewall changes. It adds public attack surface
without improving the product.

### Use Cloudflare Tunnel with a private Convive gateway

A named Cloudflare Tunnel makes outbound-only connections from `cloudflared` to
Cloudflare. The public hostname terminates TLS at Cloudflare and reaches only a
private gateway on a Convive-owned Compose network. No Convive service publishes
a host port.

This introduces Cloudflare as an availability dependency and a processor of
request traffic. The Free plan has no availability SLA and standard service
does not guarantee EU-only processing. Those constraints are acceptable only
for the fictional demonstration and must be reassessed before real use.

## Decision

Convive will use a named Cloudflare Tunnel for the initial single-VPS
fictional-data demonstration. The hostname will be managed in a Cloudflare DNS
zone. Quick Tunnels (`trycloudflare.com`) are development-only and must not be
used for the durable public deployment.

The deployed request path is:

```text
browser -> Cloudflare edge -> named tunnel -> cloudflared -> gateway
        -> Angular static files or Symfony PHP-FPM -> PostgreSQL / Redis
```

Cloudflare terminates public TLS. The tunnel encrypts the path to `cloudflared`.
The gateway is a Convive-owned Caddy 2 container. It uses private Docker
networking to serve the compiled Angular app and route `/api/v1/**` to Symfony
PHP-FPM before applying the SPA fallback. It runs without automatic HTTPS
because it is not publicly addressable and Cloudflare owns the public
certificate. Internal HTTP/FastCGI traffic never leaves the host. This Caddy
container has its own image, configuration and networks and is unrelated to the
ProjectX Caddy process.

Cloudflare Access will not protect the public reporting journey because that
would prevent the intended anonymous use. Cloudflare controls supplement, but
never replace, Symfony authorisation, CSRF protection, capability checks and
application rate limits.

## Service and trust boundaries

The production Compose project owns these services:

| Service | Responsibility | Reachability |
| --- | --- | --- |
| `cloudflared` | Maintains the outbound named tunnel | Cloudflare and `gateway` only |
| `gateway` | Runs Caddy, serves Angular, applies edge headers and routes `/api/v1/**` | `cloudflared` and `api` only |
| `api` | Runs Symfony through PHP-FPM | `gateway`, PostgreSQL and Redis only |
| `database` | Stores authoritative relational data | `api` only |
| `redis` | Stores shared rate-limit, idempotency and session state | `api` only |

The Compose topology uses four Convive-owned networks:

- `edge`: outbound-capable, containing only `cloudflared` and `gateway`;
- `application`: internal, containing only `gateway` and `api`;
- `data`: internal, containing only `api` and `database`;
- `cache`: internal, containing only `api` and `redis`.

No service publishes a host port. Convive does not join an external ProjectX
network, mount its files or rely on its Caddy instance. `cloudflared` receives no
Docker socket and no host filesystem access beyond its own read-only secret.

The gateway discards untrusted forwarding headers and derives the client
address only from Cloudflare's connector path. Symfony trusts only the gateway,
not arbitrary inbound `Forwarded`, `X-Forwarded-For` or `CF-Connecting-IP`
headers. This preserves meaningful IP-based abuse controls.

Cloudflare and the gateway must not cache HTML, API responses, access pages or
anonymous follow-up content. Only fingerprinted static assets may receive a
long-lived public cache policy. Security headers are applied at the gateway;
HSTS is applied only on the public HTTPS response path.

## Production images and configuration

CI builds production `gateway` and `api` images from a reviewed commit, publishes
them to GHCR and records immutable SHA-256 digests. The VPS pulls those digests;
it does not build application source or bind-mount editable source code.
Base images and runtime dependencies use reviewed version pins. The gateway
image contains Caddy, the compiled Angular assets, the minimal Symfony public
entry files required for FastCGI routing and its reviewed server configuration.
The API image contains the same-path Symfony public entry files, production
Composer dependencies, Symfony code and migrations, but no development tooling.

Non-secret release configuration and image digests live in a root-owned release
manifest under `/srv/convive`. Production secrets are individual root-owned
files outside the Git checkout and are mounted only into the services that need
them through Compose secrets. This includes the tunnel token, database password,
Symfony application secret and Redis credential.

Compose secret mounts are not an encrypted secret store. Host permissions,
encrypted backups and controlled operator access remain required. An
account-wide Cloudflare origin certificate or `cert.pem` must not be stored on
the VPS; the connector receives only a tunnel-scoped token. Secrets must never
appear in Compose arguments, image layers, environment manifests, CI logs or
Angular output.

Containers run as non-root where their supported images permit, drop all
unneeded capabilities, use `no-new-privileges`, a read-only root filesystem and
explicit writable volumes or `tmpfs` mounts. Logs rotate locally. Initial
resource ceilings are reviewed during implementation and load testing; the
entire Convive stack must remain within 4 vCPU and 4 GiB RAM so it cannot consume
the host's full capacity. PostgreSQL storage receives an explicit disk alert and
is never treated as backed up merely because it uses a named volume.

## Release and migration policy

Every release follows the versioned operational checklist in the
[deployment runbook](../../operations/deployment-release-and-rollback.md).
Production migrations run once from the new API image before application
replacement and only after a verified backup. The release owner reviews each
migration as one of:

- backward-compatible, allowing the previous application image to run against
  the migrated schema for the rollback window; or
- incompatible/destructive, requiring a maintenance window and database
  restoration to roll back.

Doctrine `down()` migrations are not assumed safe and are never executed
automatically. An application rollback switches the release manifest back to
the previous image digests only when schema compatibility has been established.
Otherwise recovery restores the verified pre-release database-and-attachment
generation and the previous images, with the resulting recovery point and
potential data loss made explicit.

The single-VPS topology does not promise zero downtime. A short, announced
maintenance window is preferable to an unsafe online migration. Cloudflare or
host failure still makes the service unavailable; the Free plan and one-host
design provide no SLA.

## Fictional demonstration boundary

The public deployment is visibly labelled as a demonstration and contains only
fictional organisations, users and reports. Demo creation is an explicit,
idempotent production command executed after migrations; Doctrine fixtures are
not used because they purge data. The command must refuse to run unless the
fictional-demo mode is enabled.

This decision does not authorise real personal data. Real deployment requires a
separate legal, controller/processor, Article 28, security, retention, incident,
support and operational approval. Cloudflare's terms, data-processing agreement
and data-location options must be reviewed in that process; EU-only controls
may require a paid Enterprise product.

## Consequences

### Positive

- Convive remains isolated from the existing ProjectX deployment.
- No additional inbound VPS port is exposed.
- TLS and public ingress are centrally managed without placing certificates in
  the application containers.
- Immutable images and a release manifest make deployed code identifiable.
- PostgreSQL and Redis remain private and application abuse/idempotency state
  survives container replacement.

### Negative

- Cloudflare becomes part of the request path and processes traffic metadata
  and content.
- The Free plan provides no SLA or EU-only processing guarantee.
- Tunnel, DNS and Cloudflare account recovery become operational duties.
- A single VPS remains a single point of failure.
- Safe rollback depends on migration compatibility or a tested database restore.
- Redis, production images and gateway configuration add components to operate.

## Review triggers

Review this decision before processing real data, when contractual availability
or EU-only processing is required, when the application moves off the current
VPS, when multiple hosts are needed, when Cloudflare Tunnel becomes unsuitable,
or when resource measurements exceed the defined host budget.

## References

- [Cloudflare Tunnel](https://developers.cloudflare.com/tunnel/)
- [Create a remotely-managed tunnel](https://developers.cloudflare.com/tunnel/setup/)
- [Cloudflare Tunnel configuration](https://developers.cloudflare.com/tunnel/configuration/)
- [Quick Tunnels](https://developers.cloudflare.com/cloudflare-one/networks/connectors/cloudflare-tunnel/do-more-with-tunnels/trycloudflare/)
- [Cloudflare data localization](https://developers.cloudflare.com/data-localization/)
- [Docker Compose networks](https://docs.docker.com/reference/compose-file/networks/)
- [Docker Compose secrets](https://docs.docker.com/reference/compose-file/secrets/)
- [Docker image digests](https://docs.docker.com/dhi/core-concepts/digests/)
