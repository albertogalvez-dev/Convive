import { Component, computed } from '@angular/core';

import { SaasShell } from './saas-shell';

/** Convive SaaS 2.0 — inicio profesional. Fictional sample data. */

type Bucket = 'now' | 'week';

interface QueueItem {
  reference: string;
  title: string;
  context: string;
  bucket: Bucket;
  due: string;
  overdue: boolean;
}

interface QueueGroup {
  bucket: Bucket;
  label: string;
  items: QueueItem[];
}

const BUCKET_LABELS: Record<Bucket, string> = {
  now: 'Para hoy',
  week: 'Esta semana',
};

const ITEMS: readonly QueueItem[] = [
  {
    reference: 'COM-0089',
    title: 'Valorar una comunicación nueva',
    context: 'Sin asignar · llegó hace 4 horas',
    bucket: 'now',
    due: 'Hace 4 h',
    overdue: true,
  },
  {
    reference: 'CASO-0130',
    title: 'Comunicar a inspección educativa',
    context: 'Protocolo de Andalucía · paso 3',
    bucket: 'now',
    due: 'Ayer',
    overdue: true,
  },
  {
    reference: 'CASO-0130',
    title: 'Reunión con las familias',
    context: 'Protocolo de Andalucía · paso 2',
    bucket: 'now',
    due: 'Hoy, 12:30',
    overdue: false,
  },
  {
    reference: 'COM-0091',
    title: 'Valorar una comunicación nueva',
    context: 'Sin asignar · llegó hoy',
    bucket: 'week',
    due: 'Lun 8 sep',
    overdue: false,
  },
  {
    reference: 'CASO-0119',
    title: 'Revisar el cierre propuesto',
    context: 'Eres responsable del caso',
    bucket: 'week',
    due: 'Mar 9 sep',
    overdue: false,
  },
  {
    reference: 'CASO-0142',
    title: 'Seguimiento a dos semanas',
    context: 'Seguimiento que fijaste tú',
    bucket: 'week',
    due: 'Jue 11 sep',
    overdue: false,
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

  protected readonly overdueCount = ITEMS.filter((item) => item.overdue).length;
  protected readonly todayCount = ITEMS.filter((item) => item.bucket === 'now').length;

  protected readonly groups = computed<QueueGroup[]>(() =>
    (['now', 'week'] as Bucket[])
      .map((bucket) => ({
        bucket,
        label: BUCKET_LABELS[bucket],
        items: ITEMS.filter((item) => item.bucket === bucket),
      }))
      .filter((group) => group.items.length > 0),
  );
}
