import {
  CaseAssignmentRole,
  CaseModality,
  CaseStatus,
  CaseTaskStatus,
  WorkflowSourceAuthority,
} from './professional-cases.service';

/**
 * These return translation keys, not text. The page pipes them through
 * Transloco, so the professional reads them in the locale they chose rather
 * than in whatever language the code was written in.
 *
 * Unknown values fall through to the raw code on purpose. Transloco returns
 * the key itself when no translation exists, so an unrecognised stage still
 * renders its identifier instead of an empty label — the same behaviour these
 * functions had before, when they ended in `?? stage`.
 */
const SCOPE = 'professional-case';

export const caseStatusLabel = (status: CaseStatus): string => `${SCOPE}.caseStatus.${status}`;

export const caseModalityLabel = (modality: CaseModality): string =>
  `${SCOPE}.modality.${modality}`;

export const assignmentRoleLabel = (role: CaseAssignmentRole): string =>
  `${SCOPE}.assignmentRole.${role}`;

export const taskStatusLabel = (status: CaseTaskStatus): string => `${SCOPE}.taskStatus.${status}`;

export const sourceAuthorityLabel = (authority: WorkflowSourceAuthority): string =>
  `${SCOPE}.sourceAuthority.${authority}`;

const PERSON_ROLES = ['affected', 'alleged_actor', 'witness', 'guardian', 'other'];

export function personRoleLabel(role: string): string {
  return PERSON_ROLES.includes(role) ? `${SCOPE}.personRole.${role}` : role;
}

const STAGES = [
  'identification',
  'immediate_actions',
  'urgent_protection',
  'family_communication',
  'professional_coordination',
  'information_collection',
  'educational_measures',
  'inspection_communication',
  'assessment',
  'action_plan',
  'family_measures',
  'inspection_follow_up',
];

export function stageLabel(stage: string): string {
  return STAGES.includes(stage) ? `${SCOPE}.stage.${stage}` : stage;
}
