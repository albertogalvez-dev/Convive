import { HttpErrorResponse } from '@angular/common/http';
import { Component, DestroyRef, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { provideTranslocoScope, TranslocoPipe, TranslocoService } from '@jsverse/transloco';

import { ReportHeader } from './report-header';
import { ReportHelp } from './report-help';
import { ReportResult } from './report-result';
import { ReportSending } from './report-sending';
import { EvidenceDraft, ReportEvidence } from '../report-attachments/report-evidence';
import {
  PublicReportingProfile,
  ReporterAttentionCue,
  ReporterTiming,
  ReporterRecurrence,
  ReportingService,
  ReportSubmissionResponse,
} from './reporting.service';
import { describeSituationContext, SituationContext } from './situation-context';

type ReportingProfileState =
  | { status: 'loading' }
  | { status: 'ready'; profile: PublicReportingProfile }
  | { status: 'fictional-demo'; profile: PublicReportingProfile }
  | { status: 'disabled'; profile: PublicReportingProfile }
  | { status: 'invalid' }
  | { status: 'unavailable' };

type ReporterStep = 1 | 2 | 3 | 4 | 5;

@Component({
  selector: 'app-report-form',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    ReportHeader,
    ReportHelp,
    ReportEvidence,
    ReportResult,
    ReportSending,
    RouterLink,
    TranslocoPipe,
  ],
  providers: [provideTranslocoScope('report-form')],
  templateUrl: './report-form.html',
  styleUrl: './report-form.scss',
})
export class ReportForm {
  private readonly formBuilder = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);
  private readonly reporting = inject(ReportingService);
  private readonly route = inject(ActivatedRoute);
  private readonly transloco = inject(TranslocoService);
  private profileLoadingTimer: ReturnType<typeof setTimeout> | null = null;

  private readonly publicReportingIdentifier =
    this.route.snapshot.paramMap.get('publicReportingIdentifier') ?? '';

  /**
   * Decided by which route was matched, never by anything the reporter fills
   * in. Anything other than the witness route is the first-person journey,
   * unchanged.
   */
  protected readonly isWitness = this.route.snapshot.data?.['reporterPerspective'] === 'witnessed';

  /** Lets someone choose the correct entry without interrupting the main action. */
  protected readonly otherEntryLink = this.isWitness
    ? ['/r', this.route.snapshot.paramMap.get('publicReportingIdentifier') ?? '']
    : ['/r', this.route.snapshot.paramMap.get('publicReportingIdentifier') ?? '', 'testigo'];

  protected readonly otherEntryLabelKey = this.isWitness
    ? 'report-form.witness.toFirstPerson'
    : 'report-form.witness.toWitness';

  // Only the framing changes between the two entries. Every other question --
  // where it happened, how often, when -- reads identically for a witness, so
  // it is deliberately not duplicated.
  protected readonly questionKey = this.isWitness
    ? 'report-form.witness.askedQuestion'
    : 'report-form.askedQuestion';

  protected readonly descriptionErrorKey = this.isWitness
    ? 'report-form.witness.descriptionError'
    : 'report-form.step1.descriptionError';

  protected readonly form = this.formBuilder.nonNullable.group({
    situationDescription: [
      '',
      [Validators.required, Validators.pattern(/\S/), Validators.maxLength(5000)],
    ],
    situationContext: ['', [Validators.required]],
    reporterRecurrence: ['' as ReporterRecurrence | ''],
    reporterAttentionCue: ['' as ReporterAttentionCue | ''],
    reporterTiming: ['' as ReporterTiming | ''],
    reportedPeople: ['', [Validators.maxLength(200)]],
  });

  protected readonly submitting = signal(false);
  protected readonly currentStep = signal<ReporterStep>(1);
  protected readonly selectedEvidence = signal<readonly EvidenceDraft[]>([]);
  /** Frozen at submission so a destroyed selection view cannot affect it. */
  protected readonly submittedEvidence = signal<readonly EvidenceDraft[]>([]);
  protected readonly showHelp = signal(false);
  protected readonly result = signal<ReportSubmissionResponse | null>(null);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly showProfileLoading = signal(false);
  protected readonly profileState = signal<ReportingProfileState>({
    status: 'loading',
  });

  constructor() {
    this.destroyRef.onDestroy(() => this.clearProfileLoadingTimer());
    this.resolvePublicReportingProfile();
  }

  protected continue(): void {
    const description = this.form.controls.situationDescription;

    description.markAsTouched();

    if (description.invalid) {
      return;
    }

    this.currentStep.set(2);
  }

  protected goBack(): void {
    this.errorMessage.set(null);
    this.currentStep.update((step) => Math.max(1, step - 1) as ReporterStep);
  }

  protected navigateToStep(step: number): void {
    if (step < this.currentStep() && step >= 1 && step <= 5) {
      this.errorMessage.set(null);
      this.currentStep.set(step as ReporterStep);
    }
  }

  protected continueFromContext(): void {
    const context = this.form.controls.situationContext;

    context.markAsTouched();

    if (context.invalid) {
      return;
    }

    this.currentStep.set(3);
  }

  protected continueFromDetails(): void {
    this.currentStep.set(4);
  }

  protected continueFromEvidence(): void {
    this.currentStep.set(5);
  }

  protected toggleContext(context: 'in_person' | 'digital' | 'unknown'): void {
    const control = this.form.controls.situationContext;
    const current = control.value as SituationContext | '';

    if (context === 'unknown') {
      control.setValue(current === 'unknown' ? '' : 'unknown');
    } else if (current === context) {
      control.setValue('');
    } else if (current === 'mixed') {
      control.setValue(context === 'in_person' ? 'digital' : 'in_person');
    } else if (current === 'in_person' || current === 'digital') {
      control.setValue('mixed');
    } else {
      control.setValue(context);
    }

    control.markAsTouched();
  }

  protected isContextSelected(context: 'in_person' | 'digital' | 'unknown'): boolean {
    const current = this.form.controls.situationContext.value;

    if (context === 'unknown') {
      return current === 'unknown';
    }

    return current === context || current === 'mixed';
  }

  protected contextSummary(): string {
    const context = this.form.controls.situationContext.value as SituationContext | '';

    return context === '' ? '' : describeSituationContext(context);
  }

  protected retryProfileResolution(): void {
    this.resolvePublicReportingProfile();
  }

  protected submit(): void {
    if (this.profileState().status !== 'ready') {
      return;
    }

    if (this.submitting()) {
      return;
    }

    if (this.form.invalid) {
      return;
    }

    this.submitting.set(true);
    this.errorMessage.set(null);
    this.submittedEvidence.set(this.selectedEvidence().map((draft) => ({ ...draft })));

    const value = this.form.getRawValue();

    this.reporting
      .submitReport(this.publicReportingIdentifier, {
        situationDescription: value.situationDescription,
        situationContext: value.situationContext as SituationContext,
        // These fields stay absent in the interface until the reporter chooses
        // one. The current API contract represents an omitted answer as
        // `unknown`, so preserve that contract only at submission time.
        reporterRecurrence: value.reporterRecurrence || 'unknown',
        reporterAttentionCue: value.reporterAttentionCue || 'unknown',
        reporterTiming: value.reporterTiming || 'unknown',
        // Blank means the reporter named nobody, so the field is left out of
        // the request rather than sent as an empty string.
        ...(value.reportedPeople?.trim() ? { reportedPeople: value.reportedPeople.trim() } : {}),
        // Only sent by the witness entry, so the first-person request body
        // stays byte-for-byte what it is today.
        ...(this.isWitness ? { reporterPerspective: 'witnessed' as const } : {}),
      })
      .subscribe({
        next: (response) => {
          this.result.set(response);
          this.submitting.set(false);
        },
        error: (error: unknown) => {
          this.errorMessage.set(this.describeError(error));
          this.submitting.set(false);
        },
      });
  }

  private resolvePublicReportingProfile(): void {
    this.profileState.set({ status: 'loading' });
    this.scheduleProfileLoading();

    this.reporting.getPublicReportingProfile(this.publicReportingIdentifier).subscribe({
      next: (profile) => {
        this.hideProfileLoading();
        const status =
          profile.reportingMode === 'fictional_demo'
            ? 'fictional-demo'
            : profile.reportingMode === 'disabled'
              ? 'disabled'
              : 'ready';
        this.profileState.set({
          status,
          profile,
        });
      },
      error: (error: unknown) => {
        this.hideProfileLoading();
        this.profileState.set({
          status:
            error instanceof HttpErrorResponse && error.status === 404 ? 'invalid' : 'unavailable',
        });
      },
    });
  }

  private scheduleProfileLoading(): void {
    this.clearProfileLoadingTimer();
    this.showProfileLoading.set(false);
    this.profileLoadingTimer = setTimeout(() => this.showProfileLoading.set(true), 250);
  }

  private hideProfileLoading(): void {
    this.clearProfileLoadingTimer();
    this.showProfileLoading.set(false);
  }

  private clearProfileLoadingTimer(): void {
    if (this.profileLoadingTimer !== null) {
      clearTimeout(this.profileLoadingTimer);
      this.profileLoadingTimer = null;
    }
  }

  private describeError(error: unknown): string {
    if (error instanceof HttpErrorResponse) {
      switch (error.status) {
        case 404:
          return this.transloco.translate('report-form.submissionError.invalidLink');
        case 422:
          return this.transloco.translate('report-form.submissionError.invalidData');
        case 400:
        case 415:
          return this.transloco.translate('report-form.submissionError.processingFailed');
      }
    }

    return this.transloco.translate('report-form.submissionError.generic');
  }

  protected recurrenceSummary(): string | null {
    switch (this.form.controls.reporterRecurrence.value) {
      case 'single':
        return this.transloco.translate('report-form.step2.recurrenceSingle');
      case 'repeated':
        return this.transloco.translate('report-form.step2.recurrenceRepeated');
      case 'ongoing':
        return this.transloco.translate('report-form.step2.recurrenceOngoing');
      case 'unknown':
        return this.transloco.translate('report-form.step2.unknownOption');
      default:
        return null;
    }
  }

  protected timingSummary(): string | null {
    switch (this.form.controls.reporterTiming.value) {
      case 'within_days':
        return this.transloco.translate('report-form.step2.timingWithinDays');
      case 'within_weeks':
        return this.transloco.translate('report-form.step2.timingWithinWeeks');
      case 'longer_ago':
        return this.transloco.translate('report-form.step2.timingLongerAgo');
      case 'unknown':
        return this.transloco.translate('report-form.step2.unknownOption');
      default:
        return null;
    }
  }

  protected attentionSummary(): string | null {
    switch (this.form.controls.reporterAttentionCue.value) {
      case 'needs_prompt_attention':
        return this.transloco.translate('report-form.step2.attentionYes');
      case 'no_prompt_attention_indicated':
        return this.transloco.translate('report-form.step2.attentionNo');
      case 'unknown':
        return this.transloco.translate('report-form.step2.attentionUnknown');
      default:
        return null;
    }
  }
}
