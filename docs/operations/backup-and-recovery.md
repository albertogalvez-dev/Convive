# Encrypted backup and recovery

This runbook defines the recoverable backup boundary for the fictional-data
demonstration. A provider snapshot or a Docker volume is not a Convive backup.
The production release workflow must not proceed without a recent successful
backup and restore-test evidence record.

## Recovery targets and scope

The only persistent application asset in the current deployment is PostgreSQL.
The backup therefore contains one transactionally consistent custom-format
`pg_dump` of the complete Convive database, including Doctrine migration state.
Redis is deliberately excluded: it holds restart-resistant rate-limit and
idempotency state, not authoritative business data. Angular assets, container
images and configuration are reproduced from the reviewed Git revision and
immutable image digests; secrets are restored from the separate operator secret
store, never from the database backup.

For the fictional demonstration, the operational targets are:

- recovery point objective (RPO): no more than 24 hours of database changes;
- recovery time objective (RTO): four hours from declared recovery to verified
  service restoration;
- schedule: daily at 02:30 Europe/Madrid with up to 20 minutes of random delay;
- retention: 14 daily, 8 weekly and 12 monthly snapshots;
- integrity: Restic repository metadata and 5% of stored data checked after each
  successful backup, plus a full isolated restoration exercise before release
  and at least monthly while the demonstration is public.

These periods apply only to unmistakably fictional demonstration data. They do
not define retention for real personal data. Any real-data deployment requires
controller/DPO approval of retention, deletion, legal-hold and backup-erasure
behaviour.

## Encryption and failure-domain separation

Restic `0.19.1` is pinned by image digest in the scripts. It encrypts and
authenticates repository contents client-side before upload. Production uses a
private Cloudflare R2 Standard bucket in the European Union jurisdiction, held
outside the VPS provider account and primary failure domain. The bucket must
have public access disabled, use its jurisdiction-specific TLS S3 endpoint and
a dedicated credential limited to the one backup bucket. Provider versioning or
object lock is desirable defence in depth, but does not replace Restic retention
and restore exercises.

Three independent secret values are required: the Restic repository password,
the object-storage key identifier and its secret. They must not be reused for
the VPS, database, Cloudflare Tunnel or application. Loss of the Restic password
makes the backup unrecoverable; compromise of both repository data and password
exposes the database. Store a recoverable copy of the password in Alberto's
approved password manager before initialising the repository.

Cloudflare R2 Standard in the EU jurisdiction is the accepted production
provider boundary. The subscription, private bucket and bucket-scoped API token
must still be provisioned and exercised before this issue can be closed. Local
repositories are accepted only by the automated recovery test and cannot
satisfy the off-host criterion. R2 usage is metered even when it remains within
the included monthly allowance and must be monitored.

### Cloudflare R2 provisioning checklist

Provision these resources interactively in the reviewed Convive Cloudflare
account. Enabling the R2 subscription is a billing action and requires the
account owner's explicit approval. Do not record account identifiers, payment
details or credentials in Git, tickets or terminal transcripts.

1. Enable R2 only after approval and confirm the account and billing boundary
   before submitting the subscription action.
2. Create one new Standard storage bucket dedicated to Convive backups. Under
   **Location**, choose **Specify jurisdiction** and **European Union (EU)**.
   A Western or Eastern Europe location hint is not an equivalent residency
   control, and a bucket's jurisdiction cannot be changed after creation.
3. Leave both public access mechanisms disabled: do not enable the Public
   Development URL and do not attach a custom domain. Confirm the bucket
   settings report no public access before uploading any backup.
4. Create a dedicated R2 account API token with **Object Read & Write** access
   scoped to only this bucket. Do not grant account-admin access or access to
   all buckets. Record the one-time Access Key ID and Secret Access Key directly
   in the approved password manager.
5. Populate the root-owned runtime environment file with the EU endpoint
   `s3:https://<account-id>.eu.r2.cloudflarestorage.com/<bucket>`, the two R2
   credentials and `AWS_DEFAULT_REGION=auto`. Keep the independent Restic
   password in the password manager as well as on the host.
6. Run `init-repository.sh`, `backup.sh` and the complete isolated
   `restore-test.sh`. The issue remains open until all three succeed against R2
   and the resulting non-secret evidence records identify the deployed Git
   revision and a successful outcome.
7. Recheck that no public URL or custom domain was enabled, inspect the bucket's
   stored-byte and operation metrics, and set an operational usage threshold or
   billing notification appropriate to the approved cost boundary.

The S3 credentials deliberately need delete access: Restic retention and
repository maintenance remove objects. Limiting them to this bucket contains
that authority without breaking `forget --prune`. A separate read-only token
cannot run the automated backup lifecycle and is not a substitute.

## Host configuration

Copy the non-secret example to `/etc/convive/backup-job.conf`, replace the
deployed revision on every release and keep it owned by `root:root`. Copy the
repository example to `/etc/convive/backup-repository.env`, fill it without
printing values in shell history and set `root:root` mode `0400`:

```text
install -d -o root -g root -m 0700 /etc/convive /var/lib/convive-backup/evidence
install -o root -g root -m 0600 infrastructure/backup/backup-job.conf.example /etc/convive/backup-job.conf
install -o root -g root -m 0400 infrastructure/backup/backup-repository.env.example /etc/convive/backup-repository.env
```

