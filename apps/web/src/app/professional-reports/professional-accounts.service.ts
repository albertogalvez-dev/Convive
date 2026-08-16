import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export type ProfessionalAccountStatus = 'invited' | 'active' | 'suspended' | 'deactivated';
export type ProfessionalAccountRole = 'triage' | 'administrator';

export type CaseContinuityReason =
  'responsible_absent' | 'overdue_task' | 'absent_with_overdue_task';

export interface CaseContinuityEntry {
  caseId: string;
  status: 'assessment' | 'active' | 'closed';
  responsible: { id: string; name: string };
  reason: CaseContinuityReason;
  earliestOverdueAt: string | null;
}

export interface AccountAdministrationOrganisation {
  id: string;
  name: string;
}

export interface ProfessionalAccount {
  id: string;
  name: string;
  email: string;
  status: ProfessionalAccountStatus;
  role?: ProfessionalAccountRole;
}

export interface OrganisationMembership {
  id: string;
  professionalId: string;
  name: string;
  role: ProfessionalAccountRole;
  state: 'active' | 'suspended' | 'removed';
}

export interface OneTimeCredential {
  professional: ProfessionalAccount;
  credential: { secret: string; expiresAt: string };
}

@Injectable({ providedIn: 'root' })
export class ProfessionalAccountsService {
  private readonly http = inject(HttpClient);

  administrations(): Observable<{ items: AccountAdministrationOrganisation[] }> {
    return this.http.get<{ items: AccountAdministrationOrganisation[] }>(
      '/api/v1/professional/account-administration',
    );
  }

  acceptCredential(secret: string, password: string): Observable<void> {
    return this.http.post<void>('/api/v1/professional/account-credentials/accept', {
      secret,
      password,
    });
  }

  accounts(organisationId: string): Observable<{ items: ProfessionalAccount[] }> {
    return this.http.get<{ items: ProfessionalAccount[] }>(
      `/api/v1/professional/organisations/${encodeURIComponent(organisationId)}/accounts`,
    );
  }

  memberships(organisationId: string): Observable<{ items: OrganisationMembership[] }> {
    return this.http.get<{ items: OrganisationMembership[] }>(
      `/api/v1/professional/organisations/${encodeURIComponent(organisationId)}/memberships`,
    );
  }

  changeMembership(
    organisationId: string,
    membershipId: string,
    payload: { role?: ProfessionalAccountRole; action?: 'suspend' | 'resume' | 'remove' },
  ): Observable<OrganisationMembership> {
    return this.http.patch<OrganisationMembership>(
      `/api/v1/professional/organisations/${encodeURIComponent(organisationId)}/memberships/${encodeURIComponent(membershipId)}`,
      payload,
    );
  }

  invite(
    organisationId: string,
    payload: { name: string; email: string; role: ProfessionalAccountRole },
  ): Observable<OneTimeCredential> {
    return this.http.post<OneTimeCredential>(
      `/api/v1/professional/organisations/${encodeURIComponent(organisationId)}/accounts`,
      payload,
    );
  }

  resetPassword(organisationId: string, professionalId: string): Observable<OneTimeCredential> {
    return this.http.post<OneTimeCredential>(
      `/api/v1/professional/organisations/${encodeURIComponent(organisationId)}/accounts/${encodeURIComponent(professionalId)}/password-reset`,
      {},
    );
  }

  /**
   * Operational metadata about cases needing a continuity decision. It returns
   * no case content, and reading it grants no access to the cases it names.
   */
  caseContinuity(organisationId: string): Observable<{ items: CaseContinuityEntry[] }> {
    return this.http.get<{ items: CaseContinuityEntry[] }>(
      `/api/v1/professional/organisations/${encodeURIComponent(organisationId)}/case-continuity`,
    );
  }

  /**
   * Correct a mistyped login address on behalf of a professional who can no
   * longer reach their own account. The response reports whether the change
   * ended their sessions, so the interface can say what actually happened
   * instead of guessing.
   */
  correctEmail(
    organisationId: string,
    professionalId: string,
    email: string,
  ): Observable<ProfessionalAccount & { sessionEnded: boolean }> {
    return this.http.patch<ProfessionalAccount & { sessionEnded: boolean }>(
      `/api/v1/professional/organisations/${encodeURIComponent(organisationId)}/accounts/${encodeURIComponent(professionalId)}/email`,
      { email },
    );
  }

  changeStatus(
    organisationId: string,
    professionalId: string,
    action: 'suspend' | 'reactivate' | 'deactivate',
  ): Observable<ProfessionalAccount> {
    return this.http.patch<ProfessionalAccount>(
      `/api/v1/professional/organisations/${encodeURIComponent(organisationId)}/accounts/${encodeURIComponent(professionalId)}/status`,
      { action },
    );
  }
}
