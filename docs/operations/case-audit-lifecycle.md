# Fictional case-audit lifecycle

This runbook applies only to Convive's explicit fictional-data demonstration.
It implements the bounded cleanup rule from
[ADR-0020](../architecture/decisions/0020-protect-case-audit-events-with-minimised-append-only-records.md);
it does not define a safeguarding-data retention policy or authorise a
real-data audit deletion.

## Boundary

The `case_audit_events` table is append-only in PostgreSQL. Updates always
fail. Deletes fail unless the application opens the controlled, transaction-
local retention capability, and the command below refuses to run unless the
runtime explicitly sets `APP_DEMO_MODE=1`.

For the fictional demonstration, it removes at most 200 events per invocation
whose recorded time is more than 30 days old. The command does not inspect or
export report text, task text, reasons, people, evidence, secrets, session
identifiers or IP addresses.

## Deliberate invocation

Run only against a reviewed fictional demonstration release and only after
checking that its environment deliberately enables fictional demo mode:

```text
php bin/console app:case-audit:clean-fictional --env=prod --no-debug --no-interaction --limit=50
```

Do not add a host timer, bypass the command with SQL, set the PostgreSQL purge
setting manually, or run this against a real-data environment. A production
retention period, legal hold, deletion authority, data-subject rights process,
incident-investigation access and restoration reconciliation require a
controller/DPO decision and separate implementation.

## Evidence and response

Record only the date, reviewed release revision, bounded count and command exit
status in the private operator record. Do not include audit rows, actor names,
case identifiers, CSV files, report information, environment files or
credentials in tickets, chat, CI logs or screenshots.

If the command fails, leave audit events intact, investigate through redacted
application and database health evidence, and do not attempt direct deletion.
