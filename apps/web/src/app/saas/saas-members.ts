import { Component } from '@angular/core';

import { SaasShell } from './saas-shell';

/**
 * SaaS 2.0 — centre member management (issue #513, expectation C-5).
 * Real screen for owner review; fictional data.
 */

type MemberStatus = 'active' | 'pending' | 'suspended';

interface Member {
  initials: string;
  name: string;
  detail: string;
  role: string;
  status: MemberStatus;
  soleAdministrator: boolean;
}

const MEMBERS: readonly Member[] = [
  {
    initials: 'CF',
    name: 'Concha Feito',
    detail: 'concha.feito@iesaulaabierta.edu',
    role: 'Administradora inicial',
    status: 'active',
    soleAdministrator: true,
  },
  {
    initials: 'MO',
    name: 'Marina Ortiz',
    detail: 'marina.ortiz@iesaulaabierta.edu',
    role: 'Tutoría',
    status: 'active',
    soleAdministrator: false,
  },
  {
    initials: 'IB',
    name: 'Iker Bilbao',
    detail: 'iker.bilbao@iesaulaabierta.edu',
    role: 'Coord. bienestar y protección',
    status: 'active',
    soleAdministrator: false,
  },
  {
    initials: '·',
    name: 'jose.ramos@iesaulaabierta.edu',
    detail: 'Invitación enviada hace 2 días',
    role: 'Profesorado',
    status: 'pending',
    soleAdministrator: false,
  },
  {
    initials: 'LP',
    name: 'Laura Pardo',
    detail: 'laura.pardo@iesaulaabierta.edu',
    role: 'Administración y servicios',
    status: 'suspended',
    soleAdministrator: false,
  },
];

@Component({
  selector: 'app-saas-members',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-members.html',
  styleUrl: './saas-members.scss',
})
export class SaasMembers {
  protected readonly members = MEMBERS;

  protected statusLabel(status: MemberStatus): string {
    return status === 'active' ? 'Activa' : status === 'pending' ? 'Pendiente' : 'Suspendida';
  }
}
