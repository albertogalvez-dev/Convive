import { Component, computed, signal } from '@angular/core';

import { SaasShell } from './saas-shell';

/**
 * SaaS 2.0 — pending actions and the notification model (issue #526,
 * expectation P-2). Follows the delivered cases screen: operational summary
 * tiles, filter row, grouped list. Fictional data.
 */

type Urgency = 'overdue' | 'soon' | 'ontrack';
type ViewKey = 'all' | 'overdue' | 'assessment' | 'upcoming';

interface PendingItem {
  title: string;
  context: string;
  kind: string;
  urgency: Urgency;
  due: string;
  view: ViewKey[];
}

interface PendingGroup {
  heading: string;
  items: PendingItem[];
}

interface EventType {
  label: string;
  email: string;
  push: string;
}

const ITEMS: readonly PendingItem[] = [
  {
    title: 'Comunicar a inspección educativa',
    context: 'CASO-0130 · Plazo del protocolo de Andalucía, paso 3',
    kind: 'Tarea',
    urgency: 'overdue',
    due: 'Venció hace 1 día',
    view: ['all', 'overdue'],
  },
  {
    title: 'Reunión con las familias',
    context: 'CASO-0130 · Plazo del protocolo de Andalucía, paso 2',
    kind: 'Tarea',
    urgency: 'soon',
    due: 'Vence hoy',
    view: ['all', 'upcoming'],
  },
  {
    title: 'Comunicación nueva sin valorar',
    context: 'COM-0089 · Llegó hace 4 horas',
    kind: 'Valoración',
    urgency: 'overdue',
    due: 'Venció hace 4 h',
    view: ['all', 'overdue', 'assessment'],
  },
  {
    title: 'Comunicación nueva sin valorar',
    context: 'COM-0091 · Llegó hoy',
    kind: 'Valoración',
    urgency: 'ontrack',
    due: 'Vence en 3 días',
    view: ['all', 'assessment', 'upcoming'],
  },
  {
    title: 'Seguimiento a dos semanas',
    context: 'CASO-0119 · Fijado por ti',
    kind: 'Seguimiento',
    urgency: 'ontrack',
    due: 'Vence en 12 días',
    view: ['all', 'upcoming'],
  },
];

const EVENT_TYPES: readonly EventType[] = [
  { label: 'Nueva asignación', email: 'Si lo activas', push: 'Si lo activas' },
  { label: 'Plazo próximo o vencido', email: 'Si lo activas', push: 'Si lo activas' },
  { label: 'Novedad de quien comunicó', email: 'Si lo activas', push: 'Si lo activas' },
  { label: 'Mención en un caso', email: 'Si lo activas', push: 'Si lo activas' },
  { label: 'Invitación recibida o aceptada', email: 'Si lo activas', push: 'Si lo activas' },
  { label: 'Cambio en tu rol', email: 'Si lo activas', push: 'Si lo activas' },
  { label: 'Cambio de estado del centro', email: 'Si lo activas', push: 'Si lo activas' },
  { label: 'Documento revisado y disponible', email: 'Si lo activas', push: 'Si lo activas' },
];

@Component({
  selector: 'app-saas-pending',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-pending.html',
  styleUrl: './saas-pending.scss',
})
export class SaasPending {
  protected readonly eventTypes = EVENT_TYPES;
  protected readonly view = signal<ViewKey>('all');

  protected readonly views: readonly {
    key: ViewKey;
    label: string;
    count: number;
    attention?: boolean;
  }[] = [
    { key: 'all', label: 'Todo lo pendiente', count: ITEMS.length },
    {
      key: 'overdue',
      label: 'Fuera de plazo',
      count: ITEMS.filter((item) => item.view.includes('overdue')).length,
      attention: true,
    },
    {
      key: 'assessment',
      label: 'Sin valorar',
      count: ITEMS.filter((item) => item.view.includes('assessment')).length,
    },
    {
      key: 'upcoming',
      label: 'Próximos',
      count: ITEMS.filter((item) => item.view.includes('upcoming')).length,
    },
  ];

  protected readonly groups = computed<PendingGroup[]>(() => {
    const active = this.view();
    const visible = ITEMS.filter((item) => item.view.includes(active));
    const byCase = visible.filter((item) => item.context.startsWith('CASO'));
    const unassigned = visible.filter((item) => !item.context.startsWith('CASO'));

    return [
      { heading: 'Casos asignados a ti', items: byCase },
      { heading: 'Sin caso asignado', items: unassigned },
    ].filter((group) => group.items.length > 0);
  });

  protected selectView(key: ViewKey): void {
    this.view.set(key);
  }
}
