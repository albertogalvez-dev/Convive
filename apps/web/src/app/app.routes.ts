import { Routes } from '@angular/router';

import { FollowUp } from './follow-up/follow-up';
import { ProfessionalAccess } from './professional-access/professional-access';
import { professionalAuthGuard } from './professional-access/professional-auth.guard';
import { ProfessionalDetail } from './professional-reports/professional-detail';
import { ProfessionalDashboard } from './professional-reports/professional-dashboard';
import { ProfessionalInbox } from './professional-reports/professional-inbox';
import { ProfessionalShell } from './professional-reports/professional-shell';
import { ProfessionalSettings } from './professional-reports/professional-settings';
import { ReportForm } from './reporting/report-form';

export const routes: Routes = [
  {
    path: 'r/:publicReportingIdentifier',
    component: ReportForm,
  },
  {
    path: 'seguimiento',
    component: FollowUp,
  },
  {
    path: 'profesionales/acceso',
    component: ProfessionalAccess,
  },
  {
    path: 'profesionales',
    component: ProfessionalShell,
    canActivate: [professionalAuthGuard],
    children: [
      { path: '', pathMatch: 'full', component: ProfessionalDashboard },
      { path: 'comunicaciones', component: ProfessionalInbox },
      { path: 'comunicaciones/:id', component: ProfessionalDetail },
      { path: 'ajustes', component: ProfessionalSettings },
    ],
  },
];
