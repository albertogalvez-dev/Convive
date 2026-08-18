import { HttpErrorResponse } from '@angular/common/http';
import { DatePipe, DecimalPipe, registerLocaleData } from '@angular/common';
import localeEs from '@angular/common/locales/es';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TranslocoService } from '@jsverse/transloco';

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
  CaseCommunicationChannel,
  CaseCommunicationRecipient,
  CaseCommunicationStatus,
  ProfessionalCaseAuditEvent,
  ProfessionalCaseDetail,
  ProfessionalCasesService,
  ProfessionalCaseTaskPlanningTemplate,
} from './professional-cases.service';

registerLocaleData(localeEs);

@Component({
  selector: 'app-professional-case-detail',
  standalone: true,
  imports: [DatePipe, DecimalPipe, FormsModule, RouterLink],
  templateUrl: './professional-case-detail.html',
  styleUrl: './professional-case-detail.scss',
})
export class ProfessionalCaseDetailPage implements OnInit {
  private readonly cases = inject(ProfessionalCasesService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly transloco = inject(TranslocoService);

  /**
   * Shows a protocol step in the reader's own language where a translation
   * exists, and in the Spanish source where it does not.
   *
   * The fallback is deliberate rather than incidental. Transloco returns the
   * key itself when a translation is missing, and a professional reading
   * `caseWorkflow.template.es_md.assessment` in the middle of a bullying case
   * learns nothing. Falling back to the source wording means a half-finished
   * locale degrades to correct Spanish instead of to noise.
   */
  protected resolveTemplateTitle(template: ProfessionalCaseTaskPlanningTemplate): string {
    const translated = this.transloco.translate(template.titleKey);

    return translated === template.titleKey ? template.title : translated;
  }

  protected readonly detail = signal<ProfessionalCaseDetail | null>(null);
  protected readonly loading = signal(true);
  protected readonly unavailable = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly auditEvents = signal<ProfessionalCaseAuditEvent[]>([]);
  protected readonly auditLoading = signal(false);
  protected readonly auditUnavailable = signal(false);
  protected readonly taskMessage = signal<string | null>(null);
  protected readonly taskError = signal<string | null>(null);
  protected readonly taskSaving = signal(false);
  protected readonly taskTemplates = signal<ProfessionalCaseTaskPlanningTemplate[]>([]);
  protected readonly communicationMessage = signal<string | null>(null);
  protected readonly communicationError = signal<string | null>(null);
  protected readonly communicationSaving = signal(false);
  protected readonly newCommunication = signal<{
    responsibleId: string;
    recipient: CaseCommunicationRecipient;
    channel: CaseCommunicationChannel;
    status: CaseCommunicationStatus;
    occurredAt: string;
    note: string;
  }>({
    responsibleId: '',
    recipient: 'family',
    channel: 'in_person',
    status: 'planned',
    occurredAt: '',
    note: '',
  });
  protected correctingCommunicationId: string | null = null;
  protected readonly newTask = signal({
    ownerId: '',
    templateId: '',
    title: '',
    dueAt: '',
  });
  protected notApplicableTaskId: string | null = null;
  protected notApplicableReason = '';
  protected readonly assignmentMessage = signal<string | null>(null);
  protected readonly assignmentError = signal<string | null>(null);
  protected readonly assignmentSaving = signal(false);
  protected assignmentProfessionalId = '';
  protected assignmentRole: 'lead' | 'contributor' | 'observer' = 'contributor';
  protected assignmentReason = '';
  protected revokeAssignmentId: string | null = null;
  protected revokeReason = '';
  protected handoverAssignmentId: string | null = null;
  protected handoverProfessionalId = '';
  protected handoverReason = '';
  protected changeAssignmentId: string | null = null;
  protected changeAssignmentRole: 'contributor' | 'observer' = 'contributor';
  protected changeAssignmentReason = '';
  protected readonly peopleMessage = signal<string | null>(null);
  protected readonly peopleError = signal<string | null>(null);
  protected readonly peopleSaving = signal(false);
  protected readonly newPerson = { name: '', role: 'affected' };
  protected correctingPersonId: string | null = null;
  protected correctingPerson = { name: '', role: 'affected' };
  protected readonly lifecycleMessage = signal<string | null>(null);
  protected readonly lifecycleError = signal<string | null>(null);
  protected readonly lifecycleSaving = signal(false);
  protected lifecycleStatus: 'active' | 'closed' = 'active';
  protected lifecycleReason = '';
  protected lifecycleEvidence = '';
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

  protected transitionLifecycle(item: ProfessionalCaseDetail): void {
    this.lifecycleSaving.set(true);
    this.lifecycleError.set(null);
    this.cases
      .transitionLifecycle(item.id, {
        status: this.lifecycleStatus,
        reason: this.lifecycleReason,
        evidence: this.lifecycleEvidence,
      })
      .subscribe({
        next: (detail) => {
          this.detail.set(detail);
          this.lifecycleMessage.set(
            'El estado operativo se ha actualizado. No determina hechos ni confirma comunicaciones externas.',
          );
          this.lifecycleReason = '';
          this.lifecycleEvidence = '';
          this.lifecycleSaving.set(false);
        },
        error: () => {
          this.lifecycleSaving.set(false);
          this.lifecycleError.set(
            'No se puede registrar esta transición. Revisa el estado y el registro operativo.',
          );
        },
      });
  }

  protected evidenceUrl(caseId: string, evidenceId: string): string {
    return this.cases.evidenceDownloadUrl(caseId, evidenceId);
  }

  protected auditExportUrl(caseId: string): string {
    return this.cases.auditExportUrl(caseId);
  }

  /** The approved closed set, mirroring the API enum. */
  protected readonly documentTemplates = [
    { id: 'action_record', title: 'Registro de actuación' },
    { id: 'follow_up_plan', title: 'Plan de seguimiento' },
    { id: 'coordination_note', title: 'Nota de coordinación' },
    { id: 'family_communication', title: 'Comunicación a familia' },
    { id: 'protocol_review_checklist', title: 'Checklist de revisión de protocolo' },
    { id: 'closure_report', title: 'Informe de cierre ficticio' },
  ] as const;

  protected caseDocumentUrl(caseId: string, template: string): string {
    return this.cases.caseDocumentUrl(caseId, template);
  }

  protected caseRecordExportUrl(caseId: string): string {
    return this.cases.caseRecordExportUrl(caseId);
  }

  protected startTask(item: ProfessionalCaseDetail): void {
    this.newTask.set({
      ownerId: item.assignments[0]?.professional.id ?? '',
      templateId: '',
      title: '',
      dueAt: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().slice(0, 16),
    });
    this.taskError.set(null);
    this.taskMessage.set(null);
    this.cases.taskPlanningCatalogue(item.id).subscribe({
      next: ({ items }) => {
        this.newTask.update((task) => ({
          ...task,
          templateId: items[0]?.id ?? '',
          title: items[0]?.title ?? '',
        }));
        this.taskTemplates.set(items);
      },
      error: () => this.taskError.set('No se puede cargar el catÃ¡logo de tareas revisadas.'),
    });
  }

  protected selectTaskTemplate(templateId: string): void {
    const template = this.taskTemplates().find((candidate) => candidate.id === templateId);
    this.newTask.update((task) => ({ ...task, templateId, title: template?.title ?? task.title }));
  }

  protected selectedTaskTemplate(): ProfessionalCaseTaskPlanningTemplate | null {
    return (
      this.taskTemplates().find((template) => template.id === this.newTask().templateId) ?? null
    );
  }

  protected updateTaskTitle(title: string): void {
    this.newTask.update((task) => ({ ...task, title }));
  }

  protected updateTaskOwner(ownerId: string): void {
    this.newTask.update((task) => ({ ...task, ownerId }));
  }

  protected updateTaskDueAt(dueAt: string): void {
    this.newTask.update((task) => ({ ...task, dueAt }));
  }

  protected createTask(item: ProfessionalCaseDetail): void {
    this.taskSaving.set(true);
    this.taskError.set(null);
    this.cases
      .createTask(item.id, {
        ...this.newTask(),
        dueAt: new Date(this.newTask().dueAt).toISOString(),
      })
      .subscribe({
        next: () => {
          this.taskMessage.set('La tarea se ha creado. No confirma ninguna comunicación externa.');
          this.taskSaving.set(false);
          this.load();
        },
        error: () => {
          this.taskSaving.set(false);
          this.taskError.set(
            'No hemos podido crear la tarea. Revisa los datos e inténtalo de nuevo.',
          );
        },
      });
  }

  protected startCommunication(item: ProfessionalCaseDetail): void {
    this.correctingCommunicationId = null;
    this.newCommunication.set({
      responsibleId: item.assignments[0]?.professional.id ?? '',
      recipient: 'family',
      channel: 'in_person',
      status: 'planned',
      occurredAt: new Date().toISOString().slice(0, 16),
      note: '',
    });
    this.communicationMessage.set(null);
    this.communicationError.set(null);
  }

  protected startCommunicationCorrection(
    communication: ProfessionalCaseDetail['communications'][number],
  ): void {
    this.correctingCommunicationId = communication.id;
    this.newCommunication.set({
      responsibleId: communication.responsible.id,
      recipient: communication.recipient,
      channel: communication.channel,
      status: communication.status,
      occurredAt: new Date(communication.occurredAt).toISOString().slice(0, 16),
      note: communication.note,
    });
    this.communicationMessage.set(null);
    this.communicationError.set(null);
  }

  protected updateCommunication(field: string, value: string): void {
    this.newCommunication.update((communication) => ({ ...communication, [field]: value }));
  }

  protected recordCommunication(item: ProfessionalCaseDetail): void {
    this.communicationSaving.set(true);
    this.communicationError.set(null);
    const communication = this.newCommunication();
    const request =
      this.correctingCommunicationId === null
        ? this.cases.recordCommunication(item.id, {
            ...communication,
            occurredAt: new Date(communication.occurredAt).toISOString(),
          })
        : this.cases.correctCommunication(item.id, this.correctingCommunicationId, {
            ...communication,
            occurredAt: new Date(communication.occurredAt).toISOString(),
          });
    request.subscribe({
      next: () => {
        this.communicationSaving.set(false);
        this.correctingCommunicationId = null;
        this.communicationMessage.set(
          'El registro se ha añadido. No acredita entrega, recepción ni notificación legal.',
        );
        this.load();
      },
      error: () => {
        this.communicationSaving.set(false);
        this.communicationError.set(
          'No se puede registrar la comunicación. Revisa los datos mínimos.',
        );
      },
    });
  }

  protected completeTask(item: ProfessionalCaseDetail, taskId: string): void {
    this.taskSaving.set(true);
    this.taskError.set(null);
    this.cases.completeTask(item.id, taskId).subscribe({
      next: () => {
        this.taskMessage.set('La tarea se ha marcado como completada.');
        this.taskSaving.set(false);
        this.load();
      },
      error: () => {
        this.taskSaving.set(false);
        this.taskError.set('La tarea ya no se puede actualizar o no tienes permiso.');
      },
    });
  }

  protected openNotApplicable(taskId: string): void {
    this.notApplicableTaskId = taskId;
    this.notApplicableReason = '';
  }

  protected openRevokeAssignment(id: string): void {
    this.revokeAssignmentId = id;
    this.revokeReason = '';
    this.assignmentError.set(null);
  }

  protected openChangeAssignmentRole(id: string, role: 'contributor' | 'observer'): void {
    this.changeAssignmentId = id;
    this.changeAssignmentRole = role === 'contributor' ? 'observer' : 'contributor';
    this.changeAssignmentReason = '';
    this.assignmentError.set(null);
  }

  protected updateAssignmentRole(item: ProfessionalCaseDetail): void {
    if (this.changeAssignmentId === null) return;
    this.assignmentSaving.set(true);
    this.cases
      .changeAssignmentRole(item.id, this.changeAssignmentId, {
        role: this.changeAssignmentRole,
        reason: this.changeAssignmentReason,
      })
      .subscribe({
        next: () => {
          this.assignmentMessage.set(
            'El nivel de acceso se ha actualizado con un motivo registrado.',
          );
          this.assignmentSaving.set(false);
          this.changeAssignmentId = null;
          this.load();
        },
        error: () => {
          this.assignmentError.set('No se puede actualizar este nivel de acceso.');
          this.assignmentSaving.set(false);
        },
      });
  }

  protected openHandover(item: ProfessionalCaseDetail, assignmentId: string): void {
    this.handoverAssignmentId = assignmentId;
    this.handoverProfessionalId =
      item.assignableProfessionals.find(
        (candidate) =>
          !item.assignments.some((assignment) => assignment.professional.id === candidate.id),
      )?.id ?? '';
    this.handoverReason = '';
    this.assignmentError.set(null);
  }

  protected handoverAssignment(item: ProfessionalCaseDetail): void {
    if (this.handoverAssignmentId === null) return;
    this.assignmentSaving.set(true);
    this.cases
      .handoverAssignment(item.id, this.handoverAssignmentId, {
        professionalId: this.handoverProfessionalId,
        reason: this.handoverReason,
      })
      .subscribe({
        next: () => {
          this.assignmentMessage.set('El traspaso de responsabilidad se ha registrado.');
          this.assignmentSaving.set(false);
          this.handoverAssignmentId = null;
          this.load();
        },
        error: () => {
          this.assignmentError.set(
            'No se puede completar el traspaso. Revisa el profesional y el motivo.',
          );
          this.assignmentSaving.set(false);
        },
      });
  }

  protected openAssignment(item: ProfessionalCaseDetail): void {
    this.assignmentProfessionalId =
      item.assignableProfessionals.find(
        (candidate) =>
          !item.assignments.some((assignment) => assignment.professional.id === candidate.id),
      )?.id ?? '';
    this.assignmentReason = '';
    this.assignmentError.set(null);
  }

  protected assignProfessional(item: ProfessionalCaseDetail): void {
    this.assignmentSaving.set(true);
    this.cases
      .assignProfessional(item.id, {
        professionalId: this.assignmentProfessionalId,
        role: this.assignmentRole,
        reason: this.assignmentReason,
      })
      .subscribe({
        next: () => {
          this.assignmentMessage.set('La asignación se ha registrado de forma explícita.');
          this.assignmentSaving.set(false);
          this.load();
        },
        error: () => {
          this.assignmentError.set(
            'No se puede crear esta asignación. Revisa el profesional y el motivo.',
          );
          this.assignmentSaving.set(false);
        },
      });
  }

  protected revokeAssignment(item: ProfessionalCaseDetail): void {
    if (this.revokeAssignmentId === null) {
      return;
    }
    this.assignmentSaving.set(true);
    this.cases.revokeAssignment(item.id, this.revokeAssignmentId, this.revokeReason).subscribe({
      next: () => {
        this.assignmentMessage.set('El acceso se ha retirado con un motivo registrado.');
        this.assignmentSaving.set(false);
        this.revokeAssignmentId = null;
        this.load();
      },
      error: () => {
        this.assignmentError.set(
          'No se puede retirar el último responsable sin un traspaso explícito.',
        );
        this.assignmentSaving.set(false);
      },
    });
  }

  protected markNotApplicable(item: ProfessionalCaseDetail): void {
    if (this.notApplicableTaskId === null) {
      return;
    }
    this.taskSaving.set(true);
    this.taskError.set(null);
    this.cases
      .markTaskNotApplicable(item.id, this.notApplicableTaskId, this.notApplicableReason)
      .subscribe({
        next: () => {
          this.notApplicableTaskId = null;
          this.taskMessage.set('La tarea se ha marcado como no aplicable con su motivo.');
          this.taskSaving.set(false);
          this.load();
        },
        error: () => {
          this.taskSaving.set(false);
          this.taskError.set('Indica un motivo válido o vuelve a intentarlo.');
        },
      });
  }

  protected addPerson(item: ProfessionalCaseDetail): void {
    this.peopleSaving.set(true);
    this.peopleError.set(null);
    this.cases.addPerson(item.id, this.newPerson).subscribe({
      next: () => {
        this.newPerson.name = '';
        this.newPerson.role = 'affected';
        this.peopleMessage.set('La persona vinculada se ha registrado con datos mínimos.');
        this.peopleSaving.set(false);
        this.load();
      },
      error: () => {
        this.peopleSaving.set(false);
        this.peopleError.set(
          'No se puede registrar esta persona. Revisa el nombre y la vinculación.',
        );
      },
    });
  }

  protected openPersonCorrection(person: ProfessionalCaseDetail['people'][number]): void {
    this.correctingPersonId = person.id;
    this.correctingPerson = { name: person.name, role: person.role };
    this.peopleError.set(null);
  }

  protected correctPerson(item: ProfessionalCaseDetail): void {
    if (this.correctingPersonId === null) return;
    this.peopleSaving.set(true);
    this.peopleError.set(null);
    this.cases.correctPerson(item.id, this.correctingPersonId, this.correctingPerson).subscribe({
      next: () => {
        this.correctingPersonId = null;
        this.peopleMessage.set('La vinculación se ha corregido y queda registrada.');
        this.peopleSaving.set(false);
        this.load();
      },
      error: () => {
        this.peopleSaving.set(false);
        this.peopleError.set('No se puede corregir esta vinculación.');
      },
    });
  }

  protected removePerson(item: ProfessionalCaseDetail, personId: string): void {
    this.peopleSaving.set(true);
    this.peopleError.set(null);
    this.cases.removePerson(item.id, personId).subscribe({
      next: () => {
        this.peopleMessage.set('La retirada se ha registrado; no se ha borrado el historial.');
        this.peopleSaving.set(false);
        this.load();
      },
      error: () => {
        this.peopleSaving.set(false);
        this.peopleError.set('No se puede retirar esta vinculación.');
      },
    });
  }

  protected auditActionLabel(action: CaseAuditAction): string {
    return (
      {
        case_created: 'Caso creado',
        report_linked: 'Comunicación vinculada',
        assignment_created: 'Acceso asignado',
        assignment_changed: 'Acceso modificado',
        assignment_revoked: 'Acceso retirado',
        task_created: 'Tarea creada',
        task_completed: 'Tarea completada',
        task_marked_not_applicable: 'Tarea marcada como no aplicable',
        evidence_download_authorised: 'Descarga de evidencia autorizada',
        audit_exported: 'Registro exportado',
        person_added: 'Persona vinculada',
        person_corrected: 'Vinculación corregida',
        person_removed: 'Vinculación retirada',
        status_changed: 'Estado del caso actualizado',
        communication_recorded: 'Comunicación registrada',
        communication_corrected: 'Comunicación corregida',
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
