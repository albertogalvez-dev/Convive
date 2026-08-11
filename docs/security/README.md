# Security and privacy engineering

Convive treats security and privacy as maintained engineering constraints, not
as a one-time compliance claim.

- [Threat model](threat-model.md): assets, actors, trust boundaries, misuse
  cases, prioritised threats, controls and residual risk.
- [Privacy engineering register](privacy-engineering-register.md): purposes,
  data categories, minimisation, retention gates and access boundaries.
- [Public-endpoint anti-abuse model](anti-abuse-threat-model.md): detailed
  analysis of the implemented anonymous write and verification endpoints.
- [Attachment security boundary](attachment-threat-model.md): the implemented
  fictional-data backend boundary, private lifecycle, retrieval constraints
  and deletion responsibilities for evidence attachments; the reporter UI is
  still pending.
- [Dependency management](dependency-management.md): supply-chain ownership
  and verification.
- [Code scanning and finding triage](code-scanning.md): source-only CodeQL
  coverage, least-privilege workflow boundary and alert handling.

These documents describe the fictional-data demonstration as it exists. They
do not claim GDPR, ENS or real-school deployment compliance. The repository
maintainer owns this baseline until responsibilities are formally delegated.
Material changes to authentication, authorisation, sensitive data, evidence,
third parties, logging, deployment or recovery must update it in the same PR.
