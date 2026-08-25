# Controlled release and rollback sequence

**Property made obvious:** Convive becomes public only after a prepared, healthy candidate and a reviewed Caddy route; a failed gate stops rather than falls back to a development build, a host port or another project's route.

**Status:** maintained fictional-demo release procedure  
**Last reviewed:** 25 August 2026

This sequence is the required procedure, not evidence of a completed public deployment. It follows the [deployment, release and rollback runbook](../../operations/deployment-release-and-rollback.md), [ADR-0029](../decisions/0029-use-the-platform-caddy-per-project-edge-for-public-ingress.md) and the [release workflow](../../../.github/workflows/release.yaml).

```mermaid
sequenceDiagram
    actor Maintainer as Release maintainer
    participant CI as GitHub CI and release environment
    participant VPS as VPS project enrolment and Compose
    participant State as PostgreSQL Redis and encrypted backup
    participant Edge as Platform Caddy
    participant Public as Public hostname smoke test

    Maintainer->>CI: Merge reviewed main commit and approve release environment
    CI->>CI: Verify required checks and immutable image digests
    CI->>VPS: Enrol Convive idempotently and upload reviewed manifest
    VPS->>State: Verify secret permissions recovery evidence and migration plan
    VPS->>VPS: Pull digests migrate once and run guarded prepare
    VPS-->>CI: Candidate health and internal smoke evidence
    alt Candidate or gate fails
        CI->>VPS: Stop candidate and retain prior manifest and evidence
    else Candidate healthy
        CI->>Edge: Validate exact Convive hostname route
        Edge->>Edge: Add or reload only Convive route
        CI->>Public: Run public HTTPS API and fictional-demo smoke checks
        alt Public verification fails
            CI->>Edge: Remove or restore only Convive route
            CI->>VPS: Reconcile previous manifest and image digests
        else Public verification passes
            CI-->>Maintainer: Record release digests and rollback window
        end
    end
```

## Rollback decision points

| Condition | Action | Must not happen |
| --- | --- | --- |
| Prepare, health or migration gate fails | Keep candidate non-public and retain evidence | Expose a host port or use a development image |
| No migration ran | Select previous manifest and repeat complete smoke test | Delete volumes or run host-wide Docker cleanup |
| Backward-compatible migration ran | Restore previous image digests and leave compatible schema | Automatic Doctrine `down()` |
| Incompatible migration or unrecoverable state | Keep maintenance active, restore verified generation, select previous digests and retest | Serve an uncertain state |
| Caddy/DNS verification fails | Remove or restore the Convive route only | Edit/restart another ProjectX app or introduce a tunnel |

The release record contains commit, PR, approved immutable digests, migration class, backup evidence, operator, smoke result and rollback outcome, never secret values. The Caddy route is added after preparation so a bad candidate cannot accidentally become public.

## Sources and verification

- [Controlled release workflow](../../operations/controlled-release-workflow.md)
- [Deployment, release and rollback runbook](../../operations/deployment-release-and-rollback.md)
- [Release record template](../../operations/release-records/TEMPLATE.md)
- [Production reconciliation script](../../../infrastructure/release/reconcile.sh)
- `npm run docs:check`
