# ADR-0013: Use Restic with off-host object storage for persistent-data recovery

- **Status:** Accepted
- **Date:** 10 August 2026
- **Related issues:** [#66](https://github.com/albertogalvez-dev/Convive/issues/66), [#138](https://github.com/albertogalvez-dev/Convive/issues/138)
- **Depends on:** [ADR-0007](0007-use-postgresql-and-doctrine-for-persistence.md), [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md), [ADR-0012](0012-use-cloudflare-tunnel-for-the-single-vps-deployment.md)

## Context

Convive's fictional-data demonstration will run on one VPS. PostgreSQL is its
authoritative metadata store and also contains professional sessions and
short-lived anonymous capability grants. Private attachment objects are the
second authoritative persistent asset and must remain consistent with their
database rows. A Docker volume or VPS-provider snapshot remains inside the
primary operational failure domain, does not prove application-level recovery
and can revive credentials that should have expired or been revoked.

The release process needs a backup that can be streamed without a plaintext
host copy, encrypted before leaving the host, retained independently and
restored into an isolated environment often enough to expose schema or runtime
incompatibility.

## Decision drivers

- Encrypt and authenticate every backup before off-host storage.
- Keep backup credentials and encryption material outside Git and application
  images.
- Avoid a plaintext database dump on VPS storage.
- Use a failure domain independent from the VPS provider account.
- Apply reviewable retention and integrity checks.
- Restore with ordinary PostgreSQL tooling, recover private attachment objects
  and verify their keys, sizes and hashes against the current database.
- Invalidate restored professional sessions and report capabilities.
- Remain operable by one maintainer without a second database platform.

## Options considered

### Rely on VPS-provider snapshots

Provider snapshots are convenient for whole-machine recovery, but remain tied
to the same provider/account, do not establish database consistency, do not
exercise an application restore and can silently preserve unsafe ephemeral
credentials. They may supplement recovery but cannot satisfy it.

### Stream `pg_dump`, encrypt with `age`, and upload with an S3 client

This uses small independent tools and makes encryption explicit. It also leaves
Convive to implement repository layout, atomic publication, retention, pruning,
integrity verification, deduplication and snapshot selection. That custom glue
would be the least mature part of the recovery boundary.

### Use Restic with S3-compatible object storage

Restic provides client-side authenticated encryption, appendable snapshots,
integrity checking, retention/pruning and several object-storage backends.
Convive can stream PostgreSQL's custom-format dump through standard input, so no
plaintext dump is written to the host. The repository remains portable between
compatible providers.

The cost is another pinned operational tool, a critical repository password and
the need to test Restic upgrades and recovery regularly. S3 credentials remain
visible to host root and the Docker daemon while a job runs, so host compromise
is not solved by backup encryption.

### Move immediately to a managed PostgreSQL service

A managed database may provide point-in-time recovery and independent storage,
but changes the selected single-VPS topology, adds a continuous external
processor/cost and still requires tested logical recovery and credential
invalidation. It is disproportionate for the fictional demonstration and can
be reconsidered before real data or higher availability.

## Decision

Use a version-and-digest-pinned Restic container to receive a PostgreSQL
custom-format dump over standard input and snapshot the private attachment
volume read-only. Store the encrypted repository in a
private Cloudflare R2 Standard bucket in the European Union jurisdiction. R2
is outside the VPS provider account and primary failure domain. Use the
jurisdiction-specific S3 endpoint, a bucket-scoped credential and an
independent, high-entropy Restic password held in the approved operator
password manager.

Publish the database and attachment snapshots as one randomly identified
generation only when object metadata is unchanged and valid before, between
and after both snapshots, and after restoring the object snapshot into a
disposable verification volume. Partial generations are removed and never
receive the `complete` tag used by restore selection or retention. Run an
automated backup daily. Keep 14 daily, 8 weekly and 12 monthly complete
generations for the fictional demonstration, checking repository metadata and
a 5% data sample after each backup. Exercise a complete isolated restoration in
CI on every change and against the deployed off-host repository before release
and at least monthly while public.

The restore selects the latest complete paired generation for the exact
deployed Git revision, imports PostgreSQL and attachment objects only into a
dedicated temporary Compose project, rejects the source volumes, truncates
`professional_sessions` and `report_access_grants`, verifies every non-deleted
attachment object against its restored key, byte count and SHA-256 digest,
validates Doctrine schema and migration state, and starts the Symfony health
endpoint. Timestamped evidence records only the revision, generation prefix,
non-sensitive counts and outcome.

Cloudflare R2 was selected after reviewing the tested Restic candidate and the
repository-versus-runtime boundary. The bucket, subscription, credentials and
repository remain operational resources outside Git. R2's included Standard
allowance is expected to cover the fictional demonstration, but usage remains
metered and must be monitored. Local Restic repositories are test-only and
cannot satisfy off-host recovery.

## Consequences

### Positive

- Database contents and private attachment objects are encrypted before leaving
  the VPS.
- Backups are independent from the VPS provider's host and volume failure.
- No plaintext dump is persisted on host storage.
- Retention, integrity and snapshot identity use established repository
  semantics instead of custom file naming.
- Every code change exercises recovery compatibility with fictional data.
- Restored browser/server credentials are not revived.

### Negative

- Loss of the Restic password makes every snapshot unrecoverable.
- Compromise of host root or the Docker daemon can expose live data and backup
  credentials while a job runs.
- Daily paired generations do not provide point-in-time recovery; the
  fictional-demo RPO is 24 hours.
- Repository checks, pruning and restore tests consume bandwidth, storage and
  operator attention.
- The object-storage provider is an additional operational dependency.
- R2 requires an enabled subscription even when usage remains within its
  included monthly allowance.

## Exclusions and real-data boundary

This decision does not establish retention for real safeguarding data, approve
any provider as a processor, provide immutable ransomware protection by itself
or authorise a real school deployment. Provider snapshots and object versioning
may supplement but never replace the tested logical backup.

Real data requires controller/DPO approval of provider terms, location,
retention, deletion continuity, legal holds, key custody, access auditing and
incident recovery. Point-in-time recovery or a managed database should be
reconsidered at that gate.

## Review triggers

Review this decision before processing real data; when RPO/RTO requirements
tighten; if the database or evidence becomes too large for daily logical dumps;
when the application moves to multiple hosts or managed PostgreSQL; after a
failed recovery exercise; or before changing Restic, PostgreSQL major version,
provider, encryption/key custody or retention policy.

## References

- [Restic: preparing a new repository](https://restic.readthedocs.io/en/latest/030_preparing_a_new_repo.html)
- [Restic: backing up from standard input](https://restic.readthedocs.io/en/latest/040_backup.html#reading-data-from-stdin)
- [Restic: checking integrity](https://restic.readthedocs.io/en/latest/045_working_with_repos.html#checking-integrity-and-consistency)
- [Restic: removing snapshots](https://restic.readthedocs.io/en/latest/060_forget.html)
- [PostgreSQL: `pg_dump`](https://www.postgresql.org/docs/18/app-pgdump.html)
- [PostgreSQL: `pg_restore`](https://www.postgresql.org/docs/18/app-pgrestore.html)
- [Cloudflare R2: S3 API compatibility](https://developers.cloudflare.com/r2/api/s3/api/)
- [Cloudflare R2: data location](https://developers.cloudflare.com/r2/reference/data-location/)
- [Cloudflare R2: pricing](https://developers.cloudflare.com/r2/pricing/)
