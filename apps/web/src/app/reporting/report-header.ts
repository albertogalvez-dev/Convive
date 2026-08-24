import { Component, computed, input, output } from '@angular/core';
import { RouterLink } from '@angular/router';
import { provideTranslocoScope, TranslocoPipe } from '@jsverse/transloco';

@Component({
  selector: 'app-report-header',
  standalone: true,
  imports: [RouterLink, TranslocoPipe],
  providers: [provideTranslocoScope('report-header')],
  templateUrl: './report-header.html',
  styleUrl: './report-header.scss',
})
export class ReportHeader {
  // Only meaningful while progress is shown; the follow-up journey reuses
  // the header as a plain brand bar with `showProgress` disabled.
  readonly currentStep = input(1);
  readonly totalSteps = input(1);
  readonly showProgress = input(true);
  readonly showCloseAccess = input(false);
  readonly closing = input(false);
  readonly helpRequested = output<void>();
  readonly stepRequested = output<number>();
  readonly closeRequested = output<void>();

  protected readonly steps = computed(() =>
    Array.from({ length: this.totalSteps() }, (_, index) => index + 1),
  );
}
