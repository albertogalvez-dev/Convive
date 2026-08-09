import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface ProfessionalIdentity {
  id: string;
  name: string;
  email: string;
}

export interface ProfessionalSession {
  professional: ProfessionalIdentity;
}

@Injectable({ providedIn: 'root' })
export class ProfessionalSessionService {
  private readonly http = inject(HttpClient);

  current(): Observable<ProfessionalSession> {
    return this.http.get<ProfessionalSession>('/api/v1/professional/session');
  }

  login(email: string, password: string): Observable<ProfessionalSession> {
    return this.http.post<ProfessionalSession>('/api/v1/professional/session', { email, password });
  }

  logout(): Observable<void> {
    return this.http.delete<void>('/api/v1/professional/session');
  }
}
