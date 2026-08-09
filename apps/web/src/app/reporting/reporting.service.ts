import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

import { SituationContext } from './situation-context';

export interface PublicReportingProfile {
  name: string;
}

export interface SubmitReportRequest {
  situationDescription: string;
  situationContext: SituationContext;
}

export interface ReportSubmissionResponse {
  publicReference: string;
  accessSecret: string;
  status: string;
  createdAt: string;
}

@Injectable({ providedIn: 'root' })
export class ReportingService {
  private readonly http = inject(HttpClient);

  getPublicReportingProfile(publicReportingIdentifier: string): Observable<PublicReportingProfile> {
    return this.http.get<PublicReportingProfile>(
      this.organisationEndpoint(publicReportingIdentifier),
    );
  }

  submitReport(
    publicReportingIdentifier: string,
    request: SubmitReportRequest,
  ): Observable<ReportSubmissionResponse> {
    return this.http.post<ReportSubmissionResponse>(
      `${this.organisationEndpoint(publicReportingIdentifier)}/reports`,
      request,
    );
  }

  private organisationEndpoint(publicReportingIdentifier: string): string {
    return `/api/v1/public/organisations/${encodeURIComponent(publicReportingIdentifier)}`;
  }
}
