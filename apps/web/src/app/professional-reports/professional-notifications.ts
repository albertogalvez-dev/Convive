import { DatePipe } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { Router } from '@angular/router';
import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import {
  ProfessionalNotification,
  ProfessionalNotificationPreference,
  ProfessionalNotificationsService,
} from './professional-notifications.service';
@Component({
  selector: 'app-professional-notifications',
  standalone: true,
  imports: [DatePipe],
  templateUrl: './professional-notifications.html',
  styleUrl: './professional-notifications.scss',
})
export class ProfessionalNotifications implements OnInit {
  private readonly notifications = inject(ProfessionalNotificationsService);
  private readonly router = inject(Router);
  private readonly sessions = inject(ProfessionalSessionService);
  protected readonly items = signal<ProfessionalNotification[]>([]);
  protected readonly preferences = signal<ProfessionalNotificationPreference[]>([]);
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly isDemonstration = computed(() => this.sessions.demonstrationRole() !== null);

  ngOnInit(): void {
    if (this.isDemonstration()) {
      this.items.set([
        {
          id: 'demo-case-assigned',
          type: 'case_assigned',
          createdAt: '2026-08-25T08:30:00.000+00:00',
          readAt: null,
          href: '/profesionales/casos/019fe900-0000-7000-8000-000000000083',
        },
        {
          id: 'demo-case-lifecycle',
          type: 'case_lifecycle_changed',
          createdAt: '2026-08-24T15:10:00.000+00:00',
          readAt: null,
          href: '/profesionales/casos/019fe900-0000-7000-8000-000000000084',
        },
      ]);
      this.preferences.set([
        { type: 'case_assigned', enabled: true, required: true },
        { type: 'case_lifecycle_changed', enabled: true, required: false },
      ]);
      this.loading.set(false);
      return;
    }

    this.notifications.list().subscribe({
      next: ({ items }) => {
        this.items.set(items);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('No hemos podido cargar los avisos.');
        this.loading.set(false);
      },
    });
    this.notifications
      .preferences()
      .subscribe({ next: ({ items }) => this.preferences.set(items) });
  }
  protected open(notification: ProfessionalNotification): void {
    if (this.isDemonstration()) {
      this.items.update((items) =>
        items.map((item) =>
          item.id === notification.id && item.readAt === null
            ? { ...item, readAt: '2026-08-25T09:00:00.000+00:00' }
            : item,
        ),
      );
      void this.router.navigateByUrl(notification.href);
      return;
    }

    if (notification.readAt) {
      void this.router.navigateByUrl(notification.href);
      return;
    }
    this.notifications.markRead(notification.id).subscribe({
      next: (updated) => {
        this.items.update((items) =>
          items.map((item) => (item.id === updated.id ? updated : item)),
        );
        void this.router.navigateByUrl(updated.href);
      },
      error: () => this.error.set('No hemos podido abrir el aviso. Inténtalo de nuevo.'),
    });
  }
  protected change(preference: ProfessionalNotificationPreference, enabled: boolean): void {
    if (this.isDemonstration()) {
      this.preferences.update((items) =>
        items.map((item) => (item.type === preference.type ? { ...item, enabled } : item)),
      );
      return;
    }

    this.notifications.changePreference(preference.type, enabled).subscribe({
      next: (updated) =>
        this.preferences.update((items) =>
          items.map((item) => (item.type === updated.type ? updated : item)),
        ),
      error: () => this.error.set('No hemos podido guardar el ajuste.'),
    });
  }
  protected label(type: ProfessionalNotification['type']): string {
    return type === 'case_assigned'
      ? 'Se te ha asignado un caso.'
      : 'Ha cambiado el estado de un caso al que tienes acceso.';
  }
}
