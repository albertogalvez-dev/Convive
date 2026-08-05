import { HttpErrorResponse } from '@angular/common/http';
import { Component, DestroyRef, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';

import { ReportHeader } from './report-header';
import { ReportHelp } from './report-help';
import { ReportResult } from './report-result';
import { ReportSending } from './report-sending';
import {
  PublicReportingProfile,
  ReportingService,
  ReportSubmissionResponse,
  SituationContext,
} from './reporting.service';

type ReportingProfileState =
  | { status: 'loading' }
  | { status: 'ready'; profile: PublicReportingProfile }
  | { status: 'invalid' }
  | { status: 'unavailable' };

@Component({
  selector: 'app-report-form',
  standalone: true,
  imports: [ReactiveFormsModule, ReportHeader, ReportHelp, ReportResult, ReportSending],
  templateUrl: './report-form.html',
  styleUrl: './report-form.scss',
})
export class ReportForm {
  private readonly formBuilder = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);
  private readonly reporting = inject(ReportingService);
  private readonly route = inject(ActivatedRoute);
  private profileLoadingTimer: ReturnType<typeof setTimeout> | null = null;

  private readonly publicReportingIdentifier =
    this.route.snapshot.paramMap.get('publicReportingIdentifier') ?? '';

  protected readonly form = this.formBuilder.nonNullable.group({
    situationDescription: [
      '',
      [Validators.required, Validators.pattern(/\S/), Validators.maxLength(5000)],
    ],
    situationContext: ['', [Validators.required]],
  });

  protected readonly submitting = signal(false);
  protected readonly currentStep = signal<1 | 2 | 3>(1);
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
    this.currentStep.update((step) => (step === 3 ? 2 : 1));
  }

  protected navigateToStep(step: number): void {
    if (step < this.currentStep() && (step === 1 || step === 2)) {
      this.errorMessage.set(null);
      this.currentStep.set(step);
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
    switch (this.form.controls.situationContext.value) {
      case 'in_person':
        return 'En persona';
      case 'digital':
        return 'Online';
      case 'mixed':
        return 'En persona y online';
      case 'unknown':
        return 'Prefiero no decirlo';
      default:
        return '';
    }
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

    const value = this.form.getRawValue();

    this.reporting
      .submitReport(this.publicReportingIdentifier, {
        situationDescription: value.situationDescription,
        situationContext: value.situationContext as SituationContext,
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
        this.profileState.set({
          status: 'ready',
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
          return 'Este enlace de comunicación no es válido. Compruébalo e inténtalo de nuevo.';
        case 422:
          return 'Alguna información no es válida. Revísala e inténtalo de nuevo.';
        case 400:
        case 415:
          return 'No hemos podido procesar la comunicación. Inténtalo de nuevo.';
      }
    }

    return 'No hemos podido enviar la comunicación ahora. Inténtalo de nuevo más tarde.';
  }
}
