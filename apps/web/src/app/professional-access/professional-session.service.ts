import { HttpClient } from '@angular/common/http';
import { inject, Injectable, signal } from '@angular/core';
import { Observable, of, tap } from 'rxjs';

import { DemoProfessionalRole } from './demo-professional-role';

export interface ProfessionalIdentity {
  id: string;
  name: string;
  email: string;
}

export interface ProfessionalSession {
  professional: ProfessionalIdentity;
  demonstrationRole: DemoProfessionalRole | null;
}

@Injectable({ providedIn: 'root' })
export class ProfessionalSessionService {
  private readonly http = inject(HttpClient);
  readonly professional = signal<ProfessionalIdentity | null>(null);
  readonly demonstrationRole = signal<DemoProfessionalRole | null>(null);

  current(): Observable<ProfessionalSession> {
    return this.http.get<ProfessionalSession>('/api/v1/professional/session');
  }

  restore(): Observable<ProfessionalSession> {
    const professional = this.professional();

    return professional
      ? of({ professional, demonstrationRole: this.demonstrationRole() })
      : this.current().pipe(tap((session) => this.remember(session)));
  }

  login(email: string, password: string): Observable<ProfessionalSession> {
    return this.http
      .post<ProfessionalSession>('/api/v1/professional/session', { email, password })
      .pipe(tap((session) => this.remember(session)));
  }

  startDemonstration(role: DemoProfessionalRole): Observable<ProfessionalSession> {
    return this.http
      .post<ProfessionalSession>('/api/v1/demo/professional-session', { role })
      .pipe(tap((session) => this.remember(session)));
  }

  logout(): Observable<void> {
    return this.http.delete<void>('/api/v1/professional/session').pipe(
      tap(() => {
        this.professional.set(null);
        this.demonstrationRole.set(null);
      }),
    );
  }

  private remember(session: ProfessionalSession): void {
    this.professional.set(session.professional);
    this.demonstrationRole.set(session.demonstrationRole);
  }
}
