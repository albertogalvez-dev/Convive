import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export type CaseStatus = 'assessment' | 'active' | 'closed';
export type CaseModality = 'in_person' | 'digital' | 'mixed' | 'unknown';
export type CaseAssignmentRole = 'lead' | 'contributor' | 'observer';
export type CaseOperationalView = 'assigned' | 'overdue' | 'upcoming' | 'recent';
export type CaseTaskStatus = 'pending' | 'completed' | 'not_applicable';
export type CaseCommunicationRecipient =
  'family' | 'external_service' | 'education_inspectorate' | 'other';
export type CaseCommunicationChannel =
  'in_person' | 'telephone' | 'secure_portal' | 'written_record' | 'other';
export type CaseCommunicationStatus = 'planned' | 'recorded' | 'not_applicable';
export type WorkflowSourceAuthority = 'binding' | 'recommended' | 'internal';
export type CaseAuditAction =
  | 'case_created'
  | 'report_linked'
  | 'assignment_created'
  | 'assignment_revoked'
  | 'task_created'
  | 'task_completed'
  | 'task_marked_not_applicable'
  | 'evidence_download_authorised'
  | 'audit_exported'
  | 'status_changed'
  | 'communication_recorded'
  | 'communication_corrected';

export interface ProfessionalCaseSummary {
  id: string;
  status: CaseStatus;
  modality: CaseModality;
  createdAt: string;
  organisationName: string;
  assignmentRole: CaseAssignmentRole;
  pendingTasks: number;
  overdueTasks: number;
  nextDueAt: string | null;
}

export interface ProfessionalCasePage {
  items: ProfessionalCaseSummary[];
  pagination: { limit: number; nextCursor: string | null };
}

export interface ProfessionalCaseFilters {
  view?: CaseOperationalView;
  status?: CaseStatus | '';
  modality?: CaseModality | '';
  reference?: string;
  /** Narrows to cases this professional is also assigned to. */
  responsible?: string;
  /** Narrows to cases holding at least one pending task. */
  pending?: 'true' | '';
  /** Matches the caller's own communication notes only. */
  note?: string;
  cursor?: string;
  limit?: number;
}

export interface ProfessionalCaseDetail extends ProfessionalCaseSummary {
  permissions: { manage: boolean; manageAssignments: boolean; export: boolean; viewAudit: boolean };
  people: Array<{ id: string; name: string; role: string; state: 'active' | 'removed' }>;
  assignments: Array<{
    id: string;
    professional: { id: string; name: string };
    role: CaseAssignmentRole;
    assignedAt: string;
  }>;
  tasks: Array<{
    id: string;
    title: string;
    stage: string;
    kind: 'internal_action' | 'external_communication';
    status: CaseTaskStatus;
    dueAt: string;
    overdue: boolean;
    owner: { id: string; name: string };
    source: {
      id: string;
      title: string;
      version: string;
      authority: WorkflowSourceAuthority;
      territory: string;
      uri: string | null;
    };
    resolvedAt: string | null;
    resolvedBy: { id: string; name: string } | null;
    notApplicableReason: string | null;
  }>;
  communications: Array<{
    id: string;
    recipient: CaseCommunicationRecipient;
    channel: CaseCommunicationChannel;
    status: CaseCommunicationStatus;
    occurredAt: string;
    note: string;
    responsible: { id: string; name: string };
    createdBy: { id: string; name: string };
    createdAt: string;
    supersedesId: string | null;
  }>;
  sourceReport: {
    id: string;
    publicReference: string;
    decision: { outcome: 'link_to_case'; reason: string; decidedAt: string } | null;
  } | null;
  assignableProfessionals: Array<{ id: string; name: string }>;
  evidence: Array<{
    id: string;
    description: string | null;
    mediaType: string;
    byteSize: number;
    createdAt: string;
  }>;
  timeline: Array<{ type: string; occurredAt: string }>;
}

