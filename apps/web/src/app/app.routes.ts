import { Routes } from '@angular/router';

import { EmailVerification } from './email-verification/email-verification';
import { FollowUp } from './follow-up/follow-up';
import { applicationHostGuard, publicWebsiteHostGuard } from './host-boundary.guard';
import { NotFound } from './not-found/not-found';
import { PublicHome } from './public-home/public-home';
import {
  PublicInformation,
  PublicInformationContent,
} from './public-information/public-information';
import { ProfessionalAccess } from './professional-access/professional-access';
import { professionalAuthGuard } from './professional-access/professional-auth.guard';
import { ProfessionalDashboard } from './professional-reports/professional-dashboard';
import { ProfessionalDetail } from './professional-reports/professional-detail';
import { ProfessionalInbox } from './professional-reports/professional-inbox';
import { ProfessionalSettings } from './professional-reports/professional-settings';
import { ProfessionalShell } from './professional-reports/professional-shell';
import { ReportForm } from './reporting/report-form';

const publicInformationRoutes: ReadonlyArray<{
  readonly path: string;
  readonly content: PublicInformationContent;
}> = [
  {
    path: 'demostracion',
    content: {
      eyebrow: 'DEMOSTRACIÓN FICTICIA',
      title: 'La demostración se está preparando.',
      description:
        'Mostrará recorridos ficticios para explicar Convive sin abrir el canal operativo de comunicaciones.',
      notice: 'No utilices esta página para comunicar una situación real o urgente.',
    },
  },
  {
    path: 'contacto',
    content: {
      eyebrow: 'INFORMACIÓN PARA CENTROS',
      title: 'El canal de contacto se habilitará próximamente.',
      description:
        'La solicitud de información y demostración se incorporará con un tratamiento de datos limitado.',
      notice: 'No envíes información personal ni comunicaciones de convivencia por esta vía.',
    },
  },
];

export const routes: Routes = [
  { path: '', pathMatch: 'full', component: PublicHome, canMatch: [publicWebsiteHostGuard] },
  {
    path: 'blog',
    pathMatch: 'full',
    loadComponent: () => import('./blog/blog-index').then((module) => module.BlogIndex),
    canMatch: [publicWebsiteHostGuard],
  },
  {
    path: 'blog/:slug',
    loadComponent: () => import('./blog/blog-article').then((module) => module.BlogArticle),
    canMatch: [publicWebsiteHostGuard],
  },
  ...publicInformationRoutes.map(({ path, content }) => ({
    path,
    component: PublicInformation,
    canMatch: [publicWebsiteHostGuard],
    data: { content },
  })),
  {
    path: '',
    canMatch: [applicationHostGuard],
    children: [
      { path: 'r/:publicReportingIdentifier', component: ReportForm },
      { path: 'seguimiento', component: FollowUp },
      { path: 'verificar-correo', component: EmailVerification },
      { path: 'profesionales/acceso', component: ProfessionalAccess },
      {
        path: 'profesionales',
        component: ProfessionalShell,
        canActivate: [professionalAuthGuard],
        children: [
          { path: '', pathMatch: 'full', component: ProfessionalDashboard },
          { path: 'comunicaciones', component: ProfessionalInbox },
          { path: 'comunicaciones/:id', component: ProfessionalDetail },
          {
            path: 'casos',
            loadComponent: () =>
              import('./professional-reports/professional-cases').then(
                (module) => module.ProfessionalCases,
              ),
          },
          {
            path: 'casos/:id',
            loadComponent: () =>
              import('./professional-reports/professional-case-detail').then(
                (module) => module.ProfessionalCaseDetailPage,
              ),
          },
          { path: 'ajustes', component: ProfessionalSettings },
        ],
      },
    ],
  },
  { path: '**', component: NotFound },
];
