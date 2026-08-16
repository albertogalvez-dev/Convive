# Real-data governance drafts

**Status: unapproved drafts. None of this is in force.**

Convive processes fictional data only. No controller has been appointed for real
data, and none is being appointed for this delivery. These documents exist so
that a competent controller who later considered a real-data pilot would find
the questions already framed, the technical facts already gathered and the
decisions already listed — not already made.

## How to read a document in this directory

Every document here follows three rules.

**It states its status in the first line.** A draft that looks approved is worse
than no draft, because someone downstream will rely on it.

**It leaves the controller's decisions as marked gaps.** A gap is written
`**[DECISION REQUIRED]**` and says who must decide and which facts settle it. It
is never filled with a plausible-looking assumption. An invented lawful basis or
an invented retention period would be indistinguishable from a real one once it
had been copied into a second document.

**It separates fact from proposal.** Anything describing what Convive actually
does today is a fact, traceable to code, an architecture decision record or the
[privacy engineering register](../security/privacy-engineering-register.md).
Anything describing what a pilot might do is a proposal awaiting a decision.

## What these drafts are not

They are not a record of processing activities, a completed data protection
impact assessment, an approved retention policy, a processor agreement or legal
advice. Producing the template for one of those is not producing the thing
itself, and no document here may be presented as evidence that the corresponding
obligation has been met.

## The gate

No real-data pathway may be implemented — no real email delivery, no real
retention automation, no provider activation, no centre onboarding — until a
competent controller exists and has recorded the decisions these drafts frame.
Repository configuration stays fictional-only until then, and that boundary is
not a matter of judgement for whoever reads this next.

A private individual cannot be the controller for minors' safeguarding data in a
school context. That option is explicitly discarded and is not left open as a
gap for someone to close conveniently.

## Documents

| Document | Frames the decisions for |
|---|---|
| [Controller, lawful basis and processing record](controller-and-processing-decisions.md) | Who would be accountable, on what basis, over which processing |
| [Retention, deletion, legal hold and rights](retention-deletion-and-rights.md) | How long real data would be kept, how it would be removed, and how a rights request would be answered |
| [Impact and safeguarding assessment](dpia-and-safeguarding-assessment.md) | What the system does to the people in it when it works exactly as designed |
| [Processor, vendor and access governance](vendor-and-access-governance.md) | Which providers and administrative access paths would touch real data, and on whose authority |
| [Real-email delivery and notification operations](email-delivery-operations.md) | Whether a pilot needs email at all, and what would have to be true before a single message left the system |
| [Incident response and safeguarding escalation](incident-and-safeguarding-playbooks.md) | What happens when the software is working perfectly and someone is harmed anyway |
