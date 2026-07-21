# Product Scope

## Purpose

This document defines the intended scope of Convive and the boundaries of its
initial functional release.

It describes what the product is expected to solve, who it serves and which
capabilities belong to the initial product.

The scope may evolve through documented product decisions, issues and versioned
changes.

## Product vision

Convive is a reporting and case management platform designed to help educational
communities communicate suspected school bullying safely and help authorised
school professionals manage the resulting work in one traceable environment.

The product combines two connected components:

1. a safe reporting channel;
2. a professional case management workspace.

The value of Convive comes from the connection between both components. A
report can be received safely and then assessed, organised, acted upon and
followed without fragmenting the process across unrelated tools.

## Origin of the problem

Students and other members of an educational community may avoid reporting
suspected bullying because they fear retaliation, exposure, disbelief or social
consequences.

When a situation is reported, school professionals may need to coordinate
information using paper, email, spreadsheets, verbal conversations and other
disconnected tools.

This fragmentation may make it more difficult to:

- know what has already been done;
- assign responsibilities;
- track pending actions;
- preserve confidentiality;
- follow the applicable protocol;
- maintain a reliable history;
- monitor the case over time.

Convive is designed to reduce both barriers:

- the barrier to safely communicating a concern;
- the operational difficulty of managing the response.

## Initial context

The initial product is designed for:

- secondary schools;
- publicly funded educational environments in Andalusia;
- Granada as the first operational reference;
- suspected school bullying and cyberbullying situations.

The architecture should allow future territorial profiles, but the first release
must not claim operational compliance outside Andalusia.

## Primary users

### Reporter

A reporter may be:

- a student;
- a witness;
- a family member or legal guardian;
- a teacher;
- another member of the educational community.

Students are the primary audience of the public reporting experience.

A reporter does not need a staff account.

### Authorised school professional

Depending on the organisation, this may include:

- a member of the management team;
- a guidance professional;
- the welfare and protection coordinator;
- another specifically authorised staff member.

These users assess reports and manage cases according to their permissions and
professional responsibilities.

### Administrator

An administrator manages organisational configuration, users and permissions.

Administrative access does not automatically grant unrestricted access to case
content. Permissions must follow the principle of least privilege.

## Core domain distinction

### Report

A report is an incoming communication describing a concern or suspected
situation.

A report:

- may be anonymous;
- may contain incomplete information;
- may include optional evidence;
- has not yet been professionally assessed;
- does not prove that bullying has occurred;
- does not automatically activate the official protocol.

### Case

A case is an internal professional workspace created or activated after an
authorised assessment.

A case may contain:

- assigned professionals;
- affected and involved people;
- assessments;
- protective measures;
- tasks and deadlines;
- meetings;
- evidence;
- communications;
- decisions;
- intervention actions;
- monitoring activities;
- closure information;
- an audit trail.

This separation is a fundamental product rule.

## Functional scope

### 1. Public reporting channel

The public reporting experience must allow a person to:

- select the relevant educational centre;
- describe the situation in accessible language;
- indicate whether the situation is in-person, digital, mixed or unknown;
- provide approximate dates or recurrence information;
- identify involved people when known;
- attach permitted supporting evidence;
- submit the report without creating an account;
- choose whether to provide an email address;
- receive a private tracking reference and access secret;
- consult the report status and authorised responses later.

The interface must clearly explain:

- what anonymity means;
- which information should not be included unnecessarily;
- what the platform can and cannot do;
- what to do in an immediate emergency;
- that submitting a report does not itself confirm bullying.

### 2. Anonymous tracking

Anonymous access will use:

- a public report reference;
- a separate, high-entropy access secret.

The access secret will:

- be shown to the reporter after submission;
- not be sent in notification emails;
- be stored only through a secure one-way representation;
- be required to enter the private follow-up area.

After the reference and secret are verified, the backend issues a
short-lived opaque capability in a protected cookie. That capability is limited
to the permitted follow-up operations for one report and is not a professional
account or a second Symfony session. The detailed boundary is selected in
[ADR-0008](../architecture/decisions/0008-use-server-side-sessions-and-capability-based-anonymous-access.md).

Losing the secret may mean losing anonymous access during the initial release.

The product must not create a false recovery mechanism that compromises
anonymity.

### 3. Optional email notifications

