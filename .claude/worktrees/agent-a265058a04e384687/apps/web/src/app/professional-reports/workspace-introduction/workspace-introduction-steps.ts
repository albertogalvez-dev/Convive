export interface WorkspaceIntroductionStep {
  readonly title: string;
  readonly body: readonly string[];
}

/**
 * What a first-time professional is told about the workspace.
 *
 * Every sentence describes behaviour the product actually has. In particular
 * the tour never says that Convive decides an obligation, calculates a
 * protocol deadline or notifies anybody: the case interface states the
 * opposite, and an introduction that contradicts the product it introduces is
 * worse than no introduction.
 *
 * No case, report, person or centre content appears here. The steps are static
 * text and read nothing from the session.
 */
export const WORKSPACE_INTRODUCTION_STEPS: readonly WorkspaceIntroductionStep[] = [
  {
    title: 'Este es tu espacio de trabajo',
    body: [
      'Aquí el equipo del centro ordena lo que llega, lo reparte y deja constancia de lo que hace.',
      'Convive ordena y recuerda. No valora la gravedad de nada, no decide qué hay que hacer y no actúa por su cuenta.',
    ],
  },
  {
    title: 'Solo ves los casos que te han asignado',
    body: [
      'Pertenecer al centro no te muestra ningún caso. Cada acceso a un caso es una asignación explícita, y queda registrada.',
      'Por eso puede haber trabajo en marcha que tú no ves: no es un fallo, es el límite que protege a las personas de las que se habla.',
    ],
  },
  {
    title: 'Las tareas las decides tú',
    body: [
      'Las plantillas de tarea citan la norma o el protocolo del que salen, pero no deciden una obligación ni calculan un plazo. La acción, la persona responsable y la fecha objetivo las fijas tú antes de guardar.',
      'Que una tarea esté registrada tampoco significa que se haya comunicado nada fuera de Convive. Si hay que avisar a alguien, se avisa por su cauce.',
    ],
  },
  {
    title: 'Los avisos te señalan qué ha cambiado',
    body: [
      'En avisos aparece lo que ha cambiado en tu trabajo desde la última vez que entraste.',
      'Es un recordatorio dentro de Convive, no una notificación a nadie de fuera.',
    ],
  },
];
