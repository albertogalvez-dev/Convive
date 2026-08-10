# Maintenance tooling boundary

The durable support plan is maintained in
[`docs/operations/maintenance-and-support.md`](../../docs/operations/maintenance-and-support.md).

This directory is reserved for versioned, reviewable maintenance helpers. It
must not contain populated provider configuration, credentials, renewal
tokens or runtime evidence. Host-specific timers and evidence remain outside
Git under the paths documented by the backup, release and observability
runbooks.