A reporter may optionally provide and verify an email address.

If provided, the email may be used only for generic notifications such as:

- the report has received an update;
- an authorised professional has posted a response;
- the reporter should access the secure portal.

Emails must not contain:

- case details;
- names of involved students;
- uploaded evidence;
- the access secret;
- sensitive decisions.

The email address must be stored separately from ordinary report content and
must not be visible to professionals who do not need it.

A reporter who does not provide an email can still use the tracking reference
and secret to check for updates manually.

### 4. Report triage

Authorised professionals must be able to:

- view reports assigned to their organisation;
- identify new and pending reports;
- record an initial assessment;
- request additional information through the secure portal;
- identify possible duplicates or related reports;
- record immediate protective actions;
- dismiss or redirect reports with a recorded reason;
- convert or link a report to a managed case;
- preserve the original report content.

The platform must not automatically determine whether bullying exists.

### 5. Case management

Authorised professionals must be able to:

- create and manage a case;
- assign responsible users;
- record the case modality as in-person, digital, mixed or undetermined;
- register only the case-specific people required;
- record meetings and professional assessments;
- define tasks, owners and target dates;
- record protective and intervention measures;
- attach controlled evidence and documents;
- record communications with families and external services;
- follow the applicable workflow;
- monitor progress;
- close and, where authorised, reopen a case;
- record the reason for significant decisions.

### 6. Workflow and deadlines

The Andalusian profile must provide a workflow based on the applicable official
protocol.

The system may also display reference targets from other official guidance.

Every target must indicate:

- what action is expected;
- its due date;
- its source;
- its status as binding, recommended or internal;
- whether it is pending, completed, overdue or not applicable.

Completing a task must require an explicit user action. Convive must never assume
that a family, authority or external service has been notified.

### 7. Communications

Convive must allow authorised users to record:

- the communication recipient;
- the communication channel;
- the responsible professional;
- the date and time;
- the completion status;
- an appropriate non-sensitive note or reference.

The initial product will not send official communications through Séneca,
PASEN or iPasen.

### 8. Evidence and documents

The product should support:

- controlled file uploads;
- file type and size validation;
- malware protection where technically available;
- access restrictions;
- evidence metadata;
- an integrity reference where appropriate;
- document generation for authorised case records;
- PDF export of selected case information.

Exports must respect permissions and must not expose information that the
requesting user cannot access inside the application.

### 9. Auditability

The system must record significant events such as:

- report submission;
- case creation;
- assignment changes;
- status changes;
- important decisions;
- task completion;
- evidence uploads;
- communications;
- exports;
- case closure and reopening;
- relevant administrative changes.

Audit records must be protected from ordinary modification.

Auditability must not become indiscriminate surveillance. Only security and
case-relevant events should be retained.

### 10. Roles and access control

The product must implement role-based and organisation-based access.

Access must consider:

- the user's organisation;
- their assigned role;
- whether they are assigned to the case;
- the sensitivity of the requested action;
- the need to separate administration from case access.

Detailed permissions will be defined during the security and domain design.

### 11. Search, filters and dashboards

Authorised users should be able to view:

- new reports;
- reports awaiting assessment;
- active cases;
- urgent or overdue actions;
- cases assigned to them;
- upcoming follow-up tasks;
- recently updated cases.

Search and filters must respect the same access restrictions as direct case
access.

### 12. Reports and exports

The system should provide operational information such as:

- number of reports by status;
- active and closed cases;
- overdue tasks;
- case modality;
- response and follow-up indicators.

Aggregated information must avoid identifying students or reporters.

The platform may generate controlled PDF records for authorised professional
use.

## Non-functional requirements

### Security

The product must include:

- secure authentication for staff;
- password hashing;
- protection against common web vulnerabilities;
- rate limiting for sensitive public endpoints;
- secure session management;
- encryption in transit;
- safe secret handling;
- upload validation;
- authorisation checks on every protected operation;
- security logging;
- backups and restoration procedures.

### Privacy

The product must apply:

- data minimisation;
- privacy by design and by default;
- optional reporter identification;
- separation of optional contact information;
- fictional data in public environments;
- configurable retention policies;
- deletion or anonymisation procedures where applicable;
- controlled exports;
- explicit access boundaries.

### Accessibility

The public reporting channel should target WCAG 2.2 AA principles.

