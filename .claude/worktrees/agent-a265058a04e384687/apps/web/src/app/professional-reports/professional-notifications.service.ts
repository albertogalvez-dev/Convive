import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';
export type ProfessionalNotificationType = 'case_assigned' | 'case_lifecycle_changed';
export interface ProfessionalNotification {
  id: string;
  type: ProfessionalNotificationType;
  createdAt: string;
  readAt: string | null;
  href: string;
}
export interface ProfessionalNotificationPreference {
  type: ProfessionalNotificationType;
  enabled: boolean;
  required: boolean;
}
@Injectable({ providedIn: 'root' })
export class ProfessionalNotificationsService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/v1/professional/notifications';
  list(): Observable<{ items: ProfessionalNotification[]; unreadCount: number }> {
    return this.http.get<{ items: ProfessionalNotification[]; unreadCount: number }>(this.endpoint);
  }
  markRead(id: string): Observable<ProfessionalNotification> {
    return this.http.post<ProfessionalNotification>(
      `${this.endpoint}/${encodeURIComponent(id)}/read`,
      {},
    );
  }
  preferences(): Observable<{ items: ProfessionalNotificationPreference[] }> {
    return this.http.get<{ items: ProfessionalNotificationPreference[] }>(
      '/api/v1/professional/notification-preferences',
    );
  }
  changePreference(
    type: ProfessionalNotificationType,
    enabled: boolean,
  ): Observable<ProfessionalNotificationPreference> {
    return this.http.patch<ProfessionalNotificationPreference>(
      `/api/v1/professional/notification-preferences/${type}`,
      { enabled },
    );
  }
}
