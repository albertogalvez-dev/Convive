import { Routes } from '@angular/router';

import { FollowUp } from './follow-up/follow-up';
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
];