export interface ProfessionalCaseTaskPlanningTemplate {
  id: string;
  title: string;
  stage: string;
  kind: 'internal_action' | 'external_communication';
  source: {
    title: string;
    version: string;
    authority: WorkflowSourceAuthority;
    territory: string;
    uri: string | null;
  };
}

export interface ProfessionalCaseAuditEvent {
  id: string;
  action: CaseAuditAction;
  target:
    | 'case'
    | 'triage_decision'
    | 'assignment'
    | 'task'
    | 'attachment'
    | 'audit_trail'
    | 'communication';
  actorName: string;
  occurredAt: string;
}

@Injectable({ providedIn: 'root' })
export class ProfessionalCasesService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/v1/professional/cases';

  list(filters: ProfessionalCaseFilters = {}): Observable<ProfessionalCasePage> {
    let params = new HttpParams();
    for (const [key, value] of Object.entries(filters)) {
      if (value !== undefined && value !== '') {
        params = params.set(key, String(value));
      }
    }

    return this.http.get<ProfessionalCasePage>(this.endpoint, { params });
  }

  operationalSummary(): Observable<{ assigned: number; overdue: number; upcoming: number }> {
    return this.http.get<{ assigned: number; overdue: number; upcoming: number }>(
      `${this.endpoint}/operational-summary`,
    );
  }

  detail(id: string): Observable<ProfessionalCaseDetail> {
    return this.http.get<ProfessionalCaseDetail>(`${this.endpoint}/${encodeURIComponent(id)}`);
  }

  transitionLifecycle(
    id: string,
    payload: { status: 'active' | 'closed'; reason: string; evidence: string },
  ): Observable<ProfessionalCaseDetail> {
    return this.http.post<ProfessionalCaseDetail>(
      `${this.endpoint}/${encodeURIComponent(id)}/lifecycle`,
      payload,
    );
  }

  addPerson(
    id: string,
    payload: { name: string; role: string },
  ): Observable<ProfessionalCaseDetail['people'][number]> {
    return this.http.post<ProfessionalCaseDetail['people'][number]>(
      `${this.endpoint}/${encodeURIComponent(id)}/people`,
      payload,
    );
  }

  correctPerson(
    id: string,
    personId: string,
    payload: { name: string; role: string },
  ): Observable<ProfessionalCaseDetail['people'][number]> {
    return this.http.patch<ProfessionalCaseDetail['people'][number]>(
      `${this.endpoint}/${encodeURIComponent(id)}/people/${encodeURIComponent(personId)}`,
      payload,
    );
  }

  removePerson(id: string, personId: string): Observable<void> {
    return this.http.delete<void>(
      `${this.endpoint}/${encodeURIComponent(id)}/people/${encodeURIComponent(personId)}`,
    );
  }

  createTask(
    id: string,
    payload: {
      ownerId: string;
      templateId: string;
      title: string;
      dueAt: string;
    },
  ): Observable<ProfessionalCaseDetail['tasks'][number]> {
    return this.http.post<ProfessionalCaseDetail['tasks'][number]>(
      `${this.endpoint}/${encodeURIComponent(id)}/tasks`,
      payload,
    );
  }

  taskPlanningCatalogue(id: string): Observable<{ items: ProfessionalCaseTaskPlanningTemplate[] }> {
    return this.http.get<{ items: ProfessionalCaseTaskPlanningTemplate[] }>(
      `${this.endpoint}/${encodeURIComponent(id)}/task-planning-catalogue`,
    );
  }

  completeTask(id: string, taskId: string): Observable<ProfessionalCaseDetail['tasks'][number]> {
    return this.http.post<ProfessionalCaseDetail['tasks'][number]>(
      `${this.endpoint}/${encodeURIComponent(id)}/tasks/${encodeURIComponent(taskId)}/complete`,
      {},
    );
  }

  markTaskNotApplicable(
    id: string,
    taskId: string,
    reason: string,
  ): Observable<ProfessionalCaseDetail['tasks'][number]> {
    return this.http.post<ProfessionalCaseDetail['tasks'][number]>(
      `${this.endpoint}/${encodeURIComponent(id)}/tasks/${encodeURIComponent(taskId)}/not-applicable`,
      { reason },
    );
  }

  recordCommunication(
    id: string,
    payload: {
      responsibleId: string;
      recipient: CaseCommunicationRecipient;
      channel: CaseCommunicationChannel;
      status: CaseCommunicationStatus;
      occurredAt: string;
      note: string;
    },
  ): Observable<ProfessionalCaseDetail['communications'][number]> {
    return this.http.post<ProfessionalCaseDetail['communications'][number]>(
      `${this.endpoint}/${encodeURIComponent(id)}/communications`,
      payload,
    );
  }

  correctCommunication(
    id: string,
    communicationId: string,
    payload: {
      responsibleId: string;
      recipient: CaseCommunicationRecipient;
      channel: CaseCommunicationChannel;
      status: CaseCommunicationStatus;
      occurredAt: string;
      note: string;
    },
  ): Observable<ProfessionalCaseDetail['communications'][number]> {
    return this.http.post<ProfessionalCaseDetail['communications'][number]>(
      `${this.endpoint}/${encodeURIComponent(id)}/communications/${encodeURIComponent(communicationId)}/corrections`,
      payload,
    );
  }

  revokeAssignment(
    id: string,
    assignmentId: string,
    reason: string,
  ): Observable<{ id: string; revoked: boolean }> {
    return this.http.post<{ id: string; revoked: boolean }>(
      `${this.endpoint}/${encodeURIComponent(id)}/assignments/${encodeURIComponent(assignmentId)}/revoke`,
      { reason },
    );
  }

  assignProfessional(
    id: string,
    payload: { professionalId: string; role: CaseAssignmentRole; reason: string },
  ): Observable<ProfessionalCaseDetail['assignments'][number]> {
    return this.http.post<ProfessionalCaseDetail['assignments'][number]>(
      `${this.endpoint}/${encodeURIComponent(id)}/assignments`,
      payload,
    );
  }

  handoverAssignment(
    id: string,
    assignmentId: string,
    payload: { professionalId: string; reason: string },
  ): Observable<ProfessionalCaseDetail['assignments'][number]> {
    return this.http.post<ProfessionalCaseDetail['assignments'][number]>(
      `${this.endpoint}/${encodeURIComponent(id)}/assignments/${encodeURIComponent(assignmentId)}/handover`,
      payload,
    );
  }

  changeAssignmentRole(
    id: string,
    assignmentId: string,
    payload: { role: 'contributor' | 'observer'; reason: string },
  ): Observable<ProfessionalCaseDetail['assignments'][number]> {
    return this.http.post<ProfessionalCaseDetail['assignments'][number]>(
      `${this.endpoint}/${encodeURIComponent(id)}/assignments/${encodeURIComponent(assignmentId)}/role`,
      payload,
    );
  }

  auditEvents(id: string): Observable<{ items: ProfessionalCaseAuditEvent[] }> {
    return this.http.get<{ items: ProfessionalCaseAuditEvent[] }>(
      `${this.endpoint}/${encodeURIComponent(id)}/audit-events`,
    );
  }

  evidenceDownloadUrl(caseId: string, evidenceId: string): string {
    return `${this.endpoint}/${encodeURIComponent(caseId)}/evidence/${encodeURIComponent(evidenceId)}/download`;
  }

  auditExportUrl(caseId: string): string {
    return `${this.endpoint}/${encodeURIComponent(caseId)}/audit-events/export`;
  }

  caseRecordExportUrl(caseId: string): string {
    return `${this.endpoint}/${encodeURIComponent(caseId)}/export`;
  }

  operationalOverviewExportUrl(): string {
    return `${this.endpoint}/operational-overview/export`;
  }
}
