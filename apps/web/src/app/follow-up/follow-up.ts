import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';

import { ReportEvidence } from '../report-attachments/report-evidence';
import { ReportHeader } from '../reporting/report-header';
import { FollowUpAccess } from './follow-up-access';
import { FollowUpReport } from './follow-up-report';
import { FollowUpEntry, FollowUpService, ReportFollowUpState } from './follow-up.service';

/**
 * Mirrors FollowUpEntryContent::MAX_LENGTH in the backend domain. The
 * backend remains the authority; this only bounds the field and feeds the
 * character counter.
 */
const FOLLOW_UP_CONTENT_MAX_LENGTH = 2000;

type FollowUpJourneyState =
  | { status: 'locked' }
  | { status: 'verifying' }
  | { status: 'loading' }
  | { status: 'open'; report: ReportFollowUpState }
  | { status: 'unavailable' };

@Component({
  selector: 'app-follow-up',
  standalone: true,
  imports: [FollowUpAccess, FollowUpReport, ReactiveFormsModule, ReportEvidence, ReportHeader],
  templateUrl: './follow-up.html',
  styleUrl: './follow-up.scss',
})
export class FollowUp {
  private readonly formBuilder = inject(FormBuilder);
  private readonly followUp = inject(FollowUpService);

  protected readonly maxLength = FOLLOW_UP_CONTENT_MAX_LENGTH;
  protected readonly state = signal<FollowUpJourneyState>({ status: 'locked' });
  protected readonly accessError = signal<string | null>(null);
  protected readonly accessNotice = signal<string | null>(null);
  protected readonly entrySubmitting = signal(false);
  protected readonly entryError = signal<string | null>(null);
  protected readonly entryConfirmation = signal<string | null>(null);
  protected readonly closing = signal(false);
  protected readonly closeError = signal<string | null>(null);

  protected readonly entryForm = this.formBuilder.nonNullable.group({
    content: [
      '',
      [
        Validators.required,
        Validators.pattern(/\S/),
        Validators.maxLength(FOLLOW_UP_CONTENT_MAX_LENGTH),
      ],
    ],
  });

  protected unlock(accessSecret: string): void {
    this.accessError.set(null);
    this.accessNotice.set(null);
    this.state.set({ status: 'verifying' });

    this.followUp.verifyReportAccess(accessSecret).subscribe({
      next: () => this.loadReport(),
      error: (error: unknown) => {
        this.state.set({ status: 'locked' });
        this.accessError.set(describeVerificationError(error));
      },
    });
  }

  protected retryLoadingReport(): void {
    this.loadReport();
  }

  protected addFollowUpEntry(): void {
    const control = this.entryForm.controls.content;

    control.markAsTouched();

    if (this.entrySubmitting() || control.invalid) {
      return;
    }

    this.entrySubmitting.set(true);
    this.entryError.set(null);
    this.entryConfirmation.set(null);

    this.followUp.addFollowUpEntry(control.value).subscribe({
      next: (entry) => {
        this.entrySubmitting.set(false);
        this.appendEntry(entry);
        this.entryForm.reset();
        this.entryConfirmation.set('Hemos añadido tu información a la comunicación.');
      },
      error: (error: unknown) => {
        this.entrySubmitting.set(false);

        if (isCapabilityRejected(error)) {
          this.lockAfterExpiry();
          return;
        }

        this.entryError.set(describeEntryError(error));
      },
    });
  }

  protected closeAccess(): void {
    if (this.closing()) {
      return;
    }

    this.closing.set(true);
    this.closeError.set(null);

    this.followUp.revokeReportAccess().subscribe({
      next: () => {
        this.closing.set(false);
        this.reset();
        this.accessNotice.set(
          'Has cerrado el acceso. Para volver a abrir la comunicación necesitarás el secreto otra vez.',
        );
      },
      error: () => {
        this.closing.set(false);
        this.closeError.set('No hemos podido cerrar el acceso ahora. Inténtalo de nuevo.');
      },
    });
  }

  protected attachmentAccessRejected(): void {
    this.lockAfterExpiry();
  }

  private loadReport(): void {
    this.state.set({ status: 'loading' });

    this.followUp.getReportFollowUpState().subscribe({
      next: (report) => this.state.set({ status: 'open', report }),
      error: (error: unknown) => {
        if (isCapabilityRejected(error)) {
          this.lockAfterExpiry();
          return;
        }

        this.state.set({ status: 'unavailable' });
      },
    });
  }

  private appendEntry(entry: FollowUpEntry): void {
    this.state.update((current) =>
      current.status === 'open'
        ? {
            status: 'open',
            report: {
              ...current.report,
              followUpEntries: [...current.report.followUpEntries, entry],
            },
          }
        : current,
    );
  }

  private lockAfterExpiry(): void {
    this.reset();
    this.accessNotice.set(
      'El acceso ha caducado por seguridad. Escribe el secreto otra vez para continuar.',
    );
  }

  private reset(): void {
    this.state.set({ status: 'locked' });
    this.entryForm.reset();
    this.entryError.set(null);
    this.entryConfirmation.set(null);
    this.closeError.set(null);
    this.accessError.set(null);
    this.accessNotice.set(null);
  }
}

function isCapabilityRejected(error: unknown): boolean {
  return error instanceof HttpErrorResponse && error.status === 401;
}

function describeVerificationError(error: unknown): string {
  if (error instanceof HttpErrorResponse) {
    switch (error.status) {
      case 401:
        return 'Este secreto no permite acceder a ninguna comunicación. Comprueba que lo has copiado completo.';
      case 429:
        return 'Has hecho demasiados intentos seguidos. Espera un minuto e inténtalo de nuevo.';
      case 400:
      case 415:
      case 422:
        return 'No hemos podido comprobar el secreto. Revísalo e inténtalo de nuevo.';
    }
  }

  return 'No hemos podido comprobar el acceso ahora. Inténtalo de nuevo más tarde.';
}

function describeEntryError(error: unknown): string {
  if (error instanceof HttpErrorResponse && error.status === 422) {
    return 'Este texto no es válido. Revísalo e inténtalo de nuevo.';
  }

  return 'No hemos podido añadir la información ahora. Inténtalo de nuevo más tarde.';
}
