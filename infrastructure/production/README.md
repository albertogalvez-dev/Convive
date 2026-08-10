# Convive production artifacts

These files define the reviewed production image and Compose boundary selected
by ADR-0012. They are deliberately separate from the development Compose
files: no source bind mounts or host ports are present in the production
topology.

## Images

CI builds two immutable images from the reviewed commit:

- `api.Dockerfile`: Symfony/PHP-FPM with production Composer dependencies;
- `gateway.Dockerfile`: Angular production assets served by Caddy, with the
  reviewed API FastCGI route and security headers.

The release workflow publishes both images to GHCR and records their digests.
The VPS must consume the digest form (`image@sha256:...`), never a mutable tag.

## Runtime configuration

Copy `compose.production.env.example` to a root-owned file outside Git and
replace the image digests from the release record. Populate the four files
shown in `secrets/` outside Git with root-only permissions. The Compose project
mounts them as Docker secrets and never stores their values in the image or the
release manifest.

The Cloudflare connector uses the remotely-managed tunnel `--token-file`
parameter, which keeps the tunnel token in a mounted secret file. See the
[Cloudflare run parameters](https://developers.cloudflare.com/tunnel/advanced/run-parameters/)
for the supported flag.

The public demonstration remains fictional-data only. A real-data deployment
requires the legal, privacy, security and operational approvals described in
the architecture records.
