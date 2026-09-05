import { Component, computed, signal } from '@angular/core';

import { SaasShell } from './saas-shell';

/** Convive SaaS 2.0 — inicio profesional. Fictional sample data. */

type Bucket = 'overdue' | 'today' | 'week';
type Scope = Bucket | 'all';

interface QueueItem {
  reference: string;
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

interface ActivityItem {
  title: string;
  reference: string;
  time: string;
}

const BUCKET_ORDER: readonly Bucket[] = ['overdue', 'today', 'week'];

const BUCKET_LABELS: Record<Bucket, string> = {
  overdue: 'Fuera de plazo',
  today: 'Vence hoy',
  week: 'Esta semana',
};

const ITEMS: readonly QueueItem[] = [
  {
    reference: 'COM-0089',
    title: 'Comunicación nueva sin valorar',
    context: 'Sin asignar, llegó hace 4 horas',
    bucket: 'overdue',
    due: '4 h de retraso',
  },
  {
    reference: 'CASO-0130',
    title: 'Comunicar a inspección educativa',
    context: 'Protocolo de Andalucía, paso 3',
    bucket: 'overdue',
    due: '1 día de retraso',
  },
  {
    reference: 'CASO-0130',
    title: 'Reunión con las familias',
    context: 'Protocolo de Andalucía, paso 2',
    bucket: 'today',
    due: 'Hoy, 12:30',
  },
  {
    reference: 'COM-0091',
    title: 'Comunicación nueva sin valorar',
    context: 'Sin asignar, llegó hoy',
    bucket: 'week',
    due: 'Lunes 8',
  },
  {
    reference: 'CASO-0119',
    title: 'Revisar el cierre propuesto',
    context: 'Eres responsable del caso',
    bucket: 'week',
    due: 'Martes 9',
  },
  {
    reference: 'CASO-0142',
    title: 'Seguimiento a dos semanas',
    context: 'Fijado por ti',
    bucket: 'week',
    due: 'Jueves 11',
  },
];

function formatToday(): string {
  const formatted = new Intl.DateTimeFormat('es-ES', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date());

  return formatted.charAt(0).toUpperCase() + formatted.slice(1);
}

@Component({
  selector: 'app-saas-dashboard',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-dashboard.html',
  styleUrl: './saas-dashboard.scss',
})
export class SaasDashboard {
  protected readonly today = formatToday();
  protected readonly filter = signal<Scope>('all');

  protected readonly scopes: readonly { key: Scope; label: string; count: number }[] = [
    { key: 'all', label: 'Todo', count: ITEMS.length },
    ...BUCKET_ORDER.map((bucket) => ({
      key: bucket as Scope,
      label: BUCKET_LABELS[bucket],
      count: ITEMS.filter((item) => item.bucket === bucket).length,
    })),
  ];

  protected readonly groups = computed<QueueGroup[]>(() => {
    const active = this.filter();

    return BUCKET_ORDER.filter((bucket) => active === 'all' || active === bucket)
      .map((bucket) => ({
        bucket,
        label: BUCKET_LABELS[bucket],
        items: ITEMS.filter((item) => item.bucket === bucket),
      }))
      .filter((group) => group.items.length > 0);
  });

  protected readonly activity: readonly ActivityItem[] = [
    { title: 'Marina Ortiz añadió una nota interna', reference: 'CASO-0130', time: 'Hoy, 09:14' },
    { title: 'Documento disponible tras la revisión', reference: 'CASO-0130', time: 'Ayer, 08:20' },
    { title: 'Cierre registrado por Concha Feito', reference: 'CASO-0104', time: '2 sep, 12:05' },
  ];

  protected setFilter(scope: Scope): void {
    this.filter.set(scope);
  }
}
