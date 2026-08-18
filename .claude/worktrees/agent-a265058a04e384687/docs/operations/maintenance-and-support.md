# Supported maintenance through 31 August 2027

This plan keeps the Convive public demonstration supportable through
**2027-08-31**. It is an operational checklist, not an uptime promise. The
demonstration uses fictional data only and does not provide a real-school SLA,
case-management service or safeguarding response.

## Support boundary and ownership

The repository maintainer is the accountable owner for the demonstration. A
future transfer is allowed, but the service is not considered transferred
until the receiving owner accepts the inventory and rotates the credentials.

| Capability | Accountable owner | Backup/renewal responsibility | Evidence or system of record |
|---|---|---|---|
| GitHub repository, branch protection and CI | Repository maintainer | Review repository access monthly | Git history, Actions runs and repository settings |
| Release and rollback | Repository maintainer | Release record names the operator | `docs/operations/controlled-release-workflow.md`, release records on the host |
| Convive VPS and provider billing | Repository maintainer | Verify provider renewal and payment alerts | Provider console and `/var/lib/convive-observability` (no credentials in Git) |
| Public hostname, Cloudflare Tunnel and edge TLS | Repository maintainer | Verify hostname, tunnel token and certificate path | Cloudflare console, deployment environment and release smoke test |
| R2 backup repository | Repository maintainer | Verify bucket access, usage and billing alerts | `docs/operations/backup-and-recovery.md` and root-only evidence |
| Dependency and image updates | Repository maintainer | Dependabot PR review and emergency advisories | `.github/dependabot.yml`, lockfiles and CI |
| Monitoring and incident response | Repository maintainer | Review alerts and preserve redacted evidence | `docs/operations/incident-response.md` and systemd journal |

There is currently no second named operator. Before public launch, record a
reachable backup contact in the operator's private runbook; never put personal
contact details, credentials or report data in this repository.

## Service and renewal register

The following is the review register, not proof of future payment. The owner
must update each `status` and `next_review` in the private operator record
after purchasing or renewing a service.

For a machine-readable starting point, copy
[`maintenance-calendar.example.yaml`](maintenance-calendar.example.yaml) to
that private register and fill only verified renewal dates and owners.

| Service | Current state (2026-08-10) | Renewal/expiry owner | Next review | Gate before public launch |
|---|---|---|---|---|
| GitHub repository and Actions | Available; CI is green on `main` | Repository maintainer | 2026-09-01, then monthly | Confirm repository access and Actions minutes are adequate |
| Convive VPS | Host inspected and Convive-only paths prepared; public deployment not active | Repository maintainer | 2026-09-01, then monthly | Confirm provider plan, billing method and renewal through 2027-08-31 |
| Public hostname and DNS | Not provisioned in this checkpoint | Repository maintainer | Before deployment | Register/verify a hostname and record its renewal date |
| Cloudflare Tunnel and edge TLS | Production tunnel not provisioned; TLS will terminate at the edge once a hostname exists | Repository maintainer | Before deployment and monthly thereafter | Store tunnel token outside Git and pass the public smoke test |
| Cloudflare R2 `convive-demo-backups-eu` | Private EU bucket and bucket-scoped token provisioned; current billable usage checked at USD 0 | Repository maintainer | Monthly; billing alert before any charge | Run off-host backup and isolated restore, then monitor usage |
| Backup/restore timers | Versioned; host enablement depends on the production deployment gate | Repository maintainer | After deployment, then daily signal/monthly exercise | Fresh restore evidence must be present before every release |
| Attachment lifecycle timer | Versioned; host enablement depends on the production deployment gate | Repository maintainer | After deployment, then daily signal | Bounded scan/cleanup commands run and fail closed; no real-data scanner is selected |
| Observability timer and alert publication | Versioned; host enablement depends on the production deployment gate | Repository maintainer | After deployment, then weekly review | Public health, containers, disk and restore freshness checks active |

No row marked “not provisioned”, “prepared” or “depends on the deployment gate”
may be described publicly as an active production service. The owner must set
calendar reminders at least 30 and 7 days before every provider, domain or
certificate renewal. If a renewal cannot be funded or completed, freeze new
releases and start the retirement or transfer procedure below.

## Maintenance cadence

Use the following checklist while the demonstration is public. Each completed
run should be recorded privately with the date, operator, revision and a link
to redacted evidence.

### Every release

