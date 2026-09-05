# ADR-0030: Adopt a free-tier, EU-resident, minimal-footprint infrastructure model for Convive SaaS 2.0

- **Status:** Accepted
- **Date:** 3 September 2026
- **Related issues:** [#503](https://github.com/albertogalvez-dev/Convive/issues/503) (product charter, §2.4, §4.4, §9.1), [#505](https://github.com/albertogalvez-dev/Convive/issues/505), [#509](https://github.com/albertogalvez-dev/Convive/issues/509)
- **Depends on:** [ADR-0012](0012-use-cloudflare-tunnel-for-the-single-vps-deployment.md), [ADR-0013](0013-use-restic-with-off-host-object-storage-for-database-recovery.md), [ADR-0029](0029-use-the-platform-caddy-per-project-edge-for-public-ingress.md)

## Context

Convive SaaS 2.0 (issue #503, the product charter) is a grant-funded, year-long
product line built under the Aircury Summer of Code 2026 scholarship. Two
standing constraints from the charter (§2.4) govern every infrastructure choice:

- **Free-tier-only.** Every provider and tier Convive uses must be permanently
  free; no trial that converts to a bill.
- **Minimal footprint and portability (§4.4).** The shared OVH VPS is personal
  infrastructure the owner maintains for the grant, not for Convive. Convive
  must not grow into it and must be able to leave it cleanly.

The owner also asked, explicitly, that audio, video and other large evidence
never be written to that VPS's disk (§13.1, 2026-09-03).

**Existing infrastructure, verified by direct inspection (3 September 2026):**
one shared OVH VPS (`projectx-ovh`, France) — 8 vCPU, 22 GiB RAM (~20 GiB free),
193 GB disk (~149 GB free), load average ~0.1, Ubuntu 24.04 LTS — hosting three
isolated PROJECTX projects behind one shared Caddy edge (ADR-0029):
ServiciosGRX/WordPress, Guías Caninas, and the delivered Convive fictional demo
(`px-convive-*`: API, gateway, ClamAV, PostgreSQL, Redis). The demo already
backs up off-host to a private Cloudflare R2 bucket in the EU jurisdiction
(ADR-0013) and sits behind Cloudflare DNS/CDN (ADR-0012).

Convive SaaS 2.0 needs multi-tenant application hosting, per-tenant evidence
storage (including audio and video per the charter's expanded evidence scope),
and day-one transactional email and web-push notifications (§9.1, §9.2 of the
charter) — without adding a paid line or growing the VPS's footprint.

## Decision

**Application hosting.** A new isolated `px-convive-saas-*` project on the same
shared OVH VPS, following the existing PROJECTX per-project isolation pattern
(own edge/internal Docker networks, own Compose, own secrets under
`/srv/platform/secrets/`, own domain, no published host ports other than
through the shared Caddy edge). Separate from the demo's `px-convive-*`
project (no shared datastore, per charter INV-16). Footprint is deliberately
minimal: only the application core — API, gateway, PostgreSQL, Redis — runs on
the VPS; target on the order of 1 GB RAM and a few GB of disk.

**Object storage.** All evidence, attachments, audio and video are stored in
external EU object storage — candidate **Scaleway Object Storage** (France,
S3-compatible, a permanently-free tier, and a signed DPA available). The
application database stores only references (bucket, key, content type,
checksum); no large media file is ever written to the VPS disk. Confirmed with
real evidence at #524.

**Transactional email.** An external EU transactional-email subprocessor —
candidate **Brevo** (France, permanently-free tier, DPA available). Confirmed
with real deliverability evidence at #520. Professional/account email is a
day-one feature (charter §9.2); reporter email remains gated per #519.

**Web push.** Standard Web Push (VAPID), which is a browser-platform capability,
not a subprocessor Convive selects. Push payloads are end-to-end encrypted;
delivery transits the browser vendor's push service (Google, Mozilla or
Apple), which is noted in the DPIA (#504) as a sub-processing-in-transit
consideration. Opt-in per browser.

**Malware scanning.** ClamAV in a container on the SaaS VPS project, mirroring
the pattern already proven on the delivered demo. No external scanning
subprocessor.

**Backups.** Off-host, outside the OVH account, mirroring ADR-0013: the
existing Cloudflare R2 free-allowance bucket, or a consolidated Scaleway
bucket if that simplifies operating one fewer provider relationship. Decided
at #535 with real recovery evidence.

**The free-tier nuance.** The free tier of an otherwise-paid service (as R2
already is: metered usage, currently €0.00 within the free allowance) is
acceptable only while usage stays within the permanently-free allowance and
cannot silently convert to a bill. A provider whose free tier can lapse into
billing without an explicit, reviewed decision is not acceptable under this
ADR.

**Real-data pilot infrastructure** is explicitly out of scope for this ADR. It
is decided at #537/#538 with its own funding and DPA context, inheriting these
same constraints (free-tier first, EU residency, signed Article 28 agreement
with every subprocessor); if no free option meets EU residency, Article 28 and
the functional need together, that gap is escalated to the owner rather than
resolved by assuming paid infrastructure (charter §9.1).

## Consequences

### Positive

- Zero new recurring cost: no paid subprocessor, no new paid line for SaaS 2.0.
- The VPS gains a fourth isolated project without materially affecting its
  free capacity (8 vCPU / 22 GiB / 193 GB, currently ~1% loaded).
- Large media never touches the owner's personal-use VPS disk; growth in
  evidence volume scales against object-storage allowances, not VPS disk.
- Every named subprocessor (Cloudflare, the object-storage provider, the email
  provider) is EU-jurisdiction, consistent with a safeguarding-adjacent
  product handling minors' data once real data is ever authorised.
- Fully containerised, externally-stored, per-project-isolated: relocating
  Convive SaaS 2.0 off the VPS later is moving a small container set and
  re-pointing DNS, not a rewrite (charter §4.4).

### Negative

- Introduces two new named subprocessors (object storage, email) beyond
  Cloudflare, each needing its own signed DPA before any real (non-fictional)
  use, and its own free-tier terms monitored for change.
- Web Push's payload transit through a browser vendor's infrastructure is a
  nuance that must be carried into the DPIA (#504) and cannot be fully
  eliminated by choosing a different provider — it is inherent to the Web
  Push standard.
- Shared VPS isolation (network/data/secrets/deploy) is not equivalent to
  separate virtual machines; SaaS 2.0 and the other three PROJECTX projects
  still share a kernel and Docker daemon.

## Release gates

Before any real (non-fictional) data reaches the object-storage or email
subprocessor, a signed DPA must exist for that subprocessor, and the readiness
gate in #504 must be satisfied. Before SaaS 2.0's isolated project is deployed
to the shared VPS, it must pass the same preflight checks every PROJECTX
project passes (no `ports:`, no privileged mode, no Docker socket, no host
networking, edge/internal network pair present) per the existing
`scripts/preflight-project.sh` convention. This ADR does not authorise real
data, a real-centre pilot, or any provider selection beyond the candidates
named above — #505 and #520/#524/#535 make those confirmations with real
evidence.

## Review triggers

Review before: selecting a different object-storage or email provider than the
named candidates; processing real safeguarding-domain data; the VPS approaching
capacity limits that would require a second VPS (a spending decision requiring
explicit owner approval); or any change to the free-tier terms of Cloudflare,
Scaleway or Brevo that would introduce billing risk.
