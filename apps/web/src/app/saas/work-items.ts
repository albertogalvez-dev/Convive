/**
 * Convive SaaS 2.0 — the professional's dated work, shared by the home board
 * (#508) and the calendar (#508 follow-up). Fictional sample data.
 *
 * `due` is the source of truth. The home buckets each item (Fuera de plazo /
 * Hoy / Esta semana) and shows either a red H:MM:SS counter once it is past,
 * or just the due time while it is still in hand. The calendar places the item
 * on that day.
 */

export type WorkKind = 'assessment' | 'task' | 'followup';
export type DeadlineState = 'overdue' | 'today' | 'upcoming';

export interface WorkItem {
  id: string;
  reference: string;
  kind: WorkKind;
  title: string;
  /** The professional's relationship to the item, e.g. "Sin asignar". */
  context: string;
  /** The moment it is due. */
  due: Date;
  detail: {
    caseName: string;
    body: string;
    action: string;
  };
}

/**
 * The prototype pretends "now" is the instant the screen loaded, so the sample
 * deadlines fall either side of it and the overdue counters tick against real
 * time.
 */
export const PROTOTYPE_NOW = new Date();

const HOUR_MS = 60 * 60 * 1000;
const DAY_MS = 24 * HOUR_MS;

/** A due date this many hours from load — negative is in the past. */
function hoursFromNow(hours: number): Date {
  return new Date(PROTOTYPE_NOW.getTime() + hours * HOUR_MS);
}

/** A due date N days from load at a fixed clock time. */
function dayAt(days: number, hour: number, minute: number): Date {
  const date = new Date(PROTOTYPE_NOW);
  date.setDate(date.getDate() + days);
  date.setHours(hour, minute, 0, 0);
  return date;
}

