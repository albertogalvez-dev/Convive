import { HttpErrorResponse } from '@angular/common/http';
import { DatePipe, registerLocaleData } from '@angular/common';
import localeEs from '@angular/common/locales/es';
import { Component, inject, OnInit, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';

import { assignmentRoleLabel, caseModalityLabel, caseStatusLabel } from './case-labels';
import { ProfessionalCasesService, ProfessionalCaseSummary } from './professional-cases.service';

registerLocaleData(localeEs);

@Component({
  selector: 'app-professional-cases',
  standalone: true,
  imports: [DatePipe, RouterLink],
  templateUrl: './professional-cases.html',
  styleUrl: './professional-cases.scss',
})
export class ProfessionalCases implements OnInit {
  private readonly cases = inject(ProfessionalCasesService);
  private readonly router = inject(Router);

  protected readonly items = signal<ProfessionalCaseSummary[]>([]);
  protected readonly loading = signal(true);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly caseStatusLabel = caseStatusLabel;
  protected readonly caseModalityLabel = caseModalityLabel;
  protected readonly assignmentRoleLabel = assignmentRoleLabel;

  ngOnInit(): void {
    this.load();
  }

  protected retry(): void {
    this.load();
  }

  private load(): void {
    this.loading.set(true);
    this.errorMessage.set(null);
    this.cases.list().subscribe({
      next: ({ items }) => {
        this.items.set(items);
        this.loading.set(false);
      },
      error: (error: unknown) => {
        this.loading.set(false);
        if (error instanceof HttpErrorResponse && error.status === 401) {
          void this.router.navigate(['/profesionales/acceso']);
          return;
        }
        this.errorMessage.set('No hemos podido cargar tus casos.');
      },
    });
  }
}
