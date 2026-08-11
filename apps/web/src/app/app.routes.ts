import { Routes } from '@angular/router';

import { FollowUp } from './follow-up/follow-up';
import { EmailVerification } from './email-verification/email-verification';
import { applicationHostGuard, publicWebsiteHostGuard } from './host-boundary.guard';
import { NotFound } from './not-found/not-found';
import { PublicHome } from './public-home/public-home';
import {
  PublicInformation,
  PublicInformationContent,
} from './public-information/public-information';
import { ProfessionalAccess } from './professional-access/professional-access';
import { professionalAuthGuard } from './professional-access/professional-auth.guard';
import { ProfessionalDetail } from './professional-reports/professional-detail';
import { ProfessionalDashboard } from './professional-reports/professional-dashboard';
import { ProfessionalInbox } from './professional-reports/professional-inbox';
import { ProfessionalShell } from './professional-reports/professional-shell';
import { ProfessionalSettings } from './professional-reports/professional-settings';
import { ReportForm } from './reporting/report-form';

const publicInformationRoutes: ReadonlyArray<{
  readonly path: string;
  readonly content: PublicInformationContent;
}> = [
  {
    path: 'blog',
    content: {
      eyebrow: 'BLOG DE CONVIVE',
      title: 'Contenido en preparación.',
      description:
        'Estamos preparando publicaciones revisadas sobre convivencia escolar y el producto Convive.',
      notice: 'No publicamos orientación legal, clínica ni de actuación ante emergencias.',
    },
  },
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
      {
        path: 'r/:publicReportingIdentifier',
        component: ReportForm,
      },
      {
        path: 'seguimiento',
        component: FollowUp,
      },
      {
        path: 'verificar-correo',
        component: EmailVerification,
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
    ],
  },
  { path: '**', component: NotFound },
];
