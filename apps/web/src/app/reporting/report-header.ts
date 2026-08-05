import { Component, computed, input, output } from '@angular/core';

@Component({
  selector: 'app-report-header',
  standalone: true,
  templateUrl: './report-header.html',
  styleUrl: './report-header.scss',
})
export class ReportHeader {
  readonly currentStep = input.required<number>();
  readonly totalSteps = input.required<number>();
  readonly showProgress = input(true);
  readonly helpRequested = output<void>();
  readonly stepRequested = output<number>();

  protected readonly steps = computed(() =>
    Array.from({ length: this.totalSteps() }, (_, index) => index + 1),
  );
}
