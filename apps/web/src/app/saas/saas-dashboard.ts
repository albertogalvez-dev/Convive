import { Component, computed, signal } from '@angular/core';

import { SaasShell } from './saas-shell';

/** Convive SaaS 2.0 — inicio profesional. Fictional sample data. */

type Bucket = 'now' | 'week';
type ItemKind = 'assessment' | 'task';
type DeadlineState = 'overdue' | 'today' | 'upcoming';

interface Deadline {
  state: DeadlineState;
  /** Short status: "Fuera de plazo", "Vence hoy", "En 2 días". */
  status: string;
  /** Exact moment with time: "Venció ayer, 14:00", "Hoy, 12:30". */
  when: string;
}

interface QueueItem {
  id: string;
  reference: string;
  kind: ItemKind;
  title: string;
  context: string;
  bucket: Bucket;
  deadline: Deadline;
  detail: {
    caseName: string;
    body: string;
    source?: string;
    action: string;
  };
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
    id: 'com-0089',
    reference: 'COM-0089',
    kind: 'assessment',
    title: 'Valorar una comunicación nueva',
    context: 'Sin asignar',
    bucket: 'now',
    deadline: { state: 'overdue', status: 'Fuera de plazo', when: 'Venció hoy, 09:10 · hace 4 h' },
    detail: {
      caseName: 'Comunicación sin asignar',
      body: 'Una persona informa de que un grupo de mensajería ha compartido comentarios excluyentes sobre un compañero. No se ha registrado ninguna valoración todavía.',
      source: 'Recibida el 5 de septiembre, 05:10 · la primera valoración debe hacerse en 4 h',
      action: 'Abrir y valorar',
    },
  },
  {
    id: 'caso-0130-3',
    reference: 'CASO-0130',
    kind: 'task',
    title: 'Comunicar a inspección educativa',
    context: 'Protocolo de Andalucía · paso 3',
    bucket: 'now',
    deadline: {
      state: 'overdue',
      status: 'Fuera de plazo',
      when: 'Venció ayer, 14:00 · hace 1 día',
    },
    detail: {
      caseName: 'Convivencia entre iguales — posible acoso',
      body: 'El protocolo aplicable marca la comunicación a inspección educativa como paso 3, con un plazo de 24 horas desde la activación del caso.',
      source: 'Protocolo de Andalucía (BOJA 132/2011), paso 3 · plazo de 24 h',
      action: 'Registrar la comunicación',
    },
  },
  {
    id: 'caso-0130-2',
    reference: 'CASO-0130',
    kind: 'task',
    title: 'Reunión con las familias',
    context: 'Protocolo de Andalucía · paso 2',
    bucket: 'now',
    deadline: { state: 'today', status: 'Vence hoy', when: 'Hoy, 12:30 · en 3 h' },
    detail: {
      caseName: 'Convivencia entre iguales — posible acoso',
      body: 'Reunión prevista con las familias del alumnado implicado. Queda registrar la fecha efectiva y un resumen no valorativo del encuentro.',
      source: 'Protocolo de Andalucía (BOJA 132/2011), paso 2',
      action: 'Registrar la reunión',
    },
  },
  {
    id: 'com-0091',
    reference: 'COM-0091',
    kind: 'assessment',
    title: 'Valorar una comunicación nueva',
    context: 'Sin asignar',
    bucket: 'week',
    deadline: { state: 'upcoming', status: 'En 3 días', when: 'Lun 8 sep, 09:00' },
    detail: {
      caseName: 'Comunicación sin asignar',
      body: 'Una persona informante comparte que han circulado mensajes que podrían estar aislando a un compañero. Pendiente de una primera lectura.',
      source: 'Recibida el 5 de septiembre, 08:40',
      action: 'Abrir y valorar',
    },
  },
  {
    id: 'caso-0119',
    reference: 'CASO-0119',
    kind: 'task',
    title: 'Revisar el cierre propuesto',
    context: 'Eres responsable del caso',
    bucket: 'week',
    deadline: { state: 'upcoming', status: 'En 4 días', when: 'Mar 9 sep, 10:00' },
    detail: {
      caseName: 'Discusión en clase con continuación digital',
      body: 'El profesorado colaborador ha propuesto el cierre del caso. Como responsable, revisa el resumen y confirma o devuelve la propuesta.',
      action: 'Revisar la propuesta',
    },
  },
  {
    id: 'caso-0142',
    reference: 'CASO-0142',
    kind: 'task',
    title: 'Seguimiento a dos semanas',
    context: 'Seguimiento que fijaste tú',
    bucket: 'week',
    deadline: { state: 'upcoming', status: 'En 6 días', when: 'Jue 11 sep, 09:00' },
    detail: {
      caseName: 'Acompañamiento tras un cambio de grupo',
      body: 'Seguimiento programado para comprobar cómo evoluciona la situación dos semanas después de la última actuación.',
      action: 'Registrar el seguimiento',
    },
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
  protected readonly overdueCount = ITEMS.filter((item) => item.deadline.state === 'overdue')
    .length;
  protected readonly todayCount = ITEMS.filter((item) => item.bucket === 'now').length;

  protected readonly groups: readonly QueueGroup[] = (['now', 'week'] as Bucket[])
    .map((bucket) => ({
      bucket,
      label: BUCKET_LABELS[bucket],
      items: ITEMS.filter((item) => item.bucket === bucket),
    }))
    .filter((group) => group.items.length > 0);

  protected readonly selectedId = signal<string>(ITEMS[0].id);
  protected readonly selected = computed<QueueItem>(
    () => ITEMS.find((item) => item.id === this.selectedId()) ?? ITEMS[0],
  );

  protected select(id: string): void {
    this.selectedId.set(id);
  }
}
