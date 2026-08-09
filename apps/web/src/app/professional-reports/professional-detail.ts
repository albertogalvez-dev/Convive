import { DatePipe, registerLocaleData } from '@angular/common';
import localeEs from '@angular/common/locales/es';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { reportContextLabel } from './report-context';
import {
  ProfessionalReportDetail,
  ProfessionalReportsService,
} from './professional-reports.service';

registerLocaleData(localeEs);

@Component({
  selector: 'app-professional-detail',
  standalone: true,
  imports: [DatePipe, ReactiveFormsModule, RouterLink],
  templateUrl: './professional-detail.html',
  styleUrl: './professional-detail.scss',
})
export class ProfessionalDetail implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly reports = inject(ProfessionalReportsService);
  private readonly formBuilder = inject(FormBuilder);
  private readonly id = this.route.snapshot.paramMap.get('id') ?? '';

  protected readonly report = signal<ProfessionalReportDetail | null>(null);
  protected readonly loading = signal(true);
  protected readonly submitting = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly reviewError = signal<string | null>(null);
  protected readonly contextLabel = reportContextLabel;
  protected readonly reviewForm = this.formBuilder.nonNullable.group({
    reason: ['', [Validators.required, Validators.minLength(10), Validators.maxLength(1000)]],
  });

  ngOnInit(): void {
    this.load();
  }

  protected review(): void {
    this.reviewForm.markAllAsTouched();
    if (this.reviewForm.invalid || this.submitting() || !this.report()) return;
    this.submitting.set(true);
    this.reviewError.set(null);
    this.reports.review(this.id, this.reviewForm.controls.reason.value.trim()).subscribe({
      next: ({ review }) => {
        this.report.update((report) => (report ? { ...report, status: 'reviewed', review } : null));
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
}
