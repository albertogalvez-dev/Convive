import { Routes } from '@angular/router';

/**
 * Convive SaaS 2.0 screens. Each screen is a real component on the shared
 * design system with fictional in-component data, one URL per screen.
 */
export const SAAS_REVIEW_ROUTES: Routes = [
  {
    path: '',
    pathMatch: 'full',
    title: 'Convive SaaS 2.0',
    loadComponent: () => import('./saas-review-index').then((m) => m.SaasReviewIndex),
  },
  {
    path: 'panel',
    title: 'Inicio profesional',
    loadComponent: () => import('./saas-dashboard').then((m) => m.SaasDashboard),
  },
  {
    path: 'pendientes',
    title: 'Pendientes',
    loadComponent: () => import('./saas-pending').then((m) => m.SaasPending),
  },
  {
    path: 'caso',
    title: 'Seguimiento del caso',
    loadComponent: () => import('./saas-case').then((m) => m.SaasCase),
  },
  {
    path: 'calendario',
    title: 'Mi agenda',
    loadComponent: () => import('./saas-calendar').then((m) => m.SaasCalendar),
  },
  {
    path: 'avisos',
    title: 'Avisos',
    loadComponent: () => import('./saas-notifications').then((m) => m.SaasNotifications),
  },
  {
    path: 'miembros',
    title: 'Miembros',
    loadComponent: () => import('./saas-members').then((m) => m.SaasMembers),
  },
  {
    path: 'registro',
    title: 'Crear cuenta',
    data: { view: 'signup' },
    loadComponent: () => import('./saas-onboarding').then((m) => m.SaasOnboarding),
  },
  {
    path: 'cuenta',
    title: 'Tu cuenta',
    data: { view: 'empty' },
    loadComponent: () => import('./saas-onboarding').then((m) => m.SaasOnboarding),
  },
  {
    path: 'ajustes',
    title: 'Ajustes de cuenta',
    data: { view: 'settings' },
    loadComponent: () => import('./saas-onboarding').then((m) => m.SaasOnboarding),
  },
  {
    path: 'centro',
    title: 'Crear centro',
    data: { view: 'create' },
    loadComponent: () => import('./saas-centre').then((m) => m.SaasCentre),
  },
  {
    path: 'centro-identidad',
    title: 'Identidad del centro',
    data: { view: 'identity' },
    loadComponent: () => import('./saas-centre').then((m) => m.SaasCentre),
  },
  {
    path: 'entrada',
    title: 'Comunicación',
    data: { screen: 'form' },
    loadComponent: () => import('./saas-reporting').then((m) => m.SaasReporting),
  },
  {
    path: 'entrada-confirmacion',
    title: 'Comunicación enviada',
    data: { screen: 'result' },
    loadComponent: () => import('./saas-reporting').then((m) => m.SaasReporting),
  },
  {
    path: 'entrada-revocada',
    title: 'Enlace no disponible',
    data: { screen: 'revoked' },
    loadComponent: () => import('./saas-reporting').then((m) => m.SaasReporting),
  },
  {
    path: 'cartel',
    title: 'Cartel del centro',
    data: { screen: 'poster' },
    loadComponent: () => import('./saas-reporting').then((m) => m.SaasReporting),
  },
  {
    path: 'documento',
    title: 'Ficha de caso',
    loadComponent: () => import('./saas-export').then((m) => m.SaasExport),
  },
];
