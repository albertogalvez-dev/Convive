# Incident response and safeguarding escalation playbooks

**Status: unapproved drafts. No playbook here has been exercised, no role has
been assigned to a named person, and none may be relied on during a real
incident.**

**Prepared by:** the repository maintainer, as preparatory material only.
**Last updated:** 16 August 2026.
**Review trigger:** whenever the operational topology, the accountable roles or
the safeguarding context change, and before any real-data pilot is evaluated.

Read [the directory README](README.md) first.

## The gap these fill

The repository has a working
[incident response and observability runbook](../operations/incident-response.md),
a [backup and recovery runbook](../operations/backup-and-recovery.md) and a
[maintenance runbook](../operations/maintenance-and-support.md). They cover a
machine failing: health checks, container state, disk capacity, stale restore
evidence, restoring from an encrypted generation.

They do not cover a person being harmed. A real-data service needs playbooks for
situations where the software is working perfectly and something has gone wrong
anyway: a member of staff reading a case they should not have, a report
describing a child in immediate danger, an account taken over, a report that
turns out to be malicious, or a centre discovering that the wrong family was
contacted.

Those are not technical failures. They need a named human with authority to act,
which is why every playbook below has an owner gap rather than an automated
step.

## Playbooks required

### P-01 Security incident

**[DECISION REQUIRED]** Detection sources, containment authority, evidence
preservation, the notification decision and its statutory clock, and the
post-incident review.

**Factual input:** the existing runbook already preserves alert and release
evidence and forbids pasting report content, credentials or environment files
into an issue, chat or alert destination. That instruction is the right instinct
and must survive into any real-data playbook, because the most common way an
incident becomes a second incident is someone pasting the evidence somewhere
convenient.

**[DECISION REQUIRED]** Who decides whether a personal-data breach is
notifiable, within the deadline that starts on becoming aware rather than on
finishing the investigation.

### P-02 Suspected misuse of access

**[DECISION REQUIRED]** What happens when a professional is suspected of reading
or acting on a case they had no business in.

**Factual input:** the append-only case audit trail records who did what to which
case, and access requires least-privilege membership plus exact case assignment.
The evidence to investigate this exists. Who may read that evidence, on what
suspicion, and with what employment-law care, does not.

### P-03 Urgent safeguarding situation

**[DECISION REQUIRED]** What a professional does when a report describes a child
in immediate danger, and where Convive's responsibility ends.

**The boundary that must be explicit:** Convive is not an emergency channel and
does not notify anybody. The public wording says so and the product does not
contradict it. A playbook that quietly implies someone is watching the queue
out of hours would be worse than no playbook, because a person would rely on it.

**[DECISION REQUIRED]** The out-of-hours position, stated honestly, including
the case where nobody reads a report until the next working day.

### P-04 Account compromise

**[DECISION REQUIRED]** Detection, immediate revocation, and what the affected
professional and the centre are told.

**Factual input:** revocation mechanics exist — changing an email or suspending
an account raises the security revision and ends every session, and Direction can
correct an address or issue a one-time reset. The mechanism is not the gap; the
authority to use it under suspicion is.

### P-05 Service unavailable

**[DECISION REQUIRED]** What a centre is told when the service is down, and what
the fallback is for receiving a report meanwhile.

The honest fallback for a school is the process it had before Convive existed.
Saying so in the playbook is more useful than a recovery-time promise nobody can
keep.

### P-06 Malicious or mistaken report

**[DECISION REQUIRED]** What happens to a report that is false, and what is owed
to the person it named.

**The reason this cannot be left to judgement in the moment:** an
account-free channel accepts what it is given. The harm of a false report lands
on a real person, and the record of it persists under whatever retention R-01
sets.

### P-07 Wrong recipient

**[DECISION REQUIRED]** What happens when the wrong family or the wrong member
of staff is contacted about a case.

## Cross-cutting decisions

- **[DECISION REQUIRED]** The escalation contact list, with named people rather
  than roles-in-the-abstract, and the authority each holds.
- **[DECISION REQUIRED]** How the emergency wording in the product, the public
  information and these playbooks are kept consistent, so a person is never told
  three different things about who is watching.
- **[DECISION REQUIRED]** A tabletop exercise cadence, and the requirement that
  the first exercise records its gaps and follow-up actions before a pilot
  proceeds. An unexercised playbook is a document, not a capability.
- **[DECISION REQUIRED]** The handoff point between the technical recovery
  runbooks and these playbooks, so neither assumes the other is handling it.

## What stays true until these are answered

- The existing runbooks keep their status: operational procedures for a
  fictional demonstration, useful as input and not promoted by being cited here.
- No playbook here is relied on during any incident.
- No automated safeguarding decision, notification or escalation is built. Every
  playbook above resolves to a named human, and the software does not act on its
  own — which is what the product tells its users, and must remain true.
