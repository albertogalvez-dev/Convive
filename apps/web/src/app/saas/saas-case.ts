import { Component, computed, signal } from '@angular/core';

import { SaasShell } from './saas-shell';

/**
 * SaaS 2.0 — case workspace (issue #527, expectation P-5).
 *
 * Follows the delivered case screen: summary tiles, a task panel with the
 * accent bar and the cited protocol source, and one chronological story that
 * filters in place instead of splitting into tabs. Fictional data.
 */

type StoryFilter = 'all' | 'tasks' | 'communications' | 'evidence' | 'discussion';
type StoryKind = 'assessment' | 'task' | 'communication' | 'discussion' | 'evidence';

interface Step {
  title: string;
  owner: string;
  target: string;
  urgency: 'overdue' | 'soon' | 'ontrack';
  due: string;
  source?: { authority: string; title: string; reference: string };
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
    owner: 'Iker Bilbao',
    target: '4 sep 2026, 10:00',
    urgency: 'overdue',
    due: 'Fuera de plazo',
    source: {
      authority: 'Obligatorio',
      title: 'Protocolo andaluz de actuación ante el acoso escolar',
      reference: 'BOJA 132/2011 · ES-AN · revisado 12/08/2026',
    },
  },
  {
    title: 'Reunión con las familias',
    owner: 'Iker Bilbao',
    target: '5 sep 2026, 12:30',
    urgency: 'soon',
    due: 'Vence hoy',
    source: {
      authority: 'Obligatorio',
      title: 'Protocolo andaluz de actuación ante el acoso escolar',
      reference: 'BOJA 132/2011 · ES-AN · revisado 12/08/2026',
    },
  },
  {
    title: 'Seguimiento a dos semanas',
    owner: 'Marina Ortiz',
    target: '19 sep 2026, 09:00',
    urgency: 'ontrack',
    due: 'En 12 días',
  },
];

const STORY: readonly StoryEntry[] = [
  {
    kind: 'assessment',
    actor: 'Iker Bilbao',
    tag: 'Valoración',
    time: '28 ago 2026, 10:14',
    body: 'Primera valoración registrada tras la comunicación inicial. Se activa como caso gestionado bajo el protocolo de convivencia.',
    reporterVisible: false,
  },
  {
    kind: 'task',
    actor: 'Convive',
    tag: 'Tareas',
    time: '28 ago 2026, 10:15',
    body: 'Se precargan los tres pasos procedimentales del protocolo aplicable, con los plazos que el propio protocolo indica.',
    reporterVisible: false,
  },
  {
    kind: 'communication',
    actor: 'Iker Bilbao',
    tag: 'Comunicación',
    time: '29 ago 2026, 09:02',
    body: 'Contacto telefónico con la familia del Alumno A para informar del inicio del proceso.',
    reporterVisible: true,
  },
  {
    kind: 'discussion',
    actor: 'Marina Ortiz',
    tag: 'Discusión interna',
    time: '30 ago 2026, 16:40',
    body: 'He hablado con el tutor del Alumno B, confirma la versión inicial. ¿Lo incluimos en la reunión con las familias?',
    reporterVisible: false,
  },
  {
    kind: 'evidence',
    actor: 'Iker Bilbao',
    tag: 'Documento',
    time: '31 ago 2026, 08:20',
    body: 'Captura de conversación aportada por la familia. Se consulta dentro de la aplicación, sin descarga.',
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
    { key: 'evidence', label: 'Documentos' },
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
