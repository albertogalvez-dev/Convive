# Deployment, release and rollback runbook

This runbook defines the repeatable release contract for the single-VPS
fictional-data demonstration selected in
[ADR-0012](../architecture/decisions/0012-use-cloudflare-tunnel-for-the-single-vps-deployment.md).
The production Compose files and automation will implement these steps in issue
#64. Backup and restore commands are completed and exercised in issue #66.

The runbook must not be used for real safeguarding data. A deployment is
eligible only while it is visibly marked as a demonstration and contains
fictional data.

## Release record

Record these values before changing the VPS:

- release identifier, Git commit and approved pull request;
- immutable `gateway` and `api` image digests;
- previous release identifier and image digests;
- migration compatibility classification;
- backup identifier and successful restore-test evidence;
- operator, start time, maintenance window and smoke-test result.

Do not record secret values.

## Preflight

Stop the release if any check fails:

1. The release commit is on `main`; all required CI checks passed; production
   images correspond to that commit and are addressed by digest.
2. Dependency/security review and the release diff have no unresolved blocker.
3. The Compose configuration renders successfully with the intended production
   files and contains no published Convive host ports or source bind mounts.
4. Required secret files exist under the root-owned Convive secret directory,
   have restrictive permissions and are granted only to their intended service.
5. The named tunnel and public DNS route are healthy; the connector token is
   tunnel-scoped; outbound TCP/UDP 7844 is available.
6. VPS disk, memory and load have headroom for the rollout and remain inside the
   Convive resource budget. Existing ProjectX containers are healthy before the
   change.
7. PostgreSQL and Redis report healthy. The current release is healthy and its
   exact digests are recoverable.
8. A fresh encrypted database backup exists and the backup process has recent,
   successful restore-test evidence as required by issue #66.
9. Every pending Doctrine migration has been reviewed. Its forward operation,
   locks, expected duration and rollback class are written in the release
   record. Destructive or incompatible changes have an approved maintenance and
   restore plan.
10. The fictional demo seed command, if required, is idempotent and demo mode is
    explicitly enabled. Never run Doctrine fixtures.

## Release

1. Place the reviewed non-secret release manifest in `/srv/convive/releases/`
   and atomically select it as the candidate release.
2. Pull the exact `gateway` and `api` digests. Inspect the resolved digests before
   continuing; do not deploy a mutable tag by itself.
3. Enter maintenance mode when the migration classification or release notes
   require it.
4. Run Doctrine migrations once in a one-off container created from the new API
   digest. Abort on any failure; do not start the new application generation.
5. If required, run the explicit idempotent fictional-demo seed command. It must
   refuse execution when demo mode is disabled.
6. Reconcile the production Compose project with health waiting enabled. Replace
   only Convive-owned services and never use a host-wide prune or an unrelated
   Compose project command.
7. Keep the previous release manifest and images until the rollback window has
   closed.

## Smoke test

Run the same scripted checks from the VPS and through the public hostname:

1. Compose reports `cloudflared`, `gateway`, `api`, `database` and `redis` as
   healthy, with no restart loop or resource-limit event.
2. The internal gateway health path succeeds without using the public Internet.
3. Public HTTPS uses the intended hostname and returns the expected security and
   no-cache headers for HTML and API responses.
4. An unknown route renders the controlled Angular fallback while an unknown
   `/api/v1/**` route remains an API error and never returns `index.html`.
5. The API health endpoint and one known fictional organisation profile return
   the expected contract without exposing internal data.
6. The demonstration label is visible. No real organisation, person or report
   data appears.
7. A synthetic fictional reporter journey succeeds only when the release plan
   authorises creating smoke data. Its generated reference and secret are not
   written to logs or release records, and the smoke record is removed through
   an application-owned cleanup operation.
8. ProjectX health and its public routes are unchanged after the release.

If any mandatory smoke check fails, stop the release and use the rollback
decision below. Do not declare success based only on healthy containers.

## Rollback decision

### No migration ran

Select the previous manifest, reconcile the Convive Compose project and repeat
the complete smoke test.

### A backward-compatible migration ran

Select the previous image digests, reconcile the Convive Compose project and
repeat the smoke test. Leave the compatible schema in place and repair it in a
later reviewed forward migration; do not invoke Doctrine `down()` automatically.

### An incompatible or destructive migration ran

Keep maintenance mode active. Stop application writes, preserve incident
evidence, restore the verified pre-release database backup according to the
issue #66 procedure, select the previous image digests and repeat the complete
smoke test. Record the recovery point and any data written after the backup that
could not be retained.

If the backup cannot be restored or the previous generation cannot pass smoke
tests, keep the service unavailable and escalate. Serving an uncertain state is
not a valid rollback.

### Tunnel or DNS failure

Restore the last reviewed tunnel route/configuration and verify the named
tunnel. Do not expose a container port or modify ProjectX Caddy as an emergency
workaround. A switch to another ingress path requires its own reviewed and
tested change.

## Completion and evidence

A release completes only when public and internal smoke checks pass, the
previous release remains recoverable for the defined rollback window, monitoring
has received the new release identifier and the release record is complete.
Remove superseded images only through a later Convive-scoped maintenance task.
Never run host-wide Docker cleanup as part of deployment.
