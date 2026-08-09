import { HttpClient } from '@angular/common/http';
import { inject, Injectable, signal } from '@angular/core';
import { Observable, of, tap } from 'rxjs';

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
  readonly professional = signal<ProfessionalIdentity | null>(null);

  current(): Observable<ProfessionalSession> {
    return this.http.get<ProfessionalSession>('/api/v1/professional/session');
  }

  restore(): Observable<ProfessionalSession> {
    const professional = this.professional();

    return professional
      ? of({ professional })
      : this.current().pipe(tap((session) => this.professional.set(session.professional)));
  }

  login(email: string, password: string): Observable<ProfessionalSession> {
    return this.http
      .post<ProfessionalSession>('/api/v1/professional/session', { email, password })
      .pipe(tap((session) => this.professional.set(session.professional)));
  }

  logout(): Observable<void> {
    return this.http
      .delete<void>('/api/v1/professional/session')
      .pipe(tap(() => this.professional.set(null)));
  }
}
