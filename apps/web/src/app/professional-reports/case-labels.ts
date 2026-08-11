import {
  CaseAssignmentRole,
  CaseModality,
  CaseStatus,
  CaseTaskStatus,
  WorkflowSourceAuthority,
} from './professional-cases.service';

export const caseStatusLabel = (status: CaseStatus): string =>
  ({ assessment: 'En valoración', active: 'Activo', closed: 'Cerrado' })[status];

export const caseModalityLabel = (modality: CaseModality): string =>
  ({
    in_person: 'Presencial',
    digital: 'Digital',
    mixed: 'Mixto',
    unknown: 'Sin concretar',
  })[modality];

export const assignmentRoleLabel = (role: CaseAssignmentRole): string =>
  ({ lead: 'Responsable', contributor: 'Colabora', observer: 'Consulta' })[role];

export const taskStatusLabel = (status: CaseTaskStatus): string =>
  ({ pending: 'Pendiente', completed: 'Completada', not_applicable: 'No aplica' })[status];

export const sourceAuthorityLabel = (authority: WorkflowSourceAuthority): string =>
  ({ binding: 'Normativa aplicable', recommended: 'Recomendación', internal: 'Objetivo interno' })[
    authority
  ];

export function personRoleLabel(role: string): string {
  return (
    {
      affected: 'Persona afectada',
      alleged_actor: 'Persona señalada',
      witness: 'Testigo',
      guardian: 'Responsable legal',
      other: 'Otra vinculación',
    }[role] ?? role
  );
}

export function stageLabel(stage: string): string {
  return (
    {
      identification: 'Identificación',
      immediate_actions: 'Actuaciones inmediatas',
      urgent_protection: 'Protección urgente',
      family_communication: 'Comunicación con familias',
      professional_coordination: 'Coordinación profesional',
      information_collection: 'Recogida de información',
      educational_measures: 'Medidas educativas',
      inspection_communication: 'Comunicación con Inspección',
      assessment: 'Valoración',
      action_plan: 'Plan de actuación',
      family_measures: 'Medidas con las familias',
      inspection_follow_up: 'Seguimiento de Inspección',
    }[stage] ?? stage
  );
}
