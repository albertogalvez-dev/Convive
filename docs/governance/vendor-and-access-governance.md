# Processor, vendor and access governance

**Status: unapproved draft. No controller has been appointed, no processor
arrangement exists, and no vendor below is approved for real data.**

**Prepared by:** the repository maintainer, as preparatory material only.
**Last updated:** 16 August 2026.
**Review trigger:** whenever a provider, region, subprocessor or administrative
access path changes, and before any real-data pilot is evaluated.

**This inventory covers the delivered fictional demo only.** Convive SaaS 2.0
has its own, separate infrastructure inventory (isolated OVH VPS project,
external EU object storage, external EU transactional email), decided in
**ADR-0030** and `docs/product/saas-2.0-charter.md` §9.1/§4.4, and its own
readiness gate in
[saas-2.0-readiness-gate.md](saas-2.0-readiness-gate.md). Do not assume this
document's table covers SaaS 2.0's providers.

Read [the directory README](README.md) first. This document supplies the vendor
facts that [the controller decisions](controller-and-processing-decisions.md)
defer to at D-08.

## The mistake this exists to prevent

A provider that is already configured looks approved. It is not. The fictional
demonstration reaches for hosting, DNS and object storage because a
demonstration has to run somewhere, and every one of those choices was made on
engineering grounds by one person with no data-protection assessment behind it.

Inheriting them silently is how real data ends up at a processor nobody
appointed.

## Inventory of what the demonstration actually uses

Fact, not approval. Every row is the fictional demonstration as configured
today.

| Path | What it does | Real-data status |
|---|---|---|
| VPS host | Runs the application, database and private object volume | **Not approved.** See the shared-host question below |
| Cloudflare — registrar and DNS | Holds `conviveaula.com` and its zone | **Not approved** |
| Platform Caddy and Cloudflare DNS | Public ingress through the per-project edge, under [ADR-0029](../architecture/decisions/0029-use-the-platform-caddy-per-project-edge-for-public-ingress.md) | **Not approved for real data** |
| Cloudflare Email Routing | Forwards `privacy@` and `hola@` to the maintainer's mailbox | **Not approved for anything but public contact.** It carries no report content and must never be repurposed |
| Cloudflare R2 | Off-host encrypted backup storage for restic generations, under [ADR-0013](../architecture/decisions/0013-use-restic-with-off-host-object-storage-for-database-recovery.md) | **Not approved.** This is the copy that holds everything |
| Outbound email delivery | None. `MAILER_DSN` is `null://null` in production; development uses Mailpit | Blocked until #190 |
| Attachment scanning | Preferred position is an isolated local ClamAV with quarantine rather than an external analysis provider; not activated | Not activated; signature-update source and cadence need review before it is |
| Monitoring and support tooling | None beyond local logs and the operational runbooks | Nothing to approve yet, which is itself worth recording |
| GitHub | Source, issues, CI and container images | Holds no report data; still an access path worth governing |

## The shared-host question

The VPS is not dedicated to Convive. It also runs unrelated projects belonging
to the same maintainer, and its platform standard makes a single shared Caddy
the only public HTTP entry point, with each project confined to its own `edge`
and `internal` networks and its own secrets directory.

ADR-0029 resolves the platform boundary: Convive joins only its dedicated edge
with platform Caddy, while all service and state networks remain isolated. For
a fictional demonstration that reasoning holds.

**[DECISION REQUIRED]** Whether a host shared with unrelated projects is
acceptable for real safeguarding data at all, and if so under what separation
guarantees. This is not answered by the network isolation being technically
sound. A shared host means shared kernel, shared root, shared operator and a
shared blast radius for any compromise, and the controller has to accept that
explicitly rather than inherit it from a demonstration.

The ingress decision is resolved for the fictional demonstration by ADR-0029.
Any future real-data delivery still requires its own approved controller,
privacy, operational and platform review; that work cannot reuse this demo
decision as evidence.

## Decisions required

### V-01 Approved vendor set

**[DECISION REQUIRED]** Which providers are approved for real data, for which
purpose, and who approved each.

### V-02 Processor arrangements

**[DECISION REQUIRED]** A written processor arrangement with every provider that
handles personal data on the controller's behalf, covering purpose, duration,
instructions, confidentiality, security, subprocessors, assistance with rights
requests, deletion at end of service and audit.

**Note on what "free" costs here.** The project runs on free tiers as a hard
constraint. Free tiers frequently come with standard terms and no negotiated
data-processing agreement. Whether the available terms are sufficient for
minors' safeguarding data is a controller decision, and the honest possibility
is that they are not.

### V-03 Data location and transfers

**[DECISION REQUIRED]** Where each provider stores and processes the data, and
the transfer position where processing occurs outside the EEA.

**Factual input:** backups are encrypted before leaving the host under ADR-0013,
which reduces but does not eliminate the question, because the controller must
still account for where the ciphertext rests and who could compel its
disclosure.

### V-04 Subprocessors and change notification

**[DECISION REQUIRED]** What notice the controller receives before a provider
adds a subprocessor, and what happens if the controller objects.

### V-05 Breach and support contacts

**[DECISION REQUIRED]** The named contact and the response expectation for each
provider, and how a provider-side incident reaches the controller within the
statutory notification window.

### V-06 Credential ownership and least privilege

**[DECISION REQUIRED]** Who owns each credential, at what privilege, and who may
use it.

**Factual input:** secrets live only on the VPS under a root-only directory, and
no container holds the Docker socket, which the platform standard treats as
equivalent to host administrative access. That is a sound engineering baseline
and says nothing about who is authorised.

**The uncomfortable fact to record:** today one person holds every credential
and is also the only operator. For a demonstration that is fine. For real
safeguarding data it is a single point of both failure and access, with no
separation of duties and nobody to review that person's own access.

### V-07 Joiner, leaver and emergency access

**[DECISION REQUIRED]** How access is granted, reviewed, revoked and audited,
and what emergency access exists, who may invoke it and how its use is
evidenced afterwards.

### V-08 Change management

**[DECISION REQUIRED]** What review a provider, region or subprocessor change
triggers, and how an unapproved provider is prevented from being enabled by
default.

**Factual input:** the deployment is driven by an immutable released Compose
configuration, so a provider cannot appear without a reviewed change to a
versioned file. That is a control worth keeping and is not a substitute for an
approval process.

## What stays true until these are answered

- No real data reaches any external service.
- No provider is treated as approved because it is already configured for the
  fictional demonstration.
- Outbound email stays disabled, and attachment scanning stays unactivated.
- The public contact mailboxes carry public correspondence only and are never
  repurposed to carry report content.