Never commit the populated files. The scripts refuse a secret file more
permissive than `0400` or `0600`, refuse host-local storage in `off-host` mode
and emit only stage names, shortened snapshot identifiers and non-sensitive
counts.

After the production Compose project is healthy, initialise exactly once and
run the first backup interactively:

```text
set -a
. /etc/convive/backup-job.conf
set +a
/srv/convive/current/infrastructure/backup/init-repository.sh
/srv/convive/current/infrastructure/backup/backup.sh
```

Install the reviewed service/timer units, verify their rendered schedule, then
enable the timer:

```text
install -o root -g root -m 0644 infrastructure/backup/systemd/convive-backup.service /etc/systemd/system/convive-backup.service
install -o root -g root -m 0644 infrastructure/backup/systemd/convive-backup.timer /etc/systemd/system/convive-backup.timer
systemctl daemon-reload
systemd-analyze calendar '*-*-* 02:30:00 Europe/Madrid'
systemctl enable --now convive-backup.timer
systemctl list-timers convive-backup.timer
```

## Isolated restoration exercise

Never test restoration against the live Compose project. Use a unique project
name beginning `convive-restore-`, a fresh random database password and the
dedicated restore Compose file. The API uses the deployed immutable image
digest; it is never built from or bind-mounted to source on the VPS. The
database uses temporary storage and publishes no port.

```text
export CONVIVE_RESTORE_DATABASE_PASSWORD='<fresh random value>'
export CONVIVE_RESTORE_APP_SECRET='<second fresh random value>'
export CONVIVE_RESTORE_COMPOSE_PROJECT="convive-restore-$(date +%Y%m%d)"
export CONVIVE_RESTORE_COMPOSE_FILES=/srv/convive/current/infrastructure/backup/compose.restore.yaml
docker compose -p "$CONVIVE_RESTORE_COMPOSE_PROJECT" -f "$CONVIVE_RESTORE_COMPOSE_FILES" up -d --wait database
/srv/convive/current/infrastructure/backup/restore-test.sh
docker compose -p "$CONVIVE_RESTORE_COMPOSE_PROJECT" -f "$CONVIVE_RESTORE_COMPOSE_FILES" down --volumes --remove-orphans
unset CONVIVE_RESTORE_DATABASE_PASSWORD
unset CONVIVE_RESTORE_APP_SECRET
```

The script selects the latest automated snapshot for the configured release
revision, restores it to the isolated database, truncates
`professional_sessions` and `report_access_grants`, proves both are empty,
validates the Doctrine mapping/schema, verifies that all migrations are current
and starts the Symfony health endpoint against the restored database. It records
only the snapshot prefix, report count, tested revision, timestamp and outcome.
A successful SQL import without these checks is not a successful exercise.

Before the immutable release images exist, the first R2 acceptance exercise may
run directly from the reviewed issue branch on the isolated VPS. This separates
the off-host storage proof from the later release-image publication owned by the
continuous-delivery workflow. It still uses the private EU R2 repository and
the complete isolated restore verification, but builds the API from the exact
checked-out revision and never exposes a service or reuses the deployment
project:

```text
set -a
. /etc/convive/backup-job.conf
set +a
/srv/convive/current/infrastructure/backup/exercise-off-host-recovery.sh
```

`exercise-off-host-recovery.sh` is an acceptance tool, not the scheduled
production job. It requires root, fictional demo data, the reviewed local
Compose override and a clean checkout whose revision is recorded in evidence.
The scheduled restore path remains `immutable-image` and refuses any image that
is not pinned by registry digest.

## Evidence, failures and remediation

Timestamped results are retained with mode `0600`. Atomic convenience copies of
the latest results are also written to:

- `/var/lib/convive-backup/evidence/latest-backup.json`;
- `/var/lib/convive-backup/evidence/latest-restore-test.json`.

Each record contains UTC time, exact Git revision, operation, outcome and a
non-secret detail. The systemd unit exits non-zero on dump, upload, integrity,
retention or evidence failure; issue #65 connects that failure state and stale
evidence to external alerts. Do not paste environment files or unredacted
command traces into tickets or logs.

On failure:

1. stop the release and record an incident/remediation note without credentials;
2. identify the failing stage from evidence and restricted systemd journal;
3. correct connectivity, quota, permissions, repository integrity or application
   compatibility as applicable;
4. rerun the complete backup and isolated restoration, not only the failed
   command;
5. retain the new successful evidence and link it from the release record.

If repository integrity cannot be established, create and verify a new
independent repository before treating recovery as available. Never repair or
forget snapshots merely to make a check green without preserving the last known
recoverable generation.

## Emergency recovery

During a genuine outage, first preserve the failed environment and select an
explicit known-good snapshot. Reconstruct the reviewed release and secrets on a
new isolated Compose project, restore the dump, invalidate sessions and
capabilities, validate schema/migrations and run the internal/public smoke tests
from the release runbook. Only then switch public traffic. Record the selected
snapshot, source revision, recovery time, lost-data window and final checks.

Do not automatically run Doctrine down-migrations. If the restored database and
selected application image are incompatible, follow the migration compatibility
classification in the release runbook and escalate rather than improvising on
the live database.
