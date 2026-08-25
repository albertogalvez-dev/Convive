# Deployment, release and rollback runbook

This runbook defines the repeatable release contract for the single-VPS
fictional-data demonstration selected in
[ADR-0029](../architecture/decisions/0029-use-the-platform-caddy-per-project-edge-for-public-ingress.md).
The production Compose files and automation will implement these steps in issue
#64. Backup and restore commands are defined in the
[encrypted backup and recovery runbook](backup-and-recovery.md) and are completed
and exercised in issue #66.

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

Use the fillable template in
[`release-records/TEMPLATE.md`](release-records/TEMPLATE.md), copied to a dated
file in that directory and filled **during** the release. The list above is what
to capture; the template is where it goes, and it also carries the failure,
rollback and acceptance sections a completed release needs.

## Preflight

Stop the release if any check fails:

1. The release commit is on `main`; all required CI checks passed; production
   images correspond to that commit and are addressed by digest.
2. Dependency/security review and the release diff have no unresolved blocker.
3. The Compose configuration renders successfully with the intended production
   files and contains no published Convive host ports or source bind mounts.
4. Required secret files exist under the root-owned Convive secret directory,
   have restrictive permissions and are granted only to their intended service.
5. The Convive project is registered in `PROJECTX-INFRA`, enrolled on the VPS,
   and its sole public route is the reviewed platform Caddy hostname route.
   Cloudflare DNS/proxy settings match that reviewed route; no application
   tunnel or host port exists.
6. VPS disk, memory and load have headroom for the rollout and remain inside the
   Convive resource budget. Existing ProjectX containers are healthy before the
   change.
7. PostgreSQL and Redis report healthy. The current release is healthy and its
   exact digests are recoverable.
8. A fresh encrypted persistent-data generation exists and the backup process
   has recent, successful database-and-attachment restore-test evidence as
   required by issues #66 and #138.
9. Every pending Doctrine migration has been reviewed. Its forward operation,
   locks, expected duration and rollback class are written in the release
   record. Destructive or incompatible changes have an approved maintenance and
   restore plan.
10. The [fictional demo seed command](fictional-demo-data.md), if required, is
    idempotent and demo mode is explicitly enabled. Never run Doctrine fixtures.

## Release

1. Enrol the reviewed project once with `sudo projectx-enroll-project --project
   convive`, then place the reviewed non-secret release manifest in
   `/srv/platform/projects/convive/releases/`
   and atomically select it as the candidate release.
2. Pull the exact `gateway` and `api` digests. Inspect the resolved digests before
   continuing; do not deploy a mutable tag by itself.
3. Enter maintenance mode when the migration classification or release notes
   require it.
4. Run Doctrine migrations once in a one-off container created from the new API
   digest. Abort on any failure; do not start the new application generation.
5. If required, run the explicit idempotent fictional-demo seed command using
   the [reviewed procedure](fictional-demo-data.md). It must refuse execution
   when demo mode is disabled. A destructive restore additionally requires its
   exact reset confirmation token.
6. Run the guarded **prepare** phase. It starts only Convive-owned services and
   waits for their health; it does not make them public.
7. Validate the exact new Caddy route, then add it only after the prepare phase
   is healthy. Reload only platform Caddy after validation; never restart or
   edit another project.
8. Run the guarded **verify** phase through the public hostname. Keep the
   previous release manifest and images until the rollback window has
   closed.

## Smoke test

Run the same scripted checks from the VPS and through the public hostname:

1. Compose reports `gateway`, `api`, `database`, `redis` and `clamav` as
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
evidence, restore the verified pre-release persistent-data generation according
to the issues #66/#138 procedure, select the previous image digests and repeat
the complete smoke test. Record the recovery point and any data written after
the backup that could not be retained.

If the backup cannot be restored or the previous generation cannot pass smoke
tests, keep the service unavailable and escalate. Serving an uncertain state is
not a valid rollback.

### Caddy route or DNS failure

Remove or restore only the reviewed Convive Caddy route and verify its hostname
and DNS policy. Do not expose a container port or modify unrelated ProjectX
Caddy routes as an emergency workaround. A switch to another ingress path
requires its own reviewed and tested change.

## Completion and evidence

A release completes only when public and internal smoke checks pass, the
previous release remains recoverable for the defined rollback window, monitoring
has received the new release identifier and the release record is complete.
Remove superseded images only through a later Convive-scoped maintenance task.
Never run host-wide Docker cleanup as part of deployment.
