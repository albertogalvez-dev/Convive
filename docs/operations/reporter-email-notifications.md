# Reporter email notifications

This runbook covers the fictional-data implementation of
[ADR-0015](../architecture/decisions/0015-use-verified-email-only-for-generic-reporter-notifications.md).
It does not approve a production provider, real sender identity or real contact
data.

## Development boundary

The base and production configurations keep `REPORTER_EMAIL_ENABLED=0` and use
the null mail transport. The development Compose override alone enables the
feature, starts the pinned Mailpit container and runs the database-backed
delivery worker. Mailpit binds its interface to
`http://127.0.0.1:8025`; SMTP remains inside the Compose backend network.

Start the normal development stack and apply the latest migrations. A reporter
with an active anonymous capability can opt in from `/seguimiento`. The
verification message appears in Mailpit and its link returns to the local
application without putting the token in an HTTP URL or server log: the browser
reads it from the fragment, removes the fragment and submits it in a JSON body.

Use reserved `.example` addresses and fictional reports only. Do not configure
Mailpit to relay internet email.

## Delivery and retention

`app:reporter-notifications:deliver --watch` keeps one Symfony process resident
and claims at most the requested number of jobs per poll with `FOR UPDATE SKIP
LOCKED`; it does not repeatedly boot the application. Delivery has at most
three attempts, scheduled after 1, 5 and 15 minutes. A failed send does not
affect the professional report response transaction. Logs contain only the
internal delivery UUID, generic kind, attempt and exception class; they never
contain an address or message.

Every worker pass also removes pending contacts older than 24 hours and
completed delivery evidence older than 30 days. Removing the contact through
the reporter API cascades queued and retrying work immediately. Restores delete
all reporter contacts and notification jobs before the restored application is
allowed to start, so a historical backup cannot issue stale mail, revive a
verification token or resurrect a contact removed after that backup.

## Production gate

Do not enable the feature outside isolated fictional development until all of
the following are approved and verified:

- controller/DPO purpose, rights and retention decisions;
- production provider and processor/subprocessor terms;
- sender domain, SPF, DKIM, DMARC, bounce and complaint handling;
- secret injection, least-privilege credentials and redacted monitoring;
- restore/removal reconciliation for the selected operational topology;
- a test proving that real provider metadata and payloads remain generic.
