import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export type ProfessionalReportStatus = 'new' | 'reviewed';

export interface ProfessionalReportSummary {
  id: string;
  publicReference: string;
  situationPreview: string;
  situationContext: string;
  reporterTaxonomy?: { version: string; recurrence: string; attentionCue: string };
  status: ProfessionalReportStatus;
  createdAt: string;
}

export interface ProfessionalReportPage {
  items: ProfessionalReportSummary[];
  pagination: { limit: number; nextCursor: string | null };
}

export interface ProfessionalReportDetail extends ProfessionalReportSummary {
  situationDescription: string;
  review: {
    reason: string;
    reviewedAt: string;
    professionalTaxonomy?: {
      version: string;
      concernCategory: string | null;
      recurrence: string | null;
      attentionCue: string | null;
    };
  } | null;
  followUpEntries: ProfessionalReportConversationEntry[];
}

export interface ProfessionalReportReviewResponse {
  review: NonNullable<ProfessionalReportDetail['review']>;
}

export interface ProfessionalReportConversationEntry {
  authorType: 'reporter' | 'professional';
  content: string;
  createdAt: string;
}

export interface ProfessionalReportAttachment {
  id: string;
  mediaType: 'application/pdf' | 'image/jpeg' | 'image/png';
  byteSize: number;
  createdAt: string;
  description: string | null;
}

@Injectable({ providedIn: 'root' })
export class ProfessionalReportsService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/v1/professional/reports';

  list(
    status: ProfessionalReportStatus,
    cursor?: string,
    limit = 20,
  ): Observable<ProfessionalReportPage> {
    let params = new HttpParams().set('status', status).set('limit', limit);
    if (cursor) params = params.set('cursor', cursor);

    return this.http.get<ProfessionalReportPage>(this.endpoint, { params });
  }

  detail(id: string): Observable<ProfessionalReportDetail> {
    return this.http.get<ProfessionalReportDetail>(`${this.endpoint}/${encodeURIComponent(id)}`);
  }

  review(
    id: string,
    request: {
      reason: string;
      professionalConcernCategory: string;
      professionalRecurrence: string;
      professionalAttentionCue: string;
    },
  ): Observable<ProfessionalReportReviewResponse> {
    return this.http.post<ProfessionalReportReviewResponse>(
      `${this.endpoint}/${encodeURIComponent(id)}/reviews`,
      request,
    );
  }

  respond(id: string, content: string): Observable<ProfessionalReportConversationEntry> {
    return this.http.post<ProfessionalReportConversationEntry>(
      `${this.endpoint}/${encodeURIComponent(id)}/responses`,
      { content },
    );
  }

  attachments(id: string): Observable<{ items: ProfessionalReportAttachment[] }> {
    return this.http.get<{ items: ProfessionalReportAttachment[] }>(
      `${this.endpoint}/${encodeURIComponent(id)}/attachments`,
    );
  }

  previewAttachment(id: string, attachmentId: string): Observable<Blob> {
    return this.http.get(
      `${this.endpoint}/${encodeURIComponent(id)}/attachments/${encodeURIComponent(attachmentId)}/download`,
      { responseType: 'blob' },
    );
  }
}
