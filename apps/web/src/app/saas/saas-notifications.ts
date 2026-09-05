import { Component } from '@angular/core';

import { SaasShell } from './saas-shell';

/** Convive SaaS 2.0 — avisos. Fictional sample data. */

interface Notice {
  title: string;
  meta: string;
  unread: boolean;
}

interface Preference {
  label: string;
  email: boolean;
  push: boolean;
  required: boolean;
}

@Component({
  selector: 'app-saas-notifications',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-notifications.html',
  styleUrl: './saas-notifications.scss',
})
export class SaasNotifications {
  protected readonly notices: readonly Notice[] = [
    {
      title: 'Se te ha asignado un caso',
      meta: 'Sin leer · Hoy, 09:14',
      unread: true,
    },
    {
      title: 'Una tarea ha pasado de plazo',
      meta: 'Sin leer · Hoy, 08:00',
      unread: true,
    },
    {
      title: 'Marina Ortiz te ha mencionado',
      meta: 'Sin leer · Ayer, 16:40',
      unread: true,
    },
    {
      title: 'Documento disponible tras la revisión',
      meta: 'Leído · Ayer, 08:20',
      unread: false,
    },
    {
      title: 'Hay una novedad de quien comunicó',
      meta: 'Leído · 2 sep, 12:05',
      unread: false,
    },
  ];

  protected readonly preferences: readonly Preference[] = [
    { label: 'Asignaciones de caso', email: true, push: true, required: true },
    { label: 'Plazos próximos y vencidos', email: true, push: true, required: false },
    { label: 'Novedades de quien comunicó', email: true, push: false, required: false },
    { label: 'Menciones en un caso', email: true, push: true, required: false },
    { label: 'Invitaciones al centro', email: true, push: false, required: false },
    { label: 'Cambios en tu función', email: false, push: false, required: false },
    { label: 'Cambios de estado del centro', email: false, push: false, required: false },
    { label: 'Documentos revisados y disponibles', email: false, push: true, required: false },
  ];
}
