# ADR-0004: Use Angular for the web frontend

- **Status:** Accepted
- **Date:** 19 July 2026
- **Related issue:** [#1](https://github.com/albertogalvez-dev/Convive/issues/1)
- **Depends on:** [ADR-0003](0003-use-a-separate-web-frontend.md)

## Context

ADR-0003 establishes that Convive will use one separate web frontend for both:

- the public reporting experience;
- the authenticated professional workspace.

The frontend will communicate with the Symfony backend through an explicit HTTP
interface.

A frontend technology must now be selected.

Convive is primarily an interactive application rather than a content-oriented
website. Its interface will include:

- multi-step anonymous reporting forms;
- optional evidence uploads;
- secure report tracking;
- professional authentication;
- report triage;
- case dashboards;
- filters and searches;
- tasks and deadlines;
- permission-aware navigation;
- case workflows;
- communications;
- evidence management;
- controlled exports;
- loading, error and validation states.

Symfony will remain the only authoritative backend. It will contain the business
rules, authentication and authorisation decisions, workflow transitions,
persistence operations and audit events.

The selected frontend technology should therefore provide a robust application
structure without creating a second domain backend.

## Decision drivers

- First-class TypeScript support.
- Strong support for complex and testable forms.
- Official routing and navigation facilities.
- A clear way to communicate with the Symfony API.
- Support for request interception and consistent error handling.
- A maintainable structure for a growing application.
- Explicit dependency injection and separation of responsibilities.
- Support for automated component, routing and HTTP tests.
- Suitability for dashboards and long-running authenticated workflows.
- Ability to deploy the frontend as static production assets.
- No mandatory Node.js server in production.
- Compatibility with same-origin, cookie-based authentication.
- Long-term maintenance from a stable project.
- Good documentation and development tooling.
- Support for accessible and responsive interfaces.
- Avoidance of unnecessary server-side rendering complexity.
- Avoidance of assembling too many unrelated libraries.
- Meaningful learning value without compromising the product architecture.

## Options considered

### Option A: Angular with TypeScript

Build the frontend as an Angular application using TypeScript and Angular's
official facilities for components, routing, reactive forms, dependency
injection, HTTP communication, request interception, lazy loading and testing.

Benefits:

- provides a complete and opinionated application framework;
- includes official routing, forms, HTTP and testing tools;
- reactive forms suit complex and sensitive data-entry flows;
- dependency injection encourages explicit application boundaries;
- production assets can be served without a permanent Node.js runtime;
- conventions reduce the number of project-specific decisions;
- fits a workflow and case-management interface.

Costs:

- higher initial learning curve;
- more framework concepts and conventions to understand;
- careless RxJS usage can make simple flows unnecessarily complex;
- frontend validation still coexists with authoritative backend validation;
- route guards cannot replace Symfony authorisation;
- bundle size requires attention and route-level lazy loading.

### Option B: React with TypeScript, Vite and React Router

Build a client-rendered React application using Vite and React Router, then
select additional libraries for forms, validation and server-state management.

Benefits:

- flexible component model;
- strong TypeScript support;
- fast development and compilation through Vite;
- static production output;
- suitable for interactive dashboards;
- broad freedom to select specialised libraries.

Costs:

- React is a library rather than a complete application platform;
- several important concerns require separate libraries and decisions;
- multiple independent dependencies must be coordinated and upgraded;
- different valid patterns can produce an inconsistent codebase;
- its flexibility does not solve a specific Convive requirement.

### Option C: Vue with TypeScript, Vite and Vue Router

Build a client-rendered Vue application using TypeScript, Vite and the official
Vue Router. Pinia could be introduced if shared client state justified it.

Benefits:

- approachable component and template model;
- official routing and state-management recommendations;
- good TypeScript support;
- static production output;
- suitable for interactive applications.

Costs:

- forms, validation and API-state conventions require additional choices;
- a large application still needs project-specific structure;
- it provides fewer integrated facilities than Angular for the known scope;
- no confirmed requirement gives Vue a decisive advantage.

### Option D: Next.js with TypeScript

Build the frontend with React and the Next.js application framework.

Benefits:

- integrated file-based routing;
- supports server rendering, static generation and client rendering;
- established React component model;
- suitable for public pages and interactive applications.

Costs:

- Convive already has Symfony as its authoritative backend;
- server actions and server routes could create a parallel backend;
- server and client execution boundaries add conceptual complexity;
- dynamic server rendering normally requires a Node.js production runtime;
- caching behaviour requires careful handling of sensitive data;
- the current product does not require dynamic server rendering.

### Option E: Nuxt with TypeScript

Build the frontend with Vue and Nuxt.

Benefits:

- integrated routing and application conventions;
- supports universal, client and hybrid rendering;
- good Vue and TypeScript development experience.

Costs:

- its server-side capabilities overlap with Symfony;
- dynamic rendering may require an additional production runtime;
- no current requirement makes Nuxt preferable;
- using it only as a client application would reduce its value.

### Option F: SvelteKit

Build the frontend with Svelte and SvelteKit.

Benefits:

- concise component syntax;
- integrated application routing;
- supports server, static and client rendering;
- suitable for modern interactive interfaces.

Costs:

- introduces another component model and ecosystem;
- server capabilities overlap with Symfony;
- no confirmed requirement provides a decisive reason to select it;
- security and API conventions would still be specific to Convive.

### Option G: React Router Framework Mode

Use React Router as an application framework with client rendering, server
rendering and pre-rendering support.

Benefits:

- remains within the React ecosystem;
- supports several rendering strategies;
- provides structured route modules and data loading.

Costs:

- server-rendering capabilities are not currently required;
- it is less integrated for forms, dependency injection and HTTP communication
  than Angular;
- several application concerns still require additional decisions.

### Option H: Astro

Build the frontend with Astro and add interactive component islands where
required.

Benefits:

- excellent support for content-oriented public websites;
- static output and reduced JavaScript by default;
- strong initial-load performance for mostly static content.

Costs:

- Convive is primarily an interactive application;
- dashboards, forms and workflows would require extensive client islands;
- mixing Astro with another component framework adds complexity;
- Astro's principal strengths do not match the dominant requirements.

### Option I: Emerging or less suitable alternatives

Other technologies include TanStack Start, SolidStart, Qwik, Preact, Ember and
additional JavaScript frameworks.

They are not selected for detailed evaluation because they do not solve a
unique confirmed requirement, some are still approaching a stable first major
release, and the shortlist already represents the relevant architectural
categories.

An excluded technology may be reconsidered when a future requirement provides
a specific reason.

## Decision

Convive will use Angular with TypeScript for the web frontend.

The Angular application will be stored in `apps/web`. The Symfony backend will
be stored in `apps/api`.

Angular will initially operate as a client-rendered single-page application.
Its production build will generate static frontend assets served by the public
web server or reverse proxy.

Symfony will remain the only authoritative backend and will be exposed through
the `/api` path.

The initial production request flow will be:

```text
Browser
    |
    v
Reverse proxy
    |
    +-- /       -> Angular static assets
    |
    +-- /api    -> Symfony
                       |
                       v
                   PostgreSQL
```

Node.js will be required for frontend development, dependency installation,
testing and production builds. A permanent Node.js application server will not
be required for the initial deployment.

## Initial Angular approach

The frontend should initially use:

- TypeScript with strict compiler settings;
- standalone Angular components;
- Angular Router;
- Angular reactive forms;
- Angular HttpClient;
- Angular dependency injection;
- lazy-loaded feature routes;
- Angular's supported testing tools;
- feature-oriented source organisation.

The exact Angular version will be selected when the application is created. It
must be a supported stable version and will be fixed through the dependency
manifest and lock file.

## Application structure

The frontend should be organised by product capability rather than only by
technical file type.

An initial conceptual structure may include:

```text
apps/web/src/app/
|-- core/
|-- shared/
|-- reporting/
|-- report-tracking/
|-- professional-access/
|-- report-triage/
|-- case-management/
|-- administration/
`-- app.routes.ts
```

This structure is illustrative and may evolve as the implemented domain becomes
clearer. It must not copy the backend module structure mechanically; frontend
organisation should follow user interactions and interface responsibilities.

## State management

Angular services and built-in reactive facilities should initially manage local
and shared interface state. A third-party global state-management library will
not be introduced without demonstrated need.

Server data returned by Symfony must not be treated as an independent
authoritative frontend database.

A state-management library may be reconsidered if the application develops
complex shared state across unrelated routes, advanced event-driven interface
coordination, offline synchronisation or state transitions that cannot be
managed clearly with Angular's built-in facilities.

## Form strategy

Angular reactive forms will be used for the anonymous report, authentication,
triage, case updates, tasks, communications and administrative configuration.

Frontend validation is responsible for immediate and understandable feedback.
Symfony validation remains authoritative for correctness, security and business
rules.

A value accepted by Angular must never be assumed to be accepted by Symfony.

## HTTP communication

Angular will communicate with Symfony through HttpClient.

HTTP interceptors may handle cross-cutting interface concerns such as consistent
error translation, correlation identifiers, CSRF information, authentication
expiry and safe request metadata.

Interceptors must not hide security behaviour or contain domain business rules.

The API style, JSON conventions, error format, versioning and contract strategy
will be defined in a separate ADR.

## Authentication boundary

Angular route guards may improve navigation and prevent users from entering
routes that the interface already knows are unavailable. They are not security
controls.

Symfony must authorise every protected request independently. Authentication
credentials or sensitive tokens must not be stored in browser local storage.

The authentication and session mechanism will be defined in a separate ADR.

## Rendering decision

Server-side rendering will not be enabled initially because:

- the professional workspace is authenticated;
- private dashboard content does not require search-engine indexing;
- the anonymous reporting flow is an interactive application;
- a permanent frontend server would increase deployment complexity;
- no confirmed requirement currently depends on server rendering.

Server rendering or pre-rendering may be reconsidered if the product later
requires substantial public content, stronger indexing requirements or measured
first-load improvements that cannot otherwise be achieved.

## Accessibility

Selecting Angular does not make the product automatically accessible.

The frontend must still apply:

- semantic HTML;
- keyboard-operable controls;
- visible focus states;
- appropriate labels and descriptions;
- understandable validation messages;
- sufficient colour contrast;
- responsive layouts;
- assistive-technology testing;
- WCAG 2.2 AA principles for the public reporting experience.

Accessibility must be verified through automated checks and manual testing.

## Explicit exclusions

This decision does not select:

- a visual component library;
- a CSS framework;
- a global state-management library;
- server-side rendering;
- a mobile application framework;
- the authentication mechanism;
- the API style;
- an API contract-generation tool;
- an end-to-end testing framework.

This decision does not permit:

- direct PostgreSQL access from Angular;
- authoritative business rules in Angular;
- trusting Angular route guards as authorisation;
- storing sensitive authentication credentials in local storage;
- duplicating Symfony workflow logic;
- adding a Node.js backend that competes with Symfony;
- enabling Angular server rendering without a documented requirement.

## Rationale

Convive is a form-heavy and workflow-heavy application.

Angular provides integrated facilities for complex forms, routing, HTTP
communication, request interception, dependency injection, component
organisation, lazy loading and automated testing.

React and Vue could both implement Convive successfully. They would require more
project-specific decisions and additional libraries to provide an equivalent
application platform.

Next.js, Nuxt and SvelteKit provide valuable server-rendering capabilities, but
Convive already uses Symfony as its backend and has no current requirement that
justifies a second application server.

Astro is better aligned with content-oriented sites than with the interactive
case-management workflows that dominate Convive.

Angular therefore provides the most coherent balance between application
structure, TypeScript support, integrated tools, static deployment,
maintainability, testing, learning value and respect for the Symfony backend
boundary.

The additional learning curve is accepted because the project is intended to
be a complete and maintainable application rather than a disposable prototype.

## Consequences

### Positive consequences

- The frontend uses a complete and documented application framework.
- TypeScript is used throughout the frontend.
- Forms, routing, HTTP and testing have official supported solutions.
- The production frontend can be served as static assets.
- Symfony remains the only authoritative backend.
- A permanent Node.js production server is avoided.
- Application conventions reduce arbitrary architectural variation.
- Feature routes can be loaded lazily.
- Complex workflows can be tested independently from Symfony.

### Negative consequences

- Angular introduces a substantial learning curve.
- Initial development may require time to understand framework conventions.
- Poorly structured RxJS usage may make code difficult to follow.
- Client and backend validation must remain synchronised.
- Frontend and backend types may drift until contract verification is added.
- Bundle size and initial loading require monitoring.
- Framework upgrades must be planned and tested.
- Angular-specific knowledge becomes necessary to maintain the frontend.

## Risk mitigations

- Use Angular's standard conventions before custom abstractions.
- Enable strict TypeScript settings.
- Organise code by product capability.
- Keep components focused on presentation and interaction.
- Keep authoritative rules in Symfony.
- Use reactive forms consistently.
- Lazy-load major professional feature areas.
- Avoid unnecessary global state and RxJS complexity.
- Test routes, forms, components and HTTP error handling.
- Add backend contract verification when the API stabilises.
- Monitor production bundle size.
- Pin dependencies through the lock file.
- Apply supported dependency updates through reviewed changes.
- Document unfamiliar Angular concepts as they are introduced.
- Verify accessibility manually as well as automatically.

## Review triggers

This decision should be reviewed if:

- the Angular learning cost prevents reliable delivery;
- the frontend remains too small to justify Angular;
- static client rendering fails a confirmed product requirement;
- server rendering becomes necessary for substantial public content;
- bundle size causes measured usability problems on target devices;
- Angular cannot support an essential accessibility requirement;
- maintaining Angular and Symfony becomes disproportionate;
- a native or offline-first client becomes a confirmed requirement;
- another frontend technology offers a measurable and material improvement.

If the technology changes, a later ADR must supersede ADR-0004 and describe the
migration path.

## References

- Angular overview: https://angular.dev/overview
- Angular routing: https://angular.dev/guide/routing
- Angular forms: https://angular.dev/guide/forms
- Angular HTTP client: https://angular.dev/guide/http
- Angular testing: https://angular.dev/guide/testing
- Next.js App Router: https://nextjs.org/docs/app
- Nuxt rendering modes: https://nuxt.com/docs/3.x/guide/concepts/rendering
- React Router modes: https://reactrouter.com/start/modes
- Vue routing: https://vuejs.org/guide/scaling-up/routing
- Astro design principles: https://docs.astro.build/en/concepts/why-astro/
- TanStack Start status: https://tanstack.com/start/latest/docs/framework/react/overview
