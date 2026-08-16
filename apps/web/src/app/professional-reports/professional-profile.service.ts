import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface ProfessionalMembership {
  organisation: { id: string; name: string };
  role: 'triage' | 'administrator';
  managedByAdministrator: boolean;
}

export interface ProfessionalAbsence {
  id: string;
  startsOn: string;
  endsOn: string;
  note: string | null;
}

export interface ProfessionalProfile {
  id: string;
  name: string;
  email: string;
  memberships: ProfessionalMembership[];
}

export interface UpdatedProfessionalProfile extends ProfessionalProfile {
  /** True when the email changed, which ends every existing session. */
  sessionEnded: boolean;
}

@Injectable({ providedIn: 'root' })
export class ProfessionalProfileService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/v1/professional/profile';
  private readonly absencesEndpoint = '/api/v1/professional/absences';

  get(): Observable<ProfessionalProfile> {
    return this.http.get<ProfessionalProfile>(this.endpoint);
  }

  update(payload: { name: string; email: string }): Observable<UpdatedProfessionalProfile> {
    return this.http.patch<UpdatedProfessionalProfile>(this.endpoint, payload);
  }

  absences(): Observable<{ items: ProfessionalAbsence[] }> {
    return this.http.get<{ items: ProfessionalAbsence[] }>(this.absencesEndpoint);
  }

  recordAbsence(payload: {
    startsOn: string;
    endsOn: string;
    note?: string;
  }): Observable<ProfessionalAbsence> {
    return this.http.post<ProfessionalAbsence>(this.absencesEndpoint, payload);
  }

  cancelAbsence(id: string): Observable<void> {
    return this.http.delete<void>(`${this.absencesEndpoint}/${encodeURIComponent(id)}`);
  }
}