The experience should be:

- understandable by young users;
- usable with a keyboard;
- compatible with assistive technology;
- clear under stress;
- responsive on mobile devices;
- written in direct and non-judgemental language.

### Reliability

The system should provide:

- repeatable deployments;
- database migrations;
- health checks;
- error monitoring;
- automated backups;
- restoration documentation;
- graceful handling of failed notifications;
- protection against accidental duplicate submissions.

### Maintainability

The project should include:

- a documented architecture;
- automated tests;
- continuous integration;
- code quality checks;
- versioned database migrations;
- environment-based configuration;
- meaningful Git history;
- documented product and technical decisions.

## Initial technical direction

PHP with Symfony is an explicit technical constraint selected for Convive rather
than a backend framework choice that remains open in the architecture ADRs.

The technical direction is:

- Symfony 7.4 LTS for the backend and domain logic;
- PostgreSQL with Doctrine ORM, DBAL and Migrations for relational persistence,
  as selected in
  [ADR-0007](../architecture/decisions/0007-use-postgresql-and-doctrine-for-persistence.md);
- Angular with TypeScript for the public and professional web interfaces, as
  selected in
  [ADR-0004](../architecture/decisions/0004-use-angular-for-the-web-frontend.md);
- Docker Compose for reproducible local and server environments, as selected in
  [ADR-0005](../architecture/decisions/0005-use-docker-compose-for-reproducible-environments.md);
- a resource-oriented JSON HTTP API with an OpenAPI contract, as selected in
  [ADR-0006](../architecture/decisions/0006-use-a-resource-oriented-json-http-api-with-an-openapi-contract.md);
- PostgreSQL-backed Symfony sessions for professionals and short-lived opaque,
  report-scoped capabilities for anonymous follow-up, as selected in
  [ADR-0008](../architecture/decisions/0008-use-server-side-sessions-and-capability-based-anonymous-access.md);
- automated CI through GitHub Actions;
- deployment to a controlled VPS.

A significant choice is considered architecturally selected when it is an
explicit project constraint or is supported by its relevant accepted ADR. Exact
dependency and image versions will be pinned when the applications and
environments are initialised.

## Public demonstration environment

The public deployment is a product demonstration and must:

- use fictional organisations, users and cases;
- clearly state that real situations must not be reported there;
- not present itself as an official service of the Junta de Andalucía;
- not claim a real integration with institutional systems;
- include a visible route for exploring the product safely;
- remain maintainable for the required scholarship availability period.

## Explicitly out of scope for the initial release

The following capabilities are not included initially:

- automatic determination or diagnosis of bullying;
- AI-generated safeguarding decisions;
- automated credibility or contradiction scoring;
- direct integration with Séneca, PASEN or iPasen;
- automatic communications to families or authorities;
- replacement of emergency, police or child protection services;
- legal advice;
- support for every Spanish autonomous community;
- import of complete student databases;
- unrestricted access to evidence or case information;
- processing of real student data in the public demonstration;
- a native mobile application.

These capabilities may only be reconsidered through explicit product,
regulatory, privacy and technical analysis.

## Future extensions

Possible future developments include:

- additional safeguarding protocols;
- territorial profiles for other autonomous communities;
- authorised institutional integrations;
- advanced anonymised analytics;
- configurable document templates;
- multilingual support;
- native or progressive mobile experiences;
- institution-wide prevention indicators;
- additional notification channels.

A future possibility is not part of the current supported product until it has
acceptance criteria, implementation and verification.

## Product success criteria

The initial product will be considered functionally complete when:

- a person can safely submit and track a report;
- optional notifications do not compromise report confidentiality;
- authorised staff can assess a report;
- a report can be converted or linked to a case;
- a case can be managed from assessment through closure and follow-up;
- responsibilities, actions and deadlines are traceable;
- permissions protect sensitive information;
- significant actions are audited;
- fictional demonstration data can show the complete workflow;
- automated tests cover the most critical security and business paths;
- the system can be reproducibly deployed and restored.

## Scope change policy

A proposed capability belongs to the product only when:

1. its user and problem are clear;
2. its regulatory and privacy impact has been considered;
3. it has defined acceptance criteria;
4. it has been prioritised in the project backlog;
5. its implementation and verification are traceable.

Last reviewed: 21 July 2026.
