import { Component, HostListener, input, signal } from '@angular/core';

import { ReportSubmissionResponse } from './reporting.service';

@Component({
  selector: 'app-report-result',
  standalone: true,
  templateUrl: './report-result.html',
  styleUrl: './report-result.scss',
})
export class ReportResult {
  readonly submitted = input.required<ReportSubmissionResponse>();
  protected readonly copyMessage = signal<string | null>(null);
  private readonly accessSecretCopied = signal(false);

  @HostListener('window:beforeunload', ['$event'])
  protected warnBeforeLeaving(event: BeforeUnloadEvent): void {
    if (this.accessSecretCopied()) {
      return;
    }

    event.preventDefault();
  }

  protected async copyAccessSecret(): Promise<void> {
    try {
      await navigator.clipboard.writeText(this.submitted().accessSecret);
      this.accessSecretCopied.set(true);
      this.copyMessage.set('Secreto copiado. Ya puedes cerrar esta página.');
    } catch {
      this.copyMessage.set('No se ha podido copiar. Selecciona y copia el secreto manualmente.');
    }
  }
}
