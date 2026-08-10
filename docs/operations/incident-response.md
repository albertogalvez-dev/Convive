# Convive incident response and observability

This runbook applies only to the fictional-data demonstration. It provides a
small, inspectable operational loop without sending report content, access
secrets or complete request bodies to a third-party analytics service.

## Signals

`infrastructure/observability/check.sh` runs every minute from the systemd
timer and records a secret-free JSON result under
`/var/lib/convive-observability`:

- public API health (`/api/v1/health`);
- Convive Compose service health and restart state;
- root filesystem capacity (alert at 85% used);
- freshness of the latest encrypted restore-test evidence (default two days).

The check also records the reviewed release identifier. It never records a
request body, access secret, session identifier, report reference, full URL or
database value. Application security events continue to use the dedicated
structured Monolog channel and the production formatter writes JSON to stderr.

## Alerts and ownership

A failed check writes `latest-alert.json` with the failed signal, timestamp,
release identifier, fixed remediation detail and this runbook path. The
`OnFailure` systemd unit sends the same redacted event to journald. An optional
`CONVIVE_ALERT_WEBHOOK` may be configured outside GitHub/Git; its endpoint and
credentials are runtime configuration, not product data.

The maintainer owns first response for this demonstration. A response starts
by preserving the alert and release evidence, then checking the private
Compose project and VPS capacity. Do not paste report content, credentials or
environment files into an issue, chat or alert destination.

## Triage and containment

1. Confirm whether the failure is API health, container health, disk capacity
   or stale recovery evidence.
2. Compare the alert's release identifier with the root-owned release record;
   never infer a deployment from a mutable tag.
3. If the public path is unhealthy after a release, use the release rollback
   procedure. Do not publish a Convive port or modify ProjectX Caddy.
4. If disk or memory is exhausted, keep the demonstration unavailable, stop
   only Convive-owned services if required, preserve evidence and remove no
   host-wide Docker resources.
5. If backup evidence is stale, stop release activity and run the #66 backup/
   restore procedure before declaring recovery capability again.

## Controlled failure exercise

Run the versioned fixture exercise from the repository root:

```bash
bash infrastructure/observability/exercise-failure.sh
```

It forces an API-down signal, verifies a failed redacted alert, then runs the
healthy path and verifies that the alert clears. It never stops a real service,
uses fictional release metadata and contains no external webhook or report
data. A real VPS exercise must be a separately approved maintenance action.

## Retention and access

Keep only the latest check and alert plus the active release evidence on the
demo host. Root-only permissions (`0700` directory, `0600` files) are required;
the maintainer reviews and removes operational evidence after 30 days. This is
an engineering default for the fictional demonstration, not an approved
retention period for real safeguarding data. Real deployment requires a
controller/DPO-approved purpose, access list, retention schedule and deletion/
legal-hold procedure.
