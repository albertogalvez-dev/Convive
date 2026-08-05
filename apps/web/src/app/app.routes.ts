import { Routes } from '@angular/router';

import { ReportForm } from './reporting/report-form';

export const routes: Routes = [
  {
    path: 'r/:publicReportingIdentifier',
    component: ReportForm,
  },
];
