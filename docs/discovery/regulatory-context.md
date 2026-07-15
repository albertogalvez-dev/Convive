# Regulatory Context

## Purpose

This document records the regulatory and institutional context considered during
the design of Convive.

Its purpose is to:

- identify the official sources that influence the product;
- translate regulatory requirements into traceable product decisions;
- distinguish binding rules from non-binding guidance;
- prevent the application from presenting configurable targets as legal advice;
- provide evidence for the project's technical report and future evolution.

Convive is a technical support tool. It does not replace professional judgement,
school safeguarding protocols, educational authorities or legal advice.

## Initial territorial scope

The initial product is designed for secondary schools in Andalusia, using
Granada as its first operational context.

This territorial scope affects:

- the applicable school bullying protocol;
- the terminology used by educational staff;
- the authorities and services involved;
- the tasks and communications recorded in a case;
- the configuration of deadlines and procedural stages.

Support for other Spanish autonomous communities is considered a future
extension, not part of the initial release.

## Source hierarchy

Product decisions must be based on sources in the following order:

1. Spanish and European legislation.
2. Official regulations published by the Junta de Andalucía.
3. Official instructions and protocols issued by educational authorities.
4. Official institutional guidance.
5. Secondary explanatory material.

When sources conflict, the higher-ranking and territorially applicable source
takes precedence.

Every configurable procedural target should record:

- its source;
- its territorial scope;
- whether it is legally binding or guidance;
- its effective or publication date;
- its review date.

## Spanish legal framework

### Organic Law 8/2021

Organic Law 8/2021 of 4 June on the comprehensive protection of children and
adolescents against violence establishes the national protection framework.

Relevant principles include:

- the best interests of the child;
- prevention and early detection of violence;
- accessible and secure reporting mechanisms;
- immediate communication of suspected violence;
- protection of people who report possible violence;
- confidentiality and restricted information access;
- coordination between the professionals and authorities involved;
- protocols for bullying, cyberbullying and other forms of violence;
- the presence of a welfare and protection coordinator in educational centres;
- avoidance of secondary victimisation.

The law gives educational environments a specific responsibility for
prevention, detection and response.

Official source:

https://www.boe.es/buscar/act.php?id=BOE-A-2021-9347

### Product consequences

Convive should:

- provide an accessible reporting channel;
- permit reports without forcing the reporter to identify themselves;
- protect the reporter's identity and contact information;
- restrict sensitive case data according to professional roles;
- maintain a traceable record of relevant decisions and actions;
- avoid requiring minors to repeat their account unnecessarily;
- allow authorised staff to coordinate actions without exposing the case to
  unrelated users;
- clearly communicate that submitting a report does not itself determine that
  bullying has occurred.

## Andalusia regulatory framework

### Order of 20 June 2011

The Order of 20 June 2011 regulates school coexistence and establishes the
protocol applicable to publicly funded educational centres in Andalusia.

Annex I defines school bullying as repeated psychological, verbal or physical
mistreatment of a student by one or more peers over time.

The protocol highlights the following characteristics:

- intentionality;
- repetition;
- imbalance of power;
- helplessness and personalisation;
- a possible group component;
- the presence of passive observers.

It includes cyberbullying among the possible manifestations of bullying.

The protocol establishes a sequence that includes:

1. identification and communication;
2. immediate action and an initial coordination meeting;
3. urgent protection measures when required;
4. communication with families or legal guardians;
5. controlled communication with relevant professionals;
6. collection of information;
7. application of educational or disciplinary measures where appropriate;
8. communication with the Education Inspectorate;
9. assessment of the collected information;
10. definition of measures and actions;
11. communication of those measures to the families involved;
12. follow-up by the Education Inspectorate.

The initial meeting and the actions agreed must be recorded in writing.

Official source:

https://www.juntadeandalucia.es/boja/2011/132/1

### Product consequences

Convive should:

- separate an incoming report from a formally managed case;
- allow authorised professionals to assess the report before activating the
  bullying protocol;
- support urgent protective measures without waiting for the full assessment;
- record meetings, decisions, tasks, evidence and follow-up actions;
- preserve the confidentiality of affected students and families;
- maintain an audit trail of significant case activity;
- represent the Andalusian workflow without automating the professional
  determination of whether bullying exists;
- allow staff to record communications with families, the Education
  Inspectorate and other authorised services.

### Cyberbullying instructions of 11 January 2017

The Andalusian instructions of 11 January 2017 provide specific guidance for
cyberbullying situations.

Cyberbullying may:

- occur outside the physical school;
- continue beyond school hours;
- spread rapidly;
- involve large or initially unknown audiences;
- preserve harmful content over time;
- require the preservation of digital evidence;
- require coordination with families, inspection services, social services,
  health services or law enforcement depending on the situation.

Official source:

