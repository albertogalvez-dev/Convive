# Access-control decision matrix

Verified against the controllers, application services and integration tests on
24 August 2026. This is a compact decision aid, not a second permission
implementation: the code and the tests named below remain authoritative.

## Independent grants

A professional needs an active account and active organisation membership for
every protected professional route. Organisation roles then govern
organisation-scoped work; an active exact-case assignment separately governs
case content. The two grants do not imply each other.

| Access context                          | Report visibility and triage                                                                           | Case visibility                | Assignments                                        | Tasks and people               | Evidence and communications                                               | Audit and export                  | Account administration                                           |
| --------------------------------------- | ------------------------------------------------------------------------------------------------------ | ------------------------------ | -------------------------------------------------- | ------------------------------ | ------------------------------------------------------------------------- | --------------------------------- | ---------------------------------------------------------------- |
| Anonymous report capability             | Its one report and reporter/professional follow-up only                                                | No                             | No                                                 | No                             | May append its own follow-up; no professional evidence route              | No                                | No                                                               |
| `triage` organisation membership        | Reports, available report attachments, review, triage and reporter-facing response in its organisation | No, unless separately assigned | No, unless separately assigned as `lead`           | No, unless separately assigned | Report evidence and report communication only                             | No                                | No, unless separately an `administrator`                         |
| `administrator` organisation membership | No                                                                                                     | No, unless separately assigned | No, unless separately assigned as `lead`           | No, unless separately assigned | No                                                                        | No                                | Manage professional accounts and memberships in its organisation |
| Exact-case `lead` assignment            | No, unless separately a `triage` member                                                                | Full exact-case workspace      | Create, change, revoke and hand over with a reason | Read and manage                | Read private evidence and record communications                           | Read audit; export the exact case | No, unless separately an `administrator`                         |
| Exact-case `contributor` assignment     | No, unless separately a `triage` member                                                                | Full exact-case workspace      | No                                                 | Read and manage                | Read private evidence and record communications                           | No                                | No                                                               |
| Exact-case `observer` assignment        | No                                                                                                     | Read-only exact-case workspace | No                                                 | Read only                      | Read-only evidence and communications shown by the workspace; no mutation | No                                | No                                                               |

The `lead`, `contributor` and `observer` rows all require the assignment to be
active and to belong to the same organisation as the active professional
membership. Revocation removes every case permission. `lead` handover is an
explicit, atomic recorded action; it is not an administrator override.

## Protected-action evidence

| Protected action                                                                                                                               | Enforced by                                                                                                | Authoritative test coverage                                                                                        |
| ---------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| Reporter reads or adds follow-up only for its capability's report                                                                              | `GetReportFollowUpStateController`, `AddReportFollowUpEntryController` and `ReportAccessGuard`             | `GetReportFollowUpStateControllerTest`, `AddReportFollowUpEntryControllerTest`, `RevokeReportAccessControllerTest` |
| Triage lists, reviews, triages, responds to, or obtains available evidence for reports in its organisation                                     | `AuthorisedReportingOrganisations`, `ProfessionalReportInbox` and `ProfessionalReportController`           | `ProfessionalReportControllerTest`, `ProfessionalReportAttachmentControllerTest`                                   |
| Any exact-case workspace operation requires active account, same-organisation membership, exact active assignment and the requested permission | `AuthoriseCaseAccess` and `CaseAssignment::permits`                                                        | `AuthoriseCaseAccessTest`, `ManagedCaseTest`, `ProfessionalCaseControllerTest`                                     |
| Lead-only assignment changes and atomic lead handover require a reason                                                                         | `ManageCaseAssignment` and `ProfessionalCaseController`                                                    | `ManageCaseAssignmentTest`, `ProfessionalCaseControllerTest`                                                       |
| Tasks, people, private evidence and communications use the exact-case permission boundary                                                      | `ProfessionalCaseController`, `CompleteCaseTask`, `ManageCaseInvolvedPeople` and `RecordCaseCommunication` | `ProfessionalCaseControllerTest`, `CompleteCaseTaskTest`                                                           |
| Audit visibility and export are lead-only                                                                                                      | `ProfessionalCaseController` with `CasePermission::ViewAudit` and `CasePermission::Export`                 | `ManagedCaseTest`, `ProfessionalCaseControllerTest`                                                                |
| Account and membership changes are organisation-scoped administrator actions and invalidate stale sessions                                     | `ProfessionalAccountController`, `OrganisationMembershipController` and `ManageOrganisationMembership`     | `ProfessionalAccountControllerTest`, `OrganisationMembershipControllerTest`, `ManageProfessionalAccountTest`       |

## Non-disclosure boundaries

- A public reference is not a credential. A capability is scoped to one report
  and cannot call a professional route; a professional session cannot
  impersonate that capability.
- An `administrator` role is deliberately content-blind. It does not read,
  export, close or reopen every case, and it does not create a case assignment.
- `triage` is a report-review grant, not a managed-case grant. A report-to-case
  transition creates the initial `lead` assignment explicitly and atomically.
- There is no universal case search, emergency access, inspection access,
  cross-organisation transfer or break-glass route. Adding one requires a new
  reviewed decision, enforcement and focused tests.

## Related architecture decisions

- [Authorisation: organisation role versus case assignment](diagrams/authorisation-model.md)
- [ADR-0018: Require explicit assignments for case content](decisions/0018-require-case-assignments-for-case-content.md)
- [ADR-0023: Map Andalusian centre responsibilities to least-privilege grants](decisions/0023-map-andalusian-centre-responsibilities-to-least-privilege-grants.md)
