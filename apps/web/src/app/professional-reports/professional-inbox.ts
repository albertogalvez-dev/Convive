import { HttpErrorResponse } from '@angular/common/http';
import { DatePipe, registerLocaleData } from '@angular/common';
import localeEs from '@angular/common/locales/es';
import { Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { reportContextLabel } from './report-context';
import {
  ProfessionalReportStatus,
  ProfessionalReportSummary,
  ProfessionalReportsService,
} from './professional-reports.service';

registerLocaleData(localeEs);

@Component({
  selector: 'app-professional-inbox',
  standalone: true,
  imports: [DatePipe, RouterLink],
  templateUrl: './professional-inbox.html',
  styleUrl: './professional-inbox.scss',
})
export class ProfessionalInbox implements OnInit {
  private readonly reports = inject(ProfessionalReportsService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  protected readonly activeStatus = signal<ProfessionalReportStatus>('new');
  protected readonly items = signal<ProfessionalReportSummary[]>([]);
  protected readonly loading = signal(true);
  protected readonly loadingMore = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly nextCursor = signal<string | null>(null);
  protected readonly contextLabel = reportContextLabel;

  ngOnInit(): void {
    if (this.route.snapshot.queryParamMap.get('estado') === 'reviewed') {
      this.activeStatus.set('reviewed');
    }
    this.load(false);
  }

  protected selectStatus(status: ProfessionalReportStatus): void {
    if (status === this.activeStatus()) return;
    this.activeStatus.set(status);
    this.load(false);
  }

  protected loadMore(): void {
    this.load(true);
  }
  protected retry(): void {
    this.load(false);
  }

  private load(append: boolean): void {
    if (append && (!this.nextCursor() || this.loadingMore())) return;
    append ? this.loadingMore.set(true) : this.loading.set(true);
    if (!append) this.items.set([]);
    this.errorMessage.set(null);

    this.reports
      .list(this.activeStatus(), append ? (this.nextCursor() ?? undefined) : undefined)
      .subscribe({
        next: (page) => {
          this.items.update((items) => (append ? [...items, ...page.items] : page.items));
          this.nextCursor.set(page.pagination.nextCursor);
          this.loading.set(false);
          this.loadingMore.set(false);
        },
        error: (error: unknown) => {
          this.loading.set(false);
          this.loadingMore.set(false);
          if (error instanceof HttpErrorResponse && error.status === 401) {
            void this.router.navigate(['/profesionales/acceso']);
            return;
          }
          this.errorMessage.set('No hemos podido cargar las comunicaciones.');
        },
      });
  }
}
