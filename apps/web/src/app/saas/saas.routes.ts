import { Routes } from '@angular/router';

/**
 * SaaS 2.0 owner-review prototype routes. Each screen is a real component built
 * on the real design system, with fictional in-component data, for DR-1 review.
 * A shared index lists every screen so the owner can walk the whole set and
 * judge coherence.
 */
export const SAAS_REVIEW_ROUTES: Routes = [
  {
    path: '',
    pathMatch: 'full',
    title: 'Prototipos SaaS 2.0',
    loadComponent: () => import('./saas-review-index').then((m) => m.SaasReviewIndex),
  },
  {
    path: 'panel',
    title: 'Panel principal · #508',
    loadComponent: () => import('./saas-dashboard').then((m) => m.SaasDashboard),
  },
  {
    path: 'registro',
    title: 'Registro y cuenta · #511',
    loadComponent: () => import('./saas-onboarding').then((m) => m.SaasOnboarding),
  },
  {
    path: 'centro',
    title: 'Crear centro · #512',
    loadComponent: () => import('./saas-centre').then((m) => m.SaasCentre),
  },
  {
    path: 'miembros',
    title: 'Miembros · #513',
    loadComponent: () => import('./saas-members').then((m) => m.SaasMembers),
  },
  {
    path: 'entrada',
    title: 'Recorrido de quien reporta · #516-#518',
    loadComponent: () => import('./saas-reporting').then((m) => m.SaasReporting),
  },
  {
    path: 'pendientes',
    title: 'Pendientes y avisos · #526',
    loadComponent: () => import('./saas-pending').then((m) => m.SaasPending),
  },
  {
    path: 'caso',
    title: 'Espacio de trabajo del caso · #527',
    loadComponent: () => import('./saas-case').then((m) => m.SaasCase),
  },
  {
    path: 'export',
    title: 'Exportación PDF · #543',
    loadComponent: () => import('./saas-export').then((m) => m.SaasExport),
  },
];
