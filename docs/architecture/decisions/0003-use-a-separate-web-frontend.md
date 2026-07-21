# ADR-0003: Use a separate web frontend

- **Status:** Accepted
- **Date:** 17 July 2026
- **Related issue:** [#1](https://github.com/albertogalvez-dev/Convive/issues/1)
- **Depends on:** [ADR-0001](0001-use-a-monorepository.md), [ADR-0002](0002-use-a-modular-monolith-for-the-backend.md)

## Context

Convive requires two connected user experiences:

- a public and accessible reporting channel;
- an authenticated workspace for authorised school professionals.

The public experience includes anonymous reporting, multi-step forms, optional
evidence uploads and secure report follow-up.

The professional workspace includes report triage, dashboards, filters, case
workflows, tasks, deadlines, communications, evidence management and controlled
access to sensitive information.

Symfony will contain the authoritative business rules, security decisions,
workflow operations and persistence logic.

A decision is required on whether the web interface should be rendered directly
by Symfony or implemented as a separate frontend application.

This ADR decides the architectural separation of the frontend. It does not
select a frontend framework, rendering strategy or authentication mechanism.
Those decisions will be documented separately.

## Decision drivers

- Provide an accessible and responsive interface.
- Support complex forms and interactive professional workflows.
- Maintain a clear boundary between presentation and domain logic.
- Keep authoritative business rules and permissions inside Symfony.
- Support independent frontend testing and development.
- Prevent direct browser access to the database.
- Allow the backend interface to support future authorised clients when needed.
- Keep the public and professional experiences visually consistent.
- Avoid unnecessary duplication between multiple frontend applications.
- Remain deployable on a single controlled VPS.
- Provide meaningful full-stack learning without compromising maintainability.

## Options considered

### Option A: Render the frontend inside Symfony

Use Twig templates together with Symfony UX, Stimulus, Turbo or Live Components
to implement the complete web interface inside the Symfony application.

Benefits:

- one application runtime;
- simpler deployment and local development;
- direct integration with Symfony sessions, forms and CSRF protection;
- fewer network boundaries;
- server-rendered HTML by default;
- lower initial infrastructure and maintenance cost.

Costs:

- presentation and backend concerns remain more closely coupled;
- complex dashboards may require increasing amounts of embedded JavaScript;
- frontend development and testing are less independent;
- a reusable backend interface may be introduced later instead of being an
  architectural boundary from the beginning;
- frontend technology can evolve less independently from the backend.

### Option B: Use one separate web frontend

Build the public reporting experience and professional workspace as one
independent frontend application.

The frontend communicates with Symfony through an explicit HTTP interface.

Benefits:

- clear separation between presentation and backend responsibilities;
- independent frontend structure, tooling and tests;
- suitable for interactive forms, dashboards and long-lived workflows;
- consistent components and visual identity across public and professional
  experiences;
- Symfony remains reusable by future authorised clients when justified;
- frontend and backend changes can be verified independently;
- provides practical experience integrating two application layers.

Costs:

- an explicit backend interface is required;
- authentication, cookies and CSRF require deliberate design;
- frontend and backend contracts may become inconsistent;
- validation exists at both user-experience and security levels;
- local development and continuous integration must coordinate two
  applications;
- deployment is more complex than a single Symfony-rendered application.

### Option C: Use separate public and professional frontends

Build one frontend application for anonymous reporting and another for the
professional workspace.

Benefits:

- each application could be optimised for a distinct audience;
- public and authenticated deployment boundaries would be explicit;
- each application could evolve and be released independently.

Costs:

- duplicated configuration, components and design-system work;
- increased testing and deployment complexity;
- greater risk of inconsistent user experiences;
- more applications to secure, update and monitor;
- no current product requirement justifies independent release cycles.

## Decision

Convive will use one separate web frontend application.

The repository will initially contain:

- `apps/web` for the web frontend;
- `apps/api` for the Symfony backend.

The public reporting experience and the professional workspace will belong to
the same frontend application. They will use separate routes, layouts and
permission-aware interfaces where required.

The frontend and Symfony will communicate through an explicit HTTP interface.
The interface style, data format and contract strategy will be selected in a
separate ADR.

The frontend technology and rendering approach will also be selected in a
separate ADR.

## Production boundary

Both applications should initially be exposed through the same public origin:

- `/` routes to the web frontend;
- `/api` routes to Symfony.

Using the same public origin should simplify browser security configuration and
avoid unnecessary cross-origin communication.

The reverse proxy, container structure and deployment configuration will be
documented separately.

The database and internal application services must not be exposed directly to
the public network.

## Frontend responsibilities

The web frontend is responsible for:

- page presentation and navigation;
- accessible interface components;
- responsive behaviour;
- multi-step reporting forms;
- professional dashboards and workflows;
- client-side interaction and feedback;
- presenting loading and error states;
- presenting validation errors returned by Symfony;
- optional client-side validation for immediate feedback;
- managing non-authoritative interface state;
- calling the Symfony backend.

## Symfony responsibilities

Symfony is responsible for:

- authoritative business rules;
- security-critical input validation;
- authentication and authorisation decisions;
- organisation and case access restrictions;
- report and case workflows;
- database transactions and persistence;
- audit events;
- notification orchestration;
- evidence access decisions;
- deadline and protocol rules;
- document generation;
- controlled exports.

## Boundary rules

- The frontend must not connect directly to the database.
- The frontend must not become the authoritative source of permissions.
- Hiding an interface element is not an authorisation control.
- Symfony must validate every protected operation.
- Core business rules must not be duplicated in the frontend.
- Client-side validation may improve usability but never replaces backend
  validation.
- Sensitive information must only be returned after Symfony authorises access.
- The frontend must not implement a parallel domain backend.
- The browser must not be trusted to enforce workflow transitions.
- Shared contracts should be generated or verified where practical instead of
  being manually duplicated.
- Public and professional routes must not result in separate databases or
  duplicated domain models.

## Authentication implications

Selecting a separate frontend did not itself select an authentication
mechanism. [ADR-0008](0008-use-server-side-sessions-and-capability-based-anonymous-access.md)
now defines the previously deferred boundary:

- professionals use stateful server-side Symfony sessions;
- anonymous follow-up uses a short-lived opaque capability limited to one
  report rather than a professional account or second framework session;
- protected cookies, CSRF, expiry and browser-storage rules are explicit;
- public, anonymous follow-up and professional operations use separate request
  and authenticator boundaries.

The selected mechanism keeps Symfony responsible for authoritative
authentication and authorisation decisions. It preserves the same-origin
frontend/backend boundary selected by this ADR; production reverse-proxy
implementation remains a separate deployment concern.

## Explicit exclusions

This decision does not permit:

- direct database access from the frontend;
- independent business rules in the frontend;
- storing sensitive credentials in browser local storage;
- trusting client-side validation;
- exposing Symfony or the database directly to the public network;
- creating separate public and professional frontend applications without a
  demonstrated requirement;
- implementing a second authoritative backend inside the frontend application;
- selecting a frontend framework without a separate documented decision.

## Rationale

Rendering the complete interface with Symfony and Twig would provide the
simplest operational architecture and remains a valid alternative.

Convive, however, includes a substantial interactive interface composed of an
anonymous reporting flow and a professional case-management workspace. These
experiences will contain complex forms, filters, dashboards, tasks, workflow
states and permission-aware interactions.

A separate frontend establishes a clear presentation boundary while Symfony
remains the authoritative domain and security boundary.

Using one frontend rather than separate public and professional applications
preserves a shared component system, visual identity, testing strategy and
deployment process.

The additional integration complexity is accepted because:

- the interface is a substantial part of the product;
- frontend and backend responsibilities can be documented explicitly;
- both applications can still be deployed together on one VPS;
- the backend interface can support future authorised clients when justified;
- the project has a meaningful full-stack educational objective;
- security-critical decisions remain centralised in Symfony.

## Consequences

### Positive consequences

- Frontend and backend responsibilities are explicit.
- Symfony remains the authoritative business and security boundary.
- Interface code can be structured and tested independently.
- Public and professional experiences can share components and visual rules.
- The backend interface becomes an explicit and testable contract.
- The architecture can support future authorised clients without direct
  database access.
- Frontend technology can evolve independently when justified.
- The repository demonstrates complete frontend and backend integration.

### Negative consequences

- Two application layers must be developed and tested.
- Authentication is more complex than with Symfony-rendered pages.
- Frontend and backend contracts may become inconsistent.
- User-experience validation and authoritative validation coexist.
- Continuous integration must verify both applications.
- Local development requires coordinated services.
- Deployment requires routing traffic between the frontend and Symfony.
- Developers must understand the security boundary between browser and backend.

## Risk mitigations

- Keep authoritative validation and authorisation in Symfony.
- Document the backend interface.
- Add contract verification or generated types when the interface stabilises.
- Expose both applications through the same public origin.
- Test every protected operation at the Symfony boundary.
- Avoid placing sensitive information in publicly cacheable responses.
- Keep local and production environments reproducible.
- Maintain one frontend application until independent applications are
  justified by real requirements.
- Review frontend code for duplicated workflow or permission logic.

## Review triggers

This decision should be reviewed if:

- frontend complexity remains too low to justify a separate application;
- maintaining two application layers significantly slows delivery;
- secure authentication cannot be implemented clearly;
- the backend interface becomes unnecessary;
- the product changes into a primarily server-rendered administrative tool;
- public and professional experiences require genuinely independent release
  cycles;
- deployment constraints prevent reliable operation of both application
  layers.

If this decision changes, a later ADR must supersede ADR-0003 and describe the
migration path.
