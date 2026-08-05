import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export type SituationContext = 'in_person' | 'digital' | 'mixed' | 'unknown';

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

  submitReport(
    publicReportingIdentifier: string,
    request: SubmitReportRequest,
  ): Observable<ReportSubmissionResponse> {
    return this.http.post<ReportSubmissionResponse>(
      `/api/v1/public/organisations/${encodeURIComponent(publicReportingIdentifier)}/reports`,
      request,
    );
  }
}
