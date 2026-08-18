# Reporting sequence

The anonymous report and follow-up journey, and the professional review path
that ends at the report/case boundary.

Verified against the codebase on 18 August 2026.

Private attachments and optional verified reporter email notices are
implemented and deliberately omitted here, to keep this diagram on the one
path it exists to show. Case work *after* a case opens is out of scope for the
same reason and belongs in a case-lifecycle diagram.

The step that matters most is near the end. `link_to_case` is an **explicit
outcome a professional chooses**, with a reason, recorded as an attributed
decision. There is no path in this sequence where a report becomes a case
without a person deciding it — which is the product rule the whole shape of
this diagram exists to make visible.

```mermaid
sequenceDiagram
    actor Reporter
    actor Professional
    participant Browser as Angular browser
    participant API as Symfony API
    participant DB as PostgreSQL

    Reporter->>Browser: Open organisation reporting link
    Browser->>API: Resolve public organisation profile
    API->>DB: Read public identifier and name
    DB-->>API: Minimal public profile
    API-->>Browser: Profile or indistinguishable not-found problem
    Reporter->>Browser: Submit situation and context
    Browser->>API: Create anonymous report
    API->>DB: Store report and access-secret hash
    API-->>Browser: Public reference and one-time secret disclosure
    Reporter->>Browser: Enter reference and secret later
    Browser->>API: Verify secret
    API->>DB: Validate hash and issue scoped capability
    API-->>Browser: HttpOnly report capability cookie
    Reporter->>Browser: Add follow-up information
    Browser->>API: Append capability-scoped follow-up entry
    API->>DB: Store immutable reporter entry
    API-->>Browser: Updated report state and history

    Professional->>Browser: Authenticate with professional account
    Browser->>API: Read organisation-scoped report list/detail
    API->>DB: Enforce active membership and read report data
    DB-->>API: Report and reporter-visible history
    API-->>Browser: New/reviewed detail
    Professional->>Browser: Review and/or write a response
    Browser->>API: Submit review or response with CSRF protection
    API->>DB: Persist first review or append centre entry
    API-->>Browser: Updated review/history state
    Browser->>API: Submit explicit triage outcome and reason
    API->>DB: Append attributed triage decision
    opt Outcome is link_to_case
        API->>DB: Atomically create minimal case and unique report link
    end
    API-->>Browser: Decision and optional case identifier
```

The public reference is a receipt, not authentication. The secret is never
stored in readable form; the capability is scoped to one report and is not
placed in application-controlled browser storage. Professional access is
session-based and organisation-scoped. See ADR-0008, ADR-0010, ADR-0011 and
the generated [OpenAPI contract](../../api/openapi.yaml) for exact details.
