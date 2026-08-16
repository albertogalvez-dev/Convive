# Controller, lawful basis and processing record

**Status: unapproved draft. No controller has been appointed. Nothing here is in
force, and no decision below has been made.**

**Prepared by:** the repository maintainer, as preparatory material only.
**Last updated:** 16 August 2026.
**Review trigger:** whenever the processing described changes, and before any
real-data pilot is evaluated.

This document frames the accountability decisions that would have to exist
before a real-data pilot could be considered. It does not make them. Read
[the directory README](README.md) first for how a gap is written and why none of
them is filled.

## The starting position

Convive processes fictional data only. The repository is not a controller, is
not a data protection officer, holds no record of processing activities and
gives no legal opinion.

A private individual cannot be the controller for minors' safeguarding data in a
school context. This is not left open as a gap: the option is discarded.

## Decisions required

### D-01 Controller identity

**[DECISION REQUIRED]** Who determines the purposes and means of the processing.

In a Spanish school context the realistic candidates are the educational centre
as an institution, its governing public administration, or both in a defined
relationship. Which applies is determined by facts this repository cannot
establish: whether the centre is publicly funded, `concertado` or private, who
decides that Convive is used, who decides what happens to a report once received,
and what the applicable education administration has already determined about
safeguarding records.

**Who decides:** the candidate controller's own legal representation, not the
project.

**Blocks:** every other decision in this document, and every real-data pathway in
the repository.

### D-02 Data protection officer and contact route

**[DECISION REQUIRED]** Whether a DPO is mandatory for the controller, who holds
the role, and the published contact route for exercising rights.

A public authority or body must designate one. Large-scale processing of special
categories also triggers the obligation. Whether a single centre's use of Convive
is "large scale" depends on the deployment the controller intends, which is
undecided.

**Depends on:** D-01.

### D-03 Authorised organisational participants

**[DECISION REQUIRED]** Which centres, roles and named people are authorised to
process real data, and who authorises a change to that list.

The repository already models the access boundary that would carry this decision:
least-privilege organisation membership plus exact case assignment, described in
[ADR-0018](../architecture/decisions/0018-require-case-assignments-for-case-content.md)
and mapped to Andalusian centre responsibilities in
[ADR-0023](../architecture/decisions/0023-map-andalusian-centre-responsibilities-to-least-privilege-grants.md).
The model exists; the list of real people authorised under it does not.

### D-04 Purposes

**[DECISION REQUIRED]** The specific purposes for which real data would be
processed, stated narrowly enough that a later use can be recognised as a new
purpose.

**Factual input, not a decision:** what the software does today is receive a
description of a situation, let a reporter follow it up without an account, let
authorised professionals triage and manage a case, record tasks with explicit
resolution, and keep an append-only audit trail. Each is a candidate purpose.
Stating them is not approving them.

### D-05 Categories of data and of data subjects

**Factual input.** The categories the software can hold are enumerated in the
[privacy engineering register](../security/privacy-engineering-register.md)
(entries P-01 to P-18) and in the
[data model](../architecture/data-model.dbml). They include free-text situation
descriptions, case-local names, attachments and their metadata, professional
identities, session and audit records, and backups.

**[DECISION REQUIRED]** Which of those the controller would actually permit in a
pilot, and which data subjects are in scope: reporting students, students who are
the subject of a report, families, staff, and any third party named in free text.

The register already records the honest engineering position that free text can
identify a person even when no identity is stated, and may contain
special-category data or information relating to alleged offences. That is a
fact about the data, not a lawful-basis conclusion.

### D-06 Lawful basis

**[DECISION REQUIRED]** The Article 6 basis for each purpose in D-04, and the
Article 9 condition wherever special-category data is involved.

**What the project may state:** for a public educational centre acting under a
safeguarding protocol, the bases usually examined are the performance of a task
carried out in the public interest or in the exercise of official authority, and
compliance with a legal obligation. Consent is problematic in this context
because of the power imbalance between a school and a minor, and because a
safeguarding duty does not stop applying if consent is withdrawn.

**What the project must not do:** choose one. The choice depends on D-01 and on
national and regional provisions the controller must identify. Writing a
plausible basis here would be indistinguishable from a decided one as soon as it
was copied elsewhere.

### D-07 Special-category and criminal-offence analysis

**[DECISION REQUIRED]** Whether the processing involves data revealing health,
sexual orientation, ethnic origin, religious belief or trade-union membership, or
data relating to criminal offences, and under which condition it would be
permitted.

**Factual input:** free-text descriptions of a coexistence incident routinely
reveal such data without anyone intending it. The software cannot prevent this,
and no product decision changes it. This is why the assessment in #188 cannot be
replaced by an engineering threat model.

### D-08 Data flows, recipients, processors and transfers

**[DECISION REQUIRED]** Which recipients receive real data, which providers act
as processors, where the data is located and what the transfer position is.

**Depends on:** the vendor governance framed by #189, which must not be treated
as decided by the fact that the fictional demonstration already uses a hosting
provider and object storage for backups.

### D-09 Data-subject rights and the safeguarding interaction

**[DECISION REQUIRED]** How access, rectification, erasure, restriction,
objection and portability requests are received, verified and answered, and how
each interacts with a live safeguarding process.

**The hard part, stated so it is not skipped:** an erasure or access request may
come from a person who is the subject of a report, and answering it may reveal
who reported. The repository deliberately holds no mechanism that would resolve
this. It is a controller decision with legal and safeguarding dimensions, framed
further by #187.

### D-10 Record of processing activities

**[DECISION REQUIRED]** The approved record itself, and who maintains it.

A template is offered below so that the shape is not argued about later. It is
empty on purpose.

| Field | Value |
|---|---|
| Controller identity and contact | **[DECISION REQUIRED]** — see D-01 |
| Data protection officer | **[DECISION REQUIRED]** — see D-02 |
| Purpose | **[DECISION REQUIRED]** — see D-04 |
| Categories of data subjects | **[DECISION REQUIRED]** — see D-05 |
| Categories of personal data | **[DECISION REQUIRED]** — see D-05 |
| Special categories and legal basis | **[DECISION REQUIRED]** — see D-07 |
| Recipients | **[DECISION REQUIRED]** — see D-08 |
| Third-country transfers and safeguards | **[DECISION REQUIRED]** — see D-08 |
| Retention period per category | **[DECISION REQUIRED]** — framed by #187 |
| Technical and organisational measures | Reference the [privacy engineering register](../security/privacy-engineering-register.md) and the [threat model](../security/threat-model.md), then state which the controller adopts |
| Review owner and cadence | **[DECISION REQUIRED]** |

### D-11 Whether a pilot may proceed

**[DECISION REQUIRED]** An explicit statement, by the competent controller, of
whether a real-data pilot may proceed and under what constraints.

Absent that statement, the answer is no. Silence is not authorisation, and this
document existing is not authorisation either.

## What stays true until D-11 is answered

- Repository configuration remains fictional-only.
- No real email delivery, no real retention automation, no provider activation
  and no centre onboarding.
- No document in this directory may be cited as evidence that a governance
  obligation has been met.
