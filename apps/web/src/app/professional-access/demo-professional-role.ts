export type DemoProfessionalRole =
  'triage' | 'administrator' | 'case_lead' | 'case_contributor' | 'case_observer';

export interface DemoProfessionalRoleOption {
  id: DemoProfessionalRole;
  label: string;
  description: string;
}

const DEMONSTRATION_CASE_PATH = '/profesionales/casos/019fe900-0000-7000-8000-000000000083';

export const DEMO_PROFESSIONAL_ROLES: readonly DemoProfessionalRoleOption[] = [
  {
    id: 'triage',
    label: 'Profesional de bienestar',
    description: 'Revisa las comunicaciones y los casos del centro',
  },
  {
    id: 'administrator',
    label: 'Administración',
    description: 'Consulta las cuentas y la organización del centro',
  },
  {
    id: 'case_lead',
    label: 'Responsable de caso',
    description: 'Coordina el caso preparado y sus asignaciones',
  },
  {
    id: 'case_contributor',
    label: 'Colaborador de caso',
    description: 'Trabaja en el caso al que está asignado',
  },
  {
    id: 'case_observer',
    label: 'Observador de caso',
    description: 'Consulta el caso asignado sin editarlo',
  },
];

export function demonstrationStartPath(role: DemoProfessionalRole): string {
  return role === 'administrator'
    ? '/profesionales/cuentas'
    : role === 'triage'
      ? '/profesionales'
      : DEMONSTRATION_CASE_PATH;
}

export function demonstrationRoleLabel(role: DemoProfessionalRole): string {
  return DEMO_PROFESSIONAL_ROLES.find((option) => option.id === role)?.label ?? role;
}

export function isCaseDemonstrationRole(role: DemoProfessionalRole | null): boolean {
  return role === 'case_lead' || role === 'case_contributor' || role === 'case_observer';
}
