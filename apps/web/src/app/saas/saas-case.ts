import { Component, computed, signal } from '@angular/core';

import { SaasShell } from './saas-shell';

/**
 * SaaS 2.0 — case workspace (issue #527, expectation P-5).
 * Fixed header + próximos-pasos strip + one filterable chronological story,
 * never tabs. Real screen for owner review; fictional data.
 */

type StoryFilter = 'all' | 'tasks' | 'communications' | 'evidence' | 'discussion';
type StoryKind = 'assessment' | 'task' | 'communication' | 'discussion' | 'evidence';

interface Step {
  title: string;
  source: string;
  urgency: 'overdue' | 'soon' | 'ontrack';
  due: string;
}

interface StoryEntry {
  kind: StoryKind;
  actor: string;
  tag: string;
  time: string;
  body: string;
  reporterVisible: boolean;
}

const STEPS: readonly Step[] = [
  {
    title: 'Comunicar a inspección educativa',
    source: 'Plazo del protocolo de Andalucía, paso 3',
    urgency: 'overdue',
    due: 'Venció hace 1 día',
  },
  {
    title: 'Reunión con las familias',
    source: 'Plazo del protocolo de Andalucía, paso 2',
    urgency: 'soon',
    due: 'Vence hoy',
  },
  {
    title: 'Seguimiento a dos semanas',
    source: 'Fijado por Iker Bilbao',
    urgency: 'ontrack',
    due: 'Vence en 12 días',
  },
];

const STORY: readonly StoryEntry[] = [
  {
    kind: 'assessment',
    actor: 'Iker Bilbao',
    tag: 'Valoración',
    time: '28 ago, 10:14',
    body: 'Primera valoración registrada tras la comunicación inicial. Se activa como caso gestionado bajo el protocolo de convivencia.',
    reporterVisible: false,
  },
  {
    kind: 'task',
    actor: 'Sistema',
    tag: 'Tareas creadas',
    time: '28 ago, 10:15',
    body: '3 pasos procedimentales precargados desde el protocolo de Andalucía (pasos 1, 2 y 3).',
    reporterVisible: false,
  },
  {
    kind: 'communication',
    actor: 'Iker Bilbao',
    tag: 'Comunicación · familia',
    time: '29 ago, 09:02',
    body: 'Contacto telefónico con la familia del Alumno A para informar del inicio del proceso.',
    reporterVisible: true,
  },
  {
    kind: 'discussion',
    actor: 'Marina Ortiz',
    tag: 'Discusión interna',
    time: '30 ago, 16:40',
    body: 'He hablado con el tutor del Alumno B, confirma la versión inicial. @Iker Bilbao ¿lo incluimos en la reunión de familias?',
    reporterVisible: false,
  },
  {
    kind: 'evidence',
    actor: 'Iker Bilbao',
    tag: 'Evidencia',
    time: '31 ago, 08:20',
    body: 'Captura de conversación aportada por la familia. Reproducción en la aplicación, sin descarga.',
    reporterVisible: false,
  },
];

@Component({
  selector: 'app-saas-case',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-case.html',
  styleUrl: './saas-case.scss',
})
export class SaasCase {
  protected readonly steps = STEPS;
  protected readonly filter = signal<StoryFilter>('all');
  protected readonly filters: readonly { key: StoryFilter; label: string }[] = [
    { key: 'all', label: 'Todo' },
    { key: 'tasks', label: 'Tareas' },
    { key: 'communications', label: 'Comunicaciones' },
    { key: 'evidence', label: 'Evidencia' },
    { key: 'discussion', label: 'Discusión' },
  ];

  protected readonly story = computed<readonly StoryEntry[]>(() => {
    const active = this.filter();
    if (active === 'all') return STORY;
    const map: Record<Exclude<StoryFilter, 'all'>, StoryKind[]> = {
      tasks: ['task'],
      communications: ['communication'],
      evidence: ['evidence'],
      discussion: ['discussion', 'assessment'],
    };
    return STORY.filter((entry) => map[active].includes(entry.kind));
  });

  protected setFilter(key: StoryFilter): void {
    this.filter.set(key);
  }
}
