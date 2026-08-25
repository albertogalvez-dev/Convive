import { Routes } from '@angular/router';

import { applicationHostGuard, publicWebsiteHostGuard } from './host-boundary.guard';
import { PublicHome } from './public-home/public-home';
import { PublicDemoRedirect } from './public-demo/public-demo-redirect';
import { professionalAuthGuard } from './professional-access/professional-auth.guard';
import { PUBLIC_INFORMATION_PAGES } from './public-information/public-information-content';

export const routes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    component: PublicHome,
    title: 'Convive',
    canMatch: [publicWebsiteHostGuard],
  },
  {
    path: 'demostracion',
    pathMatch: 'full',
    title: 'Demostración',
    loadComponent: () => import('./public-demo/public-demo').then((module) => module.PublicDemo),
    canMatch: [publicWebsiteHostGuard],
  },
  {
    path: 'demo',
    pathMatch: 'full',
    title: 'Demostración',
    component: PublicDemoRedirect,
    canMatch: [publicWebsiteHostGuard],
  },
  {
    path: 'blog',
    pathMatch: 'full',
    title: 'Blog',
    loadComponent: () => import('./blog/blog-index').then((module) => module.BlogIndex),
    canMatch: [publicWebsiteHostGuard],
  },
  {
    path: 'blog/:slug',
    title: 'Blog',
    loadComponent: () => import('./blog/blog-article').then((module) => module.BlogArticle),
    canMatch: [publicWebsiteHostGuard],
  },
  ...PUBLIC_INFORMATION_PAGES.map((meta) => ({
    path: meta.path.replace(/^\/|\/$/g, ''),
    loadComponent: () =>
      import('./public-information/public-information').then((module) => module.PublicInformation),
    canMatch: [publicWebsiteHostGuard],
    title: 'Información',
    data: { meta },
  })),
  {
    path: '',
    canMatch: [applicationHostGuard],
    children: [
      {
        path: 'r/:publicReportingIdentifier',
        title: 'Comunicación',
        loadComponent: () => import('./reporting/report-form').then((module) => module.ReportForm),
      },
      {
        path: 'seguimiento',
        title: 'Seguimiento',
        loadComponent: () => import('./follow-up/follow-up').then((module) => module.FollowUp),
      },
      {
        path: 'verificar-correo',
        title: 'Verificar correo',
        loadComponent: () =>
          import('./email-verification/email-verification').then(
            (module) => module.EmailVerification,
          ),
      },
      {
        path: 'profesionales/acceso',
        title: 'Acceso profesional',
        loadComponent: () =>
          import('./professional-access/professional-access').then(
            (module) => module.ProfessionalAccess,
          ),
      },
      {
        path: 'profesionales/activar',
        title: 'Activar acceso profesional',
        loadComponent: () =>
          import('./professional-access/professional-credential-activation').then(
            (module) => module.ProfessionalCredentialActivation,
          ),
      },
      {
        path: 'profesionales',
        title: 'Área profesional',
        loadComponent: () =>
          import('./professional-reports/professional-shell').then(
            (module) => module.ProfessionalShell,
          ),
        canActivate: [professionalAuthGuard],
        children: [
          {
            path: '',
            pathMatch: 'full',
            title: 'Inicio profesional',
            loadComponent: () =>
              import('./professional-reports/professional-dashboard').then(
                (module) => module.ProfessionalDashboard,
              ),
          },
          {
            path: 'comunicaciones',
            title: 'Comunicaciones',
            loadComponent: () =>
              import('./professional-reports/professional-inbox').then(
                (module) => module.ProfessionalInbox,
              ),
          },
          {
            path: 'comunicaciones/:id',
            title: 'Comunicación',
            loadComponent: () =>
              import('./professional-reports/professional-detail').then(
                (module) => module.ProfessionalDetail,
              ),
          },
          {
            path: 'casos',
            title: 'Casos',
            loadComponent: () =>
              import('./professional-reports/professional-cases').then(
                (module) => module.ProfessionalCases,
              ),
          },
          {
            path: 'casos/:id',
            title: 'Caso',
            loadComponent: () =>
              import('./professional-reports/professional-case-detail').then(
                (module) => module.ProfessionalCaseDetailPage,
              ),
          },
          {
            path: 'avisos',
            title: 'Avisos',
            loadComponent: () =>
              import('./professional-reports/professional-notifications').then(
                (module) => module.ProfessionalNotifications,
              ),
          },
          {
            path: 'ajustes',
            title: 'Ajustes',
            loadComponent: () =>
              import('./professional-reports/professional-settings').then(
                (module) => module.ProfessionalSettings,
              ),
          },
          {
            path: 'cuentas',
            title: 'Cuentas',
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
    title: 'Página no encontrada',
    loadComponent: () => import('./not-found/not-found').then((module) => module.NotFound),
  },
];
