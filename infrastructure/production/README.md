# Convive production artifacts

These files define the reviewed production image and Compose boundary selected
by ADR-0029. They are deliberately separate from the development Compose
files: no source bind mounts or host ports are present in the production
topology.

## Images

CI builds two immutable images from the reviewed commit:

- `api.Dockerfile`: Symfony/PHP-FPM with production Composer dependencies;
- `gateway.Dockerfile`: Angular production assets served by Caddy, with the
  reviewed API FastCGI route and security headers.

The release workflow publishes both images to GHCR and records their digests.
The VPS must consume the digest form (`image@sha256:...`), never a mutable tag.

## Control-plane image pins

Every production Compose service is addressed by an immutable SHA-256 digest.
The comments next to static Compose references and this table retain the reviewed
upstream version for human inspection; the application image digests are emitted
by the release workflow for the reviewed commit.

| Service                       | Reviewed upstream version                       | Immutable reference                                                       |
| ----------------------------- | ----------------------------------------------- | ------------------------------------------------------------------------- |
| PostgreSQL                    | `postgres:18.4-bookworm`                        | `sha256:882236b897e39051d2368c5ccc6cda944904723506b2dfc97f2a8f5bc9afa382` |
| Redis                         | `redis:8.2.1-alpine`                            | `sha256:987c376c727652f99625c7d205a1cba3cb2c53b92b0b62aade2bd48ee1593232` |
| Attachment volume initializer | `busybox:1.37.0`                                | `sha256:9db7b59979c38555a39def84a31fb98b5296952f9e3afd4f6f11f05b07adfab0` |
| API and gateway               | Release workflow output for the reviewed commit | Release-recorded `ghcr.io/...@sha256:...` values                          |

The isolated encrypted recovery Compose file uses the identical PostgreSQL
reference, so its database exercise covers the reviewed production runtime.
Development and its CI stack intentionally retain explicit version tags: they
are a disposable, locally-built feedback environment, whereas CI validates,
pulls and starts the production control-plane pins separately.

### Reviewing an image update

Treat a digest change as a production code change. Before updating one:

1. Review the upstream release notes, image provenance and relevant security
   advisories; choose an explicit upstream version rather than a floating tag.
2. Resolve the reference from the registry with
   `docker buildx imagetools inspect image:version`, record its manifest-list
   SHA-256 digest beside the version, and use `image:version@sha256:...` in
   Compose. Update the recovery PostgreSQL reference together with production.
3. Run the production Compose validation, pinned-image pull/start exercise and
   encrypted recovery test. CI rejects any rendered production image without a
   SHA-256 digest.
4. Submit the version, digest, advisory review and verification evidence for
   normal code review. Do not add registry credentials or secrets to the
   repository; these public images require none.

To roll back an unsuitable update, restore the previous reviewed Compose
revision or release generation. Never repoint a tag or alter a digest in place;
the controlled release procedure keeps the prior immutable application image
generation for compatible rollback.

## Runtime configuration

Copy `compose.production.env.example` to a root-owned file outside Git and
replace the image digests from the release record. Populate the four files
shown in `secrets/` outside Git with root-only permissions. The Compose project
mounts them as Docker secrets and never stores their values in the image or the
release manifest.

Platform Caddy is the only public listener. The gateway only exposes port
`8080` to the external `px-convive-edge` network shared with Caddy; Convive
publishes no host port and carries no Cloudflare Tunnel token or connector.
The project must be registered in `PROJECTX-INFRA` and enrolled with the
platform procedure before a release can create any Convive service. The
platform owns the external `px-convive-internal` network. During `prepare`,
the release reads and validates that network's actual CIDR, then records it as
the API's narrowly trusted proxy range. The remaining private Compose networks
deliberately use Docker-selected non-overlapping ranges rather than assuming a
host-wide subnet allocation.

The API is the only long-running service with the private `attachment-data`
named volume. A network-isolated one-shot initializer assigns that volume to
the unprivileged PHP user before the API starts; the gateway never mounts it or
serves it as a static path. This local store is a fictional-demo boundary, not
a selected real-data storage provider.

Production configuration is split by sensitivity. The reviewed Compose
contract declares non-secret values, including `APP_DEMO_MODE=1`, the canonical
`DEFAULT_URI`, proxy trust, public-reporting mode, attachment storage, disabled
email delivery and the private ClamAV endpoint. The root-only `api.env` file
contains only values that must remain secret: `APP_SECRET`, database/session
DSNs, the authenticated Redis DSN and `DEMO_PROFESSIONAL_PASSWORD`. The demo
password must be unique and contain at least 20 characters; never place it in
Compose, a release manifest or command history.

`check-api-environment.sh` derives the API's environment dependencies from the
Symfony configuration and source attributes, then verifies that every
production dependency is declared in exactly one of those reviewed runtime
locations. CI runs this check so adding a new API environment dependency cannot
leave it documented only in a runbook.

Redis is a private production dependency for security-sensitive cache state.
`api.env` must set `REDIS_DSN` with the same non-empty password as
`redis.conf`; the DSN is a root-only runtime secret, never a repository value.
Symfony uses it for rate-limit and public-report idempotency pools, which must
be shared across API replacements and replicas. CI boots the production API
image against authenticated Redis and proves both pools persist a non-secret
test value.

The public demonstration remains fictional-data only. A real-data deployment
requires the legal, privacy, security and operational approvals described in
the architecture records.
