import { HttpErrorResponse } from '@angular/common/http';
import { DatePipe, registerLocaleData } from '@angular/common';
import localeEs from '@angular/common/locales/es';
import { FormsModule } from '@angular/forms';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';

import { assignmentRoleLabel, caseModalityLabel, caseStatusLabel } from './case-labels';
import {
  CaseModality,
  CaseOperationalView,
  CaseStatus,
  ProfessionalCasesService,
  ProfessionalCaseSummary,
} from './professional-cases.service';

registerLocaleData(localeEs);

@Component({
  selector: 'app-professional-cases',
  standalone: true,
  imports: [DatePipe, FormsModule, RouterLink],
  templateUrl: './professional-cases.html',
  styleUrl: './professional-cases.scss',
})
export class ProfessionalCases implements OnInit {
  private readonly cases = inject(ProfessionalCasesService);
  private readonly router = inject(Router);
  private readonly sessions = inject(ProfessionalSessionService);

  protected readonly items = signal<ProfessionalCaseSummary[]>([]);
  protected readonly loading = signal(true);
  protected readonly loadingMore = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly nextCursor = signal<string | null>(null);
  protected readonly view = signal<CaseOperationalView>('assigned');
  protected readonly status = signal<CaseStatus | ''>('');
  protected readonly modality = signal<CaseModality | ''>('');
  protected readonly reference = signal('');
  protected readonly note = signal('');
  protected readonly pending = signal(false);
  protected readonly summary = signal({ assigned: 0, overdue: 0, upcoming: 0 });
  protected readonly hasFilters = computed(
    () =>
      this.status() !== '' ||
      this.modality() !== '' ||
      this.reference().trim() !== '' ||
      this.note().trim() !== '' ||
      this.pending(),
  );
  protected readonly caseStatusLabel = caseStatusLabel;
  protected readonly caseModalityLabel = caseModalityLabel;
  protected readonly assignmentRoleLabel = assignmentRoleLabel;
  protected readonly isDemonstration = computed(() => this.sessions.demonstrationRole() !== null);

  ngOnInit(): void {
    this.load();
    this.loadSummary();
  }

  protected retry(): void {
    this.load();
    this.loadSummary();
  }

  protected operationalOverviewExportUrl(): string {
    return this.cases.operationalOverviewExportUrl();
  }

  protected selectView(view: CaseOperationalView): void {
    if (this.view() === view) {
      return;
    }

    this.view.set(view);
    this.load();
  }

  protected applyFilters(): void {
    this.load();
  }

  protected clearFilters(): void {
    this.status.set('');
    this.modality.set('');
    this.reference.set('');
    this.note.set('');
    this.pending.set(false);
    this.load();
  }

  protected loadMore(): void {
    if (this.loadingMore() || !this.nextCursor()) {
      return;
    }

    this.loadingMore.set(true);
    this.load(false);
  }

  private load(reset = true): void {
    if (reset) {
      this.loading.set(true);
    }
    this.errorMessage.set(null);
    this.cases
      .list({
        view: this.view(),
        status: this.status(),
        modality: this.modality(),
        reference: this.reference().trim(),
        note: this.note().trim(),
        pending: this.pending() ? 'true' : '',
        cursor: reset ? undefined : (this.nextCursor() ?? undefined),
      })
      .subscribe({
        next: ({ items, pagination }) => {
          this.items.set(reset ? items : [...this.items(), ...items]);
          this.nextCursor.set(pagination.nextCursor);
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
          this.errorMessage.set('No hemos podido cargar tus casos.');
        },
      });
  }

  private loadSummary(): void {
    this.cases.operationalSummary().subscribe({
      next: (summary) => this.summary.set(summary),
      error: () => undefined,
    });
  }
}
