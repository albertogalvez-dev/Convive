import { Component, computed, input } from '@angular/core';

import { describeSituationContext } from '../reporting/situation-context';
import { FollowUpEntry, ReportFollowUpState } from './follow-up.service';

@Component({
  selector: 'app-follow-up-report',
  standalone: true,
  templateUrl: './follow-up-report.html',
  styleUrl: './follow-up-report.scss',
})
export class FollowUpReport {
  readonly report = input.required<ReportFollowUpState>();

  protected readonly contextLabel = computed(() =>
    describeSituationContext(this.report().situationContext),
  );

  protected authorLabel(entry: FollowUpEntry): string {
    return entry.authorType === 'professional' ? 'El centro' : 'Tú';
  }

  protected formatDateTime(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
      return '';
    }

    return new Intl.DateTimeFormat('es-ES', {
      dateStyle: 'long',
      timeStyle: 'short',
    }).format(date);
  }
}
