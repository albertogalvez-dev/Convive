import { DatePipe, registerLocaleData } from '@angular/common';
import localeEs from '@angular/common/locales/es';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, computed, ElementRef, inject, OnInit, signal, viewChild } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import { reportContextLabel } from './report-context';
import {
  ProfessionalReportAttachment,
  ProfessionalReportDetail,
  ProfessionalReportsService,
} from './professional-reports.service';
import { PrivateAttachmentPreview } from '../report-attachments/private-attachment-preview';

registerLocaleData(localeEs);

@Component({
  selector: 'app-professional-detail',
  standalone: true,
  imports: [DatePipe, PrivateAttachmentPreview, ReactiveFormsModule, RouterLink],
  templateUrl: './professional-detail.html',
  styleUrl: './professional-detail.scss',
})
export class ProfessionalDetail implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly reports = inject(ProfessionalReportsService);
  private readonly sessions = inject(ProfessionalSessionService);
  private readonly formBuilder = inject(FormBuilder);
  private readonly id = this.route.snapshot.paramMap.get('id') ?? '';

  protected readonly report = signal<ProfessionalReportDetail | null>(null);
  protected readonly isDemonstration = computed(() => this.sessions.demonstrationRole() !== null);
  protected readonly attachments = signal<ProfessionalReportAttachment[]>([]);
  protected readonly loading = signal(true);
  protected readonly submitting = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly reviewError = signal<string | null>(null);
  protected readonly responseSubmitting = signal(false);
  protected readonly responseError = signal<string | null>(null);
  protected readonly responseConfirmation = signal<string | null>(null);
  protected readonly responseField = viewChild<ElementRef<HTMLTextAreaElement>>('responseField');
  protected readonly contextLabel = reportContextLabel;
  protected readonly concernCategoryLabel = (category: string): string =>
    ({
      peer_interaction: 'Interacción entre iguales',
      digital_interaction: 'Entorno digital',
      exclusion_or_isolation: 'Exclusión o aislamiento',
      harmful_language_or_conduct: 'Lenguaje o conducta dañina',
      safety_or_wellbeing_concern: 'Bienestar o posible seguridad',
      other: 'Otro',
      unknown: 'No determinado',
    })[category] ?? 'No determinado';
  protected readonly reviewForm = this.formBuilder.nonNullable.group({
    reason: ['', [Validators.required, Validators.minLength(10), Validators.maxLength(1000)]],
    professionalConcernCategory: ['unknown'],
    professionalRecurrence: ['unknown'],
    professionalAttentionCue: ['unknown'],
  });
  protected readonly responseForm = this.formBuilder.nonNullable.group({
    content: ['', [Validators.required, Validators.maxLength(2000)]],
  });
  protected previewAttachment = (attachmentId: string) =>
    this.reports.previewAttachment(this.id, attachmentId);

  ngOnInit(): void {
    this.load();
  }

  protected review(): void {
    this.reviewForm.markAllAsTouched();
    if (this.reviewForm.invalid || this.submitting() || !this.report()) return;

    if (this.isDemonstration()) {
      this.report.update((report) =>
        report
          ? {
              ...report,
              status: 'reviewed',
              review: {
                reason: this.reviewForm.controls.reason.value.trim(),
                reviewedAt: '2026-08-25T09:15:00.000+00:00',
                professionalTaxonomy: {
                  version: 'demo',
                  concernCategory:
                    this.reviewForm.controls.professionalConcernCategory.value === 'unknown'
                      ? null
                      : this.reviewForm.controls.professionalConcernCategory.value,
                  recurrence:
                    this.reviewForm.controls.professionalRecurrence.value === 'unknown'
                      ? null
                      : this.reviewForm.controls.professionalRecurrence.value,
                  attentionCue:
                    this.reviewForm.controls.professionalAttentionCue.value === 'unknown'
                      ? null
                      : this.reviewForm.controls.professionalAttentionCue.value,
                },
              },
            }
          : null,
      );
      return;
    }

    this.submitting.set(true);
    this.reviewError.set(null);
    this.reports
      .review(this.id, {
        reason: this.reviewForm.controls.reason.value.trim(),
        professionalConcernCategory: this.reviewForm.controls.professionalConcernCategory.value,
        professionalRecurrence: this.reviewForm.controls.professionalRecurrence.value,
        professionalAttentionCue: this.reviewForm.controls.professionalAttentionCue.value,
      })
      .subscribe({
        next: ({ review }) => {
          this.report.update((report) =>
            report ? { ...report, status: 'reviewed', review } : null,
          );
          this.submitting.set(false);
        },
        error: (error: unknown) => {
          this.submitting.set(false);
          if (error instanceof HttpErrorResponse && error.status === 401) {
            void this.router.navigate(['/profesionales/acceso']);
            return;
          }
          this.reviewError.set(
            error instanceof HttpErrorResponse && error.status === 409
              ? 'Esta comunicaci\u00f3n ya ha sido revisada.'
              : 'No hemos podido guardar la revisi\u00f3n.',
          );
        },
      });
  }

  protected respond(): void {
    this.responseForm.markAllAsTouched();
    const content = this.responseForm.controls.content.value.trim();
    if (!content || this.responseForm.invalid || this.responseSubmitting() || !this.report())
      return;

    if (this.isDemonstration()) {
      this.report.update((report) =>
        report
          ? {
              ...report,
              followUpEntries: [
                ...report.followUpEntries,
                {
                  authorType: 'professional',
                  content,
                  createdAt: '2026-08-25T09:20:00.000+00:00',
                },
              ],
            }
          : null,
      );
      this.responseForm.reset();
      this.responseConfirmation.set(
        'Respuesta preparada. Se reinicia al salir de la demostración.',
      );
      queueMicrotask(() => this.responseField()?.nativeElement.focus());
      return;
    }

    this.responseSubmitting.set(true);
    this.responseError.set(null);
    this.responseConfirmation.set(null);
    this.reports.respond(this.id, content).subscribe({
      next: (entry) => {
        this.report.update((report) =>
          report ? { ...report, followUpEntries: [...report.followUpEntries, entry] } : null,
        );
        this.responseForm.reset();
        this.responseSubmitting.set(false);
        this.responseConfirmation.set('Respuesta enviada.');
        queueMicrotask(() => this.responseField()?.nativeElement.focus());
      },
      error: (error: unknown) => {
        this.responseSubmitting.set(false);
        if (error instanceof HttpErrorResponse && error.status === 401) {
          void this.router.navigate(['/profesionales/acceso']);
          return;
        }
        this.responseError.set(
          error instanceof HttpErrorResponse && error.status === 409
            ? 'Esta conversaci\u00f3n ya no admite m\u00e1s mensajes.'
            : 'No hemos podido enviar la respuesta.',
        );
      },
    });
  }

  protected retry(): void {
    this.load();
  }

  private load(): void {
    this.loading.set(true);
    this.errorMessage.set(null);
    this.reports.detail(this.id).subscribe({
      next: (report) => {
        this.report.set(report);
        this.loading.set(false);
        this.loadAttachments();
      },
      error: (error: unknown) => {
        this.loading.set(false);
        if (error instanceof HttpErrorResponse && error.status === 401) {
          void this.router.navigate(['/profesionales/acceso']);
          return;
        }
        this.errorMessage.set(
          error instanceof HttpErrorResponse && error.status === 404
            ? 'Esta comunicaci\u00f3n no est\u00e1 disponible.'
            : 'No hemos podido cargar la comunicaci\u00f3n.',
        );
      },
    });
  }

  protected attachmentAccessRejected(): void {
    void this.router.navigate(['/profesionales/acceso']);
  }

  private loadAttachments(): void {
    this.reports.attachments(this.id).subscribe({
      next: ({ items }) => this.attachments.set(items),
      error: (error: unknown) => {
        if (error instanceof HttpErrorResponse && error.status === 401) {
          void this.router.navigate(['/profesionales/acceso']);
        }
      },
    });
  }
}
