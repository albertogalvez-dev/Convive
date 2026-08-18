# Retention, deletion, legal hold and rights

**Status: unapproved draft. No controller has been appointed. No period below is
approved for real data, and nothing here changes the fictional lifecycle that is
currently in force.**

**Prepared by:** the repository maintainer, as preparatory material only.
**Last updated:** 16 August 2026.
**Review trigger:** whenever a lifecycle in the software changes, and before any
real-data pilot is evaluated.

Read [the directory README](README.md) first for how a gap is written, and
[the controller decisions](controller-and-processing-decisions.md) for the
accountability questions this one depends on.

## Why the existing periods are not a policy

The software already deletes things on a schedule. Those schedules exist to keep
a demonstration tidy and to bound a fictional dataset. They were not chosen by a
controller, they were not chosen against a safeguarding obligation, and they must
not be inherited as if they had been.

The most important consequence: a short fictional period can be **too short** for
real data. A 30-day audit cleanup is convenient for a demo and would be
indefensible if a safeguarding process needed to show what happened four months
ago.

## What is in force today, as fact

Every row describes the fictional demonstration as implemented. None is a
proposal for real data.

| Category | Current fictional behaviour | Where it lives |
|---|---|---|
| Case audit events | 30 days, then purged in batches | `CaseAuditRetentionPolicy::FICTIONAL_RETENTION` (`P30D`), deliberately not configurable through that code path |
| Attachments, unavailable or quarantined | Removed within 24 hours | [attachment lifecycle](../operations/attachment-lifecycle.md) |
| Attachments, available fictional bytes | 30-day maximum | [attachment lifecycle](../operations/attachment-lifecycle.md) |
| Reporter email contacts, unverified | 24 hours | privacy register P-13 |
| Reporter email delivery evidence | 30 days | privacy register P-13 |
| Security and operational logs | 30-day fictional default | privacy register P-07, P-16 |
| Public sandbox visitor content | Irreversibly deleted within 24 hours | public demonstration decision recorded on #158 |
| Backups | 14 daily, 8 weekly, 12 monthly restic snapshots; recovery point objective of 24 hours | [backup and recovery](../operations/backup-and-recovery.md), which states in its own text that it does not define retention for real personal data |
| Maintained fictional cases and profiles | Persist as controlled demonstration material | public demonstration decision recorded on #158 |

## Decisions required

### R-01 Retention period per category

**[DECISION REQUIRED]** A period, with its justification, for each category a
pilot would hold: reports, cases, evidence and attachments, audit events,
sessions, email contacts, backups and exports.

**What settles it:** the retention the applicable safeguarding protocol and
education administration already require for an incident record, which may be
longer than anything in the table above; the purpose limitation from D-04; and
any statutory minimum that applies to the controller as a public body.

**Trap to avoid:** setting one period for everything. Audit events, free-text
reports and backups fail differently — an audit trail deleted too early destroys
accountability, a report kept too long is an unnecessary risk, and a backup is
neither, because it cannot be selectively edited.

### R-02 Deletion, anonymisation and the difference between them

**[DECISION REQUIRED]** Whether each category is deleted or anonymised at the end
of its period, and what "anonymised" means concretely for free text.

**The hard part, stated so it is not skipped:** a coexistence description cannot
generally be anonymised by removing names. It describes a situation, a place and
a time, and inside a school those identify people to anyone who was there. A
decision that says "anonymise the description" without defining the technique is
not a decision.

### R-03 Correction

**[DECISION REQUIRED]** Who may correct a record, what evidence a correction
requires, and how a correction is reflected in an append-only audit trail.

**Factual input:** case audit events are append-only by design
([ADR-0020](../architecture/decisions/0020-protect-case-audit-events-with-minimised-append-only-records.md)).
A correction therefore adds a record; it does not rewrite one. That is a property
to preserve, not a limitation to work around.

### R-04 Legal hold

**[DECISION REQUIRED]** Who may place and lift a hold, on what grounds, how it is
evidenced, and how it suspends every automated deletion path.

**Not implemented, and deliberately so.** No hold mechanism exists in the
software. Building one before the controller defines who may invoke it would
create an override with no accountable owner.

### R-05 Backups and the restoration reconciliation problem

**[DECISION REQUIRED]** How a deletion or erasure request is honoured with
respect to backups, and what happens when a restore reintroduces data that was
deleted after the snapshot.

**Factual input:** the encrypted database and object generations are kept and
removed as a pair, and restoration is exercised in isolation. The repository
already handles one narrow case of this: a restore deletes every reporter
contact and notification job before the restored application is allowed to
start, so a historical backup cannot issue stale mail, revive a verification
token or resurrect a contact that was removed after the snapshot
([reporter email notifications](../operations/reporter-email-notifications.md)).
That shows the shape of the problem and solves exactly one instance of it.

**What settles it:** the controller's position on whether backup retention is
disclosed as a separate retention period, which is the usual honest answer, and
what reconciliation is performed after any real restore.

### R-06 Data-subject rights procedure

**[DECISION REQUIRED]** How access, rectification, erasure, restriction,
objection and portability requests are received, identity-verified, logged and
answered within the statutory deadline.

**Depends on:** D-09, which frames the same problem from the accountability side.
The unresolved core is repeated here because it is a retention decision as much
as a rights one: answering an access request from the subject of a report may
reveal who reported, and protecting a reporter is the reason the channel exists.

### R-07 Interaction between protocol obligations, investigation and deletion

**[DECISION REQUIRED]** What happens when a deletion request arrives during a
live safeguarding process or an open incident investigation.

Stating the order of precedence in advance is the point. Deciding it while a
request is on the table, under time pressure, is how a controller ends up
justifying whatever was convenient.

### R-08 Ownership of the policy itself

**[DECISION REQUIRED]** Who owns the approved policy, who reviews it, on what
cadence, and what change triggers an early review.

## What stays true until these are answered

- The fictional purge contract stays exactly as it is. It is not widened,
  narrowed or reinterpreted as a real-data policy, and no code path is made
  configurable in anticipation.
- No legal-hold mechanism is built.
- No real-data deletion is automated from an unreviewed assumption.
- Any later implementation carries migration, rollback and test evidence, as any
  schema-affecting change in this repository does.
