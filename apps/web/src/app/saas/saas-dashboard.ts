import { Component } from '@angular/core';

import { SaasShell } from './saas-shell';

/** Convive SaaS 2.0 — inicio profesional. Fictional sample data. */

interface QueueItem {
  reference: string;
  title: string;
  context: string;
  urgency: 'overdue' | 'soon' | 'ontrack';
  due: string;
}

interface ActivityItem {
  title: string;
  meta: string;
  time: string;
}

@Component({
  selector: 'app-saas-dashboard',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-dashboard.html',
  styleUrl: './saas-dashboard.scss',
})
export class SaasDashboard {
  protected readonly queue: readonly QueueItem[] = [
    {
      reference: 'COM-0089',
      title: 'Comunicación nueva sin valorar',
      context: 'Sin asignar',
      urgency: 'overdue',
      due: 'Venció hace 4 h',
    },
    {
      reference: 'CASO-0130',
      title: 'Comunicar a inspección educativa',
      context: 'Protocolo de Andalucía, paso 3',
      urgency: 'overdue',
      due: 'Venció hace 1 día',
    },
    {
      reference: 'CASO-0130',
      title: 'Reunión con las familias',
      context: 'Protocolo de Andalucía, paso 2',
      urgency: 'soon',
      due: 'Vence hoy',
    },
    {
      reference: 'COM-0091',
      title: 'Comunicación nueva sin valorar',
      context: 'Sin asignar',
      urgency: 'ontrack',
      due: 'Vence en 3 días',
    },
    {
      reference: 'CASO-0119',
      title: 'Revisar el cierre propuesto',
      context: 'Eres responsable',
      urgency: 'ontrack',
      due: 'Vence en 4 días',
    },
  ];

  protected readonly activity: readonly ActivityItem[] = [
    {
      title: 'Marina Ortiz añadió una nota interna',
      meta: 'CASO-0130 · En el centro',
      time: 'Hoy, 09:14',
    },
    {
      title: 'Documento disponible tras la revisión',
      meta: 'CASO-0130 · Aportado por la familia',
      time: 'Ayer, 08:20',
    },
    {
      title: 'Cierre registrado',
      meta: 'CASO-0104 · Concha Feito',
      time: '2 sep, 12:05',
    },
  ];
}
