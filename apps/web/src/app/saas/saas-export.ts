import { Component, signal } from '@angular/core';

/**
 * SaaS 2.0 — clean PDF document export template (issue #543, expectation P-15).
 * No Convive branding; reads as the centre's own working document. The two
 * toggles cover the one open owner decision: the minimal traceability line's
 * form and placement. Real screen for owner review; fictional data.
 */

type TracePlacement = 'footer' | 'corner';

@Component({
  selector: 'app-saas-export',
  standalone: true,
  templateUrl: './saas-export.html',
  styleUrl: './saas-export.scss',
})
export class SaasExport {
  protected readonly withLogo = signal(true);
  protected readonly trace = signal<TracePlacement>('footer');

  protected setLogo(value: boolean): void {
    this.withLogo.set(value);
  }

  protected setTrace(value: TracePlacement): void {
    this.trace.set(value);
  }
}
