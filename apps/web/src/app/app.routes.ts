import { Routes } from '@angular/router';

import { applicationHostGuard, publicWebsiteHostGuard } from './host-boundary.guard';
import { PublicHome } from './public-home/public-home';
import { professionalAuthGuard } from './professional-access/professional-auth.guard';
import { PUBLIC_INFORMATION_PAGES } from './public-information/public-information-content';

export const routes: Routes = [
  { path: '', pathMatch: 'full', component: PublicHome, canMatch: [publicWebsiteHostGuard] },
  {
    path: 'demostracion',
    pathMatch: 'full',
    loadComponent: () => import('./public-demo/public-demo').then((module) => module.PublicDemo),
    canMatch: [publicWebsiteHostGuard],
  },
  {
    path: 'demostracion/profesional',
    loadComponent: () =>
      import('./professional-demo/professional-demo').then((module) => module.ProfessionalDemo),
    canMatch: [publicWebsiteHostGuard],
  },
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
  ...PUBLIC_INFORMATION_PAGES.map((meta) => ({
    path: meta.path.replace(/^\/|\/$/g, ''),
    loadComponent: () =>
      import('./public-information/public-information').then((module) => module.PublicInformation),
    canMatch: [publicWebsiteHostGuard],
    data: { meta },
  })),
  {
    path: '',
    canMatch: [applicationHostGuard],
    children: [
      {
        path: 'r/:publicReportingIdentifier',
        loadComponent: () => import('./reporting/report-form').then((module) => module.ReportForm),
      },
      {
        path: 'seguimiento',
        loadComponent: () => import('./follow-up/follow-up').then((module) => module.FollowUp),
      },
      {
        path: 'verificar-correo',
        loadComponent: () =>
          import('./email-verification/email-verification').then(
            (module) => module.EmailVerification,
          ),
      },
      {
        path: 'profesionales/acceso',
        loadComponent: () =>
          import('./professional-access/professional-access').then(
            (module) => module.ProfessionalAccess,
          ),
      },
      {
        path: 'profesionales/activar',
        loadComponent: () =>
          import('./professional-access/professional-credential-activation').then(
            (module) => module.ProfessionalCredentialActivation,
          ),
      },
      {
        path: 'profesionales',
        loadComponent: () =>
          import('./professional-reports/professional-shell').then(
            (module) => module.ProfessionalShell,
          ),
        canActivate: [professionalAuthGuard],
        children: [
          {
            path: '',
            pathMatch: 'full',
            loadComponent: () =>
              import('./professional-reports/professional-dashboard').then(
                (module) => module.ProfessionalDashboard,
              ),
          },
          {
            path: 'comunicaciones',
            loadComponent: () =>
              import('./professional-reports/professional-inbox').then(
                (module) => module.ProfessionalInbox,
              ),
          },
          {
            path: 'comunicaciones/:id',
            loadComponent: () =>
              import('./professional-reports/professional-detail').then(
                (module) => module.ProfessionalDetail,
              ),
          },
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
          {
            path: 'avisos',
            loadComponent: () =>
              import('./professional-reports/professional-notifications').then(
                (module) => module.ProfessionalNotifications,
              ),
          },
          {
            path: 'ajustes',
            loadComponent: () =>
              import('./professional-reports/professional-settings').then(
                (module) => module.ProfessionalSettings,
              ),
          },
          {
            path: 'cuentas',
            loadComponent: () =>
              import('./professional-reports/professional-accounts').then(
                (module) => module.ProfessionalAccounts,
              ),
          },
        ],
      },
    ],
  },
  {
    path: '**',
    loadComponent: () => import('./not-found/not-found').then((module) => module.NotFound),
  },
];
