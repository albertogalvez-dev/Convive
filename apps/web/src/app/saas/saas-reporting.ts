import { Component, signal } from '@angular/core';

/**
 * SaaS 2.0 — reporter journey (issues #516 / #517 / #518, expectations
 * R-1..R-4, R-9, R-14, C-12). Public, mobile-first. Real screen for owner
 * review; the copy is the charter's own drafted text (§13). Fictional data.
 */

type ReportingView = 'entry' | 'confirmation' | 'revoked' | 'poster';

@Component({
  selector: 'app-saas-reporting',
  standalone: true,
  templateUrl: './saas-reporting.html',
  styleUrl: './saas-reporting.scss',
})
export class SaasReporting {
  protected readonly view = signal<ReportingView>('entry');
  protected readonly views: readonly { key: ReportingView; label: string }[] = [
    { key: 'entry', label: 'Entrada · #518' },
    { key: 'confirmation', label: 'Confirmación · #518' },
    { key: 'revoked', label: 'Enlace revocado · #516' },
    { key: 'poster', label: 'Cartel QR · #517' },
  ];

  protected setView(key: ReportingView): void {
    this.view.set(key);
  }
}
