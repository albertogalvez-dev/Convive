import { Component, computed, input } from '@angular/core';

import { describeSituationContext } from '../reporting/situation-context';
import { FollowUpEntry, ReporterProgressStage, ReportFollowUpState } from './follow-up.service';

interface ProgressCopy {
  readonly label: string;
  readonly explanation: string;
}

/**
 * The only words this surface may use for a report's progress.
 *
 * Each one confirms the report is moving and discloses nothing: no case
 * content, no professional's name, no reason, and no hint of which internal
 * decision was taken. None of them promises a reply or a date.
 */
const PROGRESS_COPY: Readonly<Record<ReporterProgressStage, ProgressCopy>> = {
  received: {
    label: 'Recibida',
    explanation: 'El centro ya tiene tu comunicación. Todavía no la ha revisado nadie.',
  },
  under_review: {
    label: 'En revisión',
    explanation: 'Una persona del centro ha leído tu comunicación y la está mirando.',
  },
  action_taken: {
    label: 'El centro está actuando',
    explanation: 'Tu comunicación ha dado lugar a un trabajo que el centro está haciendo.',
  },
  closed: {
    label: 'Cerrada',
    explanation:
      'El centro ha terminado de ocuparse de tu comunicación. Si algo sigue pasando, puedes escribir de nuevo.',
  },
};

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

  /**
   * Null whenever the API did not send a stage, or sent one this build does
   * not recognise. The conversation below stays fully usable either way --
   * this section is additive, never a dependency of the core journey.
   */
  protected readonly progress = computed<ProgressCopy | null>(() => {
    const stage = this.report().progressStage;

    return stage !== undefined && stage in PROGRESS_COPY ? PROGRESS_COPY[stage] : null;
  });

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