export const WORK_ITEMS: readonly WorkItem[] = [
  // --- Past due -------------------------------------------------------------
  {
    id: 'com-0089',
    reference: 'COM-0089',
    kind: 'assessment',
    title: 'Valorar una comunicación nueva',
    context: 'Sin asignar',
    due: hoursFromNow(-0.33),
    detail: {
      caseName: 'Comunicación sin asignar',
      body: 'Una persona informa de que un grupo de mensajería ha compartido comentarios excluyentes sobre un compañero. No se ha registrado ninguna valoración todavía.',
      action: 'Abrir y valorar',
    },
  },
  {
    id: 'caso-0125-1',
    reference: 'CASO-0125',
    kind: 'task',
    title: 'Activar el protocolo',
    context: 'Paso 1 del protocolo',
    due: hoursFromNow(-2.2),
    detail: {
      caseName: 'Conflicto reiterado entre dos alumnos',
      body: 'El caso se abrió a partir de una comunicación ya valorada. Queda activarlo formalmente y asignar a la persona responsable.',
      action: 'Activar el protocolo',
    },
  },
  {
    id: 'caso-0130-3',
    reference: 'CASO-0130',
    kind: 'task',
    title: 'Comunicar a inspección educativa',
    context: 'Paso 3 del protocolo',
    due: hoursFromNow(-19.5),
    detail: {
      caseName: 'Convivencia entre iguales — posible acoso',
      body: 'Queda pendiente comunicar el caso a inspección educativa y registrar la fecha de envío.',
      action: 'Registrar la comunicación',
    },
  },
  {
    id: 'com-0084',
    reference: 'COM-0084',
    kind: 'assessment',
    title: 'Valorar una comunicación nueva',
    context: 'Sin asignar',
    due: hoursFromNow(-30),
    detail: {
      caseName: 'Comunicación sin asignar',
      body: 'Una familia traslada su preocupación por comentarios repetidos hacia su hija en el patio. Pendiente de una primera valoración.',
      action: 'Abrir y valorar',
    },
  },

  // --- Due today ---------------------------------------------------------
  {
    id: 'com-0093',
    reference: 'COM-0093',
    kind: 'assessment',
    title: 'Valorar una comunicación nueva',
    context: 'Sin asignar',
    due: hoursFromNow(0.25),
    detail: {
      caseName: 'Comunicación sin asignar',
      body: 'Una persona informa de burlas repetidas a un compañero durante los cambios de clase. Sin valorar.',
      action: 'Abrir y valorar',
    },
  },
  {
    id: 'caso-0138',
    reference: 'CASO-0138',
    kind: 'followup',
    title: 'Llamada de seguimiento a la familia',
    context: 'Eres responsable del caso',
    due: hoursFromNow(0.7),
    detail: {
      caseName: 'Acompañamiento tras un parte de convivencia',
      body: 'Toca la llamada quincenal de seguimiento con la familia. Deja registrada la fecha y un breve resumen.',
      action: 'Registrar la llamada',
    },
  },
  {
    id: 'caso-0130-2',
    reference: 'CASO-0130',
    kind: 'task',
    title: 'Reunión con las familias',
    context: 'Paso 2 del protocolo',
    due: hoursFromNow(3),
    detail: {
      caseName: 'Convivencia entre iguales — posible acoso',
      body: 'Reunión prevista con las familias del alumnado implicado. Queda registrar la fecha efectiva y un resumen no valorativo del encuentro.',
      action: 'Registrar la reunión',
    },
  },
  {
    id: 'com-0092',
    reference: 'COM-0092',
    kind: 'assessment',
    title: 'Valorar una comunicación nueva',
    context: 'Sin asignar',
    due: hoursFromNow(7),
    detail: {
      caseName: 'Comunicación sin asignar',
      body: 'Un docente informa de un posible caso de aislamiento en un grupo de 2.º de ESO. Pendiente de una primera lectura.',
      action: 'Abrir y valorar',
    },
  },
  {
    id: 'caso-0125-2',
    reference: 'CASO-0125',
    kind: 'task',
    title: 'Entrevistas con el alumnado implicado',
    context: 'Paso 2 del protocolo',
    due: hoursFromNow(9),
    detail: {
      caseName: 'Conflicto reiterado entre dos alumnos',
      body: 'Entrevistas individuales previstas para hoy. Queda registrar que se han realizado y un resumen no valorativo.',
      action: 'Registrar las entrevistas',
    },
  },

  // --- This week -------------------------------------------------------
  {
    id: 'caso-0151',
    reference: 'CASO-0151',
    kind: 'task',
    title: 'Reunión del equipo de convivencia',
    context: 'Eres responsable del caso',
    due: dayAt(1, 16, 30),
    detail: {
      caseName: 'Revisión mensual de casos abiertos',
      body: 'Reunión prevista para revisar el estado de los casos abiertos y repartir las tareas de la semana.',
      action: 'Ver el orden del día',
    },
  },
  {
    id: 'com-0091',
    reference: 'COM-0091',
    kind: 'assessment',
    title: 'Valorar una comunicación nueva',
    context: 'Sin asignar',
    due: dayAt(2, 9, 0),
    detail: {
      caseName: 'Comunicación sin asignar',
      body: 'Una persona informante comparte que han circulado mensajes que podrían estar aislando a un compañero. Pendiente de una primera lectura.',
      action: 'Abrir y valorar',
    },
  },
  {
    id: 'caso-0119',
    reference: 'CASO-0119',
    kind: 'task',
    title: 'Revisar el cierre propuesto',
    context: 'Eres responsable del caso',
    due: dayAt(3, 10, 0),
    detail: {
      caseName: 'Discusión en clase con continuación digital',
      body: 'El profesorado colaborador ha propuesto el cierre del caso. Como responsable, revisa el resumen y confirma o devuelve la propuesta.',
      action: 'Revisar la propuesta',
    },
  },
  {
    id: 'caso-0144',
    reference: 'CASO-0144',
    kind: 'task',
    title: 'Revisar el cierre propuesto',
    context: 'Eres responsable del caso',
    due: dayAt(4, 12, 0),
    detail: {
      caseName: 'Uso indebido de imágenes en un grupo de clase',
      body: 'El equipo colaborador propone el cierre. Revisa el resumen y confirma o devuelve la propuesta.',
      action: 'Revisar la propuesta',
    },
  },
  {
    id: 'caso-0142',
    reference: 'CASO-0142',
    kind: 'followup',
    title: 'Seguimiento a dos semanas',
    context: 'Seguimiento que fijaste tú',
    due: dayAt(5, 9, 0),
    detail: {
      caseName: 'Acompañamiento tras un cambio de grupo',
      body: 'Seguimiento programado para comprobar cómo evoluciona la situación dos semanas después de la última actuación.',
      action: 'Registrar el seguimiento',
    },
  },
];

