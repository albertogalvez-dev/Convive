# Convive SaaS 2.0 — Product Charter and Expectations Catalogue

Version 1.0 · 3 September 2026 · Source of record for issue #503.

---

## 1. Purpose, status and how this document changes

**Purpose.** Define the authorised Convive SaaS 2.0 product boundary *before*
implementation — journeys, value, measurable expectations, terminology and
non-goals — so that downstream work (#504–#539) implements a decided product
rather than an assumed one.

**Status.** This is the source of record for issue #503 and governs Convive
SaaS 2.0. It does not modify the delivered fictional demo or its documents. It
authorises no code, deployment, spending, provider or real data — each remains
gated by its own issue.

**Language.** This document is English (repository documentation) per the standing
language rule. The product surfaces it describes are Spanish-first; existing
supported locales are translated and reviewed as one release gate before a SaaS
surface is presented as fully published (INV-18).

**Approved decisions reproduced here.** D1 (product topology), D2 (centre-first
onboarding) and the language rule — approved by the owner on 3 September 2026 and
recorded in the comments of issue #503. Where this document and those comments
differ in wording, the comment text governs.

**Related documents.**

- `docs/discovery/problem-statement.md`, `docs/discovery/regulatory-context.md` —
  background, still valid.
- `docs/discovery/product-scope.md` — describes the **delivered fictional demo**;
  not edited by SaaS 2.0 work.
- `docs/governance/*` — controller/processing decisions, DPIA, retention, incident
  playbooks, email-delivery operations, pilot onboarding, vendor governance — the
  real-data-gate artifacts that #504, #505, #536, #537 and #538 build on.
- `LICENSE`, `NOTICE.md` — licence and attribution (§2.4).
- Tracking roadmap: issue #71.

**How this document changes.** By an owner-approved decision recorded on an issue,
then a versioned change here (§11). A `[rule]` is not weakened without a recorded
decision. An `[owner decision]` is filled in by the owner through DR-1, never by
implementation.

**Tags** (full definitions in §3.0): `[rule]` — non-negotiable, testable;
`[owner decision]` — concrete shape decided by the owner before build (DR-1).

**Using this charter to implement an issue.** Read, in order: §3 for the terms the
issue uses; the §10 row for that issue (its expectations, invariants and expected
evidence); each cited `R/T/C/P-*` entry in §6 and each cited `INV-*` in §5; and,
if the issue owns an `[owner decision]` surface, DR-1 and the required artifact.
The issue does not start coding until every input DR-1 names is on the issue.
`docs/product/saas-2.0-delivery-template.md` (#507) is the reusable,
fill-in-the-blanks form of this same process.

---

## 2. Product summary, value and scope

### 2.1 What Convive SaaS 2.0 is, and its value

A multi-tenant service for schools combining:

1. a safe, per-centre **public reporting channel** for suspected school bullying
   and related concerns; and
2. a **professional case-management workspace** for authorised school staff to
   assess, organise, act on and follow that work in one traceable place.

The value is the **connection between the two** — a concern raised safely and then
handled coherently, without fragmenting across paper, email and spreadsheets —
plus **sourced territorial guidance** in the professional's context, and a
**least-privilege, role-aware** workspace that never automates the professional's
judgement.

It is delivered as a year-long, expectation-and-evidence-driven sequence
(#503–#539), operated in sandbox with fictional data until an explicit real-data
gate (#538). It is not an immediate real-data launch and makes no safety,
compliance or adoption claim not backed by evidence.

**How it presents itself.** Convive SaaS 2.0 presents as a complete, real SaaS —
not a gated preview. Registration is self-service and gets the full product
immediately, operating on fictional sandbox data (D2). Moving a centre beyond
sandbox to real data is never a self-service toggle: it is a personal request to
the accountable operator, reviewed against the real-data gate (C-10, §4.2).

### 2.2 In scope for SaaS 2.0

- Individual verified accounts; centre workspaces with one accountable initial
  administrator; membership, invitation and least-privilege roles.
- Per-centre public entry and QR/poster materials; data-minimised reporter intake;
  optional verified reporter email for generic updates.
- Safe, bounded, case-scoped evidence handling.
- A reviewed professional dashboard, actionable queue and case workspace;
  source-aware tasks; communications; follow-up; audit.
- Applicable guidance for all 19 Spanish territorial jurisdictions (17
  autonomous communities plus Ceuta and Melilla), migrated from the delivered
  demo's already-verified sources (§13, 2026-09-03) with a currency review, not
  re-researched from zero.
- Tenant isolation, backup and tenant-aware recovery; privacy operations;
  security assurance; a controlled-pilot readiness review and an explicit
  go/no-go decision.

### 2.3 Out of scope for now (future lines, not non-goals)

Deferred, revisited only by a later owner decision — not prohibited:

- Locales beyond the currently supported set.
- Offline / PWA behaviour (a minimal service worker for web-push notifications is
  in scope; installable-offline behaviour is not).
- Case and aggregate exports (demo #49 is the reference if added).
- Any real-data pilot beyond a single tightly controlled first centre.

### 2.4 Constraints this charter operates within

- **Licence and attribution** — MIT; developed under the Aircury Summer of Code
  2026 programme with funding from Aircury SL, which does not co-own, operate or
  endorse the project (`LICENSE`, `NOTICE.md`).
- **Free-tier-only infrastructure** — every provider and tier is permanently free;
  no trial that converts to a bill; the free tier of an otherwise-paid service is
  acceptable only within its permanently-free allowance and only if it cannot
  silently convert. The accepted cost floor is the existing arrangement — one
  shared OVH VPS (already maintained for the grant) and one Cloudflare domain.
  SaaS 2.0 adds **no paid line**: object storage, transactional email and backups
  all use permanently-free EU tiers with a signed DPA (§9.1). Recorded in
  **ADR-0030** (CA-3, done).
- **Public availability** — a fictional demonstration remains continuously
  publicly available through at least August 2027, recorded in
  `docs/discovery/product-scope.md` §"Public demonstration environment" and
  cross-referenced in `NOTICE.md` (CA-2, done). Constrains §4.3.
- **Evaluation** — the project is judged on social impact.

These bound every decision in this charter and are not re-opened here.

---

## 3. Terminology (canonical glossary)

### 3.0 How to read this glossary

Terms are grouped 3.1–3.7. Where a term already appears in
`docs/discovery/product-scope.md` the meaning is kept and marked *(unchanged)*; it
is repeated so the charter stands alone.

Two tags appear throughout the charter:

- **`[rule]`** — a non-negotiable, testable constraint. Implementation chooses
  *how*, never *whether*.
- **`[owner decision]`** — the concrete *shape and behaviour* of this thing
  (layout, wording, flow, what appears where and in what order, what it does and
  does not do) is decided by the product owner and recorded in this charter or its
  linked issue. It is never produced as an implementation default, a
  framework-generated screen, or an automatic guess. Until it is decided,
  downstream work stops at a reviewable proposal and does not ship (DR-1).

Every user-facing surface is measured against one question: **can the person who
actually does this work — a tutor, a wellbeing coordinator, a student at a
corridor poster — use it without training?** Generic dashboards, metrics that only
fill space, and layouts justified by "the framework generates it" are non-goals
(§8) and satisfy no expectation in §6.

### 3.1 Product lines and environments

- **Convive** — the product family and brand, covering both the fictional demo and
  Convive SaaS 2.0.
- **Convive SaaS 2.0** — the next product line: individual accounts, centre
  workspaces, per-centre public entry, safe evidence handling, a professional
  workspace and a sourced territorial guidance experience. A year-long sequence,
  not an immediate real-data launch.
- **Stable fictional demo** — the delivered, publicly deployed, no-account, wholly
  fictional showcase of the reporter and professional journeys. Production
  baseline; receives only security, reliability, availability and
  factual-correction work `[rule]` (D1).
- **Replacement fictional demo** — a future demo presenting the full SaaS 2.0
  experience end-to-end with fictional data. Does not exist yet. Scope and owner
  settled in §4.3; the stable demo is never mutated into it or into the SaaS
  `[rule]`.
- **Operating contexts** — every Convive surface runs in exactly one:

  | Context | Who holds accounts | Data | Authorisation to operate |
  |---|---|---|---|
  | **Demo** | nobody (no accounts) | wholly fictional, single showcase surface | none needed; not multi-tenant |
  | **Sandbox operation** | real people, as individual accounts | safeguarding-domain data is fictional and labelled; only minimum necessary account data is real | ordinary privacy/security baseline; all SaaS 2.0 work happens here until #515 and #538 |
  | **Pilot** | real people, in real centres | real safeguarding-domain data | written go decision in #538 + owner approval + every gate in #504 evidenced |

- **`Sandbox` state vs sandbox operation** — related, not the same. During SaaS
  2.0 the whole service runs in *sandbox operation* and every centre is in the
  `Sandbox` *state* (§3.2). After #538, an `Activated` centre may leave sandbox
  operation while new centres still start in the `Sandbox` state.
- **Fictional data / labelled fictional data** — invented for demo or sandbox use
  and visibly marked as such in the interface; no real safeguarding-domain data.
  Sandbox fictional data is isolated per tenant and reset on a defined
  deterministic schedule (#515).
- **Real data** — any non-fictional data about identifiable people.
- **Safeguarding-domain data** — information about actual concerns, incidents,
  minors, families or protection situations involving real identifiable people.
- **Minimum necessary account data** — a real adult professional's name and work
  email, needed to operate an account. Sandbox operation may process this under an
  ordinary baseline; it may **not** process real safeguarding-domain data `[rule]`
  (D2).

### 3.2 Tenancy and centres

- **Tenant** — the technical isolation boundary. No tenant can discover, read,
  mutate, export or recover another tenant's data `[rule]` (#506, #510, #514).
- **Centre** — the real-world educational centre a tenant represents.
- **Centre workspace** — the product surface (dashboard, inbox, cases, members,
  settings, public entry, guidance) for one centre.
- One **tenant** ↔ one **centre** ↔ one **centre workspace**: the same object from
  the infrastructure, real-world and product angles. 1:1:1. The stable fictional
  demo is outside this model — a single-surface deployment, not a tenant.
- **Ownership bootstrap** — the atomic step that establishes exactly one
  accountable initial administrator when a centre workspace is created (#512).
- **Centre lifecycle states** — D2 is canonical; issue #512's earlier wording
  ("pending", "future pilot") is superseded. State identifiers are English; the
  Spanish labels a user sees are `[owner decision]` (§6.3).
  - **`Sandbox`** — default on creation. Fictional safeguarding-domain data only.
    No institutional responsibility asserted. Every centre is in this state during
    SaaS 2.0 development.
  - **`Activation under review`** — the centre has requested activation; the
    real-data gates are being evaluated.
  - **`Activated`** — all gates passed; institutional responsibility designated;
    may process real data within the approved pilot. No centre reaches `Activated`
    before the go decision in #538; afterwards, reaching it still requires that
    centre's own gate evidence (#537) `[rule]`.
  - Centre suspension, deactivation and closure states are **not** defined by this
    charter; #512 owns whether they are needed.

### 3.3 People and access

- **Account / staff identity** — one individual person's email-verified login,
  independent of any centre. One person, one account. *Practical picture:* a
  guidance counsellor signs up once with her work email and can later belong to
  more than one centre without a second account.
- **Membership** — the link between an account and a centre workspace, carrying
  exactly one role; independently scoped per centre.
- **Role** `[owner decision]` — a named set of least-privilege capabilities within
  one centre workspace. Roles are **not** job titles and confer no statutory
  power, institutional authority or automatic access to all cases `[rule]`. Which
  roles exist and exactly what each may do is grounded in real centre functions
  (§6.3, #508, #513); the demo's six functions (dirección; coordinación de
  bienestar y protección; orientación; tutoría; profesorado; administración y
  servicios) are the starting reference, not a fixed answer.
- **Least privilege** — an account, role or grant holds only the minimum
  capabilities needed for its purpose, and no more.
- **Initial administrator** — the account that creates a centre workspace; holds
  the administrator role there from creation via the ownership bootstrap. A
  product-configuration responsibility only — **not** the centre's legal
  representative, data controller or institutional spokesperson `[rule]`.
  Institutional responsibility is designated only at `Activated` (D2).
- **Authorised professional** — a member whose role and explicit case assignments
  let them do case work. *(Termed "authorised school professional" in
  `product-scope.md`; shortened because a sandbox professional operates a
  fictional centre.)*
- **Reporter** — a person who uses a centre's public entry to communicate a
  concern. Has no account and needs none `[rule]`. *Practical picture:* a
  14-year-old scans the poster QR, or types the printed URL, and describes what is
  happening to a classmate. *Anonymous* means no identity or account is required —
  **not** a legal guarantee the submission cannot identify anyone (free text,
  named people, attachments, optional email, technical identifiers may). The
  public notice states these limits. *(unchanged.)*
- **Data-protection roles** (controller, processor, subprocessor) — as defined in
  `docs/governance/controller-and-processing-decisions.md`; not redefined here.

### 3.4 Reporting, cases and the professional's day

- **Report** — an incoming communication describing a concern; may lack a stated
  identity, may be incomplete, may include optional evidence; not yet assessed;
  does not prove bullying; activates no protocol automatically `[rule]`.
  *(unchanged.)*
- **Case** — the internal professional workspace created or activated after an
  authorised assessment; the strict per-tenant boundary for assigned people,
  assessments, tasks, communications, evidence, decisions, monitoring and the
  audit trail. The report/case separation is a fundamental product rule `[rule]`.
  *(unchanged.)*
- **Dashboard** `[owner decision]` — the first screen an authorised professional
  sees on entering a centre workspace. It shows only what that person must act on
  and may act on, from real authorised data; never mock metrics, decorative
  charts, or centre-wide figures the person cannot access `[rule]`. **This is the
  screen that was previously generated automatically and did not match the owner's
  intent.** Its contents, sections and layout are decided in §6.4 before it is
  implemented (#508, #526).
- **Actionable queue / inbox** `[owner decision]` — the list of items awaiting
  *this* professional's action: a communication to assess, an overdue task, a
  follow-up now due. Distinct from the dashboard and from the case workspace.
  Grouping, ordering and filters decided in §6.4 (#526).
- **Case workspace** — the single place where all work on one case lives: state,
  assigned people, tasks, communications, evidence, follow-up, audit. Nothing
  about a case is created or edited outside it `[rule]` (#527).
- **Case state** `[owner decision]` — the lifecycle stage of a case. The demo set
  (`nueva comunicación`, `en valoración`, `plan de actuación`, `seguimiento`,
  `cerrado`, `archivado`) is the starting reference; conclusion or judgement
  labels such as `resuelto` or `sin fundamento` are prohibited `[rule]`.
  Transitions require appropriate assignment and are audited `[rule]`. Final set
  decided in §6.4 (#527).
- **Task** — a concrete action on a case: assigned person, due time, a stage from
  the applicable protocol, a kind, and a status of pending / completed /
  not-applicable. Convive never completes a task automatically or from elapsed
  time `[rule]` (#527).
- **Communication** — a recorded exchange linked to a case (public, family,
  internal). Recording it is not an assessment, diagnosis or protocol step, and
  applies no label automatically `[rule]`.
- **Follow-up** — a scheduled review of a case after actions are underway. It
  reminds a person; it never changes a case state or task status by itself
  `[rule]`.
- **Evidence** — a bounded item of supporting material attached to one specific
  report or case within one tenant. *Practical picture:* a tutor photographs a
  threatening note and attaches it; a student attaches a screenshot to their
  report. Convive provides **no** general-purpose file store, folder tree or
  document-sharing drive `[rule]`. Permitted types, size, retention, metadata,
  preview and deletion defined by #523 before any upload is built.
- **Audit trail / audit event** — an append-only record of who did what and when
  within a tenant, under that tenant's retention rules. Never contains report free
  text, evidence contents, credentials or session identifiers `[rule]` (#513,
  #524, #525, #527).
- **Public centre entry / reporting gateway** `[owner decision]` — the stable
  per-centre public destination where a reporter starts. Discloses the centre's
  approved identity and a clear non-emergency boundary, collects minimised data,
  never discloses another centre `[rule]`. Wording and steps decided in §6.1
  (#516, #518).
- **QR material** — a printable artifact whose only function is to route a person
  to a centre's public entry. Never authentication, never a secret, never the sole
  access method; a readable fallback URL and emergency guidance always accompany
  it `[rule]` (#516, #517, #522).

### 3.5 Guidance and sources

- **Regulatory source** — an official, citable document (law, instruction,
  protocol) with a stated authority, territory, version and publication date.
- **Territorial profile** — the curated, reviewed set of regulatory sources for
  one territory. **Decided (§13, 2026-09-03): all 19 Spanish jurisdictions** (17
  autonomous communities plus Ceuta and Melilla) **are in scope, migrated from
  the delivered demo's already-verified `WorkflowSourceVersion` sources** into
  the SaaS 2.0 registry (#530), not re-researched from scratch. Each source was
  read in full from the official gazette; 8 of the original 19 corrected real
  research errors (superseded documents, misread deadlines) before being merged.
  Migration adds only a **currency review per jurisdiction** — confirming the
  source is still in force — under the same quarterly-plus-material-change
  cadence as the rest of the territorial model `[rule]` (#529, #530, #532, CA-7).
  A territory's authority (binding / recommended / internal) is preserved
  exactly as verified; it is never upgraded by assumption.
- **Applicable guidance** `[owner decision]` — the in-product presentation of
  relevant regulatory sources for a professional's territory and context.
  Informational only; discloses source, territory and review status; never a
  binding determination, legal advice or a universal deadline `[rule]`. *Practical
  picture:* while assessing a case, the coordinator sees "Protocolo de Andalucía
  2011 — sección acoso — revisado 2026-06" with a link to the source, not a
  countdown telling her she has 48 hours. Placement and form decided in §6.4
  (#531).
- **Protocol** — a specific procedure defined by a regulatory source. Convive
  references it; it never selects, activates or executes a protocol automatically
  `[rule]`.

### 3.6 Governance and gates

- **Real-data / readiness gate** — the verifiable go/no-go conditions
  (controller/processor roles, DPO and legal review, DPIA/risk analysis, Article
  28 agreement, subprocessors, incident response) evidenced before any real data
  or real-centre pilot. Defined in #504; decided in #538; a missing gate blocks
  pilot use — sandbox operation continues regardless.
- **Subprocessor** — an external service that would process data on Convive's
  behalf (email delivery, malware scanning, backup storage). Model and selection
  decided in #505 under the constraints in §9.

### 3.7 Document conventions

- **Expectation** — an observable outcome in the §6 catalogue, written as positive
  and negative Given/When/Then. Verifiable by inspection or test, without
  production analytics or user tracking. Each expectation is tagged `[rule]`,
  `[owner decision]`, or both. Section A of
  `docs/product/saas-2.0-delivery-template.md` (#507) generalises this format
  into a reusable per-issue template.
- **Invariant** (`INV-*`) — a property the system must always uphold, stated in §5
  and cited by the expectations that depend on it.
- **`[owner decision]` surface set** — the bounded set DR-1 governs. As of this
  version: `R-4`, `R-8`, `R-9` · `T-1`, `T-9`, `T-12`, `T-15` · `C-1`, `C-3`,
  `C-4`, `C-5`, `C-12` · `P-1`, `P-2`, `P-5`, `P-12`, `P-15`, `P-20` (18). `C-9`
  and `P-8` were resolved to fixed `[rule]`s during the Bloque 2 walkthrough
  (2026-09-03) and dropped from this set. §10 is the authoritative list; DR-1's
  "unanticipated decisions" clause adds to it.

---

## 4. Product model

Convive SaaS 2.0 rests on two approved decisions — D1 and D2 — recorded in issue
#503 and reproduced here.

### 4.1 Two product lines: the stable fictional demo and SaaS 2.0 (D1)

> **D1 (approved, 3 September 2026).** Convive is a SaaS for schools accompanied by
> a public fictional demonstration of the eventual product. The future demo will
> present the complete future dashboard, QR journey and a fictional school
> end-to-end, but must never register real schools or process real data. It and
> the SaaS may share reviewed code and visual direction, but require separate
> deployments, configuration, accounts, data, attachments, backups and email
> boundaries. The present delivered demo remains the stable fictional baseline
> only until that replacement demo exists; it is then retired deliberately, not
> mutated into the SaaS.

The two lines share **only** reviewed source code and visual direction. Every
operational boundary is separate `[rule]` (INV-16):

| Boundary | Stable fictional demo | Convive SaaS 2.0 |
|---|---|---|
| Deployment | existing isolated `px-convive-*` project on the shared OVH VPS (`conviveaula.com` / `app.conviveaula.com`) | isolated `px-convive-saas-*` project on the same VPS, separate from the demo; minimal footprint (§4.4) — #506, #509 |
| Configuration & feature flags | its own; unchanged except maintenance | its own — #509 |
| Accounts | none exist | individual accounts + per-centre memberships — #511–#513 |
| Data | one wholly fictional dataset | per-tenant; fictional under sandbox operation until #538 — #514, #515 |
| Attachments / evidence | demo's existing bounded fictional attachments | external EU object storage (candidate Scaleway); DB stores references only; no large media on the VPS — #524, #525 |
| Backups & recovery | existing encrypted EU R2 repository | separate repository + tenant-aware recovery — #535 |
| Email | existing `privacy@` alias only; no reporter mail | external EU transactional-email subprocessor (candidate Brevo, free tier + DPA); professional email day-one, reporter email per #519/#520 — §9 |
| Domain | `conviveaula.com` | separate domain/subdomain — #509 |

The stable demo receives **only** security, reliability, availability and
factual-correction changes `[rule]`. Feature work, schema growth for SaaS
concepts and onboarding never land in it. `docs/discovery/product-scope.md`
§"Public demonstration environment" describes this current demo.

### 4.2 Centre-first onboarding (D2)

> **D2 (approved, 3 September 2026).** Convive is organised around independent
> centre workspaces. Any education professional may create an individual account
> and a centre sandbox, where the dashboard, QR journey and cases use fictional
> data. The creator is the initial centre-workspace administrator, not by
> implication a legal or institutional representative. A centre is visibly
> Sandbox, Activation under review or Activated; only the later activation can
> designate institutional responsibility and pass the separately documented
> real-data gates. The public demo has no accounts and remains wholly fictional. A
> SaaS sandbox may process the minimum necessary adult account data, but never
> real safeguarding-domain data.

**Account and centre.** A person creates one email-verified account, independent
of any centre (§3.3). From that account they create a centre workspace; the
ownership bootstrap (#512) makes that account the sole initial administrator
atomically. One account may create or join several centre workspaces; each
membership is independently scoped.

**What the initial administrator is and is not.** They configure the workspace and
manage its members and roles. By that act alone they do **not** bind any
institution, become a data controller, or speak for the centre `[rule]`.
Institutional responsibility attaches only when the centre reaches `Activated`.

**Centre lifecycle.** `Sandbox` ⇄ `Activation under review` → `Activated`. A
withdrawn or failed activation returns the centre to `Sandbox`; `Activated` is
terminal for now. No centre reaches `Activated` before the go decision in #538,
and afterwards only with that centre's own gate evidence (#537) `[rule]`.
Requesting activation is a personal request to the accountable operator, never a
self-service toggle (C-10). Suspension, deactivation and closure states are not
defined here — #512 owns the complete state model.

**What sandbox operation may process.** Minimum necessary adult account data (a
professional's name and work email) under an ordinary privacy/security baseline.
Never real safeguarding-domain data; every report, case, QR and guidance
interaction uses labelled fictional data `[rule]` (§3.1).

### 4.3 Lifecycle of the current demo and the replacement demo

**The current demo is not retired on a date.** It stays deployed, maintenance-only,
throughout SaaS 2.0 development. Per §2.4, at least one fictional demonstration is
continuously publicly available through at least August 2027 `[rule]`.

**Retirement condition.** The current demo is retired only when a **replacement
fictional demo** exists, is deployed and is verified, **and** retiring it does not
break the continuous-availability constraint. Retirement is a deliberate,
owner-approved step — never automatic, never a side effect of reaching a SaaS
milestone `[rule]`.

**The replacement fictional demo** presents the SaaS 2.0 experience — dashboard,
QR journey, a fictional school end-to-end — reusing SaaS 2.0 reviewed code and
visual direction but running in the **Demo** operating context (§3.1): no
accounts, no onboarding, no account creation, no real email, one wholly fictional
showcase centre. It is **not** a tenant and **not** sandbox operation. Once it
exists, it carries a visible *"reflects the product as of YYYY-MM-DD; it may have
changed since"* label, is rebuilt on any material user-visible change to a surface
or journey, and is reviewed for representativeness at least quarterly.

**No issue owns building it (gap G1).** A new issue is added to milestone #20
(SaaS 5, fictional track): *"Specify the replacement fictional demo and the
current-demo retirement runbook."* It delivers the specification — what it shows,
the Demo-context deployment shape, the verification checklist, the two-phase
retirement runbook (A: replacement verified in production; B: retire the current
demo — redirect, ephemeral-content and 30-day-record handling, Search Console /
Bing, the `privacy@` alias, public communication), and the trigger conditions.
Its **execution is a separate owner go decision**, taken when a trigger fires
(SaaS surfaces materially ahead of the current demo *and* a reason to show them
publicly). It depends on #522, #528 and #531; it is not gated on #538; it blocks
#539. Owner: the product owner.

### 4.4 Infrastructure footprint and portability

**Decided (§13, 2026-09-03).** The shared OVH VPS is personal infrastructure
maintained for the grant. Convive must not grow into it and must be able to leave
it cleanly.

- **Minimal footprint on the VPS.** Only the SaaS 2.0 application core runs there
  — API, gateway, database, cache. Target: on the order of 1 GB of RAM and a few
  GB of disk added, inside the existing PROJECTX per-project isolation.
- **Everything heavy is external and EU.** All evidence, attachments, audio and
  video go to external EU object storage (§9.1); email is an external EU
  subprocessor (§9.1); backups are off-host. No large media and no third-party
  runtime is placed on the VPS disk.
- **Portable by design.** Fully containerised, external storage and email,
  per-project edge/internal networks. Moving Convive SaaS 2.0 off the VPS is
  relocating a small container set plus re-pointing DNS — not a rewrite.

This binds #506 (isolation architecture) and #509 (environments).

---

## 5. Cross-cutting invariants

Properties the system must always uphold. Every expectation in §6 cites the
invariants it depends on; every implementing issue (#510–#535) shows its evidence
against them.

**Violating an invariant is a release-blocking defect**, unless the invariant
scopes otherwise (INV-17).

**Data minimisation** underlies the whole set: every surface collects only the
data its stated purpose requires and never repurposes it. Its sharply checkable
parts are INV-12 and INV-13; the principle applies everywhere regardless.

IDs run 1–18 in the group order below and are stable — issues and expectations
reference them by number.

### A. Isolation and access

**INV-1 — Tenant isolation.** No tenant can discover, read, mutate, export or
recover another tenant's data through any path: API, URL or identifier guessing,
session, membership, attachment, export, search, backup or recovery. Cross-tenant
access is denied by default and proven by negative tests. *(#506, #510, #514,
#535)*

**INV-2 — Least privilege.** Every account, role, membership and grant holds only
the capabilities its purpose requires. No role confers automatic access to all
cases; case access is granted per explicit assignment. Administrative capability
over a centre's configuration and members does not by itself grant access to case
content. *(#513, #526, #527)*

**INV-3 — Session and credential hygiene.** Authentication requires a verified
email. Credentials, tokens and secrets never appear in the UI, logs, URLs, error
messages or audit records. A session is invalidated on password change, role
change, membership revocation or suspension, and on account recovery. *(#511,
#513, #514)*

### B. No automation of professional judgement

**INV-4 — No automated safeguarding judgement.** Convive never produces a
diagnosis, risk score, credibility or severity assessment, protocol selection,
conclusion label (`resuelto`, `sin fundamento` or equivalent) or any automated
decision about a person or a reported situation. It records, organises, links and
reminds; people assess and decide. *(#527, #531)*

**INV-5 — Human-only progression.** No case-state change, task completion,
follow-up outcome or communication completion happens automatically, on a
schedule, or from elapsed time. Each requires an appropriately assigned person and
is recorded in the audit trail. "Overdue" may be *derived* from a due time but
changes nothing by itself. *(#527)*

**INV-6 — Report and case separation.** A report is not a case. A case exists only
after an authorised human assessment. Submitting or receiving a report activates
no protocol, assigns no responsibility and applies no label automatically. *(#518,
#526, #527; `docs/discovery/product-scope.md`)*

### C. Public entry and reporters

**INV-7 — QR and public links are routing, never authentication.** A QR code or
public link only routes a person to a centre's public entry. It is never a secret,
a credential or the sole way to reach the entry; a human-readable fallback URL and
non-emergency guidance always accompany it. Revoking or rotating a public link
never depends on the QR having been secret. *(#516, #517, #522)*

**INV-8 — Out-of-app notifications are generic; contact data is isolated.** Any
notification Convive sends outside the application — email or web push, to a
reporter or a professional — says only that there is something to review: no case
detail, no names, no credential, no link that bypasses secure access. Push
payloads are end-to-end encrypted; their transit through the browser vendor's push
service is recorded in the DPIA (#504). Notification endpoints (email address,
push subscription) are stored apart from ordinary staff visibility, are opt-in and
reversible, and are never an access-recovery mechanism. A reporter needs no
endpoint to use the service; a professional's account email is the minimum
necessary account data (D2). *(#511, #519, #520, #526)*

**INV-9 — Non-emergency boundary.** Every public entry and reporter-facing surface
states, visibly and before submission, that Convive is not an emergency channel
and points to 112. Convive never triages urgency, never promises monitoring or a
response time, and never represents itself as an emergency or crisis service. A
centre administrator cannot remove, weaken or hide this notice. *(#516, #518,
#522)*

### D. Guidance

**INV-10 — Sourced guidance, honest deadlines.** Applicable guidance, and every
deadline, countdown or time-remaining indicator Convive shows, states where it
comes from: a named reviewed regulatory source (with territory, version and review
status), or an explicit human decision. Convive shows no deadline it cannot
attribute to one of those, presents none as universal legal fact or as its own
legal determination, acts on none automatically (INV-5), and lets none imply it is
handling an emergency (INV-9). A source that is unreviewed, superseded or
unavailable is withdrawn or clearly marked pending review — never shown as
current. Within these limits, deadlines and overdue indicators are shown wherever
they help a professional manage the work. *(#527, #529, #530, #531, #532, #533)*

### E. Data: fictional, minimal, not silently retained

**INV-11 — No real safeguarding data before the written gate.** Under sandbox
operation, all safeguarding-domain data is fictional and labelled as such in the
interface. Only minimum necessary adult account data is real. No centre reaches
`Activated`, and no real safeguarding-domain data is processed, before the written
go decision in #538 and that centre's own gate evidence (#537). *(D2, #504, #515,
#537, #538)*

**INV-12 — Minimal audit records.** Audit trails are append-only, scoped to one
tenant and retained under that tenant's retention rules. They never contain report
free text, evidence contents, credentials or session identifiers — only actor,
action, subject reference and time. *(#513, #524, #525, #527)*

**INV-13 — Evidence is bounded, case-scoped and fail-closed.** Every piece of
evidence — document, image, audio or video — is attached to exactly one report or
case within one tenant. There is no general-purpose file store, folder tree or
document drive. An upload is private and quarantined until it passes scanning;
before that it has no access path and no public object URL. Audio and video are
played back application-mediated, the same way as any other evidence — never
transcribed, and never processed by any AI or machine-learning model: the
professionals who review evidence listen to and watch it directly, so automated
transcription or analysis adds no value and is not built (decided §13,
2026-09-03). Permitted types, size and handling follow the policy in #523.
*(#523, #524, #525)*

**INV-14 — Nothing is retained silently.** Data is retained only for a stated
purpose and period. When the period lapses, or a permitted deletion is requested,
removal is real — not a hidden flag or an orphaned copy left in backups beyond the
stated window. The sandbox seed and reset schedule is deterministic and disclosed.
*(#515, #536)*

### F. No hidden behaviour

**INV-15 — No hidden tracking.** No third-party analytics, advertising or
non-essential tracking runs on any surface, public or authenticated. Observability
is privacy-minimised: it never logs sensitive content and never profiles
reporters. Only strictly necessary cookies are used, and they are disclosed.
*(#521)*

### G. Product-line separation

**INV-16 — Demo and SaaS are separate at runtime.** The stable fictional demo and
Convive SaaS 2.0 run as separate deployments with separate configuration,
accounts, data stores, attachment stores, backups, email boundaries and domains.
They share no datastore. They share only reviewed source code and visual
direction. *(D1, #506, #509)*

### H. Accessibility and language

**INV-17 — The accessibility baseline does not regress.** Every SaaS user-facing
surface meets at least the delivered demo's documented accessibility baseline —
`docs/testing/accessibility.md` (WCAG 2 A / 2 AA / 2.1 AA automated rules, full
keyboard operation, screen-reader labelling) and `docs/content/plain-language-
standard.md`, with WCAG 2.2 AA as an objective, not a conformance claim. A
regression below that baseline on a critical journey blocks release; a minor issue
on a non-critical surface is tracked and remediated separately. *(#522, #528)*

**INV-18 — Language completeness gate.** A SaaS surface may operate in sandbox in
Spanish first, but it is not labelled or announced as fully published in any
locale until every supported locale is translated and reviewed together as one
release gate. A partial or machine-draft translation is never presented as
complete. Enforced through section C of
`docs/product/saas-2.0-delivery-template.md` (#507). *(language rule, #507)*

---

## DR-1 — Delivery rule: the owner decides the shape and behaviour before it is built

`[rule]`

**Origin.** The professional dashboard was previously produced autonomously and
did not match the owner's intent. DR-1 exists so that cannot recur. This section
is the authoritative rule; section B of `docs/product/saas-2.0-delivery-template.md`
(#507) is its fill-in-the-blanks checklist form for use on an issue. It is
placed here, immediately after the invariants, because it governs how every
`[owner decision]` surface reaches implementation.

**Scope.** The surfaces and flows tagged `[owner decision]` in §3 and §6 — a
bounded set (18 today), listed with their owning issues in §10. Routine UI detail
is out of scope and follows the `[rule]` invariants in §5 and ordinary review.

**Inputs recorded on the implementing issue before any code:**

1. **Owner intent statement** — the owner's direction in the owner's words,
   covering both what the surface *looks like* and what it *does and does not do*
   (what data it shows, where each action leads, the empty and error states). It
   may be a finished picture or "here are the constraints, propose two or three
   options and I choose." For any surface with a counterpart in the delivered demo
   it says concretely what to **keep**, **change**, **drop**. A generic
   implementation may not proceed while this is absent.
2. **Approved artifact** — a concrete representation built from (1): wireframe,
   hand sketch, marked-up demo screenshot, or — for text-only surfaces — the
   literal strings in order. Where options were proposed, the owner picks one,
   annotates it, and the issue records in one line why the others were set aside.
3. **Owner approval** — an explicit comment from the owner account on the issue
   approving the artifact with any changes. A reaction or unrecorded chat approval
   does not count.

**Completeness bar.** The artifact is sufficient when someone other than its
author could build the surface without guessing any owner-facing choice. If they
would have to guess, it is not ready.

**Checkpoints.**

- **Proposal** — the owner reviews the artifact before any code.
- **First working version** — the owner *uses* the surface running with fictional
  data in sandbox operation (opens it, clicks through it), not just views a
  screenshot, before the issue closes and before any dependent "validate" issue
  (#522, #528, …) runs. This review is the first of the two iteration passes
  already run per piece, not an extra step.
- Divergence from the approved artifact is corrected before close, or the artifact
  is revised with fresh owner approval. Silent drift is release-blocking.

**Unanticipated decisions.** If implementation reaches a shape or behaviour choice
that no approved artifact covers and no `[rule]` dictates, work pauses and the
choice goes to the owner — it is never resolved by implementer default. The new
decision point is added to §3/§10 so the tagged set stays complete.

**Enforcement.** An `[owner decision]` issue does not merge until the owner has
confirmed on the issue that the built surface matches the approved artifact. This
rides the existing issue → branch → PR → checks workflow.

---

## 6. Expectations catalogue

### 6.0 How to read the catalogue

- Each entry has an ID (`R-1`, `T-1`, …), a short title, a **positive** expectation
  (Given/When/Then — what must happen) and one or more **negative** expectations
  (what must not happen, or must fail safely).
- Each entry is tagged `[rule]`, `[owner decision]`, or both, lists the `INV-*` it
  depends on and the issue(s) that implement it.
- Expectations are observable outcomes, verifiable by inspection or test without
  production analytics or user tracking.
- An `[owner decision]` entry does not ship until DR-1 is satisfied (owner intent
  statement + approved artifact).
- `docs/product/saas-2.0-delivery-template.md` (#507) turns this format into
  the reusable per-issue template.
- Fiction-framing appears once per journey (`R-12`, `T-17`, `C-13`, `P-18`), each
  citing INV-11 and INV-16; the accessibility-and-language sweep appears once per
  journey (`R-13`, `T-18`, `C-17`, `P-19`), each citing INV-17 and INV-18.
- The stitched first-run path (sign up → verify → empty state → create centre →
  empty dashboard → configure public entry → invite colleagues) is described by
  #508, not here.
- **Shared app shell (owner intent, §13, 2026-09-03).** A persistent header/nav
  — account settings, sign out, help, the centre selector (T-15), and direct
  navigation to the dashboard (P-1) and the pending-actions screen (P-2) — is
  shared across every authenticated teacher/professional screen. Each screen's
  own `[owner decision]` scope is its **content area only**; chrome already
  covered by the shell is not re-decided per surface. This is what makes
  "everything follows the same style" real, more than repeating colours per
  screen.

### 6.1 Reporter journey (`R-*`)

A person — often a student — uses a centre's public entry to communicate a
concern, without an account. The delivered demo already has this journey
(`product-scope.md` §1–§3); here it is adapted to multi-tenant.

**R-1 — Reaching a centre's public entry** · `[rule]` · INV-7, INV-1 · #516, #518

- **+** GIVEN a centre with an approved public entry, WHEN a person opens the
  entry's URL (typed or via its QR), THEN it loads showing that centre's approved
  identity and no other centre's. The entry is reachable whether the centre is
  `Sandbox` or `Activated`; the sandbox framing is governed by R-12.
- **−** GIVEN the centre's public link, WHEN the QR image is unavailable or cannot
  be scanned, THEN the same entry is reached by typing the human-readable URL; the
  QR is never the only way in.
- **−** WHEN a person alters the URL to guess another centre's identifier, THEN
  they get only a generic not-found response that confirms or reveals no other
  centre.

**R-2 — Non-emergency boundary before use** · `[rule]` · INV-9 · #516, #518, #522

- **+** GIVEN the public entry, WHEN it loads, THEN a clearly visible notice states
  Convive is not an emergency channel and directs to 112, before any input field.
- **−** WHEN a person submits a report, THEN Convive never shows a response-time
  promise, urgency rating, or wording implying monitoring or emergency handling.
- **−** WHEN a centre configures its entry, THEN a centre administrator cannot
  remove, weaken or hide this notice; it is standard across all centres, not
  centre-editable.

**R-3 — Reporting without an account or identity** · `[rule]` · INV-6, INV-8 · #518

- **+** GIVEN the public entry, WHEN a person writes and submits a report, THEN it
  is accepted with no account, no login and no required identity field.
- **−** WHEN a person leaves every optional identity/contact field blank, THEN
  submission still succeeds and they are not asked to authenticate at any point.

**R-4 — Plain language and structure** · `[owner decision]` (draft copy decided
§13, 2026-09-03; final artifact at #518) + `[rule]` · INV-17, INV-18 · #518, #522

- **+** GIVEN the public entry, WHEN a person reads it, THEN instructions, labels
  and help are in plain language appropriate for a secondary-school student. The
  draft flow and copy, refined into a real design artifact at #518:

  1. *"Estás en un lugar seguro para contar lo que te preocupa. No hace falta que
     digas quién eres. Convive no es un canal de emergencia: si hay peligro
     inmediato, llama al 112."*
  2. *"Cuéntanos qué está pasando"* — free text: *"Puedes escribirlo con tus
     palabras, no hace falta que sepas cómo se llama lo que ha pasado."*
  3. *"¿Tienes algo que enseñarnos? (opcional)"* — photo, audio, document.
  4. *"¿Quieres contarnos algo más? (opcional)"* — situation type, date, minimal
     people references.
  5. *"¿Quieres que te avisemos si hay novedades? (opcional)"* — email, explained,
     never required.
  6. Submit → confirmation (R-9).

  Anonymity is reassured **first**, before the safety notice, because it is the
  reason the channel exists; evidence follows the main text immediately, as its
  natural continuation, rather than being a separate step at the end.
- **−** WHEN the entry is presented, THEN it shows no legal jargon, no unexplained
  internal terms (`case`, `triage`, `tenant`), and no field whose purpose is not
  stated.

**R-5 — Neutral optional details, no forced classification** · `[rule]` · INV-4,
INV-6 · #518

- **+** GIVEN the report form, WHEN a person optionally selects a neutral concern
  area from the product's defined set and when it happened, THEN these are recorded
  as neutral reporter-provided context only. *(The concern-area set is an owner
  decision, settled in #523/#527.)*
- **−** WHEN a person provides or omits these details, THEN Convive assigns no risk
  level, credibility, severity or protocol, and shows the reporter no automated
  assessment of their report.

**R-6 — Minimised references to people** · `[rule]` · data minimisation (§5),
INV-15 · #518

- **+** GIVEN the report form, WHEN a person names or refers to people involved,
  THEN the form guides them to give only what is necessary and records those
  references minimised to the case.
- **−** WHEN a report is stored, THEN no reporter-facing analytics, enrichment or
  external lookup is run on the names it contains.

**R-7 — Optional evidence, bounded and safe** · `[rule]` · INV-13 · #523, #524,
#525

- **+** GIVEN the report form, WHEN a person attaches evidence within the permitted
  types and size (#523) — which may include documents, images, audio or video —
  THEN it is accepted, held privately and quarantined until it passes scanning,
  and attached only to this report.
- **−** WHEN an attachment is pending or fails scanning, THEN it has no access
  path, no preview and no public URL, and the reporter sees a clear, non-technical
  status.
- **−** WHEN a person tries a disallowed type or an oversize file, THEN the entry
  refuses it with a plain-language explanation and the rest of the report is
  preserved.

**R-8 — Informed choice about optional email** · `[owner decision]` (how the ask
is presented) + `[rule]` · INV-8 · #519, #520

- **+** GIVEN the report form, WHEN a person chooses to give an email for updates,
  THEN the entry first explains, in plain language, that this makes them reachable
  and is not required, and the email is verified before any notification is sent.
  The ask is designed to be genuinely persuasive — clear benefit, professional
  presentation — never to pressure; opting out never degrades the rest of the
  journey.
- **−** WHEN a person gives an email, THEN it is stored apart from report content
  and from ordinary staff visibility, is never used for access recovery, and can
  be removed later by the reporter.
- **−** WHEN a notification is later sent, THEN it contains no case detail, no
  names and no direct case link that bypasses the secure channel — only that an
  update is available.

**R-9 — Confirmation and a secure way back, no account** · `[rule]` +
`[owner decision]` (draft copy decided §13, 2026-09-03; final artifact at #518) ·
INV-8 · #518

- **+** GIVEN a submitted report, WHEN submission completes, THEN the reporter sees
  a plain confirmation that the report reached the centre, and is given a single
  secure means to return, with clear guidance to keep it safe; no account is
  created. *(The return mechanism — the demo's printable access receipt, ADR-0016
  — is the starting reference; its exact form for SaaS is settled in #519/#518.)*
  Draft confirmation copy:

  *"Tu mensaje ha llegado al centro y alguien lo va a leer. Este es tu código
  para volver a consultar el estado o añadir más información más adelante:
  [CÓDIGO]. Puedes hacerle una foto a esta pantalla, guardarla o imprimirla — es
  la única forma de volver a entrar, porque no hemos creado ninguna cuenta.
  Guárdalo en un sitio donde lo vayas a encontrar: si lo pierdes, no podemos
  dártelo de nuevo por email ni por ningún otro medio."*

  It confirms a human will read it, restates that the same code lets them add
  more information later (R-10), gives practical ways to keep it (photo, print),
  and ends on a practical tip rather than a bare warning.
- **−** WHEN the reporter loses the secure means, THEN Convive offers no
  email/identity-based recovery that would weaken anonymity; this limit is stated
  up front.

**R-10 — Following up through the secure channel** · `[rule]` · INV-6, INV-2 · #518

- **+** GIVEN a reporter with their secure means, WHEN they return, THEN they see
  only the entries a professional has explicitly marked reporter-visible, and can
  add further information to the same report.
- **−** WHEN a reporter returns, THEN they never see internal notes, professional
  assessments, other reports, other centres, or any case-management surface.
- **−** WHEN a reporter adds information, THEN there is no reporter-facing deletion
  that removes the centre's received record; corrections are additive.

**R-11 — Abuse resistance without tracking** · `[rule]` · INV-15 · #521

- **+** GIVEN the public entry, WHEN it receives abusive volume from a source, THEN
  rate limiting and safe failure protect the service and legitimate reporters
  still get a clear retry path.
- **−** WHEN anti-abuse controls run, THEN they use no third-party tracking, store
  no reporter profile, and log no report content; false-positive and bypass paths
  are tested.

**R-12 — Fictional and labelled under sandbox operation** · `[rule]` · INV-11,
INV-16 · #515

- **+** GIVEN sandbox operation, WHEN a person uses any centre's public entry, THEN
  the surface is visibly marked fictional/practice and the safety notice against
  real personal data is shown and accepted before the first submission, and shown
  again after a material change to its conditions.
- **−** WHEN sandbox operation is active, THEN real reporter-notification email is
  sent only to an address that already belongs to a verified member of that
  centre — never to an arbitrary public address (§9.2; professional account email
  is separate and decided), and submitted content follows the disclosed sandbox
  reset schedule.

**R-13 — Accessibility of the whole journey** · `[rule]` · INV-17 · #522, #528

- **+** GIVEN the public entry and follow-up, WHEN used with keyboard only, a
  screen reader, or at mobile width, THEN every step — arrival, writing,
  attaching, submitting, returning — is operable and understandable, at least at
  the demo baseline.
- **−** WHEN a person relies on assistive technology, THEN no step depends on the
  QR image, colour alone, a timed interaction, or an unlabelled control.

**R-14 — Public entry lifecycle is safe for reporters** · `[rule]` · INV-7 · #516

- **+** GIVEN a centre rotates or revokes its public link, WHEN a person uses the
  old link, THEN they get a clear, centre-neutral message and, where the centre
  allows, a route to the current entry — never a silent failure or a wrong centre.
- **−** WHEN a link is revoked, THEN it grants no residual access and reveals
  nothing about the centre or any report previously made through it.

**R-15 — Language choice at the entry** · `[rule]` · INV-18 · #522

- **+** GIVEN the public entry, WHEN a person opens it, THEN it is available in the
  centre's configured locales and they can switch language at any step.
- **−** WHEN a locale is incomplete, THEN it is not offered as complete or
  presented as fully published.

**R-16 — Privacy of the entry itself** · `[rule]` · INV-15 · #521

- **+** GIVEN the public entry, WHEN it loads in the reporter's browser, THEN it
  loads no third-party resources that could observe that person (analytics,
  external fonts/scripts, pixels), and uses only strictly necessary, disclosed
  cookies.
- **−** WHEN the reporter navigates away from the entry, THEN no referrer leaks
  that they made a report or from which centre.

### 6.2 Teacher journey (`T-*`)

By D2, "teacher" is the **individual-account** journey — the first user. Any
education professional creates an account, verifies email, and from there creates
a centre (becoming its initial administrator) or joins one. Actions *an
administrator takes over other people* (invite, suspend, change roles) belong to
the centre journey (`C-*`); this is the side of the person receiving or acting.

**T-1 — Sign-up / onboarding flow** · `[owner decision]` (intent captured §13,
2026-09-03; artifact at #511) + `[rule]` · INV-11, INV-3, data minimisation ·
#511

**Owner intent.** One step: full name (nombre y apellidos), email, password —
nothing more. Tone follows `docs/brand/README.md`'s visual tone verbatim
("professional, but not bureaucratic") and its interface foundations (rounded
controls, generous spacing, restrained Voice Blue), matching the public
reporting flow's existing look so the SaaS account surfaces read as the same
product. "Professional" is achieved through precise copy, a real privacy-notice
moment and genuine password-strength feedback — **not** by asking for more data.
Explicitly excluded from sign-up: phone number (no proven need, extra PII),
centre/institution (belongs to C-1) and professional function (belongs to
membership, C-4) — asking for them here would collect a value that cannot yet be
meaningful and contradicts data minimisation. Further profile completeness is
progressive, offered afterward in account settings (T-9), not at sign-up.

- **+** GIVEN the SaaS sign-up surface, WHEN an education professional registers
  with their full name, a work email and a valid password, THEN an unverified
  account is created holding only that name and email, and no centre, membership
  or privilege.
- **−** WHEN an account is registered, THEN employment, institution or
  professional status is not verified — that is only asserted at centre
  activation.
- **−** WHEN a weak or breached password is offered, THEN it is rejected with
  plain guidance; controls follow current good practice (length first,
  breached-password check), not arbitrary composition rules.
- **−** WHEN registration data is stored, THEN it contains no safeguarding-domain
  data and nothing beyond the minimum to operate the account.

**T-2 — Account privacy notice at sign-up** · `[rule]` · INV-11, INV-15 · #511,
§2.4

- **+** GIVEN the sign-up surface, WHEN a person registers, THEN they are shown and
  must accept a plain-language account privacy notice stating what account data is
  processed, by whom, for how long, and their rights.
- **−** WHEN that notice is shown, THEN it is not bundled into unrelated terms, not
  hidden, and not pre-accepted by default.

**T-3 — Verified-email activation** · `[rule]` · INV-3 · #511

- **+** GIVEN an unverified account, WHEN the person follows the verification link
  sent to their email, THEN the account becomes active and can sign in.
- **−** WHEN the account is unverified, THEN it cannot sign in, be invited into a
  centre, or create a centre.
- **−** WHEN a verification link is used, expired or re-requested, THEN it is
  single-use and time-bound, re-requests are throttled, and the response never
  reveals whether an email is registered.

**T-4 — Non-disclosure across auth surfaces** · `[rule]` · INV-3 · #511

- **+** GIVEN the sign-up and sign-in surfaces, WHEN any credential, verification
  or recovery attempt is made, THEN messages are uniform and never disclose
  whether an email is registered, verified, suspended or unknown.
- **−** WHEN sign-in fails, THEN the response does not distinguish "wrong password"
  from "no such account" and does not enable enumeration.

**T-5 — Abuse resistance on sign-up and sign-in** · `[rule]` · INV-3, INV-15 ·
#511, #521

- **+** GIVEN the sign-up and sign-in surfaces, WHEN attempts arrive at abusive
  volume, THEN they are rate-limited with safe failure and a clear retry path for
  the legitimate user.
- **−** WHEN sign-in throttling applies, THEN a legitimate user is not permanently
  locked out by a third party's failed attempts; throttling combines factors, not
  the target email alone.
- **−** WHEN sign-up is protected against bots, THEN no third-party tracking
  challenge (reCAPTCHA and similar) is used.

**T-6 — Sign-in, session lifetime and invalidation** · `[rule]` · INV-3 · #511,
#514

- **+** GIVEN an active account, WHEN the person signs in with correct
  credentials, THEN a session is established whose scope is only the centres they
  are a member of, with a bounded lifetime and an idle timeout.
- **−** WHEN a session exists, THEN it is invalidated on password change, on
  membership revocation or suspension, and on recovery.

**T-7 — Password change and account recovery** · `[rule]` · INV-3, INV-17 · #511

- **+** GIVEN an active account, WHEN the person changes their password or
  completes recovery, THEN all other sessions for that account are invalidated and
  the change is confirmed.
- **−** WHEN recovery is requested, THEN the flow never reveals account existence,
  never exposes a credential, and a recovery link is single-use and time-bound.
- **+** GIVEN the recovery and password surfaces, WHEN used with keyboard only or a
  screen reader, THEN every step is operable and labelled.

**T-8 — Changing the account email** · `[rule]` · INV-3 · #542

- **+** GIVEN an active account, WHEN the person changes their account email, THEN
  the new email must be verified before it takes effect.
- **−** WHEN the account email changes, THEN pending invitations addressed to the
  old email do not transfer automatically.

**T-9 — Account settings and progressive profile** · `[owner decision]` (intent
captured §13, 2026-09-03; artifact at #511) + `[rule]` · INV-11, INV-3 · #511

**Owner intent.** This is where "profile completeness" lives, offered
progressively and always optional: a profile picture (humanises P-21's
discussion threads — shows who is commenting, not just a name) and a
**self-described professional title** (for example "Orientadora Educativa") that
the person writes about themselves. The title is display-only — it appears on
their profile and their comments and grants **no capability whatsoever**; it must
never be confused with the actual professional function governed by C-4 through
centre membership, which is what determines what someone can actually do.

- **+** GIVEN an active account, WHEN the person opens account settings, THEN they
  can change their name, password and language; add or change an optional profile
  picture and an optional self-described professional title; and see which
  centres they belong to and with what role.
- **−** WHEN a self-described title is set, THEN it changes no capability, no
  role and no access anywhere — it is a display label only.
- **−** WHEN account settings are shown, THEN they collect no data beyond what
  operating the account requires and expose no cross-centre or case data.
- **−** WHEN the account email is used, THEN it is only for verification, security,
  recovery and membership events — never marketing; non-disableable types are the
  security-essential ones and are stated.

**T-10 — Account suspension (the person's view)** · `[rule]` · INV-14, INV-3 ·
#513

- **+** GIVEN a platform-suspended account, WHEN the person tries to sign in, THEN
  they cannot, are told how to make contact, and their data is retained per policy
  pending resolution — not deleted.
- **−** WHEN an account is suspended, THEN its sessions are invalidated, it retains
  no centre access, and the suspension reveals no detail to third parties.

**T-11 — Deleting the account** · `[rule]` · INV-14, INV-3 · #511, #536

- **+** GIVEN an account, WHEN the person requests deletion and holds no
  final-administrator role blocking it, THEN the account, its credentials and its
  memberships are really removed within the stated window, sessions are
  invalidated, and the person is told what was removed. A sandbox account is
  deletable from the start (#511); #536 adds full retention/legal-hold/
  anonymisation operations for activated contexts.
- **−** WHEN an account is deleted, THEN no hidden copy, credential or session
  survives beyond the stated window, and audit entries retain only the minimal
  actor reference already permitted by INV-12.
- **−** WHEN the person is a final administrator of a centre, THEN deletion is
  blocked until ownership is transferred, with a clear explanation.

**T-12 — The account before any centre (empty state)** · `[owner decision]`
(intent captured §13, 2026-09-03; artifact at #511) + `[rule]` · INV-2 · #511

**Owner intent.** Minimalist content area: only the two paths (create a centre;
accept a pending invitation), the primary action ("crear centro") visually
heavier than the secondary. Account-level controls (settings, sign out, help)
live in the shared app shell (§6.0), not on this screen's content. Visual
language follows `docs/brand/README.md` literally: generous spacing, Convive
Navy headings, rounded controls (~0.65rem), Voice Blue used only as a restrained
accent on the primary action, never as body text.

- **+** GIVEN a newly active account with no membership, WHEN the person signs in,
  THEN they see the empty state's content area showing only the two paths above;
  account-level controls remain available through the shared shell, not the
  content area.
- **−** WHEN the account has no membership, THEN no dashboard, case surface,
  guidance or centre data is shown, because none applies.

**T-13 — Creating a centre (bridge to `C-*`)** · `[rule]` · INV-2 · #512

- **+** GIVEN an active account in the empty state, WHEN the person chooses "create
  a centre" and completes the flow, THEN they hold the initial-administrator
  membership of exactly that centre and are taken into it; the detailed creation
  rules are `C-*`.
- **−** WHEN a centre is created, THEN no capability over any other centre, and no
  legal or institutional role by implication, is granted (D2).

**T-14 — Accepting an invitation to a centre** · `[rule]` · INV-2, INV-1 · #513

- **+** GIVEN a pending invitation to an account's verified email, WHEN the person
  accepts it while signed in as that account, THEN a membership is created with
  exactly the single role the inviter set, scoped to that one centre.
- **+** GIVEN an invitation to an email with no account yet, WHEN an account is
  created and verified for that email, THEN the pending invitation appears to
  accept.
- **−** WHEN an invitation is accepted, THEN it grants no capability beyond that
  role, no access to any other centre, and no administrator capability unless the
  role explicitly carries it.
- **−** WHEN an invitation is expired, revoked or addressed to a different email,
  THEN it cannot be accepted and the response reveals nothing about the centre.
- **−** WHEN the account already has a membership in that centre, THEN a duplicate
  invitation creates no second membership and does not silently change the
  existing role.

**T-15 — Belonging to several centres (centre selector)** · `[owner decision]`
(intent captured §13, 2026-09-03; artifact at #513) + `[rule]` · INV-1, INV-2 ·
#513

**Owner intent.** Lives in the shared app shell (§6.0), not a screen of its own:
a simple dropdown naming the active centre, shown only when the account has more
than one membership (hidden entirely for a single-centre account). Same rounded
controls, Voice Blue restricted to the selected-state indicator.

- **+** GIVEN an account with memberships in more than one centre, WHEN the person
  is signed in, THEN the shell's centre selector lets them move between centres,
  and each centre's data, roles and cases stay strictly separate.
- **−** WHEN the person is working in one centre, THEN no other centre's cases,
  members, counts or names are visible or searchable from it.

**T-16 — Leaving a centre** · `[rule]` · INV-2, INV-14 · #513

- **+** GIVEN a member who is not the final administrator of a centre, WHEN they
  leave or are removed, THEN their membership and capabilities end immediately,
  their sessions for that centre are invalidated, and their case assignments are
  handled per the centre's reassignment rules.
- **−** WHEN a person leaves a centre, THEN their account and other memberships are
  unaffected, and they retain no residual access to the centre they left.
- **−** WHEN the person is the centre's only administrator, THEN they cannot leave
  until another administrator is designated.

**T-17 — Centre lifecycle-state awareness + fictional framing** · `[rule]` ·
INV-11, INV-16 · #512, #537

- **+** GIVEN a centre the person is a member of, WHEN they enter it, THEN the
  interface clearly shows its state (`Sandbox` / `Activation under review` /
  `Activated`).
- **+** GIVEN a `Sandbox` centre, WHEN the person works in it, THEN the framing is
  unmistakably fictional/practice and only their name and email are real.
- **−** GIVEN an `Activated` centre, WHEN the person enters, THEN it is unmistakable
  that real-data handling rules now apply; a centre never moves from sandbox to
  activated without the member perceiving it.

**T-18 — Accessibility and language of the account journey** · `[rule]` · INV-17,
INV-18 · #511, #522

- **+** GIVEN sign-up, verification, sign-in, recovery, the empty state, the centre
  selector and account settings, WHEN used with keyboard only, a screen reader, or
  at mobile width, THEN every step is operable, labelled and in plain language, at
  least at the demo baseline.
- **−** WHEN a supported locale is incomplete, THEN the account surfaces are not
  presented as fully published in it.

### 6.3 Centre journey (`C-*`)

The **lifecycle of the centre workspace**, not a person: creation, ownership,
roles, membership administration, states and activation, public-entry
configuration, sandbox data, isolation and closure.

**C-1 — Controlled centre creation** · `[owner decision]` (form shape decided
§13, 2026-09-03; artifact at #512) + `[rule]` · INV-2, INV-11 · #512

**Owner intent.** Minimalist single-page form (no wizard steps), same visual
language as elsewhere (brand spacing/controls/restrained accent), but with more
data collected than the bare minimum, within coherence:

- **Required**: centre name; province/autonomous community (real functional
  weight — it routes the centre to its territorial guidance profile, P-12).
- **Optional**: educational level/type (infantil, primaria, secundaria, FP,
  otro); municipality — future-proofing, no protocol differentiates by these
  today.
- **Not here** (stays in C-3, configured after creation): visual identity,
  contact details, public-facing description, locale/timezone settings.
- **Territorial coverage indicator**, next to the province/CCAA field: since all
  19 Spanish jurisdictions are already verified and available (§3.5), this shows
  the matching territorial profile and its review status — for example
  *"Este centro tendrá acceso a la guía de [territorio] (revisado [fecha])"* —
  sourced, never implying more coverage than actually exists at that moment.

- **+** GIVEN a verified active account, WHEN the person completes centre creation
  with the required and any optional data above, THEN a centre workspace is
  created in the `Sandbox` state with that account as its sole initial
  administrator.
- **−** WHEN a centre is created, THEN it collects no data beyond the fields
  above, asserts no institutional affiliation, and enters no state other than
  `Sandbox`.
- **−** WHEN creation is attempted at abusive volume, or with a name that collides
  or enables enumeration, THEN it is rate-limited and the response does not
  confirm whether a given centre name already exists.

**C-2 — Ownership bootstrap is atomic and singular** · `[rule]` · INV-2 · #512

- **+** GIVEN centre creation completing, WHEN the workspace is created, THEN
  exactly one initial administrator membership is established in the same atomic
  step; the centre never exists without an administrator.
- **−** WHEN creation fails partway, THEN no orphan centre, no admin-less centre
  and no duplicate administrator is left behind.

**C-3 — Centre identity and settings** · `[owner decision]` (intent captured
§13, 2026-09-03; artifact at #512) + `[rule]` · INV-1, INV-13 · #512, #516

**Owner intent.** Display name, basic settings (locales, timezone), and an
**optional centre logo/crest** — one image asset, uploaded here once and reused
consistently everywhere the centre's identity appears: the public entry (C-12)
and every exported PDF (P-15). This is the centre's *own* identity, not
Convive's — it does not reopen P-15's "no logo" rule, which was always about
**Convive's** branding, not the school's. If no logo is uploaded, the space
stays blank everywhere, exactly as already decided.

- **+** GIVEN a centre, WHEN its administrator sets its display name, basic
  settings and an optional logo, THEN they are used consistently on the
  workspace, the public entry and exported PDFs, and are editable with an audit
  record.
- **+** GIVEN a logo is uploaded, WHEN it is stored, THEN it follows the same
  format/malware scanning as any other upload (ClamAV) and is bounded to one
  image asset for this purpose — it is not a general-purpose file store (INV-13).
- **−** WHEN a centre's identity is set or changed, THEN it cannot claim
  affiliation with an institution, authority or official service it is not (this
  applies to the logo too, with no automated judgement of its content — the
  centre is accountable for its own identity, same as the rest of C-3), and it
  never exposes another centre's identity.

**C-4 — The role set: two independent axes** · `[owner decision]` + `[rule]` ·
INV-2 · #508, #513

**Decided (§13, 2026-09-03): function and technical capability are independent
axes, not one role list.** A person's **professional function** (which case work
they naturally do) and their **technical capabilities** (workspace
administration, reassignment) are granted separately; holding one implies
nothing about the other. The initial administrator (D2) is a technical
capability, not a professional function.

- **+** GIVEN a centre workspace, WHEN professional functions are available for
  assignment, THEN they are grounded in the demo's six functions as the starting
  reference — dirección, coordinación de bienestar y protección, orientación
  educativa, tutoría, profesorado, administración y servicios — each with an
  explicit, documented capability list. The starting sketch:

  | Función | Evalúa comunicaciones nuevas | Puede ser responsable de un caso | Reasigna por defecto | Ve casos sin estar asignada |
  |---|---|---|---|---|
  | Dirección | No (salvo asignación) | Sí, si se asigna | Sí | No |
  | Coordinación de bienestar y protección | Sí | Sí (típicamente responsable) | Sí | No |
  | Orientación educativa | Sí | Sí | No | No |
  | Tutoría | Sí (de sus tutorías) | Sí (de sus alumnos) | No | No |
  | Profesorado | Limitado | Como colaborador | No | No |
  | Administración y servicios | No | No | No | No |

  This matrix is a starting reference for #508's research, not the final answer.
- **+** GIVEN the workspace-administrator technical capability, WHEN it is
  granted, THEN it covers configuration and membership only; it may additionally
  be granted the reassignment capability (P-16), but neither is automatic, and
  neither implies a professional function or case-content access.
- **−** WHEN a role or capability is assigned, THEN it grants only its documented
  scope, never automatic access to all cases, never statutory power, and never
  institutional authority by implication.
- **−** WHEN the role model is presented, THEN generic technical roles (`admin`,
  `user`) are not exposed as the product model, and administración y servicios
  never gets default case access (matches existing project precedent).

**C-5 — Inviting members** · `[owner decision]` (member-management screen —
intent captured §13, 2026-09-03; artifact at #513) + `[rule]` · INV-2, INV-1 ·
#513

**Owner intent.** A simple list of members: role and status (active / invitation
pending / suspended) visible per row, with basic actions (change role, suspend,
revoke) directly on the row — no navigation to a separate detail page for common
actions. Full history (C-8) lives in its own view, not mixed into this list. The
final-administrator's protected actions (C-7) appear disabled on their row
rather than failing after the fact.

- **+** GIVEN an administrator, WHEN they invite a person by email and choose one
  role, THEN a pending invitation is created for that email, scoped to this centre
  and that role, with an audit record.
- **−** WHEN an invitation is created, THEN it grants nothing until accepted,
  reveals nothing about the centre to an unintended recipient, is time-bound, and
  can be revoked.
- **−** WHEN the invited email already has a membership here, THEN no second
  membership or silent role change results.

**C-6 — Changing roles, suspending and revoking members** · `[rule]` · INV-2,
INV-3, INV-12 · #513

- **+** GIVEN an administrator, WHEN they change a member's role, suspend or revoke
  them, THEN the change takes effect immediately, that member's sessions for this
  centre are invalidated as required, and an audit entry records actor, subject,
  before/after and time.
- **−** WHEN a member is suspended or revoked, THEN they lose all centre access at
  once and retain nothing residual; their case assignments are surfaced for
  reassignment, never silently dropped.
- **−** WHEN a role is changed, THEN it never escalates access to cases the member
  was not explicitly assigned.

**C-7 — Final-administrator protection and ownership transfer** · `[rule]` · INV-2
· #513

- **+** GIVEN a centre with one administrator, WHEN that person tries to leave, be
  removed, downgraded or deleted, THEN the action is blocked until another
  administrator is designated.
- **+** GIVEN two administrators, WHEN one transfers the initial-administrator
  responsibility, THEN the transfer is explicit, recorded, and leaves exactly one
  accountable initial administrator.
- **−** WHEN any membership change completes, THEN the centre is never left with
  zero administrators.

**C-8 — Membership and role history** · `[rule]` · INV-12 · #513

- **+** GIVEN a centre, WHEN an administrator reviews membership history, THEN they
  see an append-only record of invitations, acceptances, role changes,
  suspensions and revocations with actor and time.
- **−** WHEN membership history is shown, THEN it contains no case content, no
  credentials and no data from other centres.

**C-9 — Lifecycle states and their labels** · `[rule]` (Spanish labels decided
§13, 2026-09-03 — satisfies DR-1's text-only-surface bar, no further artifact
needed) · INV-11 · #512

- **+** GIVEN a centre, WHEN its state is displayed anywhere in the workspace, THEN
  it shows one of `Sandbox` (**"Modo de prueba"**) / `Activation under review`
  (**"Activación en revisión"**) / `Activated` (**"Activado"**), consistently.
  "Modo de prueba" is used instead of the English loanword because the audience
  (teachers, counsellors) should not need to recognise it.
- **−** WHEN the state is `Sandbox` or `Activation under review`, THEN no real
  safeguarding-domain data can be entered and the interface says so.

**C-10 — Requesting activation is personal, not self-service** · `[rule]` ·
INV-11 · #504, #537, #538

- **+** GIVEN a `Sandbox` centre, WHEN its administrator requests activation, THEN
  the request is a **direct, personal contact with the accountable operator**
  (for example, an email) — never an automated self-service toggle that grants
  anything by itself. Making the request moves the centre to `Activation under
  review`, the required gate evidence (#504/#537) is presented as outstanding,
  not assumed, and the centre's members are notified of the request.
- **·** This is deliberate: the product presents itself as a complete SaaS —
  self-service registration, full functionality in sandbox from the first minute
  — but the step into real data is a human decision the operator makes personally
  for that one centre, never a form that processes itself.
- **−** WHEN activation is requested, THEN the centre processes no real data while
  under review, and cannot reach `Activated` before the written go decision in
  #538.
- **−** WHEN gate evidence is missing or a review fails or is withdrawn, THEN the
  centre returns to `Sandbox` with a clear explanation; nothing real was processed
  in the meantime.

**C-11 — Becoming Activated** · `[rule]` · INV-11, INV-2 · #537, #538

- **+** GIVEN #538 has decided go and a centre has its own complete gate evidence
  (#537), WHEN it is activated, THEN `Activated` is shown, institutional
  responsibility is designated to a named accountable party, real-data handling
  rules become effective and visible to members, and members are notified of the
  decision.
- **−** WHEN a centre is `Activated`, THEN activation of one centre never implies
  or triggers activation of any other; each is individual.

**C-12 — Configuring the public entry** · `[owner decision]` (config screen +
default poster template decided §13, 2026-09-03; artifact at #516/#517) +
`[rule]` · INV-7, INV-9, INV-1 · #516, #517

**Owner intent — poster (#517).** A **default, fully Convive-branded poster** is
generated automatically the moment a centre is created — no manual step —
carrying that centre's QR, its readable fallback URL, the centre's name (and
logo if uploaded, C-3) inside Convive's own visual template. Separately, the
administrator can download **just the raw QR image and the plain link** to build
their own poster. Because a self-designed poster is built outside Convive, the
non-emergency notice and fallback URL cannot be physically guaranteed on it — the
raw-asset download requires an explicit, non-dismissable-as-fine-print
acknowledgement that a self-made poster **must** include both. This is a
best-effort guarantee, honestly short of the default poster's `[rule]`
guarantee.

- **+** GIVEN a centre is created, WHEN creation completes, THEN its public entry
  and a default Convive-branded poster (QR, readable URL, non-emergency notice,
  centre name/logo) already exist — generating them is not a separate manual
  step.
- **+** GIVEN an administrator, WHEN they configure the entry further (name/slug,
  displayed identity, instructions) or regenerate poster materials, THEN the
  entry routes only to this centre and changes are audited.
- **+** GIVEN an administrator downloads the raw QR and link (not the default
  poster), WHEN they do, THEN they must acknowledge that a self-designed poster
  needs the readable URL and the non-emergency notice; Convive cannot enforce
  this on a poster made outside the app.
- **−** WHEN the public entry or its default poster exist, THEN the administrator
  cannot remove or weaken the non-emergency notice, cannot make the QR the sole
  access path, and cannot choose a slug that enables enumeration of other
  centres.
- **−** WHEN a public link is rotated or revoked, THEN the old link grants no
  residual access and reveals nothing.

**C-13 — Sandbox data: seed and reset** · `[rule]` · INV-11, INV-14 · #515

- **+** GIVEN a `Sandbox` centre, WHEN it is created or reset, THEN it is populated
  from a narrow deterministic fictional seed, and the reset schedule is disclosed
  to the centre's members.
- **−** WHEN a sandbox reset runs, THEN it removes the centre's fictional working
  data really (no hidden retention), and never touches another centre or the
  stable demo.

**C-14 — Centre isolation** · `[rule]` · INV-1 · #510, #514

- **+** GIVEN multiple centres, WHEN anyone operates within one, THEN that centre's
  data, members, cases, evidence, exports, search and backups are inaccessible
  from any other, proven by negative tests.
- **−** WHEN an identifier, URL, session or membership from one centre is used
  against another, THEN access is denied and nothing about the other centre is
  revealed.

**C-15 — Administrator visibility over cases** · `[rule]` · INV-2 · #513, #527

- **+** GIVEN a centre administrator, WHEN they manage configuration and members,
  THEN they can do so without that role granting them read access to case content.
  This includes reassigning a case between professionals (P-16): reassignment is
  an operational, capability-gated action, not a content grant.
- **−** WHEN an administrator is not assigned to a case, THEN they do not see its
  content; governing the centre is not access to every case, and reassigning one
  does not change that.

**C-16 — Centre deactivation and closure** · `[rule]` · INV-14 · #512

- **+** GIVEN a centre no longer needed, WHEN closure is defined (by #512), THEN it
  follows an explicit owner-approved procedure covering member notification, data
  retention/deletion and public-entry withdrawal.
- **−** Until #512 defines this, THEN closure is not offered as a self-service
  action; a centre is never silently deleted. *(§3 defers the complete state model
  here; this entry holds the placeholder.)*

**C-17 — Accessibility and language of centre administration** · `[rule]` ·
INV-17, INV-18 · #522, #528

- **+** GIVEN centre creation, member management, role assignment, lifecycle and
  public-entry configuration, WHEN used with keyboard only, a screen reader or at
  mobile width, THEN every step is operable, labelled and in plain language at
  least at the demo baseline.
- **−** WHEN a supported locale is incomplete, THEN centre-administration surfaces
  are not presented as fully published in it.

### 6.4 Professional journey (`P-*`)

The authorised professional doing case work inside a centre workspace. This is the
most `[owner decision]`-heavy journey and the one where the dashboard previously
drifted from the owner's intent.

**P-1 — Entering the workspace / first screen** · `[owner decision]` (default
layout intent captured §13, 2026-09-03; artifact at #508/#526) + `[rule]` ·
INV-2, INV-4 · #508, #526

> *This is the surface DR-1 was written for.*

**Owner intent — default layout.** Organised by urgency (what needs action),
presented with the brand's calm tone — never a triage/emergency-room register,
which would contradict INV-9. Two zones:

1. **"Necesitas actuar"** (top): a short, capped list (e.g. top 5, with a "ver
   todo" link) ordered overdue-first then soonest-due, each line naming the item,
   its minimised case reference, time remaining or overdue-by, and a one-click
   way in. When nothing is due, it shows a calm, real "todo al día" state — never
   hidden, never fabricated activity.
2. **General snapshot** (below, quieter): open-case counts by state — context,
   not the headline. A light preview of P-20, not the full reporting view.

Urgency wins as the organising principle (matches "only what they must/may act
on"), but the presentation stays calm, matching the brand's "safe and calm, but
not clinical" tone — this is the synthesis, not a stark urgent-first triage
board.

- **+** GIVEN an authorised professional who has selected a centre, WHEN they enter
  the workspace, THEN they see the owner-decided first screen, showing only what
  they must act on and may act on, built from real authorised data for that
  centre.
- **−** WHEN that screen renders, THEN it shows nothing not backed by real data
  the professional can access — no mock metrics, no chart or figure without
  underlying accessible data, no centre-wide counts they are not permitted to see,
  no automated assessment of any case. Sourced deadline countdowns and overdue
  indicators (P-9) are exactly the kind of real, actionable information that
  belongs here.
- **−** WHEN the professional has no assigned work, THEN an owner-decided empty
  state is shown, not fabricated activity.
- **·** Whether the first screen *is* the actionable queue, contains it, or is
  separate from it is an owner decision (DR-1); P-2's rules apply to whichever
  surface exists.

**Personalisation — decided (§13, 2026-09-03).** Two layers, both in scope:

- **+** GIVEN a role, WHEN #508's research defines its dashboard, THEN that role
  gets a genuinely distinct **default** layout and widget set grounded in that
  role's real work (not the same screen re-skinned per role) — this is design
  depth, not a new mechanism.
- **+** GIVEN an individual professional, WHEN they customise their own
  dashboard, THEN they can add, remove, resize and reorder widgets **from the set
  their role and capabilities already permit** — never a widget showing data they
  could not otherwise access (INV-2) — and reset back to their role's default at
  any time.
- **−** WHEN a widget is available to add, THEN it is one of Convive's own
  built-in widgets, built from data already scoped elsewhere in this charter
  (the pending-actions list P-2, deadlines P-9, guidance P-12, operational
  reporting P-20, recent mentions P-21); there is no third-party or
  externally-sourced widget.
- **−** WHEN a professional customises their dashboard, THEN it is purely a
  personal display preference: it never changes what any other person sees,
  never changes underlying data or permissions, and never crosses centres — a
  layout saved in one centre has no effect in another (T-15).
- **·** The widget catalogue, the default layout per role, and the
  drag-and-drop/customisation interaction are all part of this surface's DR-1
  artifact — the same one already required for P-1.

**P-2 — The pending-actions list and alerts** · `[owner decision]` (structure
decided §13, 2026-09-03; artifact at #526) + `[rule]` · INV-2, INV-8 · #526

**Owner intent — a full screen, not a dashboard expansion.** P-1's "necesitas
actuar" is a deliberately short, calm preview; real daily work needs filtering,
sorting, grouping (by case, type, deadline) and marking items handled, which
would break P-1's calm brief if crammed in there. So P-2 is its **own screen**
in the shared app shell (§6.0) — reachable directly, not only via P-1's "ver
todo" — where someone with real caseload sits and works through everything
pending.

- **+** GIVEN the workspace, WHEN the professional opens the pending-actions
  screen, THEN it lists everything that awaits their action — new communications
  to assess (if they hold that capability), their overdue tasks, their due
  follow-ups — with filtering, sorting and grouping, in the owner-decided default
  order.
- **+** GIVEN any of the full set of notification-triggering events — a new
  assignment, a task or procedural-step deadline approaching or overdue, a
  reporter update, an @mention (P-21), an invitation received or accepted, a
  role change affecting the professional, a centre lifecycle-state change, or an
  evidence scan completing — WHEN it occurs, THEN the professional sees it
  **without reloading** (live in-app), and — on the channels they opted into —
  receives an email and a web-push notification. In-app live delivery, email and
  opt-in web push are all day-one. This full event taxonomy is the required
  starting checklist for the DR-1 artifact — it is not illustrative, it is
  complete until a new event type is added (DR-1 "unanticipated decisions").
- **+** GIVEN a professional, WHEN they open notification preferences, THEN they
  choose, per event type, whether it also reaches them by email and/or web push;
  live in-app delivery for anything in their pending-actions list cannot be fully
  disabled, so they are never blind inside the app itself.
- **+** GIVEN any notification sent outside the app, WHEN it is delivered, THEN it
  says only that there is something to review — no case detail, names, credential
  or bypass link (INV-8). Live urgency indicators are framed as work management,
  not an unfolding emergency (INV-9, INV-10).
- **−** WHEN the list renders, THEN it contains no item outside the professional's
  explicit assignments and capabilities, and no item from another centre (INV-1).
- **·** How notifications surface (toast, badge, notification centre, sound), the
  exact grouping/priority treatment of deadline-related events, and the wording
  are an owner decision — this surface's DR-1 artifact must cover the whole event
  taxonomy above, not a partial mock-up, before it ships.

**P-3 — Assessing a new communication (report → case)** · `[rule]` · INV-6, INV-4
· #518, #526, #527

- **+** GIVEN a professional with the assessment capability, WHEN they review a new
  communication, THEN they can record a first assessment and decide whether it
  becomes a managed case; a case is created only by that explicit human decision.
- **−** WHEN a communication arrives, THEN no case, protocol, assignment or label
  is created automatically, and elapsed time alone never converts it.
- **−** WHEN a communication is assessed as not requiring a managed case, THEN that
  decision is recorded with actor and time and is reversible by an authorised
  professional.

**P-4 — Case assignment** · `[rule]` · INV-2 · #527

- **+** GIVEN a new managed case, WHEN it is created, THEN it has exactly one
  explicit primary responsible professional; collaborators are added individually
  with only the access their contribution needs (e.g. contribute vs observe).
- **−** WHEN someone holds an administrator or direction role, THEN they gain no
  automatic access to the case; access is by explicit assignment only.
- **−** WHEN a collaborator is added, THEN their access is scoped and never
  silently widened.

**P-5 — The case workspace** · `[owner decision]` (structure decided §13,
2026-09-03; artifact at #527) + `[rule]` · INV-2, INV-12 · #527

**Owner intent — header + próximos pasos + one filterable story, never tabs.**
Tabs would fragment exactly what P-5's own rule protects: one case, one place.
The structure:

1. **Fixed header**: state, assigned lead and collaborators, minimised people
   references, neutral domain and date.
2. **Fixed "próximos pasos" strip**: this case's own pending/overdue tasks with
   their countdowns (P-9) — the same calm-urgency pattern as the dashboard (P-1),
   so it never requires scrolling through history to see what needs doing now.
3. **The story**: one chronological feed — communications, professional
   discussion (P-21), completed and pending tasks, evidence attachments, the
   professional assessment — rendered directly from the append-only history
   (P-6). Filterable by type ("todo / tareas / comunicaciones / evidencia /
   discusión") to narrow a busy case, but always the same feed, never separate
   tab-pages that hide content from each other.

- **+** GIVEN an assigned professional, WHEN they open a case, THEN they see the
  header, the próximos-pasos strip and the filterable story in one workspace —
  state, minimised people references, the reporter-provided description and
  neutral domains, the separate professional assessment, tasks, communications,
  evidence, follow-up and the audit trail are all part of it.
- **−** WHEN the case workspace is shown, THEN nothing about the case is created or
  edited outside it, and it exposes no data from other cases or centres.

**P-6 — Append-only case history** · `[rule]` · INV-12, INV-6 · #527

- **+** GIVEN a case, WHEN internal notes, communications or action records are
  added, THEN they enter an append-only chronological history with actor and time.
- **−** WHEN something is corrected, THEN the correction is a new traceable entry,
  never a silent edit; the reporter sees only entries marked reporter-visible.

**P-7 — Structured case information stays neutral; authoring the assessment** ·
`[rule]` · INV-4, INV-12 · #527

- **+** GIVEN a case, WHEN information is recorded, THEN it is limited to
  communication type, date and centre, free-text description, neutral selectable
  domains, minimised references to involved people, and a separate optional
  professional assessment.
- **+** GIVEN an assigned professional, WHEN they author or correct the
  assessment, THEN it is attributed to them with time, and a correction is a new
  traceable entry.
- **−** WHEN case information is recorded, THEN no diagnosis, special-category
  label, credibility level, automatic risk score or automated recommendation is
  produced or stored.

**P-8 — Case state and its set** · `[rule]` (set confirmed §13, 2026-09-03) ·
INV-4, INV-5 · #527

- **+** GIVEN a case, WHEN its state is shown or changed, THEN it uses the
  confirmed set — `nueva comunicación` → `en valoración` → `plan de actuación` →
  `seguimiento` → `cerrado` → `archivado` — and every transition requires an
  appropriately assigned professional and is audited.
- **−** WHEN a state would be set, THEN conclusion or judgement labels (`resuelto`,
  `sin fundamento`, equivalent) are never available, and no transition happens
  automatically, on a schedule, or from elapsed time.
- **−** WHEN a case is archived, THEN archiving is not deletion; retention of
  archived cases follows #536, not an invented period.
- **+** GIVEN a `cerrado` or `archivado` case, WHEN a recurrence or new
  information means it needs reopening, THEN a person with case-management
  capability can move it back to `en valoración` or `seguimiento`, with a required
  reason, audited the same as any other transition. Reopening is never automatic
  or time-based.
- **+** GIVEN a case is activated as managed under a protocol, WHEN it enters that
  state, THEN the protocol's procedural steps and their sourced deadlines are
  pre-populated as tasks (P-9).

**P-9 — Tasks and procedural-step deadlines** · `[rule]` · INV-5, INV-10, INV-4 ·
#527, #530

- **+** GIVEN a case, WHEN a task is created, THEN it carries an assigned person, a
  due time, a stage from the applicable protocol version, an explicit kind and a
  status of pending / completed / not-applicable.
- **+** GIVEN a professional activates a managed case under a protocol, WHEN the
  case opens, THEN Convive pre-populates that protocol's standard procedural steps
  as tasks (for example: activate the protocol, meet the families, communicate to
  educational inspection, communicate to the juvenile prosecutor where there are
  indications of an offence), each with the timeframe the reviewed protocol
  version states for it and a citation to that source (#530). It adds no step the
  protocol does not have and does not decide whether the protocol applies (P-3).
- **+** GIVEN a task with a due time, WHEN the workspace shows it, THEN the time
  remaining and, past the due time, the overdue state are visible; a due time
  either comes from the protocol version (cited) or is set by an assigned
  professional, and the origin is shown (INV-10).
- **+** GIVEN a communication step is completed, WHEN it is marked done, THEN it
  links to its communication record (P-10).
- **−** WHEN a task exists, THEN it is never completed automatically or from
  elapsed time, the overdue state triggers no action, a terminal transition needs
  an explicit actor with the case's manage capability, and "not-applicable" needs
  a reason.
- **−** WHEN a protocol version states no timeframe for a step, THEN no due time is
  invented; the professional sets one or leaves it open (INV-10).

**P-10 — Communications** · `[rule]` · INV-4, INV-6 · #527

- **+** GIVEN a case, WHEN a professional records a communication (public, family,
  internal), THEN it is stored as a dated record linked to the case, and entries
  meant for the reporter are explicitly marked reporter-visible.
- **−** WHEN a communication is recorded, THEN it is not an assessment, applies no
  label, does not diagnose or classify credibility, and never completes from
  creation or elapsed time.

**P-11 — Evidence in the case** · `[rule]` · INV-13 · #525

- **+** GIVEN an assigned professional with the right capability, WHEN they open
  evidence attached to the case, THEN access is application-mediated with no-store
  delivery, safe preview and an audit event; only permitted people can view or
  download. For audio and video this means in-app playback, not a downloadable
  raw file.
- **−** WHEN evidence is pending scanning, quarantined, or the professional lacks
  the capability, THEN it cannot be viewed or downloaded, and cross-tenant or
  unauthorised paths are denied.
- **−** WHEN audio or video evidence is opened, THEN it is never transcribed and
  never processed by any AI or machine-learning model — the assigned professional
  listens to or watches it directly (INV-13).

**P-12 — Applicable guidance in context** · `[owner decision]` (placement decided
§13, 2026-09-03; artifact at #531) + `[rule]` · INV-10, INV-4 · #531

**Owner intent — a compact, always-present citation line, never a panel.**
Reached after iterating a próximos-pasos integration (would bloat its brief),
a fixed sidebar (risks reading as Convive "always advising"; fights for space on
mobile) and a buried on-demand button (easy to miss the guidance exists at all).
The synthesis: one compact, footnote-styled line near the header or
próximos-pasos — *"Guía aplicable: Protocolo de [territorio] (revisado
[fecha]) — ver"* — always present so it is never missed, but visually neutral
(no urgent colour, no notification tied to it) so it never reads as Convive
actively weighing in. Expands to full source content on click.

- **+** GIVEN a professional working a case, WHEN applicable guidance is shown,
  THEN it discloses source, territory, version and review status, links to the
  source, is drawn from the centre's territorial profile (any of the 19 verified
  jurisdictions, §3.5), and appears as the compact citation line above, expanding
  to full content on demand.
- **+** GIVEN a protocol with stated timeframes, WHEN guidance shows them, THEN
  each is attributed to its source, version and section, and framed as that
  protocol's timeframe — not as Convive's own legal determination or a universal
  rule.
- **−** WHEN guidance is shown, THEN it is never presented as a binding
  determination or legal advice, and it never selects a protocol or decides the
  case.

**P-13 — Follow-up** · `[rule]` · INV-5 · #527

- **+** GIVEN a case with actions underway, WHEN a follow-up is scheduled, THEN at
  its time it reminds the assigned person.
- **−** WHEN a follow-up time passes, THEN it changes no case state and no task
  status by itself.

**P-14 — Search and filters** · `[rule]` · INV-1, INV-2, INV-12 · #526

- **+** GIVEN the workspace, WHEN the professional searches or filters, THEN
  results are limited to their explicit assignments in the active centre.
- **−** WHEN results are returned, THEN they expose no report free text, task
  content, people, reasons, audit payloads or any cross-centre data.

**P-15 — Clean document exports** · `[owner decision]` (PDF template) + `[rule]` ·
INV-2, INV-11, INV-1 · #527, #543

- **+** GIVEN a professional, WHEN they export from the case record, the
  procedural-step and deadline view, the communications log, or an operational
  report (P-20), THEN they get a well-typeset PDF — proper headings, page numbers,
  Spanish — with **no Convive branding and no decorative chrome**; it reads as
  the centre's own working document. Any traceability line (source app, date,
  centre) is minimal and its exact form is an owner decision.
- **+** GIVEN the centre has uploaded its own logo (C-3), WHEN a PDF is exported,
  THEN that logo appears consistently across every export type — this is the
  centre's own identity, not Convive's, so it does not conflict with "no
  branding" above. Without an uploaded logo, the space stays blank.
- **+** GIVEN an export is produced, WHEN it is delivered, THEN it is generated in
  memory and not persisted by Convive; the centre keeps its own copy; the export
  is an audited event (INV-11).
- **−** WHEN any export is produced, THEN it contains only data the exporter can
  see in-app (INV-2), no other case or centre (INV-1), no credentials or session
  data, and the case record export is available only to the assigned lead.
- **−** WHEN a professional lacks the capability for the underlying data, THEN the
  corresponding export is unavailable.

**P-16 — Reassignment is an operational capability, not a content grant** ·
`[rule]` · INV-2 · #513, #527

- **+** GIVEN a case needs reassignment (for example, the assigned professional is
  absent), WHEN someone holding the reassignment capability acts, THEN they see
  only minimal case metadata to decide — current assignee, state, neutral domain,
  date — never the narrative content or evidence, and they move the case to a new
  assigned professional, recording a reason and an audit trail.
- **+** GIVEN the reassignment capability, WHEN it is granted, THEN it is a
  distinct capability in the role set (C-4) that does **not** require or grant
  read access to case content. Direction and wellbeing/protection coordination
  hold it by default (mirroring their real coordination role); the workspace
  administrator may also be granted it explicitly, but does not receive it
  automatically just by being an administrator (mirrors C-15).
- **−** WHEN a case is reassigned, THEN the destination is always a professional
  who already holds case-work capability in that centre — never a role with no
  default case access (for example, administración y servicios).
- **−** WHEN someone with only the reassignment capability reassigns a case to
  **themselves**, THEN this is allowed only if they already hold a role that could
  normally receive case assignments; reassignment is never a back door for
  someone who would not otherwise be eligible to be assigned. Every reassignment,
  including to oneself, is fully audited with the actor visible — never silent.
- **−** WHEN reassignment occurs, THEN there is no break-glass, no automatic
  emergency access, and no centre-wide access is created.

**P-17 — What the professional never sees** · `[rule]` · INV-1, INV-2 · #514, #527

- **+** GIVEN any professional surface, WHEN it renders, THEN it shows only cases,
  people and data within the professional's explicit assignments and active
  centre.
- **−** WHEN the professional navigates, guesses identifiers, or reuses a session,
  THEN cases they are not assigned to, other centres, and internal notes outside
  their scope remain inaccessible.

**P-18 — Fictional framing under sandbox operation** · `[rule]` · INV-11, INV-16 ·
#515

- **+** GIVEN sandbox operation, WHEN a professional works in a `Sandbox` centre,
  THEN the workspace is unmistakably fictional/practice and all cases,
  communications and evidence are labelled fictional.
- **−** WHEN sandbox operation is active, THEN nothing implies a real safeguarding
  responsibility, a real reporter, or that real cases may be entered.

**P-19 — Accessibility and language of the whole workspace** · `[rule]` · INV-17,
INV-18 · #528

- **+** GIVEN the dashboard, queue, case workspace, evidence, guidance, search,
  reports and error/empty states, WHEN used with keyboard only, a screen reader
  and at mobile width, THEN every part is operable, labelled and in plain language
  at least at the demo baseline.
- **−** WHEN a supported locale is incomplete, THEN the professional workspace is
  not presented as fully published in it.

**P-20 — Operational reporting and statistics** · `[owner decision]` + `[rule]` ·
INV-2, INV-4, INV-15 · #526, #543

- **+** GIVEN a professional, WHEN they open operational reporting, THEN it shows
  real figures computed from data they are permitted to see — for example their
  open cases, tasks by status, procedural steps due or overdue, cases by neutral
  domain within their assignments — each figure traceable to the records behind
  it.
- **+** GIVEN an administrator without case-content access, WHEN they view
  centre-level reporting, THEN they see only counts and status, never case
  content (INV-2).
- **−** WHEN reporting renders, THEN it contains no vanity metric, no figure
  without accessible underlying data, no ranking or scoring of people, no
  automated assessment (INV-4), and no third-party analytics or tracking
  (INV-15).
- **+** GIVEN any report, WHEN the professional exports it, THEN it uses the clean
  unbranded PDF of P-15.

**P-21 — Professional discussion within a case** · `[rule]` · INV-2, INV-1,
INV-12 · #527

- **+** GIVEN a case, WHEN an assigned professional wants to discuss it with a
  colleague, THEN they post a note in the case's internal discussion, threaded to
  the relevant task or to the case overall.
- **−** WHEN someone is not assigned to a case, THEN they cannot be @mentioned
  into it and cannot see or post in its discussion — mentioning never grants
  access, and never reveals to an unassigned person that the case exists
  (INV-2).
- **+** GIVEN a note @mentions a colleague who is assigned, WHEN it is posted,
  THEN that colleague gets a live, real-time notification (P-2) linking directly
  to the case, on the channels they opted into.
- **−** WHEN discussion notes are recorded, THEN they are part of the case's
  append-only history (P-6, INV-12) — a correction is a new entry, never a silent
  edit — and never leave the case: no free-floating chat, no channel spanning
  multiple cases or centres (INV-1).
- **−** WHEN a discussion note is posted, THEN it is never itself a communication
  to the reporter; anything meant for the reporter is a separate, explicit,
  reporter-visible communication (P-10, INV-6).
- **·** The discussion's visual placement inherits the case workspace's
  `[owner decision]` artifact (P-5); it does not need one of its own.

---

## 7. Ambiguities resolved or deferred

Ambiguous or under-specified requests are settled here, or explicitly deferred to
a named issue. Nothing ambiguous is left for an implementer to resolve by default
(DR-1, "unanticipated decisions").

### 7.1 Resolved

- **"Subir páginas" / page uploads / evidence.** This means attaching a *bounded
  item of supporting material* — a photographed or scanned page, a screenshot, a
  document — to exactly one report or case within one tenant, to inform
  professional assessment (INV-13). It does **not** mean publishing pages, a wiki,
  a document library, arbitrary file hosting, or shared folders. The exact
  permitted types, size, retention, metadata handling, preview, deletion and
  unsupported cases are the subject of **#523** and are settled there before any
  upload capability is built.
- **"Dashboard".** The professional's first screen (§3, P-1). It shows only what
  the professional must act on and may act on, from real authorised data. It is
  not an analytics or metrics surface. Its concrete shape is an owner decision
  under DR-1 and is implemented in **#526**; it does not ship without the owner's
  intent statement and approved artifact.
- **Reporter identity and "anonymous".** A reporter needs no account and no stated
  identity (R-3). "Anonymous" is a product sense — no identity or account is
  required — not a legal guarantee that a submission cannot identify anyone
  (INV-8). An optional *verified* email may be offered for generic update
  notifications only; its exact rules are settled in **#519**.
- **"Centre" / "organisation" / "school".** One tenant ↔ one centre ↔ one centre
  workspace (§3). "School" is the real-world referent. "Organisation" is not used
  as a separate concept.
- **What activation grants.** Only the `Activated` state designates institutional
  responsibility and permits real data, and only after the written go decision in
  **#538** plus that centre's own gate evidence (**#537**). Creating a centre, or
  being its initial administrator, grants none of this (D2, INV-11, C-11).
- **QR semantics.** A QR code or public link is routing only — never
  authentication, never a secret, never the sole way in (INV-7). Its rotation and
  revocation lifecycle is settled in **#516**.
- **"Least privilege roles".** Roles are owner-decided groups of least-privilege
  capabilities, grounded in real centre functions — not job titles, and carrying
  no statutory power or institutional authority (INV-2, C-4). The concrete role
  set and per-role capabilities are an owner decision informed by **#508** and
  implemented in **#513**.
- **Territorial scope.** All 19 Spanish jurisdictions are in scope, migrated
  from the delivered demo's already-verified sources with a currency review —
  not re-researched from scratch, and not artificially restricted to Andalucía
  once verified work already existed for the rest (INV-10, **#529**, **#530**,
  **#532**, **CA-7**).
- **"Migration" of the demo.** There is no demo→SaaS data migration. The
  "migration boundary" language in **#506** denotes the isolation wall, not a
  feature; the stable demo is retired deliberately, never converted (INV-16, D1,
  §4.1).
- **Email as a channel.** Email never carries case detail and is never an access
  recovery mechanism, anywhere in the product (INV-8). Whether the SaaS *sandbox*
  sends real email at all, and the sending mechanism / subprocessor, are settled
  in §9 (delegated to **#519 / #520 / #505**).
- **"Applicable guidance" vs legal advice.** Guidance is informational, sourced,
  versioned and territorial; it is never a binding determination, legal advice, or
  a deadline presented as universal legal fact or as Convive's own determination
  (INV-10, P-12). Deadlines and countdowns drawn from a reviewed protocol version
  and attributed to it are shown (INV-10, P-9).
- **"Monitoring".** Convive never promises monitoring, a response time, or
  emergency handling (INV-9). Notifications say only that an update is available
  (INV-8).

### 7.2 Deferred (explicitly, to a named issue)

- **Reporter-initiated withdrawal of a report** — whether a reporter may withdraw
  a just-submitted report, or whether corrections are additive only (R-10) →
  **#519**.
- **Account email change** — expectation T-8, owned by **#542** (CA-5), since
  #511 is registration and activation only.
- **Centre deactivation and closure state model** — §3 defers the complete state
  model → **#512** (C-16 holds the placeholder).
- **Case and aggregate exports** — out of initial SaaS 2.0 scope; the delivered
  demo's permission-aware PDF (#49) is the reference if they are added later
  (P-15).
- **The replacement fictional demo** — its build, deployment, verification,
  refresh model and the current-demo retirement runbook → **new SaaS 5 issue**
  (§4.3); execution is a separate owner go decision.
- **Language completeness gate mechanics** — INV-18 is enforced through
  `docs/product/saas-2.0-delivery-template.md` (**#507**).

---

## 8. Non-goals

Capabilities Convive SaaS 2.0 will **not** build. Distinct from §2.3 (out of scope
*for now*): these are permanent boundaries. Each names the invariant and issue
that enforce it.

1. **Automated safeguarding, bullying or legal decision-making** — no diagnosis,
   risk score, credibility or severity assessment, protocol selection, outcome
   label, or automated recommendation about a person or a situation. *(INV-4,
   INV-5; #527, #531)*
2. **Real-data intake without the written gate** — no real safeguarding-domain
   data and no real-centre pilot before #538's go decision, #537 evidence and the
   #504 conditions. *(INV-11; #504, #537, #538)*
3. **Hidden tracking or profiling** — no third-party analytics, advertising,
   behavioural tracking, reporter fingerprinting, or non-essential cookies.
   *(INV-15; #521)*
4. **Unapproved third-party integrations** — no external service processing data
   on Convive's behalf, and no external resource loaded into a Convive surface,
   without the subprocessor decision in #505 and, for personal data, the readiness
   gate. *(INV-15; §2.4; #505, #504)*
5. **A general-purpose file store** — evidence is bounded and case-scoped; no
   folder trees, document libraries, arbitrary file hosting or shared drives.
   *(INV-13; #523)*
6. **An emergency or crisis-response channel** — no urgency triage, no monitoring
   promise, no response-time guarantee, no representation as an emergency service;
   every public surface points to 112. *(INV-9; #516, #518)*
7. **A demo→SaaS migration path** — the stable demo is retired deliberately, never
   converted into a tenant or mutated into the SaaS; no data flows between them.
   *(INV-16; D1, #506)*
8. **Identity-based access recovery for reporters** — no email or identity
   mechanism that would weaken reporter anonymity to restore access. *(INV-8;
   #519)*
9. **Job-title-based or blanket access** — no role mapped to a job title with
   automatic access to all cases; no universal management access; no
   emergency/break-glass administrator. *(INV-2; #513, #527)*
10. **Silent data retention or deletion** — nothing retained past its stated
    period without notice; no soft-delete presented as deletion; deletion is real.
    *(INV-14; #536)*
11. **Partial-translation "published" claims** — no SaaS surface announced as
    fully published in a locale before the whole-locale review gate. *(INV-18;
    #507)*
12. **Automated case progression** — no case state, task, follow-up or
    communication that completes on a timer or schedule. *(INV-5; #527)*
13. **Cross-tenant visibility of any kind** — no shared search, no global
    directory, no cross-centre analytics, no "all centres" view. *(INV-1; #514)*
14. **AI or machine-learning processing of evidence** — audio and video evidence
    are never transcribed and never run through any AI/ML model (speech-to-text,
    content analysis or otherwise); the assigned professional reviews it directly.
    Considered and declined explicitly (§13, 2026-09-03): the professionals who
    review evidence are the ones who listen to and watch it, so automated
    processing adds no value here. *(INV-13; #523, #524, #525)*

---

## 9. Infrastructure model and delegated decisions

§9.1 is decided (§13). The rest set a boundary that a named issue fills within.

### 9.1 Funding, hosting and subprocessor model

**Sandbox / SaaS 2.0 development model — decided (see §13, 2026-09-03).**

- **Application hosting.** An isolated `px-convive-saas-*` project on the shared
  OVH VPS (EU, France), separate from the delivered demo's `px-convive-*` project
  and following the PROJECTX per-project isolation pattern (own edge/internal
  networks, own compose, own secrets, own domain). Minimal footprint (§4.4): only
  the application core — API, gateway, database, cache — runs on the VPS.
- **Object storage.** All evidence, attachments, audio and video are stored in
  **external EU object storage** (candidate: Scaleway Object Storage — France,
  permanently-free tier, S3-compatible, DPA). The database stores only references;
  no large media is written to the VPS disk.
- **Transactional email.** An **external EU transactional-email subprocessor**
  (candidate: Brevo — France, permanently-free tier, DPA). Confirmed at #520 with
  real deliverability evidence. Generic content only (INV-8, INV-9).
- **Malware scanning.** ClamAV in-container, as already proven on the delivered
  demo. No external scanning subprocessor.
- **Backups.** External, off-host (Cloudflare R2 free allowance as today, or
  consolidated on Scaleway).
- **Subprocessors under this model:** Cloudflare (DNS/CDN), the email provider,
  the object-storage provider — each on a permanently-free tier with a signed DPA
  before any non-fictional use. **No paid subprocessor and no new paid line.**

**Real-data pilot model → deferred to #537 / #538.** Decided there with its
funding and DPA context. Inherited constraints: free-tier first; EU data
residency; a signed Article 28 agreement with every subprocessor that would touch
personal data; the free tier of an otherwise-paid service is acceptable only
within its permanently-free allowance and only if it cannot silently convert to
billing. If no free option can meet EU residency **and** Article 28 **and** the
functional need together, that gap is **escalated to the owner** — it is never
resolved by assuming paid infrastructure.

**No accelerated minimal-pilot track — decided (§13, 2026-09-03).** A staged
option was considered: define a "safety-critical minimum" subset (tenant
isolation, backups, auth — roughly SaaS 1 plus the assurance work in #534/#535)
and evaluate a real pilot against that subset alone, well before the rest of the
roadmap (public entry polish, evidence, guidance library, reporting) is built.
The owner explicitly declined it: there is no deadline pressure, and the product
is delivered as one coherent whole. #503–#543 are built in the roadmap's existing
order; #537/#538 are evaluated only once **all** of it is done — not against a
reduced subset. This is a considered choice, not an oversight, should anyone
later propose accelerating it.

### 9.2 Email under sandbox operation

- **Professional / account email — decided (§13, 2026-09-03).** Real
  transactional email to a verified adult professional's account address
  (verification, security, recovery, membership and case-activity notifications)
  is a **day-one** part of SaaS 2.0, sent via the §9.1 email subprocessor. Content
  is generic (INV-8): it never carries case detail, names, or a link that bypasses
  the secure channel. This is within D2's "minimum necessary adult account data".
- **Reporter email — decided (§13, 2026-09-03).** A reporter is not an account
  holder, so D2's account-data basis does not cover a reporter's email. The
  optional email prompt at the public entry stays opt-in, never required (R-8) —
  presented as professionally and persuasively as the entry's design allows
  (`[owner decision]`, R-4/R-8/R-9), because a required field would suppress
  reporting from exactly the person most afraid of being identified. Under
  **sandbox operation**, real delivery is restricted: it is sent only to an
  address that already belongs to a verified member of that centre, never to an
  arbitrary public address someone types in — the SaaS is openly self-service
  (§2.1), so this boundary matters more, not less. #519 confirms the post-#538
  behaviour for real pilots; #520 implements the approved path — verified,
  opt-in, reversible, generic content only, contact data isolated, delivery
  failures handled safely — through the same §9.1 subprocessor.

### 9.3 Other delegations

The remaining delegated items and their owning issues are listed in §7.2; they
carry no additional charter constraint beyond the invariants in §5.

---

## 10. Issue-to-expectation index

Each SaaS 2.0 issue with the charter expectations (`R/T/C/P-*`) and invariants
(`INV-*`) it must satisfy, its milestone, the kind of verification evidence
expected, and any DR-1 artifact required before implementation.

### SaaS 0 — Product, legal and architecture gates (milestone #15)

| Issue | Expectations | Invariants | Evidence expected | DR-1 artifact |
|---|---|---|---|---|
| #503 | *this charter* | — | reviewed product document + this index | — |
| #504 | C-10, C-11 | INV-11 | documented go/no-go gate with evidence-required decisions; no compliance claim | — |
| #505 | §9.1 | INV-15, INV-16 | published model decision; EU residency + Art. 28 assessment; free-tier constraint honoured; no provider selected | — |
| #506 | T-15, C-14 | INV-1, INV-16 | tenant/membership/partitioning/cross-tenant-denial invariants; migration/rollback boundaries; no silent demo→tenant conversion — decided in **ADR-0031** | — |
| #507 | §6.0, §7 (format); DR-1 generalisation | INV-18 (enforcement) | the reusable delivery template; alignment with issue→PR workflow; visual/a11y/contract/operational evidence types — delivered as **`docs/product/saas-2.0-delivery-template.md`** | — |
| #508 | P-1, P-2, P-5; C-4 | INV-2, INV-4 | reviewed role-aware IA with genuinely distinct per-role dashboard defaults (not one screen re-skinned); default layout urgency-first + calm tone (§13); least privilege preserved; no fake analytics; validated responsive/accessible direction | dashboard, actionable queue, case-workspace IA, role set |
| #509 | T-17, C-9 (context) | INV-16 | environment purposes (local/test/sandbox/demo-prod/pilot); release/flag/config/rollback; no fictional↔customer co-mingling | — |

### SaaS 1 — Tenant identity and onboarding (milestone #16)

| Issue | Expectations | Invariants | Evidence expected | DR-1 artifact |
|---|---|---|---|---|
| #510 | C-14 | INV-1 | domain/integration/migration tests including negative tenant cases; no self-service, no real data | — |
| #511 | T-1–T-7, T-9, T-11, T-18 | INV-3, INV-11 | verified-email activation; password controls; throttling; session invalidation; non-disclosure; a11y + recovery coverage | sign-up flow, empty state, account settings |
| #512 | C-1, C-2, C-3, C-9, C-16; T-13, T-17 | INV-1, INV-2, INV-11, INV-13 | minimised centre data (territorial field added, §13); atomic single accountable admin; identity + optional logo, scanned, bounded to one asset; state distinction (labels decided, §13); collision/ambiguity/enumeration prevention | creation form, centre identity screen (incl. logo upload) |
| #513 | C-4, C-5, C-6, C-7, C-8, C-15; P-16; T-7, T-10, T-14, T-16 | INV-2, INV-3, INV-12 | simple member list with role/status per row and inline actions (§13); invitation/suspension/revocation/role-change audit history; final-admin protection shown disabled, not failed after the fact; session invalidation; demo isolation; reassignment as a separately grantable capability, not a content grant | role set, member-management screen (simple list, inline actions) |
| #514 | C-14, P-17; T-6, T-15 | INV-1 | negative tests across API/URL/session/membership/attachment/export; stale sessions + recovery; isolation failure release-blocking | — |
| #515 | R-12, T-17, C-13, P-18 | INV-11, INV-16, INV-14 | sandbox notices/access/data/retention separate from demo; narrow deterministic seed/reset; deployment/observability/backup boundaries | — |

### SaaS 2 — Secure public centre entry (milestone #17)

| Issue | Expectations | Invariants | Evidence expected | DR-1 artifact |
|---|---|---|---|---|
| #516 | R-1, R-14; C-3, C-12 | INV-7, INV-9 | public route/identity-disclosure/lifecycle/rotation/revocation/enumeration rules incl. optional centre logo display; aligned with host boundary + QR-poster ADR | public-entry wording and steps |
| #517 | R-1; C-12 | INV-7, INV-9 | tenant-scoped revocable QR destination; default Convive-branded poster auto-generated at centre creation (§13); raw QR+link download with mandatory acknowledgement for self-made posters; poster carries readable URL + emergency guidance; QR never sole method; demo materials truthful/separate | QR poster layout — default template + raw-asset flow |
| #518 | R-1, R-2, R-3, R-4, R-5, R-6, R-7, R-9, R-10, R-12, R-14; C-3 | INV-1, INV-6, INV-9 | approved centre identity/instructions; non-emergency boundary; data-minimised intake; no cross-centre disclosure; safe fictional operation | branded gateway |
| #519 | R-8, R-10 (withdrawal); §9.2 | INV-8 | anonymity trade-off explained; contact/content separation; email ≠ recovery / case-detail transport; removal/retention/verification/opt-in defined | — |
| #520 | R-8; §9.2 | INV-8 | generic notifications, no case detail/credential; contact data isolated; reversible; provider/delivery failure safe | — |
| #521 | R-11, R-16; T-5 | INV-15 | rate limits; safe failure; log minimisation; incident signals; false-positive + bypass tests; no third-party tracking/sensitive log content | — |
| #522 | R-13, R-15; C-17 | INV-17, INV-18 | mobile/keyboard/screen-reader/plain-language/visual checks; readable fallback URL; error/recovery states; findings recorded truthfully | — |

### SaaS 3 — Case data and professional workspace (milestone #18)

| Issue | Expectations | Invariants | Evidence expected | DR-1 artifact |
|---|---|---|---|---|
| #523 | R-7, P-11; §7.1 ("subir páginas") | INV-13 | permitted evidence types (documents, images, audio, video)/intent/limits/retention/metadata/access/preview/deletion/unsupported cases; no generic file drive; no AI/ML processing (§8 non-goal 14) | — |
| #524 | R-7, P-11 | INV-13, INV-1, INV-12 | tenant + report scope; size/type limits incl. audio/video; private quarantine; scanning (format/malware only, no AI); error paths; audit boundary; no public URL / processing before scan | — |
| #525 | P-11 | INV-13 | application-mediated no-store access; in-app playback for audio/video, never raw download; authorisation; safe preview; deletion/retention state; audit events; cross-tenant/unauthorised denied | — |
| #526 | P-1, P-2, P-14, P-20 | INV-1, INV-2, INV-4, INV-8, INV-9 | dashboard/inbox/case-work separation — P-2 is its own full screen with filter/sort/group, not a dashboard expansion (§13); real authorised data not mock metrics; only permitted actions; mobile + keyboard; per-user drag-and-drop widget customisation from a permission-scoped built-in catalogue, personal only, resettable to role default; complete notification-event taxonomy with live in-app, day-one email and opt-in web push, per-event preferences, generic content outside the app | dashboard (incl. widget catalogue + customisation), pending-actions screen (filter/sort/group), notification system (full event taxonomy) |
| #527 | P-3–P-10, P-12, P-13, P-15, P-16, P-20, P-21; C-15 | INV-1, INV-2, INV-4, INV-5, INV-6, INV-10, INV-12 | case workspace structure: fixed header + próximos-pasos strip + one filterable chronological story, never tabs (P-5, §13); case state set confirmed incl. reopening (P-8, §13); assignments; protocol-driven procedural steps with sourced deadlines and visible countdowns (P-9, with #530); communication records; threaded internal discussion with access-scoped @mentions (P-21); follow-up; understandable + permission-preserving; no automated diagnosis/protocol/notification | case workspace (header + próximos pasos + filterable story) |
| #543 (CA-6) | P-15, P-20 | INV-1, INV-2, INV-11, INV-15 | clean well-typeset PDF exports with no Convive branding but the centre's own optional logo if uploaded (case record, procedural-step/deadline view, communications log, operational reports); permission-scoped; in-memory, not persisted; audited; operational statistics from accessible real data, non-judgemental, no vanity metrics or tracking | PDF/report template |
| #528 | P-19; C-17 | INV-17 | desktop/mobile/keyboard/screen-reader/contrast/error/empty across dashboard/inbox/case/evidence/admin; remediation separate + traceable | — |

### SaaS 4 — Regulatory source library and experience (milestone #19)

| Issue | Expectations | Invariants | Evidence expected | DR-1 artifact |
|---|---|---|---|---|
| #529 | P-12 | INV-10 | source authority/version/territory/review-date/maintainer workflow/user-facing limits; **all 19 jurisdictions in scope** (§13, 2026-09-03), migrated not re-researched; no legal advice or universal deadlines | — |
| #530 | P-12 | INV-10, INV-12 | source/version/territory/provenance/review-status preserved for all 19 migrated jurisdictions; changes reviewed + auditable; no free-text legal-rule editing by ordinary users | — |
| #531 | P-12 | INV-10, INV-4 | compact footnote-styled citation line, never a panel (§13); discloses territory/source/review status; guidance ≠ binding determination; role + case boundaries preserved; accessible source links; territory routed from the centre's province (C-1) | applicable-guidance placement and form |
| #532 | P-12 | INV-10 | primary official sources for all 19 jurisdictions, carried over from the demo's verified `WorkflowSourceVersion` data; version + review date recorded; currency re-checked, not re-researched; uncertainty + review triggers identified | — |
| #533 | — | INV-10, INV-12 | authorised maintainer workflow; change review; version history; validation + rollback; no unreviewed source published as guidance | — |
| #544 (CA-7) | P-12; C-1 | INV-10, INV-12 | migrate all 19 already-verified territorial profiles from the demo into the SaaS 2.0 registry (#530); per-jurisdiction currency review confirming each source is still in force; authority (binding/recommended/internal) preserved exactly, never upgraded by assumption | — |

### SaaS 5 — Assurance, controlled pilot and launch decision (milestone #20)

| Issue | Expectations | Invariants | Evidence expected | DR-1 artifact |
|---|---|---|---|---|
| #534 | — (all security-relevant) | INV-1, INV-2, INV-13, INV-3 | threat-model refresh; penetration-test scope; dependency/secret review; authorization + attachment testing; remediation ownership; independent-review criteria; no certification claim | — |
| #535 | C-14 | INV-1, INV-14, INV-3 | recovery exercise covering encrypted backup, restoration, session/capability purge, tenant isolation, evidence handling, retained evidence; no production data unless approved | — |
| #536 | T-11; P-8 (archiving retention) | INV-14, INV-12 | retention/legal-hold/deletion/anonymisation/access-export/incident workflows with owner boundaries, tests, auditability; no universal retention period invented | — |
| #537 | C-10, C-11 | INV-11 | review of all prior gate evidence, support ownership, training, a11y, security, recovery, processor decisions; unresolved blockers documented; no automatic enrolment | — |
| #538 | C-11 | INV-11 | written, owned, evidence-based go/no-go from legal/DPO/security/ops/product gates; a missing gate blocks pilot use; sandbox continues regardless | — |
| #539 | — | — | states what is live/sandbox/pilot; no unevidenced safety/anonymity/compliance/adoption claims; approved materials only; publication remains the owner's decision | — |
| #540 (CA-1) | replacement fictional demo + retirement runbook | INV-16, INV-14 | build from SaaS codebase; Demo-context deployment; verification checklist; retirement runbook (redirect, retention, Search Console/Bing, `privacy@`, comms); refresh model; **execution = separate owner go decision** | replacement-demo presentation |

### Orphans — expectations/invariants with no owning issue (→ charter actions)

- **Replacement fictional demo** (§4.3) → **new SaaS 5 issue** above (blocks
  #539).
- **INV-18 language completeness gate** enforcement → folded into **#507** (the
  delivery template makes "all locales reviewed as one gate" a required evidence
  item).
- **T-8 account email change** → **#542** (CA-5).
- **P-15 / P-20 exports and operational reporting** → **#543** (CA-6).
- **DR-1 preconditions** for the 18 `[owner decision]` surfaces → **#541**
  (CA-4).

---

## 11. Change log

| Version | Date | Change |
|---|---|---|
| 0.1 | 2026-09-03 | Initial charter for issue #503: purpose and scope; constraints (§2.4); terminology (§3); product model — D1, D2, demo lifecycle and the replacement-demo decision (§4); delivery rule DR-1; 18 cross-cutting invariants (§5); 70 expectations across the reporter, teacher, centre and professional journeys (§6); ambiguities resolved or deferred (§7); 13 non-goals (§8); two constrained delegations (§9); issue-to-expectation index (§10); charter actions (§12). |
| 0.2 | 2026-09-03 | Owner decision walkthrough (§13). Point 1 closed: infrastructure and email model decided (§9.1, §9.2, §4.1, §4.4, §2.4, CA-3). SaaS 2.0 hosting = isolated project on the shared OVH VPS (real specs verified: 8 vCPU / 22 GB / 193 GB, idle), minimal footprint; all media on external EU object storage; external EU transactional email (professional email day-one); portable by design. Seven vision items from the owner brain-dump logged for the walkthrough (§13). |
| 0.3 | 2026-09-03 | Point 2 closed. INV-10 rewritten ("sourced guidance, honest deadlines" — deadlines and countdowns allowed when sourced, inert and non-authoritative). Protocol-driven procedural steps with sourced deadlines and visible countdowns (P-9, P-8). Exports flipped to in scope: clean unbranded PDFs (P-15). New P-20 operational reporting and statistics. New issue CA-6. `[owner decision]` set now 19. Vision items 2 and 5 resolved. |
| 0.4 | 2026-09-03 | Point 3 closed. Real-time notifications: live in-app (`[rule]`, no reload), day-one email, and day-one opt-in web push. INV-8 rewritten to cover all out-of-app notifications (reporter + professional) as generic with isolated endpoints. Minimal push service worker in scope; installable-offline still out. Vision item 1 resolved. |
| 0.5 | 2026-09-03 | Presentation model resolved: self-service registration into the full product on sandbox data; real-data activation is a personal request to the operator, never self-service (§2.1, C-10, §4.2). Point 4 closed: reporter email stays opt-in and persuasively presented (`[owner decision]`, R-8); real sandbox delivery restricted to a centre's own verified members (R-12, §9.2). `[owner decision]` set now 20. |
| 0.6 | 2026-09-03 | Charter actions CA-1…CA-6 executed. Issues #540, #541, #542, #543 created; `docs/discovery/product-scope.md` and `NOTICE.md` record the August 2027 availability commitment (§2.4); **ADR-0030** records the infrastructure and free-tier model (§2.4, §4.4, §9.1). Roadmap #71 updated with the new issues and refined non-negotiable boundaries. |
| 0.7 | 2026-09-03 | Point 7 confirmed as-is: the real-data go/no-go stays exactly as originally specified (owner-only, written, evidence-complete, evaluated only once the whole roadmap is done). An accelerated "safety-critical minimum" pilot track was proposed and explicitly declined — no deadline pressure, product delivered as one coherent whole (§9.1). Bloque 1 of the owner walkthrough is now complete. |
| 0.8 | 2026-09-03 | Vision item 3 resolved. New **P-21** — professional discussion within a case: threaded, access-gated @mentions (mentioning never grants access), part of the append-only case history, never itself a reporter communication. Per the owner's explicit request that "the notification system has to be perfectly designed," **P-2 rewritten** with a complete, mandatory notification-event taxonomy (assignment, deadline approaching/overdue, reporter update, @mention, invitation, role change, centre state change, evidence scan) and per-event channel preferences, with in-app delivery that can never be fully silenced. |
| 0.9 | 2026-09-03 | Vision item 4 resolved. Audio and video are permitted evidence types (INV-13, R-7, P-11), played back application-mediated in-app, never as a raw download. "Analysed" is scoped to malware/format scanning only (ClamAV) — no transcription and no AI/ML processing of any kind. Explicitly considered (a self-hosted transcription option was proposed) and declined by the owner: the professionals who review evidence listen to and watch it directly, so automated processing adds no value. New §8 non-goal 14. No change to the ADR-0030 infrastructure footprint. |
| 0.10 | 2026-09-03 | Vision item 6 resolved, fully specified after a second pass. **P-16 rewritten**: reassignment is a distinct capability (C-4), never bundled with case-content read access; the reassigner sees only minimal metadata (assignee, state, domain, date), never content. Default holders: direction and wellbeing/protection coordination; the workspace administrator may be granted it explicitly but not automatically. Destination must already hold case-work capability (never a no-default-access role like administración y servicios). Closed the self-assignment loophole: reassigning to oneself is allowed only if already eligible to receive case assignments, and is always fully audited. C-15 updated to match. |
| 0.11 | 2026-09-03 | Vision item 7 resolved. Owner chose full personalisation (option 3, both layers): **P-1 expanded** with genuinely distinct per-role dashboard defaults (#508, design depth not a new mechanism) **and** full per-user drag-and-drop widget customisation — add/remove/resize/reorder from a permission-scoped, Convive-only built-in widget catalogue (pending actions, deadlines, guidance, reporting, mentions), resettable to the role default, purely personal and never crossing centres. This is the last of the seven owner-brain-dump vision items; only Bloque 2 and Bloque 3 of the walkthrough remain. |
| 0.12 | 2026-09-03 | Bloque 2 closed (5 text-specifiable surfaces). Centre-state Spanish labels and the case-state set (plus a new reopening rule) resolved to fixed `[rule]`s and dropped from the `[owner decision]` set (20 → 18). Role model split into professional function vs technical capability as two independent axes, with a starting capability matrix (C-4). Draft copy written for the public entry (R-4) and reporter receipt (R-9); both await a final visual artifact at #518. Only Bloque 3 (12 layout-heavy surfaces) remains in the owner walkthrough. |
| 0.13 | 2026-09-03 | Bloque 3 started. **T-1 owner intent**: single-step sign-up (full name, email, password only), tone per `docs/brand/README.md`'s visual tone verbatim, professional feel from copy/privacy-moment/password feedback rather than extra fields; phone, centre and function explicitly excluded from sign-up. **T-9 owner intent**: progressive, optional profile completeness — profile picture and a self-described professional title, both purely display (the title grants no capability and is never confused with the C-4 function that actually governs access). 2 of 12 Bloque 3 surfaces captured. |
| 0.14 | 2026-09-03 | New cross-cutting principle (§6.0): a **shared, persistent app shell** (settings, sign out, help, centre selector) spans every authenticated teacher/professional screen; each surface's `[owner decision]` scope is its content area only. **T-12 owner intent**: minimalist content area (only the two onboarding paths, primary action visually heavier), account-level chrome lives in the shell, brand visual language applied literally. **T-15 owner intent**: the centre selector lives in the shell as a simple dropdown, hidden for single-centre accounts. 4 of 12 Bloque 3 surfaces captured. |
| 0.15 | 2026-09-03 | **C-1 owner intent** (5/12): minimalist single-page form, more data than the bare minimum within coherence (name, required province/CCAA, optional level/municipality), territorial-coverage indicator next to the province field. That indicator surfaced a real error: the charter had scoped territorial guidance to Andalucía only, based on a stale memory of unreliable research. **Corrected against the actual repository state**: issue #253 already verified and merged all 19 Spanish jurisdictions from their official gazettes; scoping SaaS 2.0 to Andalucía only would have discarded that work. Territorial scope rewritten throughout (§2.2, §2.3, §3.5, §7.1, §10); new issue #544 (CA-7) migrates the 19 sources with a currency review; GitHub issues #529/#530/#532 corrected; stale scope statements in `product-scope.md`/`regulatory-context.md` fixed (CA-8). |
| 0.16 | 2026-09-03 | **C-3 owner intent** (6/12): optional centre logo/crest, uploaded once, reused consistently on the public entry and every exported PDF. Clarified this refines rather than reverses P-15's "no logo" rule — that rule was always about Convive's own branding, not the centre's. Bounded to one scanned image asset; covered by C-3's existing no-false-affiliation rule with no automated content judgement. |
| 0.17 | 2026-09-03 | **C-5 owner intent** (7/12): simple member list, inline row actions, history kept separate. **C-12/#517 owner intent** (8/12): default Convive-branded poster auto-generated at centre creation; raw QR+link download gated on a mandatory acknowledgement, since Convive cannot enforce non-emergency-notice content on a self-made poster built outside the app. |
| 0.18 | 2026-09-03 | **P-1 owner intent** (9/12), the surface DR-1 exists for: default layout is urgency-first ("necesitas actuar", capped, overdue then soonest-due, calm empty state) with a quieter general snapshot below, presented in the brand's calm register rather than a triage/emergency board (would contradict INV-9). Synthesis, not either extreme alone. |
| 0.19 | 2026-09-03 | **P-2 owner intent** (10/12): a full pending-actions screen with filter/sort/group, distinct from P-1's short calm preview, reachable directly from the shared shell. Shell principle (§6.0) names both P-1 and P-2 as direct nav destinations. |
| 0.20 | 2026-09-03 | **P-5 owner intent** (11/12): case workspace = fixed header + fixed próximos-pasos strip + one filterable chronological story (never tabs), reached after iterating tabs vs. single-feed vs. hybrid designs against P-6's append-only history and P-1's urgency pattern. |
| 0.21 | 2026-09-03 | **P-12 owner intent** (12/12) — **Bloque 3 complete.** Applicable guidance placed as a compact, always-present, footnote-styled citation line, never a fixed panel, expanding to full source on click; also corrected its lingering "Andalucía initially" wording to the 19-jurisdiction scope (§3.5). **The full owner decision walkthrough is now complete**: all of Bloque 1 (7 charter-level decisions), Bloque 2 (5 text-specifiable surfaces) and Bloque 3 (12 layout-heavy surfaces), plus the seven vision items from the initial owner brain-dump, are resolved and recorded in §13. |
| 1.0 | 2026-09-03 | Final consistency pass: verified INV-1..18 contiguous, the `[owner decision]` set at 18 with no stale counts, no leftover pending/TBD markers anywhere in rule text, all 8 charter actions (CA-1..CA-8) done, all 24 walkthrough items closed. Promoted from draft (0.x) to **1.0**: the charter is a complete, fully-decided source of record ready for implementation to begin from. |
| 1.1 | 2026-09-05 | Post-1.0 implementation amendments (engineering territory, no owner decision reopened). **#506** decided as **ADR-0031**: tenant = the existing `Organisation` entity, a mandatory Doctrine query filter as a second isolation enforcement layer alongside existing per-service checks, migration/rollback/recovery boundaries for a growing multi-tenant table set. **#507** delivered as `docs/product/saas-2.0-delivery-template.md`: generalises §6.0's expectation format, DR-1's checklist, and INV-17/INV-18's evidence types into one reusable per-issue template; every forward-reference to "#507 will..." across §1, §3.7, §5, DR-1, §6.0 and §10 now points at the delivered file. |

---

## 12. Charter actions

Follow-ups this charter creates. They are tracked **outside** the charter pull
request so it stays reviewable. None is a code change to a product surface.

| # | Action | Kind | Status |
|---|---|---|---|
| CA-1 | Create the SaaS 5 issue *"Specify the replacement fictional demo and the current-demo retirement runbook"* (§4.3): what it shows, the Demo-context deployment shape, verification checklist, two-phase retirement runbook, refresh model, trigger conditions. Execution is a later owner go decision. Depends on #522/#528/#531; blocks #539. | new issue | ✅ done — **#540**, 2026-09-03 |
| CA-2 | Record the August 2027 public-availability commitment in `docs/discovery/product-scope.md` §"Public demonstration environment", with a cross-reference line in `NOTICE.md` under the Aircury acknowledgement. | doc edit | ✅ done, 2026-09-03 |
| CA-3 | Write a new ADR for the infrastructure and free-tier-only policy: the decided sandbox stack (isolated `px-convive-saas-*` project on the shared OVH VPS; external EU object storage, candidate Scaleway; external EU transactional email, candidate Brevo; ClamAV in-container; off-host backups), the minimal-footprint-and-portability principle (§4.4), and the nuance that the free tier of an otherwise-paid service is acceptable only within its permanently-free allowance and only if it cannot silently convert to billing. Cited by §2.4, §4.4 and §9.1. | new ADR | ✅ done — **ADR-0030**, 2026-09-03 |
| CA-4 | Create the follow-up issue *"Apply DR-1 preconditions to SaaS `[owner decision]` issues"*: amend the acceptance criteria of #508, #512, #513, #516, #517, #518, #519, #520, #526, #527, #531 (plus CA-6) to carry the DR-1 precondition and name their artifact. | new issue | ✅ done — **#541**, 2026-09-03 |
| CA-5 | Create a new issue owning the account email-change flow (expectation T-8), which #511 does not cover. | new issue | ✅ done — **#542**, 2026-09-03 |
| CA-6 | Create the SaaS 3 issue *"Clean document exports and operational reporting"* (P-15, P-20): well-typeset unbranded PDF exports of the case record, procedural-step/deadline view, communications log and operational reports; permission-scoped; in-memory, not persisted; audited. Operational statistics from accessible real data, non-judgemental, no vanity metrics or tracking. The PDF/report template is an `[owner decision]` artifact. | new issue | ✅ done — **#543**, 2026-09-03 |
| CA-7 | Create the SaaS 4 issue "Migrate the 19 already-verified territorial profiles into the SaaS 2.0 registry" (#530/#529/#532 corrected). | new issue | ✅ done — **#544**, 2026-09-03 |
| CA-8 | Fix stale scope statements in `docs/discovery/product-scope.md` ("support for every Spanish autonomous community" listed as out of scope) and `docs/discovery/regulatory-context.md`, which predate the 19-jurisdiction territorial work completed under #253 and no longer reflect the delivered demo. Factual correction, not a product decision. | doc edit | ✅ done, 2026-09-03 |

---

## 13. Owner decision walkthrough and log

Nothing is decided by omission. Every substantive product or infrastructure
decision the owner makes is logged here with its date and where it landed in the
document, so context is never lost.

### 13.1 Decision log

| Date | Decision | Recorded in |
|---|---|---|
| 2026-09-03 | D1 — product topology (two lines: stable demo + SaaS 2.0). | §4.1 |
| 2026-09-03 | D2 — centre-first onboarding; centre lifecycle `Sandbox` / `Activation under review` / `Activated`. | §4.2 |
| 2026-09-03 | Language rule — English repo docs; Spanish-first product copy; whole-locale release gate. | §1, INV-18 |
| 2026-09-03 | Replacement fictional demo — specify now (new SaaS 5 issue), execute on a later owner go decision; the current demo guarantees public availability through ≥ August 2027 regardless. | §4.3, CA-1 |
| 2026-09-03 | Free-tier ADR + August 2027 availability recorded as charter actions, not assumed to exist. | §2.4, CA-2, CA-3 |
| 2026-09-03 | **Point 1 — infrastructure & email.** SaaS 2.0 hosting = isolated `px-convive-saas-*` project on the shared OVH VPS (verified 8 vCPU / 22 GB RAM / 193 GB disk, load ~0), minimal footprint. All evidence/attachments/audio/video on **external EU object storage** (candidate Scaleway); DB holds references only; no large media on the VPS. **External EU transactional email** (candidate Brevo, free tier + DPA); professional/account email is **day-one**; reporter email stays deferred to #519/#520. Malware scanning via ClamAV in-container. Backups off-host. No paid subprocessor, no new paid line. Real-data pilot infra deferred to #537/#538 with inherited free-tier / EU / Article 28 constraints and owner escalation if no free option fits. Portability principle added (§4.4). | §9.1, §9.2, §4.1, §4.4, §2.4, CA-3 |

| 2026-09-03 | **Point 2 — deadlines, timers, clean exports and statistics.** INV-10 rewritten from "no countdown" to "sourced guidance, honest deadlines": deadlines and countdowns are shown wherever they help, provided each states its origin (a reviewed protocol version, or an explicit human decision), none is presented as universal legal fact or Convive's own determination, none acts automatically, none implies emergency handling. Activating a managed case under a protocol pre-populates that protocol's procedural steps (activate protocol, meet families, communicate to inspection, communicate to prosecutor where applicable) as tasks with the protocol version's stated timeframes and visible countdowns (P-9, P-8). P-15 flipped from deferred to in scope: clean, **unbranded**, well-typeset PDF exports of the case record, procedural-step view, communications log and operational reports; permission-scoped; in-memory; audited. New P-20 — operational reporting and statistics from accessible real data, non-judgemental, no vanity metrics or tracking. New issue CA-6 owns exports + reporting; the PDF/report template is a new `[owner decision]` surface (set now 19). | INV-10, P-1, P-8, P-9, P-12, P-15, P-20, §7.1, §10, CA-6 |

| 2026-09-03 | **Point 3 — real-time notifications.** Three channels, all day-one: live in-app updates (a professional never reloads to see a new assignment, overdue deadline, reply or @mention) as a `[rule]`; email (decided in Point 1); opt-in **web push** (end-to-end encrypted generic payload; transit through the browser vendor's push service recorded in the #504 DPIA; a minimal push service worker is in scope, installable-offline is not). INV-8 rewritten to cover all out-of-app notifications — reporter and professional — as generic, with isolated opt-in reversible endpoints. How notifications surface and their wording are an owner decision. | INV-8, P-2, §2.3, §10 (#526) |

| 2026-09-03 | **Presentation model — "full SaaS" resolved.** Convive SaaS 2.0 presents as a complete, real SaaS, not a gated preview: registration is self-service and grants the full product immediately, operating on fictional sandbox data. Moving a centre beyond sandbox to real data is never a self-service toggle — it is a **personal request to the accountable operator** (e.g. an email), reviewed against the existing real-data gate (#504/#537/#538). This resolves the owner's "full SaaS" concern without weakening INV-11 or D2: the product feels complete from minute one; the real-data boundary is a human decision, not a form that processes itself. | §2.1, §4.2, C-10 |
| 2026-09-03 | **Point 4 — reporter email under sandbox, closed.** The optional email prompt at the public entry stays opt-in, never required — a required field would suppress reporting from the person most afraid of being identified (R-8, now also `[owner decision]` for how persuasively it is presented). Real delivery in sandbox operation is restricted to addresses that already belong to a verified member of the centre; never to an arbitrary public address, precisely because the SaaS is now openly self-service. #519/#520 confirm the post-#538 real-pilot behaviour. `[owner decision]` set now 20 (added R-8). | R-8, R-12, §9.2, §2.1 |

| 2026-09-03 | **Bloque 2 — 5 text-specifiable surfaces closed.** (8) Centre-state Spanish labels fixed: "Modo de prueba" / "Activación en revisión" / "Activado" — resolved to `[rule]`, DR-1 satisfied. (9) Case-state set confirmed as-is, plus a new reopening rule for `cerrado`/`archivado` cases (reason required, audited, never automatic) — resolved to `[rule]`. (10) Role model split into two independent axes — professional function (the six demo functions) vs technical capability (workspace admin, reassignment) — with a starting capability matrix; remains `[owner decision]`, #508 validates the matrix with real research. (11)/(12) Draft copy written for the public entry and the reporter receipt (anonymity reassured first, evidence follows the main text, receipt confirms a human reads it and that the code also lets a reporter add more information later); both remain `[owner decision]` pending a final visual artifact at #518. `[owner decision]` set now 18 (C-9, P-8 dropped out; §3.7). | C-9, P-8, C-4, R-4, R-9, §3.7, §10 |

| 2026-09-03 | **Territorial scope corrected: 19 jurisdictions, not Andalucía only.** The charter had scoped SaaS 2.0's guidance to Andalucía only, on the (once-true) assumption that broader territorial research was unreliable — per the memory of the original 16 August 2026 research pass, which was wrong in most of what it was checked against. Verified against the actual repository state (not memory): issue #253 already redid this properly and closed — all 17 autonomous communities plus Ceuta and Melilla, each sourced from the official gazette read in full (27–88 pages), 8 of 19 correcting real errors in the original pass, merged into `apps/api` (`WorkflowSourceVersion`, `WorkflowTaskTemplate`). Restricting SaaS 2.0 to Andalucía would have discarded verified work for no reason. **Corrected**: §2.2, §2.3, §3.5 (Territorial profile), §7.1, §10 (#529, #530, #531, #532), `docs/discovery/product-scope.md` and `docs/discovery/regulatory-context.md` (stale scope statements, CA-8). **New**: C-1 gains a province/CCAA field routing to the matching territorial profile, with an honest coverage indicator; issue #544 (CA-7) migrates the 19 verified sources into the SaaS 2.0 registry with a per-jurisdiction currency review, not a re-research. GitHub issues #529 and #532 corrected directly; #529/#530/#532 carry an explanatory comment. | §2.2, §2.3, §3.5, §7.1, §10, C-1, CA-7, CA-8 |

| 2026-09-03 | **C-3 owner intent** (6/12): optional centre logo, uploaded once in C-3, reused consistently on the public entry (C-12) and every exported PDF (P-15) — the centre's own identity, not Convive's, so it does not reopen the "no branding" rule (that was always about Convive's branding). Bounded to one scanned image asset (INV-13); the existing "no false institutional affiliation" rule (C-3) covers the logo too, with no automated content judgement. Blank space if no logo is uploaded, exactly as before. P-15 and #512/#516/#543 updated to reference it. | C-3, P-15, §10 |

| 2026-09-03 | **C-5 owner intent** (7/12): simple member list, role/status per row, inline actions, no separate detail page; history (C-8) stays its own view; final-admin protection shown disabled on the row. **C-12/#517 owner intent** (8/12): default Convive-branded poster auto-generated the moment a centre is created (QR, fallback URL, non-emergency notice, centre name/logo); separate raw QR+link download for self-made posters, gated on a mandatory (not fine-print) acknowledgement that a self-made poster must include the fallback URL and non-emergency notice — an honest best-effort limit, since Convive cannot enforce content on a poster built outside the app. | C-5, C-12, §10 |

| 2026-09-03 | **P-1 owner intent** (9/12) — the surface DR-1 was written for. Default layout: urgency-first ("necesitas actuar", capped list, overdue then soonest-due, calm real empty state) as the organising principle, with brand-calm presentation rather than a triage/emergency-room register (would contradict INV-9); a quieter general open-case snapshot below, previewing P-20. Synthesis of "urgent first" and "calm overview", not either alone. | P-1, §10 |

| 2026-09-03 | **P-2 owner intent** (10/12): its own full screen (filter/sort/group, mark handled) in the shared shell, reachable directly, not only via P-1's "ver todo" — P-1's preview stays short and calm on purpose, so real daily-work capability lives here instead of being crammed into the dashboard. Shell principle (§6.0) updated to name P-1 and P-2 as direct nav destinations. | P-2, §6.0, §10 |

| 2026-09-03 | **P-5 owner intent** (11/12), reached after four iterations: never tabs (would fragment "one case, one place"); fixed header (state, people, domain) + fixed "próximos pasos" strip (this case's own deadlines, same calm-urgency pattern as P-1) + one filterable chronological story rendered from the append-only history (P-6) — filterable by type to manage a busy case, never split into separate tab-pages. | P-5, §10 |

| 2026-09-03 | **P-12 owner intent** (12/12 — Bloque 3 complete). Reached after iterating a próximos-pasos integration, a fixed sidebar and a buried button: a compact, always-present, footnote-styled citation line ("Guía aplicable: Protocolo de [territorio]...") near the header, visually neutral so it never reads as Convive actively advising, expanding to full source content on click. **The entire owner decision walkthrough (Bloque 1, 2 and 3) is now complete.** | P-12, §10 |

### 13.2 Walkthrough tracker

The owner walkthrough runs before the charter is merged and before AI agents build
code. Status: **complete** (2026-09-03) — all of Bloque 1 (7), Bloque 2 (5),
Bloque 3 (12) and the seven owner-brain-dump vision items are resolved. Nothing
below is open.

**Bloque 1 — charter-level decisions**

| # | Question | Status |
|---|---|---|
| 1 | Infrastructure & email model (#505 scope) | ✅ decided — §13.1, 2026-09-03 |
| 2 | Deadlines, timers, clean exports, statistics (vision items 2, 5) | ✅ decided — §13.1, 2026-09-03 |
| 3 | Real-time notifications, explicit (vision item 1) | ✅ decided — §13.1, 2026-09-03 |
| 4 | Reporter email under sandbox (#519/#520 posture) | ✅ decided — §13.1, 2026-09-03 |
| 5 | Replacement demo shape (G1) | ✅ decided — §13.1 |
| 5b | Presentation model — self-service "full SaaS" vs gated preview | ✅ decided — §13.1, 2026-09-03 |
| 6 | Charter actions CA-1…CA-6 | ✅ done — 2026-09-03. Issues #540 (CA-1), #541 (CA-4), #542 (CA-5), #543 (CA-6); doc edits CA-2 (`product-scope.md`, `NOTICE.md`); **ADR-0030** (CA-3). Roadmap #71 updated. Comment logged on #503. |
| 7 | #538 posture — real-data go/no-go always owner, written | ✅ confirmed as-is — §9.1, 2026-09-03. Accelerated minimal-pilot track considered and explicitly declined; full roadmap built as one whole. |
| 8 | Professional-to-professional discussion (vision item 3) | ✅ decided — §13.1, 2026-09-03 |
| 9 | Audio/video upload + "analysis" scope (vision item 4) | ✅ decided — §13.1, 2026-09-03 |
| 10 | Case reassignment scope (vision item 6) | ✅ decided — §13.1, 2026-09-03 |
| 11 | Dashboard personalisation: per-role vs per-user (vision item 7) | ✅ decided — §13.1, 2026-09-03 |

**Bloque 2 — `[owner decision]` surfaces specifiable in text now**

| # | Surface | Status |
|---|---|---|
| 8 | Spanish labels for the three centre lifecycle states (C-9) | ✅ decided — §13.1, 2026-09-03 |
| 9 | Final case-state set (P-8) | ✅ decided — §13.1, 2026-09-03 |
| 10 | Role set + per-role capabilities (C-4) | ✅ decided (starting matrix) — §13.1, 2026-09-03 |
| 11 | Public-entry wording and steps (R-4) | ✅ draft copy decided; final artifact at #518 — §13.1, 2026-09-03 |
| 12 | Reporter receipt wording (R-9) | ✅ draft copy decided; final artifact at #518 — §13.1, 2026-09-03 |

**Bloque 3 — layout-heavy `[owner decision]` surfaces (capture owner intent now, artifact at issue time)**

| # | Surface | Status |
|---|---|---|
| 1 | Sign-up flow (T-1) | ✅ intent captured — §13.1, 2026-09-03 |
| 2 | Account settings / progressive profile (T-9) | ✅ intent captured — §13.1, 2026-09-03 |
| 3 | Empty state (T-12) | ✅ intent captured — §13.1, 2026-09-03 |
| 4 | Centre selector (T-15) | ✅ intent captured — §13.1, 2026-09-03 |
| 5 | Centre creation form (C-1) | ✅ intent captured — §13.1, 2026-09-03 (incl. territorial-scope correction) |
| 6 | Centre identity screen (C-3) | ✅ intent captured — §13.1, 2026-09-03 (incl. optional centre logo, reused in PDFs) |
| 7 | Member-management screen (C-5) | ✅ intent captured — §13.1, 2026-09-03 |
| 8 | QR poster layout (#517) | ✅ intent captured — §13.1, 2026-09-03 |
| 9 | Dashboard / first screen (P-1) | ✅ intent captured — §13.1, 2026-09-03 |
| 10 | Actionable queue (P-2) | ✅ intent captured — §13.1, 2026-09-03 |
| 11 | Case workspace (P-5) | ✅ intent captured — §13.1, 2026-09-03 |
| 12 | Guidance placement (P-12) | ✅ intent captured — §13.1, 2026-09-03 — **Bloque 3 complete, 12/12** |

**Vision items from the owner brain-dump (2026-09-03) — mapping status**

| # | Item | Charter status |
|---|---|---|
| 1 | Real-time notifications connected to email, day-one | ✅ resolved — live in-app + email + opt-in web push, all day-one (P-2, INV-8) — Q3, 2026-09-03 |
| 2 | Explicit legal-minimum deadline timers on tasks | ✅ resolved — INV-10 rewritten; protocol-driven procedural steps with sourced deadlines and countdowns (P-9, P-8) — Q2, 2026-09-03 |
| 3 | Professional-to-professional discussion in the app | ✅ resolved — new P-21: case-scoped threaded discussion, access-gated @mentions, no free-floating chat; notification system (P-2) expanded to a complete, mandatory event taxonomy with per-event preferences — 2026-09-03 |
| 4 | Upload audio/video, viewable, "analysed" | ✅ resolved — audio/video are permitted evidence types (INV-13, R-7, P-11), played back in-app; "analysed" = malware/format scanning only (ClamAV). No transcription, no AI/ML processing of any kind — considered and explicitly declined by the owner (new §8 non-goal 14) — 2026-09-03 |
| 5 | Real statistics | ✅ resolved — P-20 operational reporting; real, permission-scoped, non-judgemental; exported via the clean PDF of P-15; owned by CA-6 — Q2, 2026-09-03 |
| 6 | Admin (or others) can reassign cases | ✅ resolved — P-16 rewritten: reassignment is a distinct capability (C-4), never bundled with content access; reassigner sees only minimal metadata. Default holders: direction, wellbeing coordination; administrator may be granted it explicitly, not automatically. Destination must hold case-work capability. Self-assignment loophole closed (only eligible professionals, always audited) — 2026-09-03 |
| 7 | Professional dashboard personalised "a full" | ✅ resolved — **both** layers: genuinely distinct per-role defaults (#508) **and** full per-user drag-and-drop widget customisation from a permission-scoped catalogue, resettable, never crossing centres (P-1) — owner chose option 3, 2026-09-03 |
