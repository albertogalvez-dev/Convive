# Release acceptance records

One record per public release, so a maintainer can say exactly what was
deployed, what was verified, and what happened if it went wrong.

The [deployment runbook](../deployment-release-and-rollback.md) says what to do.
These records say what was actually done, which is a different thing and the one
that matters six months later when nobody remembers.

## Where a record lives

One file per release in this directory, named `YYYY-MM-DD-<release>.md`, copied
from [`TEMPLATE.md`](TEMPLATE.md) and committed to the repository. Committing it
means the record travels with the code it describes and is reviewable like any
other change.

## What never goes in a record

- **Secrets.** No token, password, tunnel credential, database password or
  access secret, in any form, including inside a pasted log line.
- **Visitor or reporter data.** No report content, access code, follow-up text,
  attachment name or anything derived from them.
- **Raw operational logs.** Reference where a log lives; do not paste it. A log
  excerpt is the most common way a secret or a personal detail reaches a
  document that was never meant to hold one.

Digests, revisions, timings and pass/fail outcomes are safe and are the point.

## A record is not a formality

Fill it **during** the release, not afterwards from memory. A record written
after the fact tends to describe the release that was intended rather than the
one that happened, and the difference between those two is usually where the
next incident starts.

An abandoned or rolled-back release still gets a record. A release that failed
and was reverted is more valuable to write down than one that went smoothly.
