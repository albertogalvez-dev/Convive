import { DatePipe, registerLocaleData } from '@angular/common';
import localeEs from '@angular/common/locales/es';
import { Component, computed, inject, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import { reportContextLabel } from './report-context';
import { ProfessionalPortalStore } from './professional-portal.store';
import { ProfessionalReportPage, ProfessionalReportSummary } from './professional-reports.service';

registerLocaleData(localeEs);

@Component({
  selector: 'app-professional-dashboard',
  standalone: true,
  imports: [DatePipe, RouterLink],
  templateUrl: './professional-dashboard.html',
  styleUrl: './professional-dashboard.scss',
})
export class ProfessionalDashboard implements OnInit {
  private readonly portal = inject(ProfessionalPortalStore);
  private readonly session = inject(ProfessionalSessionService);
  protected readonly professional = this.session.professional;
  protected readonly loading = this.portal.loading;
  protected readonly errorMessage = this.portal.errorMessage;
  protected readonly newPage = this.portal.newPage;
  protected readonly reviewedPage = this.portal.reviewedPage;
  protected readonly recentReports = computed<ProfessionalReportSummary[]>(() =>
    [...(this.newPage()?.items ?? []), ...(this.reviewedPage()?.items ?? [])]
      .sort((a, b) => b.createdAt.localeCompare(a.createdAt))
      .slice(0, 5),
  );
  protected readonly contextLabel = reportContextLabel;

  ngOnInit(): void {
    this.portal.load();
  }
  protected count(page: ProfessionalReportPage | null): string {
    return page ? `${page.items.length}${page.pagination.nextCursor ? '+' : ''}` : '0';
  }
  protected retry(): void {
    this.portal.load(true);
  }
}
