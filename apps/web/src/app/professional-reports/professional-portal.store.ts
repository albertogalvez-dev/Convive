import { HttpErrorResponse } from '@angular/common/http';
import { inject, Injectable, signal } from '@angular/core';
import { Router } from '@angular/router';
import { forkJoin } from 'rxjs';

import { ProfessionalReportPage, ProfessionalReportsService } from './professional-reports.service';

@Injectable({ providedIn: 'root' })
export class ProfessionalPortalStore {
  private readonly reports = inject(ProfessionalReportsService);
  private readonly router = inject(Router);
  private requested = false;

  readonly loading = signal(true);
  readonly errorMessage = signal<string | null>(null);
  readonly newPage = signal<ProfessionalReportPage | null>(null);
  readonly reviewedPage = signal<ProfessionalReportPage | null>(null);

  load(force = false): void {
    if (this.requested && !force) return;
    this.requested = true;
    this.loading.set(true);
    this.errorMessage.set(null);
    forkJoin({
      newReports: this.reports.list('new', undefined, 50),
      reviewedReports: this.reports.list('reviewed', undefined, 50),
    }).subscribe({
      next: ({ newReports, reviewedReports }) => {
        this.newPage.set(newReports);
        this.reviewedPage.set(reviewedReports);
        this.loading.set(false);
      },
      error: (error: unknown) => {
        this.loading.set(false);
        this.requested = false;
        if (error instanceof HttpErrorResponse && error.status === 401) {
          void this.router.navigate(['/profesionales/acceso']);
          return;
        }
        this.errorMessage.set('No hemos podido cargar el resumen del centro.');
      },
    });
  }
}
