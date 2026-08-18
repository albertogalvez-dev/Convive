import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

import { SituationContext } from './situation-context';

export interface PublicReportingProfile {
  name: string;
  reportingMode: 'operational' | 'fictional_demo' | 'disabled';
}

export interface SubmitReportRequest {
  situationDescription: string;
  situationContext: SituationContext;
  reporterRecurrence: ReporterRecurrence;
  reporterAttentionCue: ReporterAttentionCue;
  reporterTiming: ReporterTiming;
  /** Omitted entirely when the reporter named nobody. */
  reportedPeople?: string;
  /**
   * Set by the entry point used, never asked as a question. Omitted by the
   * first-person entry so its request body is byte-for-byte what it sends
   * today; the API defaults to 'experienced'.
   */
  reporterPerspective?: ReporterPerspective;
}

/**
 * Whether the writer lived the situation or saw it. Not a severity or
 * credibility signal, and deliberately not part of the triage taxonomy.
 */
export type ReporterPerspective = 'experienced' | 'witnessed';

export type ReporterRecurrence = 'single' | 'repeated' | 'ongoing' | 'unknown';
export type ReporterTiming = 'within_days' | 'within_weeks' | 'longer_ago' | 'unknown';
export type ReporterAttentionCue =
  'needs_prompt_attention' | 'no_prompt_attention_indicated' | 'unknown';

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
