# SaaS 2.0 real-data readiness gate

**Status: unapproved draft. No centre has passed this gate. Nothing here is in
force, and completing this checklist for a centre is not the same as deciding
its answers.**

**Prepared by:** the repository maintainer, as preparatory material only.
**Last updated:** 5 September 2026.
**Review trigger:** whenever a document in this directory changes, whenever the
product charter's §9 changes, and before #537 evaluates any actual candidate
centre.

Read [the directory README](README.md) first for how a gap is written and why
none of them is filled. This document does not repeat those decisions — it is
the single checklist [#537](https://github.com/albertogalvez-dev/Convive/issues/537)
(the controlled-pilot readiness review) and
[#538](https://github.com/albertogalvez-dev/Convive/issues/538) (the go/no-go
decision) evaluate against, tying the existing drafts to the SaaS 2.0 product
charter (`docs/product/saas-2.0-charter.md`).

## What the charter already fixed (facts, not open here)

- **INV-11** — no real safeguarding-domain data is processed for any centre
  before #538's written go decision, and no centre reaches `Activated` before
  that centre's own gate evidence (this document) is complete.
- **C-10 / C-11** — requesting activation is a personal request to the
  accountable operator, never a self-service toggle; the decision is always
  the owner's, always written.
- **§9.1** — the *sandbox* infrastructure model (an isolated project on the
  shared OVH VPS, external EU object storage, external EU transactional email)
  is decided and published in **ADR-0030**. That is not the real-data pilot's
  infrastructure or subprocessor model — that stays a separate decision here
  and in #505/#537/#538, inheriting the same free-tier-first, EU-residency,
  signed-Article-28-agreement constraints.
- A private individual cannot be the controller for minors' safeguarding data
  in a school context (already settled in
  [controller-and-processing-decisions.md](controller-and-processing-decisions.md)).
  This applies to every centre; it is not re-opened per centre.

## Per-centre vs shared decisions

SaaS 2.0 is multi-tenant. That changes exactly one thing about the existing
drafts in this directory: some decisions are answered **once, for all of SaaS
2.0**; others must be answered **separately for every centre** that requests
activation, because a centre's controller is its own institution, not Convive.

**Shared — answered once, confirmed here by reference:**

- Subprocessor selection and signed DPAs for SaaS 2.0's own infrastructure
  (object storage, transactional email — see
  [vendor-and-access-governance.md](vendor-and-access-governance.md) and
  ADR-0030; this is a **separate inventory** from the fictional demo's).
- Incident-response infrastructure and escalation paths
  ([incident-and-safeguarding-playbooks.md](incident-and-safeguarding-playbooks.md)).
- The technical security-assurance baseline (#534, #535).

**Per-centre — answered separately for each candidate centre:**

- Controller identity (D-01 in
  [controller-and-processing-decisions.md](controller-and-processing-decisions.md))
  — that centre's own institution or administration.
- DPO and contact route (D-02) — that centre's own, unless the shared
  subprocessor model changes who this is.
- Authorised organisational participants (D-03) — that centre's own staff
  list.
- A DPIA specific to that centre's real deployment
  ([dpia-and-safeguarding-assessment.md](dpia-and-safeguarding-assessment.md)).
- An Article 28 agreement between that centre (as controller) and Convive (as
  processor) — a per-centre legal instrument, not a shared one.
- Retention, deletion and rights-request positions adopted by that centre's
  controller ([retention-deletion-and-rights.md](retention-deletion-and-rights.md)).
- Pilot onboarding and training delivered to that centre
  ([pilot-onboarding-and-support.md](pilot-onboarding-and-support.md)).

## Sandbox-only functions (until a centre's gate passes)

Named explicitly, per INV-11 and the charter's §9:

- Accepting real safeguarding-domain data into any report or case.
- Sending real email to an arbitrary reporter address — only delivery to a
  verified centre member is enabled pre-gate (R-12, §9.2 of the charter).
- Any claim of real institutional affiliation on a centre's public entry
  beyond what C-3's identity settings allow for a `Sandbox` or
  `Activation under review` centre.

## Evidence checklist (per candidate centre)

- [ ] Controller identity, DPO, authorised participants (D-01–D-03) answered
      for this centre
- [ ] DPIA completed for this centre's actual intended deployment
- [ ] Retention, deletion and rights positions adopted by this centre's
      controller
- [ ] Article 28 agreement signed between this centre and Convive
- [ ] Subprocessor DPAs confirmed (shared; reference the signed agreements
      here rather than re-negotiating per centre)
- [ ] Incident-response contacts confirmed for this centre
- [ ] Pilot onboarding and training materials delivered and acknowledged
- [ ] Security-assurance evidence referenced from #534/#535 (shared)
- [ ] #538's written go decision recorded, naming this centre

## No compliance claim

Completing this checklist for a centre is evidence that the right party made
each decision — not that the decision was correct, complete, or that Convive
is thereby compliant with anything. That responsibility stays with the
deciding controller. This document is not legal advice, not a data protection
impact assessment, and not a processor agreement.
