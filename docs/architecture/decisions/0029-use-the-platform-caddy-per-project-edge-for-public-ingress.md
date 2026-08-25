# ADR-0029: Use the platform Caddy per-project edge for public ingress

- **Status:** Accepted
- **Date:** 25 August 2026
- **Related issue:** [#350](https://github.com/albertogalvez-dev/Convive/issues/350)
- **Supersedes:** [ADR-0012](0012-use-cloudflare-tunnel-for-the-single-vps-deployment.md)
- **Depends on:** [ADR-0005](0005-use-docker-compose-for-reproducible-environments.md), the PROJECTX platform isolation standard and its reviewed project registration

## Context

ADR-0012 selected a dedicated Cloudflare Tunnel before the VPS platform had a
sanctioned project-isolation pattern. The platform now gives every project an
explicit edge network shared only by the platform Caddy and that project's web
gateway, plus project-owned internal networks. Guías Caninas and ServiciosGRX
already use this ownership model.

Keeping a separate Convive tunnel would introduce a second public ingress path
on a host whose platform rule is that Caddy owns HTTP(S). It would also leave
the Compose boundary, VPS registration and release runbook contradictory.

## Decision

Convive uses the platform Caddy as its only public HTTP(S) ingress for the
fictional demonstration. The platform Caddy joins the externally-created
`px-convive-edge` network and routes the explicit Convive hostname to the
Convive `gateway` service on port 8080. The Convive gateway serves the compiled
Angular application and routes only API paths to PHP-FPM.

Convive publishes no host ports and does not run `cloudflared`. Cloudflare
continues to own the registered DNS zone and may proxy the hostname only under
the platform's reviewed DNS and origin-firewall configuration; it is not a
Tunnel connector or an application access-control layer.

The project registration is `PROJECTX-INFRA/projects/convive/project.yml`. The
platform enrolment command creates `px-convive-edge`, connects Caddy, creates
`px-convive-internal` and prepares only the root-owned project and secret
directories. It does not publish a route or start Convive.

The gateway joins both `px-convive-edge` and the internal application network.
The API, database, Redis and scanner remain unreachable from the edge. The
scanner retains a separate, scanner-only signature-refresh network so that its
definition updates do not grant egress to application or state services.

## Consequences

### Positive

- Caddy remains the platform's sole public listener and the only external
  member of Convive's edge network.
- Convive keeps independent Compose ownership, named volumes, secrets,
  internal networks and rollback while avoiding a second ingress stack.
- The route is an explicit platform configuration change with a narrow,
  reversible blast radius.

### Negative

- A release needs coordinated, reviewed registration and Caddy-route steps;
  it cannot be completed by application Compose alone.
- Convive depends on the platform Caddy lifecycle for availability.
- The old tunnel token is no longer a deployment input and must not be carried
  forward as an unused secret.

## Release gates

Before a public route is enabled, the operator must complete the platform
migration gates: inventory and rollback session, rendered Compose with no
`ports:`, project-only networks and volumes, healthy Convive services, Caddy
as the only external edge member, validated Caddy configuration, external
domain verification and direct-origin-port verification. A failed gate removes
only the Convive route and stops only the Convive Compose project; it never
recreates or restarts another project.

This decision does not approve real data, a Cloudflare Access policy, a tunnel,
or a change to the fictional demonstration boundary.

## Review triggers

Review before processing real data, changing the platform Caddy ownership,
introducing another public hostname, moving hosts, enabling an external WAF or
analytics provider, or allowing any non-Caddy service to join the edge.
