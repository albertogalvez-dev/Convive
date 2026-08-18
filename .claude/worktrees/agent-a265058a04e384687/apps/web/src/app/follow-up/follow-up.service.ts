import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

import { SituationContext } from '../reporting/situation-context';

export type FollowUpAuthorType = 'reporter' | 'professional';

export interface FollowUpEntry {
  authorType: FollowUpAuthorType;
  content: string;
  createdAt: string;
}

export interface ReportFollowUpState {
  publicReference: string;
  situationDescription: string;
  situationContext: SituationContext;
  status: string;
  createdAt: string;
  followUpEntries: FollowUpEntry[];
}

export interface ReporterEmailNotificationStatus {
  readonly enabled: boolean;
  readonly status: 'none' | 'pending' | 'verified';
}

@Injectable({ providedIn: 'root' })
export class FollowUpService {
  private readonly http = inject(HttpClient);

  /**
   * Exchanges the access secret for a report-scoped capability. The
   * capability is returned as a protected cookie the browser stores and
   * replays on its own: it never reaches this code (ADR-0008, ADR-0010).
   */
  verifyReportAccess(accessSecret: string): Observable<void> {
    return this.http.post<void>('/api/v1/public/report-access-grants', {
      accessSecret,
    });
  }

  getReportFollowUpState(): Observable<ReportFollowUpState> {
    return this.http.get<ReportFollowUpState>('/api/v1/reporter/report');
  }

  addFollowUpEntry(content: string): Observable<FollowUpEntry> {
    return this.http.post<FollowUpEntry>('/api/v1/reporter/report/follow-up-entries', {
      content,
    });
  }

  revokeReportAccess(): Observable<void> {
    return this.http.delete<void>('/api/v1/reporter/access-grant');
  }

  getEmailNotificationStatus(): Observable<ReporterEmailNotificationStatus> {
    return this.http.get<ReporterEmailNotificationStatus>(
      '/api/v1/reporter/report/email-notifications',
    );
  }

  configureEmailNotifications(email: string): Observable<ReporterEmailNotificationStatus> {
    return this.http.put<ReporterEmailNotificationStatus>(
      '/api/v1/reporter/report/email-notifications',
      { email, consentAccepted: true },
    );
  }

  removeEmailNotifications(): Observable<void> {
    return this.http.delete<void>('/api/v1/reporter/report/email-notifications');
  }
}
