# ADR-0008: Use server-side sessions and capability-based anonymous access

- **Status:** Accepted
- **Date:** 21 July 2026
- **Related issue:** [#1](https://github.com/albertogalvez-dev/Convive/issues/1)
- **Depends on:** [ADR-0002](0002-use-a-modular-monolith-for-the-backend.md), [ADR-0003](0003-use-a-separate-web-frontend.md), [ADR-0004](0004-use-angular-for-the-web-frontend.md), [ADR-0005](0005-use-docker-compose-for-reproducible-environments.md), [ADR-0006](0006-use-a-resource-oriented-json-http-api-with-an-openapi-contract.md), [ADR-0007](0007-use-postgresql-and-doctrine-for-persistence.md)

## Context

Convive has two fundamentally different private-access needs:

1. school professionals need authenticated accounts and access controlled by
   organisation, role, assignment and action;
2. an anonymous reporter needs to revisit one report without creating an
   account or proving a real-world identity.

These mechanisms must not be confused. A professional session represents an
authenticated user. Anonymous follow-up represents possession of a narrowly
scoped secret and must not silently become an identity system.

The Angular frontend and Symfony backend will be served under one public origin.
The backend is the authoritative authentication and authorisation boundary.

The product scope already requires:

- no staff account for submitting a report;
- a public tracking reference and separate high-entropy access secret;
- one-way storage of that access secret;
- secure staff authentication and password hashing;
- role-based and organisation-based access;
- authorisation on every protected operation;
- secure sessions, rate limiting and security logging.

ADR-0006 deliberately deferred the authentication mechanism. ADR-0007 selected
UUIDv7 for applicable internal identifiers but explicitly prohibited using or
exposing those identifiers as anonymous access secrets.

This ADR selects the initial browser authentication and session boundaries. It
does not define the complete permission matrix or claim readiness to process
real student data.

## Decision drivers

- Preserve genuine account-free reporting.
- Limit anonymous follow-up access to one report.
- Keep session credentials out of JavaScript-accessible storage.
- Make logout effective immediately and make account or privilege changes take
  effect before the next protected operation.
- Fit the same-origin Angular and Symfony architecture.
- Use Symfony Security rather than implementing authentication primitives from
  scratch.
- Protect state-changing requests against cross-site request forgery.
- Enforce organisation and object-level authorisation in the backend.
- Avoid adding an identity provider or infrastructure service without confirmed
  access or need.
- Remain manageable for one primary developer and one VPS.
- Leave a clear migration path to institutional identity federation and
  stronger authentication.

## Options considered

### Option A: Server-side sessions with secure cookies

Authenticate staff through Symfony and retain authentication state on the
server. The browser receives only an opaque session identifier in a protected
cookie.

Benefits:

- the browser does not receive reusable access or refresh tokens;
- logout, account disabling and server-side invalidation are direct;
- session state and expiry remain under backend control;
- Symfony provides the authentication, session and password infrastructure;
- it fits a first-party web application served from one origin;
- CSRF protection can integrate with Angular's HTTP client.

Costs:

- the backend must store and clean up session state;
- cookie authentication requires explicit CSRF protection;
- horizontal scaling would require a shared session store;
- session expiry and invalidation behaviour require integration tests.

### Option B: JWT access and refresh tokens in the browser

Issue signed tokens after login and send them as bearer credentials with API
requests.

Benefits:

- common for APIs serving independent clients;
- access-token verification can be stateless;
- useful when several separately operated services accept the same token.

Costs:

- Convive has no confirmed independent client or distributed-service need;
- browser storage can expose tokens to JavaScript and cross-site scripting;
- refresh-token rotation, reuse detection and revocation add security state;
- immediate logout and account disabling become more complicated;
- storing JWTs in HttpOnly cookies returns to cookie and CSRF concerns without
  providing a current architectural benefit;
- token claims can become stale or leak unnecessary information.

### Option C: External OpenID Connect identity provider

Delegate professional authentication to an institutional or commercial OpenID
Connect provider.

Benefits:

- centralised account lifecycle and potentially stronger authentication;
- possible single sign-on for school staff;
- Convive would not need to verify staff passwords itself.

Costs:

- no supported Junta de Andalucia, Seneca or other institutional provider has
  been confirmed for this project;
- onboarding, claims and organisation mapping depend on an external contract;
- local development and public evaluation would depend on provider setup;
- federation does not remove Convive's own session and authorisation needs.

### Option D: Email magic links as the primary staff login

Send a short-lived link to the professional's verified email address for every
login.

Benefits:

- no staff password to remember or store;
- simple user experience when email is immediately available.

Costs:

- authentication security becomes dependent on the email account and delivery
  channel;
- delayed or unavailable email can block professional access;
- emailed links require careful replay, leakage and expiry controls;
- it is unsuitable as the only administrator recovery mechanism;
- Convive already needs email for optional notifications, but that does not make
  email a sufficiently strong or reliable primary authenticator.

## Decision

Convive will use two separate access contexts:

1. stateful server-side sessions for authenticated professionals;
2. a short-lived, report-scoped capability grant represented by an opaque
   protected cookie after an anonymous reporter proves possession of the report
   reference and access secret.

Public report submission remains account-free and does not start a professional
session.

The application will not use JWT access or refresh tokens for the initial web
application. Authentication credentials, professional session identifiers,
anonymous capability handles and report access secrets must not be stored in
`localStorage` or `sessionStorage`.

An external OpenID Connect provider remains a future integration option when a
real deployment provides a documented provider, client registration and claims
contract.

## Professional account boundary

Professional users will authenticate through Symfony Security using a stateful
firewall and a JSON login endpoint suitable for the Angular client.

Professional accounts are provisioned by an authorised administrator or through
a controlled invitation. Public self-registration is not allowed.

The first deployment administrator will be created through an explicit
deployment command or equivalent controlled bootstrap process. Default shared
credentials must not exist in source code, fixtures or container images.

Authentication establishes the user identity. It does not by itself grant
access to every report or case.

An authenticated professional may act only when backend authorisation confirms:

- an active account;
- an active relationship with the relevant organisation;
- an applicable role or permission;
- any required assignment to the report or case;
- permission for the specific action and data sensitivity.

Administration permission does not automatically grant access to case content.

Symfony roles may express broad capabilities. Object and organisation checks
will use explicit authorisation policies or voters close to the protected use
case. Controllers and frontend route guards are not sufficient authorisation
boundaries.

All protected operations use a deny-by-default policy. The backend must apply
the same rules to direct reads, searches, exports, attachments and workflow
actions.

## Staff credentials

Staff passwords will be processed through Symfony PasswordHasher using its
`sodium` algorithm, backed by libsodium's Argon2id password hashing, with
password migration support.

Symfony's `auto` option was considered. In Symfony 7.4 it currently selects
bcrypt, which remains supported but has a 72-byte input limitation and is not
the preferred algorithm for a new deployment when Argon2id is available. The
Docker runtime will therefore include the sodium extension explicitly.

Memory and time costs will be benchmarked on the deployment class and must not
fall below the then-current reviewed OWASP baseline. Stored parameters allow
future rehashing when that baseline changes.

The stored password-hash column must allow algorithm and parameter changes. A
length of at least 255 characters is the initial persistence convention.

The initial password policy will:

- require at least 15 characters while password-only authentication is used;
- permit at least 64 characters;
- allow spaces, Unicode and password-manager generated values;
- avoid arbitrary composition rules requiring particular character classes;
- reject known-compromised and common passwords using a reviewed blocklist
  approach;
- permit paste and browser or password-manager autofill;
- avoid periodic password changes without evidence of compromise;
- require a change after confirmed or suspected compromise.

Plain-text passwords must never be logged, persisted, placed in events or
returned in API responses.

Login attempts will use progressive throttling with independently useful
account and network limits, rather than relying only on one combined
account-and-IP bucket. The network limit must be tuned for shared school
networks, and the policy must not create an indefinite attacker-controlled
account lockout. Responses must not reveal whether an account exists, is
disabled or belongs to a particular organisation.

Account state checks will prevent disabled, expired or otherwise ineligible
accounts from authenticating or continuing to use a refreshed session.

The refreshed Symfony security identity will include a non-secret account
security revision, or an equivalent revocation marker, in its user-comparison
rules. Disabling an account, changing security-relevant roles or memberships,
resetting credentials or responding to confirmed compromise increments that
revision. Symfony must then reject the stale identity before the next protected
operation. The implementation may use `EquatableInterface` or an equivalent
supported comparison mechanism; deleting opaque session rows by user is not the
only revocation control.

## Professional session lifecycle

Symfony will store professional session state on the server. PostgreSQL will be
the initial shared session store through Symfony's `PdoSessionHandler`.

This reuses the selected database, survives ordinary API-container replacement
and avoids adding Redis before its operational value is demonstrated.

The session table is technical infrastructure, not a domain aggregate. Its
creation and changes remain versioned through the application migration
workflow, and expired rows require a documented cleanup mechanism.

Active sessions have no recovery value. Database restoration procedures must
purge restored session rows so that an old backup cannot resurrect an
invalidated login.

The professional session cookie will use an application-specific `__Host-`
name and the following production properties:

- `Secure`;
- `HttpOnly`;
- `SameSite=Lax`;
- `Path=/`;
- no `Domain` attribute;
- no persistent `remember me` lifetime.

`SameSite=Lax` preserves ordinary top-level navigation from a trusted link while
CSRF tokens protect every state-changing operation. `SameSite` is defence in
depth and does not replace CSRF validation.

The session identifier contains no user, organisation, role or case data.
Sensitive domain data must not be copied into the session.

Only the minimum information required to restore the authenticated security
context may be serialised. Doctrine associations and the complete stored
password hash must not be copied into the session. If password-change
invalidation uses Symfony's user-comparison mechanism, the session may contain
only the supported transformed fingerprint of that hash. Authorisation must use
current account, membership and assignment state rather than treating roles
serialised at login as permanently authoritative.

The session identifier will be renewed after successful authentication or
reauthentication. A security-relevant privilege change normally invalidates the
stale identity through the security revision and requires a freshly established
context instead of preserving the old identifier.

Initial expiry targets are:

- 30 minutes of inactivity;
- 12 hours of total authenticated lifetime.

These values are explicit security defaults and will be configurable. Expiry
must be enforced by the server, not only by deleting a browser cookie.

Logout invalidates the current server-side session immediately. Password reset,
administrator deactivation, a security-relevant role or membership change and
confirmed credential compromise must make applicable sessions unusable before
their next protected operation through the security-revision check or a
stronger invalidation mechanism.

Sensitive authenticated responses use restrictive cache controls such as
`Cache-Control: no-store` where browser or intermediary storage would create a
confidentiality risk.

## CSRF and browser boundary

Cookie-authenticated state-changing requests require CSRF protection.

Symfony's CSRF facilities and Angular's same-origin XSRF support will be
integrated deliberately rather than assuming that their defaults implement the
same protocol.

Symfony 7.4 stateless CSRF validation checks the expected origin and can add a
JavaScript-generated cookie/header double submission. Angular's built-in
interceptor instead reads a JavaScript-accessible cookie issued by the backend
and copies it to a configurable header on mutating same-origin requests. An
initial integration test must therefore select and verify one supported token
lifecycle: either adapt the frontend to Symfony's stateless protocol, or issue
and validate a backend token through Symfony's supported stateful facilities.
The application must not implement an unreviewed custom token algorithm merely
to connect the two defaults.

Whichever supported lifecycle is selected:

- cookie and header names are configured explicitly on both sides;
- Angular sends the header only on mutating same-origin requests;
- Symfony validates the token and expected origin where applicable;
- login, logout and both authenticated mutation contexts are covered by
  integration tests.

The XSRF token is not the session identifier. The session cookie remains
`HttpOnly`. If the verified lifecycle uses a cookie/header pair, only the
dedicated XSRF cookie is readable by Angular.

CSRF validation applies to `POST`, `PUT`, `PATCH`, `DELETE` and any other method
that changes state. Safe `GET` and `HEAD` operations must not change state.

Login and logout receive explicit CSRF protection. A public operation without
ambient credentials is not made safe from automated abuse merely by adding a
CSRF token. Report submission will use origin validation where appropriate,
rate limiting and separately selected anti-automation controls; stateless CSRF
may be added as browser defence in depth.

Production will use one public origin for Angular and Symfony. Cross-origin
credentialed access is not enabled by default, and wildcard origins are never
compatible with credentialed private access.

## Anonymous follow-up boundary

An anonymous reporter is not represented as a professional user account.

Access to initial public report submission is a separate concern. A future
product decision may require a centre-specific shared code before showing or
accepting that centre's reporting form. Such a code would be a centre-routing
and anti-abuse control, not a professional identity, proof that the reporter
belongs to the centre, or an anonymous follow-up capability. This ADR does not
select that mechanism.

Ordinary public pages and initial report submission must not create a server
session or anonymous capability merely because they are visited. An anonymous
capability grant is issued only after successful verification.

To enter the private follow-up area, the reporter submits the public tracking
reference and separate access secret in a request body. Neither value is placed
in a URL.

The backend will:

1. apply rate limits before expensive verification;
2. locate the report through the public reference without revealing whether it
   exists;
3. verify the one-way representation of the high-entropy secret using a
   constant-time comparison appropriate to the selected token representation;
4. create a new, short-lived capability grant scoped only to the permitted
   follow-up operations for that report;
5. return the same safe failure representation for an unknown reference and an
   invalid secret.

The access secret is not reused as the ongoing credential. After verification,
the backend generates a new opaque high-entropy capability handle, returns it
only in a protected cookie and stores only a one-way representation of that
handle together with the minimum report scope, granted operations, creation and
expiry times, and revocation state required to authorise subsequent requests.

Capability records are technical security infrastructure rather than domain
aggregates. Expired or revoked records require deterministic cleanup, and a
database restoration must purge them so that an old backup cannot revive
anonymous access.

The capability handle is not a second Symfony or PHP session. A dedicated
anonymous authenticator verifies its one-way representation against the
server-side capability record on each follow-up request. The capability cookie
is distinct from the professional session cookie and follows the same `Secure`,
`HttpOnly`, `SameSite=Lax`, `Path=/` and host-only production rules.

Because both host cookies may be sent to the same origin, professional and
anonymous routes must have disjoint firewall or authenticator boundaries. The
professional firewall remains stateful through the Symfony session. The
anonymous follow-up boundary uses its dedicated capability authenticator and
does not rely on a second framework session. Each endpoint accepts only its
declared context: professional endpoints ignore an anonymous capability, and
anonymous follow-up endpoints do not infer staff authority from the presence
of a professional cookie. A separately designed staff endpoint may access a
report only through normal professional authorisation.

The anonymous and professional security contexts are therefore mutually
exclusive for an operation. The backend must never choose a context implicitly,
upgrade anonymous access into a professional session or use a capability to
traverse to another report.

The initial browser flow supports one active anonymous report capability at a
time. Successfully unlocking another report replaces and invalidates the
previous capability grant presented by that browser. Returning to the first
report requires its reference and access secret again. This prevents one cookie
from accumulating grants for several reports.

Initial anonymous capability-grant expiry targets are:

- 15 minutes of inactivity;
- 2 hours of total lifetime.

The reporter can explicitly close the private area, which invalidates the
server-side capability record and clears its cookie. Re-entry requires the
reference and access secret again.

Losing the access secret does not create a staff-controlled bypass. The optional
reporter notification email is not an account identifier and is not an access
recovery factor in the initial release.

The access secret and the newly issued capability handle must never appear in:

- application or reverse-proxy logs;
- analytics or error-reporting payloads;
- notification emails;
- URLs, browser history or referrer data;
- audit-event descriptions;
- frontend persistent storage.

## Invitation and account recovery

Professional invitation and password-reset flows will use random, single-use,
short-lived tokens. Invitations are sent only to an administrator-approved
professional address; password reset uses the account's previously verified
contact channel.

Only a one-way representation of each token will be stored. Token consumption
will be rate-limited and must not reveal whether an account exists.

The implementation must also define safe link transport before these flows are
enabled. Invitation and reset tokens must be excluded from application,
reverse-proxy and analytics logs, protected from third-party referrer leakage,
exchanged only once and removed from the browser-visible URL immediately after
the exchange. No page that handles an unconsumed token may load third-party
resources.

A password reset invalidates other active professional sessions. Security
questions and knowledge-based recovery will not be used.

The reporter's optional notification email must not be reused as a professional
account or recovery channel.

Email delivery, retry and provider selection remain separate infrastructure
concerns. An unavailable email provider must not create an insecure manual
recovery shortcut.

## Multi-factor authentication and federation

The public demonstration uses fictional data and will not claim a formal
authentication assurance level.

Before Convive processes real student or reporter personal data, the deployment
must provide multi-factor authentication for professional and administrator
accounts, or use an institutional identity provider that provides equivalent or
stronger assurance.

The exact factor may be selected later from confirmed deployable options such as
WebAuthn/passkeys, TOTP or institutional OpenID Connect. Recovery, factor reset
and administrator support procedures must be designed with the chosen factor.

SMS will not be assumed as the preferred second factor.

Federation does not replace Convive authorisation. External claims must be
mapped to an internal active account, organisation membership and permitted
roles before access is granted.

## Security events and privacy

Security-relevant events will be recorded without storing credentials or
unnecessary personal data.

Candidate events include:

- successful and failed professional authentication;
- login throttling;
- logout and session invalidation;
- password reset and credential change;
- account creation, invitation, disabling and role change;
- repeated failed anonymous capability verification;
- denied access to sensitive resources.

Logs and audit records must not include passwords, session identifiers,
capability handles, access secrets, reset tokens, CSRF tokens or complete
request bodies.

IP addresses and user-agent information are personal or potentially identifying
data. Their security purpose, retention and access must be defined before they
are retained beyond immediate rate-limiting needs.

## API behaviour

The API will use the Problem Details conventions selected in ADR-0006.

The OpenAPI contract will describe the professional-session cookie and the
anonymous-capability cookie as separate cookie security schemes and apply only
the expected scheme to each protected operation. Mutating cookie-authenticated
operations will also document the required XSRF header. Examples, logs and
generated documentation must use placeholders and never contain working
credentials or access secrets.

Protected endpoints return:

- `401 Unauthorized` when the required authentication or capability context is
  absent or no longer valid;
- `403 Forbidden` when an authenticated context exists but lacks permission for
  the operation;
- `404 Not Found` when a resource is absent or its existence must not be
  disclosed to that context.

Anonymous reference and secret verification returns a generic failure that does
not disclose which input was incorrect or whether the report exists.

The frontend may use route guards to improve navigation, but every backend
operation performs its own authentication and authorisation checks.

## Testing and verification

The first authentication implementation must verify:

- successful and failed staff login;
- password hashing and automatic rehash behaviour;
- login throttling and non-enumerating failures;
- shared-network throttling and resistance to attacker-induced permanent
  lockout;
- production cookie attributes;
- session renewal after authentication;
- idle and absolute expiry;
- logout, password-reset, disabled-account and security-role-change
  invalidation;
- CSRF rejection and acceptance for every state-changing authentication
  context;
- rejection of the wrong security context on professional and anonymous routes;
- denial of cross-organisation and unassigned-object access;
- anonymous reference and secret verification without enumeration;
- report-scoped capability isolation;
- replacement of an existing anonymous capability when another report is
  unlocked;
- absence of sessions or capability grants for ordinary public browsing and
  initial submission;
- rejection of expired or revoked capability handles;
- absence of credentials and secrets from logs and API errors.

Security tests will include attempts to reuse expired sessions, fix a session
identifier, access another report, bypass frontend guards, omit CSRF tokens and
repeat invalid credentials.

Automated tests will use fictional accounts, organisations and reports.

## Explicit exclusions

This decision does not define:

- the complete role and permission matrix;
- a shared or rotating centre code for access to public report submission;
- the complete anti-automation and abuse-prevention policy for the public
  reporting channel;
- the account, membership, session or capability-record table schema;
- a production institutional identity provider;
- an OpenID Connect claims contract;
- the final MFA or factor-recovery implementation;
- email-provider selection or delivery retries;
- the exact access-secret and capability-handle lengths and encodings;
- production encryption-key management;
- final security-log retention periods;
- a web application firewall or external denial-of-service service;
- authentication for future native or third-party API clients.

## Rationale

A first-party Angular application and Symfony backend under one origin do not
need browser-managed bearer tokens. Server-side sessions reduce token exposure
and keep invalidation under backend control.

PostgreSQL provides a shared session store without adding Redis to the first
deployment. Redis remains a valid future optimisation if measured load or
multiple application instances justify it.

Anonymous reporters need continuity without identity. A verified,
report-scoped opaque capability grant limits repeated exposure of the original
access secret while preventing it from becoming a general user account or a
second framework session.

Separating authentication from authorisation ensures that a valid login cannot
bypass organisation, assignment and action boundaries.

This approach uses maintained Symfony and Angular security mechanisms while
preserving a later path to institutional federation and stronger factors.

## Consequences

### Positive consequences

- Professional credentials remain outside JavaScript storage.
- Logout is immediate, and security-relevant account changes invalidate stale
  identities before their next protected operation.
- Anonymous reporting remains account-free.
- Anonymous follow-up is limited to one report.
- The access secret is not transmitted with every follow-up request.
- Angular and Symfony use a documented same-origin CSRF convention.
- PostgreSQL avoids an additional initial session infrastructure service.
- Organisation and object-level authorisation remain explicit backend duties.
- Password hashing and upgrades use maintained Symfony components.
- The design can later incorporate institutional OpenID Connect and MFA.

### Negative consequences

- PostgreSQL must store and clean up technical session rows and anonymous
  capability records.
- Cookie authentication requires correctly implemented CSRF protection.
- Two access contexts and protected cookies require careful authenticator and
  route separation.
- Server-side expiry and invalidation require more than browser cookie settings.
- Local accounts require invitation, reset and administrator procedures.
- Password blocklist selection and maintenance require implementation work.
- Real-data deployment remains blocked until MFA and related operational
  controls are implemented.
- An external identity provider will require a later integration decision and
  migration plan.

## Review triggers

Review this decision if:

- a supported institutional OpenID Connect provider becomes available;
- Convive introduces a native mobile application or third-party API client;
- multiple API instances make PostgreSQL session load unsuitable;
- measured load justifies Redis or another dedicated session store;
- the application moves away from a same-origin browser architecture;
- formal assurance requirements mandate a specific authentication method;
- passkeys become the practical primary staff authenticator;
- the anonymous capability model creates unacceptable usability or security
  problems;
- a real deployment changes the account-provisioning authority.

A material change from server-side browser sessions, or a change to the
anonymous capability boundary, requires a superseding ADR.

## Review evidence collected

The professional and anonymous contexts were checked against the product scope.
PostgreSQL session storage remains consistent with ADR-0005 and ADR-0007.
Current Symfony 7.4 and Angular documentation confirms the required session,
CSRF and configurable XSRF building blocks, while also showing that their
defaults require an explicit integration test rather than assumed
compatibility. Cookie, expiry, invalidation, account recovery and fictional-data
boundaries were reviewed. A final technical review identified that Symfony's
firewall contexts are separated within the framework session but do not by
themselves provide two independently named session cookies. The anonymous
boundary was therefore refined to use an opaque cookie capability backed by a
one-way server-side record and a dedicated authenticator, rather than a second
Symfony session. The project owner confirmed this refinement on 21 July 2026.
The ADR was then accepted and its dependent living documents were reconciled in
the same change.

## References

- [Symfony Security](https://symfony.com/doc/7.4/security.html)
- [Symfony Security configuration](https://symfony.com/doc/7.4/reference/configuration/security.html)
- [Symfony password hashing and verification](https://symfony.com/doc/7.4/security/passwords.html)
- [Symfony sessions](https://symfony.com/doc/7.4/session.html)
- [Symfony CSRF protection](https://symfony.com/doc/7.4/security/csrf.html)
- [Symfony Rate Limiter](https://symfony.com/doc/7.4/rate_limiter.html)
- [Angular security](https://angular.dev/best-practices/security)
- [OpenAPI 3.1 Security Scheme Object](https://spec.openapis.org/oas/v3.1.0.html#security-scheme-object)
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [OWASP Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)
- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [OWASP Cross-Site Request Forgery Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [OWASP Forgot Password Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html)
- [NIST SP 800-63B: Authentication and Authenticator Management](https://pages.nist.gov/800-63-4/sp800-63b.html)
- [PHP sodium password hashing](https://www.php.net/manual/en/function.sodium-crypto-pwhash-str-verify.php)
- [OpenID Connect Core 1.0](https://openid.net/specs/openid-connect-core-1_0.html)
