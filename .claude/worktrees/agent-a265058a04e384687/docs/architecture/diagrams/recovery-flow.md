# Implemented encrypted recovery flow

This diagram describes the Restic/R2 backup and isolated restoration path used
by release and maintenance gates. It contains no populated credentials or
runtime values.

```mermaid
flowchart LR
    timer["Backup timer"] --> dump["pg_dump custom format"]
    dump --> restic["Restic encrypts and authenticates"]
    restic --> r2[("Private EU R2 bucket")]
    r2 --> select["Select latest automated snapshot"]
    select --> isolated["Fresh isolated PostgreSQL"]
    isolated --> invalidate["Truncate professional sessions\nand anonymous capabilities"]
    invalidate --> validate["Schema, migrations and API health"]
    validate --> evidence["Root-only redacted evidence"]
    evidence --> gate["Release/maintenance gate"]
    validate -. failure .-> alert["Stop release and publish alert"]
    alert -.-> gate
```

The live Compose project is never used as the restore target. The isolated
exercise verifies the deployed revision, report count, schema/migrations and
health endpoint, and proves that sessions and capabilities are not revived.
Retention, failure handling and the exact command boundary are maintained in
the [backup and recovery runbook](../../operations/backup-and-recovery.md).
