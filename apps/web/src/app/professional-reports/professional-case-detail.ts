import { HttpErrorResponse } from '@angular/common/http';
import { DatePipe, DecimalPipe, registerLocaleData } from '@angular/common';
import localeEs from '@angular/common/locales/es';
import { Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import {
  assignmentRoleLabel,
  caseModalityLabel,
  caseStatusLabel,
  personRoleLabel,
  sourceAuthorityLabel,
  stageLabel,
  taskStatusLabel,
} from './case-labels';
import {
  CaseAuditAction,
  ProfessionalCaseAuditEvent,
  ProfessionalCaseDetail,
  ProfessionalCasesService,
} from './professional-cases.service';

registerLocaleData(localeEs);

@Component({
  selector: 'app-professional-case-detail',
  standalone: true,
  imports: [DatePipe, DecimalPipe, RouterLink],
  templateUrl: './professional-case-detail.html',
  styleUrl: './professional-case-detail.scss',
})
export class ProfessionalCaseDetailPage implements OnInit {
  private readonly cases = inject(ProfessionalCasesService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected readonly detail = signal<ProfessionalCaseDetail | null>(null);
  protected readonly loading = signal(true);
  protected readonly unavailable = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly auditEvents = signal<ProfessionalCaseAuditEvent[]>([]);
  protected readonly auditLoading = signal(false);
  protected readonly auditUnavailable = signal(false);
  protected readonly caseStatusLabel = caseStatusLabel;
  protected readonly caseModalityLabel = caseModalityLabel;
  protected readonly assignmentRoleLabel = assignmentRoleLabel;
  protected readonly personRoleLabel = personRoleLabel;
  protected readonly sourceAuthorityLabel = sourceAuthorityLabel;
  protected readonly stageLabel = stageLabel;
  protected readonly taskStatusLabel = taskStatusLabel;

  ngOnInit(): void {
    this.load();
  }

  protected retry(): void {
    this.load();
  }

  protected evidenceUrl(caseId: string, evidenceId: string): string {
    return this.cases.evidenceDownloadUrl(caseId, evidenceId);
  }

  protected auditExportUrl(caseId: string): string {
    return this.cases.auditExportUrl(caseId);
  }

  protected caseRecordExportUrl(caseId: string): string {
    return this.cases.caseRecordExportUrl(caseId);
  }

  protected auditActionLabel(action: CaseAuditAction): string {
    return (
      {
        case_created: 'Caso creado',
        report_linked: 'Comunicación vinculada',
        assignment_created: 'Acceso asignado',
        assignment_revoked: 'Acceso retirado',
        task_created: 'Tarea creada',
        task_completed: 'Tarea completada',
        task_marked_not_applicable: 'Tarea marcada como no aplicable',
        evidence_download_authorised: 'Descarga de evidencia autorizada',
        audit_exported: 'Registro exportado',
      }[action] ?? 'Acción registrada'
    );
  }

  protected timelineLabel(type: string): string {
    return (
      {
        case_created: 'Caso creado',
        assignment_added: 'Profesional asignado',
        task_created: 'Tarea creada',
        task_resolved: 'Tarea resuelta',
      }[type] ?? 'Actividad registrada'
    );
  }

  private load(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (!id) {
      this.unavailable.set(true);
      this.loading.set(false);
      return;
    }

    this.loading.set(true);
    this.unavailable.set(false);
    this.errorMessage.set(null);
    this.auditEvents.set([]);
    this.auditLoading.set(false);
    this.auditUnavailable.set(false);
    this.cases.detail(id).subscribe({
      next: (detail) => {
        this.detail.set(detail);
        this.loading.set(false);
        if (detail.permissions.viewAudit) {
          this.loadAudit(id);
        }
      },
      error: (error: unknown) => {
        this.loading.set(false);
        if (error instanceof HttpErrorResponse && error.status === 401) {
          void this.router.navigate(['/profesionales/acceso']);
          return;
        }
        if (error instanceof HttpErrorResponse && error.status === 404) {
          this.unavailable.set(true);
          return;
        }
        this.errorMessage.set('No hemos podido cargar el caso.');
      },
    });
  }

  private loadAudit(id: string): void {
    this.auditLoading.set(true);
    this.auditUnavailable.set(false);
    this.cases.auditEvents(id).subscribe({
      next: ({ items }) => {
        this.auditEvents.set(items);
        this.auditLoading.set(false);
      },
      error: (error: unknown) => {
        this.auditLoading.set(false);
        if (error instanceof HttpErrorResponse && error.status === 401) {
          void this.router.navigate(['/profesionales/acceso']);
          return;
        }
        this.auditUnavailable.set(true);
      },
    });
  }
}
