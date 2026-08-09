# Security and privacy engineering

Convive treats security and privacy as maintained engineering constraints, not
as a one-time compliance claim.

- [Threat model](threat-model.md): assets, actors, trust boundaries, misuse
  cases, prioritised threats, controls and residual risk.
- [Privacy engineering register](privacy-engineering-register.md): purposes,
  data categories, minimisation, retention gates and access boundaries.
- [Public-endpoint anti-abuse model](anti-abuse-threat-model.md): detailed
  analysis of the implemented anonymous write and verification endpoints.
- [Dependency management](dependency-management.md): supply-chain ownership
  and verification.

These documents describe the fictional-data demonstration as it exists. They
do not claim GDPR, ENS or real-school deployment compliance. The repository
maintainer owns this baseline until responsibilities are formally delegated.
Material changes to authentication, authorisation, sensitive data, evidence,
third parties, logging, deployment or recovery must update it in the same PR.
