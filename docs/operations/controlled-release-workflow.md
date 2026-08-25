# Controlled release workflow

Issue #64 owns the controlled delivery boundary for the single-VPS fictional
demonstration. The release workflow is manual by design: a push to `main`
builds and tests the application through CI, while a maintainer explicitly
chooses a reviewed `main` commit and approves the `convive-demo` environment
before any VPS mutation.

## Release inputs and gates

The workflow in `.github/workflows/release.yaml` accepts:

- `release_ref`: a commit or tag that must resolve to `main`;
- `deploy`: `false` for build-and-evidence only, `true` to request the guarded
  VPS rollout;
- `migration_class`: `none`, `backward-compatible` or `incompatible`.
- `release_phase`: `prepare` before the reviewed Caddy route exists, then
  `verify` only after that route has been validated and reloaded.

Before building, it verifies that the required CI checks for the selected
commit are successful. The build publishes the API and gateway images to GHCR
and captures immutable digests. The deployment job is attached to the
`convive-demo` GitHub environment, so repository administrators can require a
reviewer approval and scope its secrets independently from other environments.

The deployment path remains fail-closed unless all of the following are
present in that environment: VPS host/user/SSH key, the public demonstration
URL, and the approved migration class. Runtime application, database and Redis
secrets are provisioned on the VPS under the root-owned secret
directory; they are never copied through GitHub logs or command arguments.

## What the VPS rollout does

The versioned `infrastructure/release/reconcile.sh` script:

1. verifies root execution, digest-pinned images, secret-file permissions and
   the private production Compose topology;
2. checks recent encrypted backup/restore evidence before changing services;
3. starts PostgreSQL and Redis, runs migrations once from the new API image and
   then reconciles only the Convive Compose project;
4. stops after healthy internal preparation so the operator can validate and
   add the explicit platform Caddy route;
5. records a secret-free release manifest and previous generation only after
   the separate public verification phase; and
6. restores the previous generation automatically only when the migration is
   declared absent or backward-compatible. Incompatible migrations keep the
   service stopped for the documented database-restore procedure instead of
   guessing.

The script never joins unrelated ProjectX networks, changes its Caddy configuration,
publishes a Convive host port or runs a host-wide Docker cleanup.

## Environment setup boundary

Creating the GitHub environment and adding its scoped secrets is an operator or
repository-administrator action. The repository contains only the names and
validation rules; it does not contain real credentials. The first public
deployment also requires the Convive `PROJECTX-INFRA` registration, the platform
enrollment command and one reviewed Caddy hostname route. Cloudflare may provide
DNS or a reviewed proxy policy, but Convive does not run a Tunnel.

The deployment workflow idempotently invokes the platform enrollment command
before uploading a release. It fails before any Convive image or runtime change
if the reviewed ProjectX registration is absent or the deployment account cannot
perform that narrow enrollment action.

The release record identifies the commit, image digests, previous generation,
migration class, backup evidence, operator, timings and smoke outcome. It never
records application, database, Redis or SSH secret values.
