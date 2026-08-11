import { HttpClient, HttpEvent } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export type ReporterAttachmentStatus = 'processing' | 'available' | 'unavailable';

export interface ReporterAttachment {
  id: string;
  status: ReporterAttachmentStatus;
  createdAt: string;
  description: string | null;
  mediaType?: 'application/pdf' | 'image/jpeg' | 'image/png';
  byteSize?: number;
}

export interface ReporterAttachmentCollection {
  items: ReporterAttachment[];
}

@Injectable({ providedIn: 'root' })
export class ReportAttachmentService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/v1/reporter/report/attachments';

  establishReportAccess(accessSecret: string): Observable<void> {
    return this.http.post<void>('/api/v1/public/report-access-grants', { accessSecret });
  }

  list(): Observable<ReporterAttachmentCollection> {
    return this.http.get<ReporterAttachmentCollection>(this.endpoint);
  }

  upload(file: File, description: string): Observable<HttpEvent<ReporterAttachmentCollection>> {
    const form = new FormData();

    form.append('attachments[]', file);
    form.append('descriptions[]', description);

    return this.http.post<ReporterAttachmentCollection>(this.endpoint, form, {
      observe: 'events',
      reportProgress: true,
    });
  }

  downloadUrl(id: string): string {
    return `${this.endpoint}/${encodeURIComponent(id)}/download`;
  }
}
