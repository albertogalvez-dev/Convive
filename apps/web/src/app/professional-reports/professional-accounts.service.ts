import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export type ProfessionalAccountStatus = 'invited' | 'active' | 'suspended' | 'deactivated';
export type ProfessionalAccountRole = 'triage' | 'administrator';

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