function startOfDay(date: Date): number {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
}

export function sameDay(a: Date, b: Date): boolean {
  return startOfDay(a) === startOfDay(b);
}

/** Where a due date sits relative to now: past, today, or a later day. */
export function deadlineState(due: Date, now: Date): DeadlineState {
  if (due.getTime() <= now.getTime()) return 'overdue';
  return sameDay(due, now) ? 'today' : 'upcoming';
}

export interface DeadlineChip {
  state: DeadlineState;
  /** Board face: "3:20:41" counting up (past) · "en 3 h" (today) · "en 2 días". */
  text: string;
  /** The exact moment, spelt out, for the expanded detail. */
  exact: string;
  /** True while `text` is a second-by-second counter (past due only). */
  ticking: boolean;
}

const WEEKDAY_FMT = new Intl.DateTimeFormat('es-ES', { weekday: 'short' });
const TIME_FMT = new Intl.DateTimeFormat('es-ES', { hour: '2-digit', minute: '2-digit' });

function pad(value: number): string {
  return value.toString().padStart(2, '0');
}

function capitalise(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

/** "Mié 9, 10:00" — weekday, day, time. */
function spellMoment(date: Date): string {
  const weekday = capitalise(WEEKDAY_FMT.format(date).replace('.', ''));
  return `${weekday} ${date.getDate()}, ${TIME_FMT.format(date)}`;
}

/** The deadline shown on a board card: a relative distance on the face, the
 *  exact moment kept for the expanded detail. */
export function deadlineChip(due: Date, now: Date): DeadlineChip {
  const state = deadlineState(due, now);

  if (state === 'overdue') {
    const totalSeconds = Math.floor((now.getTime() - due.getTime()) / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    return {
      state,
      text: `${hours}:${pad(minutes)}:${pad(seconds)}`,
      exact: `Venció el ${spellMoment(due)}`,
      ticking: true,
    };
  }

  if (state === 'today') {
    const msLeft = due.getTime() - now.getTime();
    const exact = `Hoy, ${TIME_FMT.format(due)}`;
    // Under an hour: a live count-down, like the overdue counter.
    if (msLeft < 60 * 60 * 1000) {
      const total = Math.max(Math.floor(msLeft / 1000), 0);
      const hours = Math.floor(total / 3600);
      const minutes = Math.floor((total % 3600) / 60);
      const seconds = total % 60;
      return { state, text: `${hours}:${pad(minutes)}:${pad(seconds)}`, exact, ticking: true };
    }
    // An hour or more away: relative distance plus the concrete hour.
    const hoursLeft = Math.round(msLeft / 3_600_000);
    return { state, text: `en ${hoursLeft} h · ${TIME_FMT.format(due)}`, exact, ticking: false };
  }

  const daysLeft = Math.round((startOfDay(due) - startOfDay(now)) / DAY_MS);
  const relative = daysLeft === 1 ? 'mañana' : `en ${daysLeft} días`;
  const weekday = WEEKDAY_FMT.format(due).replace('.', '');
  return {
    state,
    text: `${relative} · ${weekday} ${due.getDate()}`,
    exact: spellMoment(due),
    ticking: false,
  };
}
