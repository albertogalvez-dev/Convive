import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { Router } from '@angular/router';
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
  protected readonly items = signal<ProfessionalNotification[]>([]);
  protected readonly preferences = signal<ProfessionalNotificationPreference[]>([]);
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  ngOnInit(): void {
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
