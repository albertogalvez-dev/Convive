# Private attachment lifecycle

This runbook is for the fictional-data demonstration only. It schedules the
bounded private attachment lifecycle introduced by issue #37; it does not
authorise real evidence, select a scanner provider or make a file readable
while scanning is unavailable.

## Behaviour and boundary

Every five minutes, the timer runs two application commands against the
currently released immutable Compose configuration:

1. `app:attachments:process-pending --limit=50` starts/retries bounded scans.
   The default scanner fails closed, so an unavailable scanner never releases
   an attachment; pending work times out to an unavailable state after 30
   minutes.
2. `app:attachments:clean-expired --limit=50` removes expired fictional
   evidence and orphaned quarantine objects. Unavailable quarantine bytes are
   removed within 24 hours; available fictional bytes have a 30-day maximum.

The root-owned timer only invokes Docker Compose. The command itself runs as
the API container's unprivileged `www-data` user and sources the API
environment only inside that container. It follows the `current.env` release
pointer written by `infrastructure/release/reconcile.sh`, so it cannot target a
mutable image or an unrelated Compose project. A host lock prevents overlap.

## Installation after a reviewed deployment

Install the versioned script and units only after the production Compose
project has reconciled successfully. These commands are for the VPS operator;
they are not a local-development step.

```text
install -o root -g root -m 0644 /srv/convive/current/infrastructure/maintenance/systemd/convive-attachment-lifecycle.service /etc/systemd/system/convive-attachment-lifecycle.service
install -o root -g root -m 0644 /srv/convive/current/infrastructure/maintenance/systemd/convive-attachment-lifecycle.timer /etc/systemd/system/convive-attachment-lifecycle.timer
systemctl daemon-reload
systemctl enable --now convive-attachment-lifecycle.timer
systemctl list-timers convive-attachment-lifecycle.timer
```

The release checkout contains the versioned script at the path referenced by
the service unit. Do not copy that script, the release environment file or
Compose configuration to a shared home directory.

## Verification and response

After enabling, run one controlled fictional-data invocation and inspect only
the systemd status and redacted security logs:

```text
systemctl start convive-attachment-lifecycle.service
systemctl status convive-attachment-lifecycle.service --no-pager
journalctl -u convive-attachment-lifecycle.service --since '-15 minutes' --no-pager
```

Do not place attachment identifiers, filenames, report content, capabilities
or API secrets in an incident ticket, chat, timer output or screenshot. A
failed timer blocks releases until the issue is understood; do not bypass the
scanner or make private storage public as a workaround.