https://www.juntadeandalucia.es/educacion/portales/documents/270701/999788/INSTRUCCIONES%20PROTOCOLO%20DE%20CIBERACOSO.pdf/0b74c1b7-9552-9cf0-9f13-b07bf527d943?version=1.0

### Product consequences

Convive should not create a separate type of case management system for
cyberbullying.

A case may instead be classified as:

- in-person;
- digital;
- mixed;
- undetermined.

The system should support digital evidence while applying strict access,
retention and security controls.

## 2026 Ministry reference framework

On 15 April 2026, the Spanish Ministry of Education presented a common
reference framework for bullying and cyberbullying.

The published framework describes eight stages:

1. detection and communication;
2. protocol initiation and precautionary measures;
3. collection of information and evidence;
4. analysis and decision-making;
5. notifications;
6. intervention plan;
7. monitoring and evaluation;
8. closure.

The Ministry also described the following targets:

- notifications within a maximum of 24 hours;
- an intervention plan within a maximum of 10 days;
- monitoring for at least six months.

Official source:

https://www.educacionfpydeportes.gob.es/prensa/actualidad/2026/04/20260415-protocoloacoso.html

### Status inside Convive

As of 15 July 2026, this source is treated as a national reference framework,
not as a replacement for the applicable Andalusian protocol.

Its time targets may be represented as configurable recommendations, but the
interface must not label them as binding Andalusian legal deadlines unless a
competent official source establishes that status.

## Institutional context in Granada

The first operational context is the province of Granada.

Depending on the case assessment, relevant external actors may include:

- the Provincial Education Inspectorate;
- the school welfare and protection coordinator;
- educational guidance professionals;
- social services;
- healthcare services;
- child protection services;
- law enforcement authorities;
- judicial authorities.

Convive records and coordinates work performed by authorised professionals. It
does not decide when an external authority must intervene.

## Séneca and PASEN/iPasen

Séneca is the administrative and educational management system used in
Andalusia.

PASEN is the communication service for families and students, while iPasen is
its mobile application.

The initial version of Convive will not claim a real integration with Séneca,
PASEN or iPasen.

Instead, authorised staff will be able to:

- create a pending communication task;
- record the intended recipient and channel;
- mark the communication as completed;
- record when it was completed and by whom;
- add a non-sensitive reference or observation.

A future official integration would require technical access, institutional
authorisation, a lawful basis, security analysis and an agreement with the
relevant administration.

## Data protection implications

Convive processes information concerning minors and potentially highly
sensitive situations.

The product must therefore follow:

- data minimisation;
- purpose limitation;
- restricted access;
- confidentiality by default;
- encryption in transit and at rest where appropriate;
- traceability of access and significant actions;
- defined retention and deletion rules;
- separation of optional reporter contact data from the report;
- secure handling of uploaded evidence;
- prohibition of real personal data in the public demonstration environment.

Convive should not import or duplicate the complete student database when only
case-specific information is required.

Before a real deployment in a school, the responsible organisation would need
to establish, among other matters:

- the data controller and any data processor;
- the applicable lawful basis;
- processor agreements where required;
- the involvement of the relevant Data Protection Officer;
- the retention policy;
- incident response procedures;
- whether a Data Protection Impact Assessment is required.

These matters are deployment requirements and cannot be resolved solely by the
software repository.

## Product interpretation rules

Convive follows these rules:

- A report is not proof of bullying.
- A report is not automatically an activated protocol.
- Only authorised professionals can assess a report and activate a case.
- The software must not diagnose bullying.
- The software must not infer that a person is lying or contradicting
  themselves.
- The software must not make safeguarding decisions through automated scoring.
- Deadlines must indicate their source and status.
- External communications are recorded, not assumed to have occurred.
- The platform supports professional work but does not replace it.

## Territorial extensibility

The long-term architecture should permit the addition of other autonomous
communities through configurable territorial profiles.

A territorial profile may define:

- applicable terminology;
- workflow stages;
- recommended or binding time targets;
- mandatory tasks;
- external institutions;
- document templates;
- closure requirements;
- official source references.

The initial product supports only the Andalusian profile. Extensibility does not
mean that other territories are already legally or operationally supported.

## Open regulatory questions

The following matters require validation before a real institutional
deployment:

- the definitive legal and operational status of the 2026 national framework;
- the exact data retention period for each category of information;
- the controller and processor roles for each deployment model;
- whether a Data Protection Impact Assessment is mandatory;
- the conditions for an official Séneca or PASEN/iPasen integration;
- the exact external notification procedures used by a specific school;
- the hosting and security requirements imposed by the competent authority.

These questions do not block development with fictional data.

## Review policy

This document must be reviewed:

- before a real pilot with an educational centre;
- whenever an applicable regulation or protocol changes;
- before adding a new autonomous community;
- before integrating with an institutional system;
- before processing real student data.

Last reviewed: 15 July 2026.