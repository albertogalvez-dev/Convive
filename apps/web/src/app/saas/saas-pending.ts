import { Component } from '@angular/core';

import { SaasShell } from './saas-shell';

/**
 * SaaS 2.0 — full pending-actions screen and notification system
 * (issue #526, expectation P-2). Real screen for owner review; fictional data.
 */

type Urgency = 'overdue' | 'soon' | 'ontrack';

interface PendingItem {
  reference: string;
  title: string;
  context: string;
  urgency: Urgency;
  due: string;
}

interface PendingGroup {
  heading: string;
  items: PendingItem[];
}

interface EventType {
  label: string;
  live: string;
  email: string;
  push: string;
}

const GROUPS: readonly PendingGroup[] = [
  {
    heading: 'CASO-0130 · Convivencia entre iguales',
    items: [
      {
        reference: 'Tarea',
        title: 'Comunicar a inspección educativa',
        context: 'Plazo del protocolo de Andalucía, paso 3',
        urgency: 'overdue',
        due: 'Venció hace 1 día',
      },
      {
        reference: 'Tarea',
        title: 'Reunión con las familias',
        context: 'Plazo del protocolo de Andalucía, paso 2',
        urgency: 'soon',
        due: 'Vence hoy',
      },
    ],
  },
  {
    heading: 'Sin caso asignado',
    items: [
      {
        reference: 'Evaluación',
        title: 'Comunicación nueva sin valorar',
        context: 'Llegó hace 4 h',
        urgency: 'overdue',
        due: 'Venció hace 4 h',
      },
      {
        reference: 'Evaluación',
        title: 'Comunicación nueva sin valorar',
        context: 'Llegó hoy',
        urgency: 'ontrack',
        due: 'Vence en 3 días',
      },
    ],
  },
];

const EVENT_TYPES: readonly EventType[] = [
  { label: 'Nueva asignación', live: 'Siempre', email: 'Opcional', push: 'Opcional' },
  { label: 'Plazo próximo o vencido', live: 'Siempre', email: 'Opcional', push: 'Opcional' },
  { label: 'Novedad de quien reportó', live: 'Siempre', email: 'Opcional', push: 'Opcional' },
  { label: 'Mención (@)', live: 'Siempre', email: 'Opcional', push: 'Opcional' },
  { label: 'Invitación recibida o aceptada', live: 'Siempre', email: 'Opcional', push: 'Opcional' },
  { label: 'Cambio de rol propio', live: 'Siempre', email: 'Opcional', push: 'Opcional' },
  { label: 'Cambio de estado del centro', live: 'Siempre', email: 'Opcional', push: 'Opcional' },
  {
    label: 'Escaneo de evidencia completado',
    live: 'Siempre',
    email: 'Opcional',
    push: 'Opcional',
  },
];

@Component({
  selector: 'app-saas-pending',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-pending.html',
  styleUrl: './saas-pending.scss',
})
export class SaasPending {
  protected readonly groups = GROUPS;
  protected readonly eventTypes = EVENT_TYPES;
}
