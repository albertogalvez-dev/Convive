# Data protection impact assessment and safeguarding risk assessment

**Status: unapproved draft. This is not a DPIA. No controller has been
appointed, no assessment has been carried out, and nothing here may be cited as
evidence that one has.**

**Prepared by:** the repository maintainer, as preparatory material only.
**Last updated:** 16 August 2026.
**Review trigger:** whenever the processing or the deployed vendors change, and
before any real-data pilot is evaluated.

Read [the directory README](README.md) first. This document depends on
[the controller decisions](controller-and-processing-decisions.md) and on
[retention and rights](retention-deletion-and-rights.md).

## Why an engineering threat model is not enough

The repository already holds a
[threat model](../security/threat-model.md), an
[anti-abuse threat model](../security/anti-abuse-threat-model.md), an
[attachment threat model](../security/attachment-threat-model.md) and a
[privacy engineering register](../security/privacy-engineering-register.md).
They are good evidence and they are not a substitute.

A threat model asks what an attacker could do to the system. An impact
assessment asks what the system does to the people in it when it works exactly
as designed. A perfectly secure Convive can still harm a child — by making a
report reachable by the wrong member of staff, by keeping a description longer
than anyone needed, by making a reporter believe they are anonymous when the
text names them, or by causing a family to be told something before anyone
verified it.

Those risks survive every technical control in the repository, which is why this
assessment is a controller obligation and not an engineering deliverable.

## Necessity and proportionality

**[DECISION REQUIRED]** Whether processing this data by this means is necessary
for the purposes in D-04, and whether a less intrusive means would achieve them.

**The question a controller must actually answer:** what a centre does today
without Convive, and what changes. If the answer is that incidents are currently
recorded on paper in a locked cabinet, the assessment has to say why a networked
system is proportionate rather than assume it.

## Risk areas that must be assessed

Each is stated as a risk to a person, not as a system weakness. None is assessed
here: the severity, likelihood and residual position are controller decisions.

### A-01 Anonymous reporting that is not anonymous

Convive requires no account and states no identity, and the reporter may
reasonably infer more protection than exists. Free text can identify its author
by what it describes. The public wording already refuses to promise absolute
anonymity; the assessment must decide whether the residual risk to a reporting
child is acceptable and what mitigation applies.

### A-02 Free text containing special categories

A coexistence description routinely reveals health, disability, sexual
orientation, ethnic origin or religious belief without anyone intending it, and
may describe conduct that is a criminal offence. No product decision prevents
this. See D-07.

### A-03 Attachment content and metadata

Evidence is often a photograph or a screenshot. Location data and device
identifiers ride along with it, and the subject of the image has usually not
been asked. The
[attachment threat model](../security/attachment-threat-model.md) covers the
technical lifecycle; the assessment must cover the person in the photograph.

### A-04 Role access and the reach of a case

Access is least-privilege membership plus exact case assignment, which is a
strong boundary. The residual question is human: who in a real centre gets
assigned, how quickly, and whether a small staff makes the boundary
theoretical.

### A-05 The audit trail as a record about people

The append-only trail records who did what to which case. That is
accountability, and it is also a record of staff behaviour. Its retention and
who may read it are decisions with an employment dimension.

### A-06 Backups as an unerasable copy

A backup holds everything the live system held, including something deleted
afterwards. See R-05.

### A-07 Email, if it is ever enabled

An email is a copy of a fact leaving the controlled system, arriving somewhere
the controller does not govern, possibly on a shared family device. The existing
position that a notice may say only that an update is available exists precisely
because of this risk. See #190.

### A-08 Public ingress

A publicly reachable reporting endpoint invites abuse, false reports and
enumeration attempts. The anti-abuse model covers the traffic; the assessment
must cover what a malicious or mistaken report does to the person it names.

### A-09 Secondary victimisation

Making a child repeat their account, or exposing them to a process that treats
them as evidence, is a harm the safeguarding framework explicitly names. It is
not a data-protection risk in the narrow sense, which is why a safeguarding
assessment sits alongside the DPIA rather than inside it.

## Decisions required

- **[DECISION REQUIRED]** Who leads or approves the assessment. It must be the
  competent controller; the repository cannot self-certify.
- **[DECISION REQUIRED]** The severity and likelihood assigned to each risk
  above, and the mitigations adopted with a named owner for each.
- **[DECISION REQUIRED]** Which residual risks are accepted, by whom, and which
  are unacceptable and therefore block deployment until resolved.
- **[DECISION REQUIRED]** Whether prior consultation with the supervisory
  authority is required, which depends on the residual risk position.
- **[DECISION REQUIRED]** The review trigger and cadence for the assessment
  itself.

## How findings come back

Any finding that requires a change to the software becomes a traceable issue in
this repository, with its acceptance criteria stated the way every other issue
here states them. A finding recorded only inside an assessment document is a
finding nobody will implement.

## What stays true until this is done

- No real-data pilot is authorised, whatever the state of the technical gates.
- No claim of compliance, and no presentation of the fictional demonstration's
  evidence as a real-data assessment.
- The threat models keep their own status: living engineering baselines for a
  fictional demonstration, useful as input here and not promoted by being cited.
