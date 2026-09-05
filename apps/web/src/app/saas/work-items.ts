/**
 * Convive SaaS 2.0 — the professional's dated work, shared by the home queue
 * (#508) and the calendar (#508 follow-up). Fictional sample data.
 *
 * `due` is the source of truth. The home derives its "Fuera de plazo / Vence
 * hoy / En N días" status from it; the calendar places the item on that day.
 */

export type WorkKind = 'assessment' | 'task' | 'followup';
export type DeadlineState = 'overdue' | 'today' | 'upcoming';

export interface WorkItem {
  id: string;
  reference: string;
  kind: WorkKind;
  title: string;
  context: string;
  due: Date;
  detail: {
    caseName: string;
    body: string;
    source?: string;
    action: string;
  };
}

/**
 * The prototype pretends "now" is the morning of Saturday 5 September 2026, so
 * the sample deadlines fall either side of it in a stable way.
 */
export const PROTOTYPE_NOW = new Date('2026-09-05T09:30:00');

export const WORK_ITEMS: readonly WorkItem[] = [
  {
    id: 'com-0089',
    reference: 'COM-0089',
    kind: 'assessment',
    title: 'Valorar una comunicación nueva',
    context: 'Sin asignar',
    due: new Date('2026-09-05T09:10:00'),
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
    due: new Date('2026-09-04T14:00:00'),
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
    due: new Date('2026-09-05T12:30:00'),
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
    due: new Date('2026-09-08T09:00:00'),
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
    due: new Date('2026-09-09T10:00:00'),
    detail: {
      caseName: 'Discusión en clase con continuación digital',
      body: 'El profesorado colaborador ha propuesto el cierre del caso. Como responsable, revisa el resumen y confirma o devuelve la propuesta.',
      action: 'Revisar la propuesta',
    },
  },
  {
    id: 'caso-0142',
    reference: 'CASO-0142',
    kind: 'followup',
    title: 'Seguimiento a dos semanas',
    context: 'Seguimiento que fijaste tú',
    due: new Date('2026-09-11T09:00:00'),
    detail: {
      caseName: 'Acompañamiento tras un cambio de grupo',
      body: 'Seguimiento programado para comprobar cómo evoluciona la situación dos semanas después de la última actuación.',
      action: 'Registrar el seguimiento',
    },
  },
];

const DAY_MS = 24 * 60 * 60 * 1000;

function startOfDay(date: Date): number {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
}

export function sameDay(a: Date, b: Date): boolean {
  return startOfDay(a) === startOfDay(b);
}

/** Short relative distance for same-day deadlines, e.g. "en 3 h", "hace 20 min". */
function relativeWithinDay(due: Date, now: Date): string {
  const diffMs = due.getTime() - now.getTime();
  const past = diffMs < 0;
  const minutes = Math.round(Math.abs(diffMs) / (60 * 1000));
  const amount = minutes < 60 ? `${minutes} min` : `${Math.round(minutes / 60)} h`;

  return past ? `hace ${amount}` : `en ${amount}`;
}

const TIME_FMT = new Intl.DateTimeFormat('es-ES', { hour: '2-digit', minute: '2-digit' });
const WEEKDAY_FMT = new Intl.DateTimeFormat('es-ES', { weekday: 'short' });
const DAY_MONTH_FMT = new Intl.DateTimeFormat('es-ES', { day: 'numeric', month: 'short' });

export interface DeadlineView {
  state: DeadlineState;
  /** One compact token for the list row, e.g. "Vencido · 20 min", "Mar 8". */
  chip: string;
  /** The status word for the detail pane, e.g. "Fuera de plazo". */
  status: string;
  /** The exact moment for the detail pane, e.g. "Venció ayer, 14:00". */
  when: string;
}

function capitaliseWeekday(due: Date): string {
  const weekday = WEEKDAY_FMT.format(due).replace('.', '');
  return weekday.charAt(0).toUpperCase() + weekday.slice(1);
}

/**
 * Turns a due date into what the home shows: a short chip on the list row and
 * the full status + exact moment on the detail pane.
 */
export function describeDeadline(due: Date, now: Date): DeadlineView {
  const time = TIME_FMT.format(due);
  const dayGap = (startOfDay(due) - startOfDay(now)) / DAY_MS;

  // Overdue on an earlier day.
  if (dayGap <= -1) {
    const day = dayGap === -1 ? 'ayer' : DAY_MONTH_FMT.format(due);
    return {
      state: 'overdue',
      chip: `Vencido · ${day}`,
      status: 'Fuera de plazo',
      when: `Venció ${dayGap === -1 ? 'ayer' : `el ${day}`}, ${time}`,
    };
  }

  // Same day: the magnitude (overdue) or the time (still due) matters.
  if (dayGap === 0) {
    const past = due.getTime() < now.getTime();
    if (past) {
      return {
        state: 'overdue',
        chip: `Vencido · ${relativeWithinDay(due, now).replace('hace ', '')}`,
        status: 'Fuera de plazo',
        when: `Hoy, ${time} · ${relativeWithinDay(due, now)}`,
      };
    }
    return {
      state: 'today',
      chip: `Hoy · ${time}`,
      status: 'Vence hoy',
      when: `Hoy, ${time} · ${relativeWithinDay(due, now)}`,
    };
  }

  // Upcoming: weekday + day is enough while scanning; the pane has the time.
  return {
    state: 'upcoming',
    chip: `${capitaliseWeekday(due)} ${due.getDate()}`,
    status: `En ${dayGap} ${dayGap === 1 ? 'día' : 'días'}`,
    when: `${capitaliseWeekday(due)} ${DAY_MONTH_FMT.format(due)}, ${time}`,
  };
}
