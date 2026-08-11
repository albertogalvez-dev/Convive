import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export type CaseStatus = 'assessment' | 'active' | 'closed';
export type CaseModality = 'in_person' | 'digital' | 'mixed' | 'unknown';
export type CaseAssignmentRole = 'lead' | 'contributor' | 'observer';
export type CaseTaskStatus = 'pending' | 'completed' | 'not_applicable';
export type WorkflowSourceAuthority = 'binding' | 'recommended' | 'internal';

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

export interface ProfessionalCaseDetail extends ProfessionalCaseSummary {
  permissions: { manage: boolean; manageAssignments: boolean };
  people: Array<{ id: string; name: string; role: string }>;
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
  sourceReport: {
    id: string;
    publicReference: string;
    decision: { outcome: 'link_to_case'; reason: string; decidedAt: string } | null;
  } | null;
  evidence: Array<{
    id: string;
    description: string | null;
    mediaType: string;
    byteSize: number;
    createdAt: string;
  }>;
  timeline: Array<{ type: string; occurredAt: string }>;
}

@Injectable({ providedIn: 'root' })
export class ProfessionalCasesService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/v1/professional/cases';

  list(): Observable<{ items: ProfessionalCaseSummary[] }> {
    return this.http.get<{ items: ProfessionalCaseSummary[] }>(this.endpoint);
  }

  detail(id: string): Observable<ProfessionalCaseDetail> {
    return this.http.get<ProfessionalCaseDetail>(`${this.endpoint}/${encodeURIComponent(id)}`);
  }

  evidenceDownloadUrl(caseId: string, evidenceId: string): string {
    return `${this.endpoint}/${encodeURIComponent(caseId)}/evidence/${encodeURIComponent(evidenceId)}/download`;
  }
}
