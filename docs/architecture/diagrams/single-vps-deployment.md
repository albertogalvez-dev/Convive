# Single-VPS deployment topology

This deployment view implements
[ADR-0029](../decisions/0029-use-the-platform-caddy-per-project-edge-for-public-ingress.md).
It describes the fictional-data demonstration; it is not a real-data approval.

Every service in `infrastructure/production/compose.production.yaml` appears
here: `gateway`, `api`, `database`, `redis` and `clamav`, plus the attachment
volume its init container prepares. Platform Caddy is the only process that
accepts public HTTP(S) traffic on the VPS.

```mermaid
flowchart LR
    browser["Reporter or professional browser"]
    cloudflare["Cloudflare DNS / reviewed proxy"]

    subgraph vps["Existing single VPS"]
        direction LR
        caddy["Platform Caddy<br/>sole public 80/443 listener"]

        subgraph edge["px-convive-edge"]
            gateway["Convive Caddy gateway<br/>static Angular + routing"]
        end

        subgraph internal["Convive private networks"]
            api["Symfony API<br/>PHP-FPM"]
            db[("Convive PostgreSQL")]
            redis[("Convive Redis")]
            clamav["ClamAV<br/>attachment scanning"]
            attachments[("Attachment volume<br/>outside the web root")]
        end

        caddy -->|"explicit Convive hostname route"| gateway
        gateway -->|"/api/v1 only"| api
        api --> db
        api --> redis
        api -->|"scan before release"| clamav
        api --> attachments
    end

    browser -->|"HTTPS"| cloudflare
    cloudflare -->|"reviewed DNS/proxy policy"| caddy

    projectx["Other ProjectX services<br/>separate edges, unchanged"]
    vps ~~~ projectx

    classDef public fill:#E0F2FE,stroke:#0284C7,color:#0C4A6E,stroke-width:2px
    classDef edge fill:#F0F9FF,stroke:#0EA5E9,color:#0F172A,stroke-width:2px
    classDef app fill:#E2E8F0,stroke:#475569,color:#0F172A,stroke-width:2px
    classDef data fill:#F8FAFC,stroke:#64748B,color:#0F172A,stroke-width:2px
    classDef unrelated fill:#FFF7ED,stroke:#F97316,color:#7C2D12,stroke-dasharray:5 5
    class browser,cloudflare,caddy public
    class gateway edge
    class api,clamav app
    class db,redis,attachments data
    class projectx unrelated
```

## Trust boundaries

1. **Public Internet to Cloudflare:** Cloudflare provides DNS and, only when
   deliberately enabled, a reviewed proxy policy. It is not a Convive Tunnel or
   an application access-control layer.
2. **Cloudflare to platform Caddy:** Caddy is the VPS's sole public listener.
   Convive publishes no host port and runs no `cloudflared` connector.
3. **Platform Caddy to Convive edge:** only platform Caddy and the Convive
   gateway join `px-convive-edge`. The explicit hostname route is installed
   only after Convive health checks pass.
4. **Gateway to API:** only `/api/v1/**` reaches PHP-FPM on Convive's private
   internal network. The SPA fallback cannot consume an API route.
5. **API to state:** PostgreSQL and Redis use separate internal networks and
   are reachable only by Symfony. The scanner has a distinct signature-refresh
   egress and never shares database, Redis or attachment storage networks.
6. **Host coexistence:** ProjectX projects share physical resources only. They
   do not share Convive volumes, secrets, Compose ownership or release steps.

Symfony trusts only the Convive gateway's private proxy address. Platform
Caddy and the gateway replace untrusted forwarding headers; public clients
cannot select the address used by rate-limiting logic.

## Data and control paths

- Fingerprinted Angular assets may be cached publicly; HTML, API traffic,
  anonymous access and follow-up responses are not cached.
- PostgreSQL is authoritative for fictional product data and professional
  sessions. Redis contains expiring idempotency and abuse-control state.
- CI publishes immutable images. The VPS pulls reviewed digests from GHCR.
- Operators first prepare healthy Convive services, then validate and install
  the one Caddy route, then complete public smoke verification. Secrets never
  enter an image or release manifest.
- Backups leave the live Compose project through the separately controlled
  backup process defined by issue #66.
