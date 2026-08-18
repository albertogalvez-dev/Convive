# How to reach each part of Convive

Written for someone arriving with no context — an evaluator, a school, a new
contributor. It answers four questions: what are the ways in, what is each
for, what can you try, and what deliberately does not exist.

Every address below was checked against `apps/web/src/app/app.routes.ts` and
`infrastructure/production/compose.production.yaml` rather than written from
memory.

## Two hosts, on purpose

Convive is served from **two separate domains**, and this is a deliberate
security boundary rather than an accident of hosting:

| | Production | Local development |
|---|---|---|
| Public website | `conviveaula.com` | `127.0.0.1:4200` |
| Application | `app.conviveaula.com` | `127.0.0.1:4200` |

In code the boundary is enforced by `publicWebsiteHostGuard` and
`applicationHostGuard` in `app.routes.ts`: a route belonging to one host
**does not exist** on the other, rather than redirecting. The reasoning is in
[ADR-0014](architecture/decisions/0014-separate-public-website-and-application-domains.md).

Locally both share one address, because a single dev server serves both route
trees. This is the one place where local and production differ in a way that
matters: a routing mistake that production would reject is reachable locally.

## The three ways in

### 1. The public website — `conviveaula.com`

Marketing, explanation and legal notices. No account, nothing to report here.

- `/` — home
- `/demostracion` and `/demostracion/profesional` — guided walkthroughs
- `/blog`, `/blog/:slug`
- The legal pages (demonstration notice, privacy, cookies, terms,
  accessibility), generated from one metadata list so they cannot drift apart

### 2. The reporting entry — `app.conviveaula.com`

Where a student reports. **No account, ever.** This is the surface the whole
product exists for, and it is reached per school:

| Route | Who it is for |
|---|---|
| `/r/:publicReportingIdentifier` | A student reporting something that happened **to them** |
| `/r/:publicReportingIdentifier/testigo` | Someone reporting something they **saw happen to another person** |
| `/seguimiento` | Returning to a report, using the access secret issued at the end |
| `/verificar-correo` | Confirming an optional email address for notifications |

`:publicReportingIdentifier` is the school's own code — the value a QR poster
in a corridor encodes. Two schools do not share a link.

The witness route is a **sibling route, not a flag** on the first-person one.
That is deliberate: the first-person URL keeps rendering exactly what it
rendered before the witness entry existed, and neither entry can be reached by
accident from the other.

There is no login because there is no account. Returning to a report uses the
secret issued when it was sent, which is why the result page insists on saving
it and says plainly that it cannot be recovered. The mechanism is
[ADR-0008](architecture/decisions/0008-use-server-side-sessions-and-capability-based-anonymous-access.md).

### 3. The professional area — `app.conviveaula.com/profesionales`

Where school staff work. This one does have accounts.

| Route | What it is |
|---|---|
| `/profesionales/acceso` | Sign in |
| `/profesionales/activar` | Accept a one-time credential, or set a new password after a reset |
| `/profesionales` | Dashboard |
| `/profesionales/comunicaciones`, `/comunicaciones/:id` | Incoming reports |
| `/profesionales/casos`, `/casos/:id` | Cases opened from reports |
| `/profesionales/avisos` | Notifications |
| `/profesionales/ajustes` | Settings |
| `/profesionales/cuentas` | **Account administration** |

## What deliberately does not exist

**There is no separate admin system.** Administration is a **role inside the
professional area**, and it lives at `/profesionales/cuentas`. Looking for an
admin panel and not finding one is not a gap — a school's coordinator is a
member of staff, not a different kind of user, and giving them a separate
system would mean a separate login, a separate session and a second place for
permissions to drift.

**A report does not become a case automatically.** The two are distinct: a
report is what someone sent, a case is what staff opened after reviewing it.
Nothing promotes one to the other without a person deciding.

**Convive is not an emergency channel** and, in the demonstration, reaches no
real school. Every public surface says so.

## Demo credentials

Fictional professional accounts **do exist** — two of them, a triage role and
an administrator role, created by `SeedFictionalDemo`.

**Their password is not in this repository and never will be.** It comes from
a secret mount at seed time, and the seeding command deliberately prints no
credentials at all. If you are looking for a username and password in the
source and finding none, that is the policy working, not an omission.

To obtain access to a running demonstration, ask the maintainer. The operating
procedure — seeding, rotating and recording access without writing credentials
into a log — is in
[fictional-demo-data.md](operations/fictional-demo-data.md).

All demonstration data is fictional. No real student, family or school
information is present, and none may be entered.

## Trying it locally

The README covers setup in full; the short version once the environment is
running at `127.0.0.1:4200`:

1. Load the fictional data (README, "Load fictional development data").
2. Open `/` for the public website.
3. Open `/r/<identifier>` for the reporting entry, using an identifier from
   the loaded fixtures.
4. Open `/profesionales/acceso` for the professional area, with a seeded
   account.

Locally both hosts share one address, so all three are reachable from the same
origin. In production they are not, by design.
