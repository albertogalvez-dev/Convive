import { Component, computed, signal } from '@angular/core';

import { SaasShell } from './saas-shell';

/** Convive SaaS 2.0 — inicio profesional. Fictional sample data. */

type Bucket = 'overdue' | 'today' | 'week';
type ItemKind = 'assessment' | 'task' | 'followup';

interface QueueItem {
  reference: string;
  kind: ItemKind;
  title: string;
  context: string;
  bucket: Bucket;
  due: string;
}

interface QueueGroup {
  bucket: Bucket;
  label: string;
  items: QueueItem[];
}

const ITEMS: readonly QueueItem[] = [
  {
    reference: 'COM-0089',
    kind: 'assessment',
    title: 'Comunicación nueva sin valorar',
    context: 'Sin asignar · llegó hace 4 horas',
    bucket: 'overdue',
    due: '4 h de retraso',
  },
  {
    reference: 'CASO-0130',
    kind: 'task',
    title: 'Comunicar a inspección educativa',
    context: 'Protocolo de Andalucía · paso 3',
    bucket: 'overdue',
    due: '1 día de retraso',
  },
  {
    reference: 'CASO-0130',
    kind: 'task',
    title: 'Reunión con las familias',
    context: 'Protocolo de Andalucía · paso 2',
    bucket: 'today',
    due: 'Hoy, 12:30',
  },
  {
    reference: 'COM-0091',
    kind: 'assessment',
    title: 'Comunicación nueva sin valorar',
    context: 'Sin asignar · llegó hoy',
    bucket: 'week',
    due: 'Lunes 8',
  },
  {
    reference: 'CASO-0119',
    kind: 'task',
    title: 'Revisar el cierre propuesto',
    context: 'Eres responsable',
    bucket: 'week',
    due: 'Martes 9',
  },
  {
    reference: 'CASO-0142',
    kind: 'followup',
    title: 'Seguimiento a dos semanas',
    context: 'Fijado por ti',
    bucket: 'week',
    due: 'Jueves 11',
  },
];

const GROUP_LABELS: Record<Bucket, string> = {
  overdue: 'Fuera de plazo',
  today: 'Hoy',
  week: 'Esta semana',
};

@Component({
  selector: 'app-saas-dashboard',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-dashboard.html',
  styleUrl: './saas-dashboard.scss',
})
export class SaasDashboard {
  protected readonly today = (() => {
    const formatted = new Intl.DateTimeFormat('es-ES', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    }).format(new Date());

    return formatted.charAt(0).toUpperCase() + formatted.slice(1);
  })();

  protected readonly filter = signal<Bucket | 'all'>('all');

  protected readonly counts = {
    overdue: ITEMS.filter((item) => item.bucket === 'overdue').length,
    today: ITEMS.filter((item) => item.bucket === 'today').length,
    week: ITEMS.filter((item) => item.bucket === 'week').length,
  };

  protected readonly groups = computed<QueueGroup[]>(() => {
    const active = this.filter();
    const buckets: Bucket[] = ['overdue', 'today', 'week'];

    return buckets
      .filter((bucket) => active === 'all' || active === bucket)
      .map((bucket) => ({
        bucket,
        label: GROUP_LABELS[bucket],
        items: ITEMS.filter((item) => item.bucket === bucket),
      }))
      .filter((group) => group.items.length > 0);
  });

  protected readonly activity: readonly { title: string; meta: string; time: string }[] = [
    {
      title: 'Marina Ortiz añadió una nota interna',
      meta: 'CASO-0130',
      time: 'Hoy, 09:14',
    },
    {
      title: 'Documento disponible tras la revisión',
      meta: 'CASO-0130',
      time: 'Ayer, 08:20',
    },
    {
      title: 'Cierre registrado por Concha Feito',
      meta: 'CASO-0104',
      time: '2 sep, 12:05',
    },
  ];

  protected setFilter(bucket: Bucket | 'all'): void {
    this.filter.update((current) => (current === bucket ? 'all' : bucket));
  }
}