- [ ] The selected commit is on `main` and all required CI checks pass.
- [ ] The controlled release workflow classifies migrations, runs a fresh
      backup/restore gate and records the immutable image digests.
- [ ] The public and internal health smoke tests pass after reconciliation.
- [ ] The rollback generation and release record remain available until the
      next successful release is accepted.

### Daily automated signals

- [ ] Backup timer runs at 02:30 Europe/Madrid with the documented retention.
- [ ] Attachment lifecycle timer runs every five minutes without bypassing an
      unavailable scanner.
- [ ] Observability checks run every minute and publish only redacted status.
- [ ] A failed timer or stale restore evidence pages the maintainer through the
      configured systemd failure path.

### Weekly review (Monday)

- [ ] Review new observability alerts, failed timers, disk usage and service
      restarts; resolve or record an incident before closing the week.
- [ ] Review Dependabot PRs and security advisories for Composer, npm, Docker
      base images and GitHub Actions. Critical or exploited issues are handled
      immediately, without waiting for Monday.
- [ ] Check R2 stored bytes/operations and provider billing notifications.

### Monthly review (first working day)

- [ ] Run the complete isolated R2 restoration exercise and verify that no
      sessions or capability grants are revived.
- [ ] Confirm the latest backup and restore evidence is root-only and contains
      no report content, secrets or complete URLs.
- [ ] Verify VPS billing, hostname/DNS renewal dates, Cloudflare Tunnel
      connectivity, edge TLS validity and the public health smoke test.
- [ ] Review GitHub collaborators, Cloudflare/R2 tokens, VPS SSH keys and
      operator access; revoke unused access and rotate exposed credentials.
- [ ] Review the support register and move any unverified service to an
      explicit `needs operator confirmation` state.

### Quarterly review (or after a material provider change)

- [ ] Rehearse recovery from a new isolated project and document elapsed RTO.
- [ ] Recheck the architecture, privacy register, incident contacts, release
      rollback and resource budget against the actual deployment.
- [ ] Confirm that the demonstration claims, screenshots and home-page copy
      still describe fictional-data support rather than school operations.

## Security, backup and incident rules

The dependency cadence, immutable Action pins and emergency advisory process
are defined in [dependency update management](../security/dependency-management.md).
The R2 repository, retention, restore evidence and failure handling are defined
in [encrypted backup and recovery](backup-and-recovery.md). Alerts and first
response follow [incident response](incident-response.md). These runbooks are
the source of truth; an operator must not improvise by copying secrets into
issues, chat, CI logs or screenshots.

If any backup, restore, health or security gate fails, stop releases and keep
the public demonstration unavailable until the failed check is understood and
the complete verification passes again. Do not remove host-wide Docker
resources: Convive cleanup must never affect ProjectX.

## Retirement or transfer

### Planned retirement

1. Announce an end date in the private operator record and stop accepting new
   public traffic (disable the tunnel/hostname or show a static unavailable
   page).
2. If authorised, create one final encrypted backup and a final restore-test
   record. Keep only the retention required for the fictional demonstration;
   do not carry real personal data into this process.
3. Stop and remove only Convive's production Compose project, volumes and
   release directories. Never run host-wide Docker prune or alter ProjectX.
4. Revoke the Cloudflare Tunnel token, R2 bucket token, VPS deploy key and CI
   environment secrets. Remove the DNS record and cancel provider services
   only after the retention decision is recorded.
5. Delete fictional data and operational evidence when the documented period
   ends, or preserve the minimum redacted evidence needed for an audit. Any
   real-data deployment requires the controller/DPO-approved deletion and
   legal-hold procedure before deletion.

### Transfer to another maintainer

1. Share this repository revision, the private service/renewal register, the
   release and recovery runbooks, and the latest redacted evidence.
2. The receiving owner verifies billing, DNS, tunnel, TLS, R2 restore and
   rollback access on an isolated exercise.
3. Rotate all credentials during handover; do not send one-time secrets in
   chat or commit them to Git.
4. Record the new accountable owner and acceptance date. Until that record
   exists, the original maintainer remains responsible and no availability
   claim changes.

## Public claim

Until the deployment and funding gates are evidenced, the accurate statement
is: “Convive is a fictional-data demonstration maintained by its repository
owner, with documented recovery and incident procedures.” It must not claim
continuous availability, school adoption, professional response times or
support for real safeguarding cases.
