export type DemoProfessionalRole =
  'triage' | 'administrator' | 'case_lead' | 'case_contributor' | 'case_observer';

export interface DemoProfessionalRoleOption {
  id: DemoProfessionalRole;
  label: string;
}

const DEMONSTRATION_CASE_PATH = '/profesionales/casos/019fe900-0000-7000-8000-000000000083';

export const DEMO_PROFESSIONAL_ROLES: readonly DemoProfessionalRoleOption[] = [
  {
    id: 'triage',
    label: 'Gestión de casos',
  },
  {
    id: 'administrator',
    label: 'Administración',
  },
];

export function demonstrationStartPath(role: DemoProfessionalRole): string {
  return role === 'administrator'
    ? '/profesionales/cuentas'
    : role === 'triage'
      ? '/profesionales'
      : DEMONSTRATION_CASE_PATH;
}

export function isCaseDemonstrationRole(role: DemoProfessionalRole | null): boolean {
  return role === 'case_lead' || role === 'case_contributor' || role === 'case_observer';
}
