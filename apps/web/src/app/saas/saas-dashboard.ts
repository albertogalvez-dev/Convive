import { Component, computed, signal } from '@angular/core';

import { DemoRoleOption, SaasShell } from './saas-shell';

/**
 * SaaS 2.0 — professional workspace first screen (issue #508, expectation P-1).
 *
 * Real, runnable screen for owner review under DR-1, not a wireframe. Fictional
 * sample data lives here; no API, no real data. The role picker is a review aid
 * so the owner can compare the genuinely-distinct default layout per C-4.
 */

type Urgency = 'overdue' | 'soon' | 'ontrack';
type ItemKind = 'task' | 'communication' | 'assessment';

interface QueueItem {
  reference: string;
  kind: ItemKind;
  title: string;
  context: string;
  urgency: Urgency;
  due: string;
}

interface Metric {
  value: string;
  label: string;
}

interface RolePanel {
  key: string;
  firstName: string;
  fullName: string;
  centre: string;
  functionLabel: string;
  unreadNotifications: number;
  scopeNote: string;
  queue: QueueItem[];
  panorama: Metric[];
}

const PANELS: readonly RolePanel[] = [
  {
    key: 'tutoria',
    firstName: 'Marina',
    fullName: 'Marina Ortiz',
    centre: 'IES Aula Abierta',
    functionLabel: 'Tutoría',
    unreadNotifications: 2,
    scopeNote:
      'Ves solo lo relativo a tus propias tutorías: alumnado del que eres responsable y tus plazos. Ningún caso de otro tutor aparece aquí.',
    queue: [
      {
        reference: 'CASO-0142',
        kind: 'task',
        title: 'Reunión con la familia de un alumno de 2.º A',
        context: 'Tarea · asignada a ti',
        urgency: 'overdue',
        due: 'Venció hace 1 día',
      },
      {
        reference: 'CASO-0138',
        kind: 'communication',
        title: 'Registrar la evolución tras la última comunicación',
        context: 'Tu grupo de tutoría',
        urgency: 'soon',
        due: 'Vence hoy',
      },
      {
        reference: 'CASO-0151',
        kind: 'task',
        title: 'Confirmar asistencia a la reunión de coordinación',
        context: 'Colaboras en el caso',
        urgency: 'ontrack',
        due: 'Vence en 3 días',
      },
    ],
    panorama: [
      { value: '4', label: 'Casos activos tuyos' },
      { value: '1', label: 'En seguimiento' },
      { value: '12', label: 'Cerrados este curso' },
    ],
  },
  {
    key: 'bienestar',
    firstName: 'Iker',
    fullName: 'Iker Bilbao',
    centre: 'IES Aula Abierta',
    functionLabel: 'Coordinación de bienestar y protección',
    unreadNotifications: 5,
    scopeNote:
      'Evalúas las comunicaciones nuevas y sueles ser responsable de los casos del centro. El panel prioriza lo que aún no tiene una primera valoración.',
    queue: [
      {
        reference: 'COM-0089',
        kind: 'assessment',
        title: 'Comunicación nueva sin valorar',
        context: 'Sin asignar · pendiente de evaluación',
        urgency: 'overdue',
        due: 'Venció hace 4 h',
      },
      {
        reference: 'CASO-0130',
        kind: 'task',
        title: 'Notificar a la familia según el protocolo',
        context: 'Eres responsable del caso',
        urgency: 'overdue',
        due: 'Venció hace 1 día',
      },
      {
        reference: 'COM-0091',
        kind: 'assessment',
        title: 'Comunicación nueva sin valorar',
        context: 'Sin asignar · pendiente de evaluación',
        urgency: 'soon',
        due: 'Vence hoy',
      },
      {
        reference: 'CASO-0119',
        kind: 'task',
        title: 'Revisar el cierre propuesto por el profesorado colaborador',
        context: 'Eres responsable del caso',
        urgency: 'ontrack',
        due: 'Vence en 4 días',
      },
    ],
    panorama: [
      { value: '11', label: 'Casos activos' },
      { value: '3', label: 'Sin valorar' },
      { value: '2', label: 'Reasignaciones pendientes' },
      { value: '47', label: 'Cerrados este curso' },
    ],
  },
  {
    key: 'direccion',
    firstName: 'Concha',
    fullName: 'Concha Feito',
    centre: 'IES Aula Abierta',
    functionLabel: 'Dirección',
    unreadNotifications: 0,
    scopeNote:
      'Dirección no evalúa comunicaciones ni recibe casos por defecto: solo ves lo que se te ha asignado de forma explícita. Un panel vacío aquí es lo correcto, no un fallo.',
    queue: [],
    panorama: [
      { value: '0', label: 'Casos asignados a ti' },
      { value: '9', label: 'Casos activos en el centro' },
      { value: '58', label: 'Cerrados este curso' },
    ],
  },
];

@Component({
  selector: 'app-saas-dashboard',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-dashboard.html',
  styleUrl: './saas-dashboard.scss',
})
export class SaasDashboard {
  protected readonly roleOptions: readonly DemoRoleOption[] = PANELS.map((entry) => ({
    key: entry.key,
    label: entry.functionLabel,
    person: entry.fullName,
  }));
  protected readonly activeKey = signal<string>(PANELS[0].key);
  protected readonly panel = computed<RolePanel>(
    () => PANELS.find((entry) => entry.key === this.activeKey()) ?? PANELS[0],
  );

  protected selectRole(key: string): void {
    this.activeKey.set(key);
  }
}
