import { Component, computed, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

/**
 * SaaS 2.0 — reporter journey (issues #516 / #517 / #518).
 *
 * Follows the delivered public reporting flow: five steps with a progress
 * header, the same wording, the same option-card controls. The SaaS additions
 * are the standing non-emergency notice (R-2), the optional contact step (R-8),
 * the revoked-link response (R-14) and the centre poster (C-12).
 */

type Screen = 'form' | 'result' | 'revoked' | 'poster';

@Component({
  selector: 'app-saas-reporting',
  standalone: true,
  templateUrl: './saas-reporting.html',
  styleUrl: './saas-reporting.scss',
})
export class SaasReporting {
  protected readonly screen: Screen =
    (inject(ActivatedRoute).snapshot.data['screen'] as Screen) ?? 'form';
  protected readonly step = signal(1);
  protected readonly totalSteps = 5;

  protected readonly context = signal<string[]>([]);
  protected readonly recurrence = signal<string>('');
  protected readonly wantsUpdates = signal<boolean>(false);

  protected readonly progress = computed(() => (this.step() / this.totalSteps) * 100);

  protected next(): void {
    if (this.step() < this.totalSteps) {
      this.step.update((value) => value + 1);
    } else {
      this.step.set(this.totalSteps);
    }
  }

  protected back(): void {
    this.step.update((value) => Math.max(1, value - 1));
  }

  protected toggleContext(value: string): void {
    this.context.update((current) =>
      current.includes(value) ? current.filter((entry) => entry !== value) : [...current, value],
    );
  }

  protected isContext(value: string): boolean {
    return this.context().includes(value);
  }
}
