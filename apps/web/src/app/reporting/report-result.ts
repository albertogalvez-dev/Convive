import { Component, HostListener, inject, input, signal } from '@angular/core';
import { provideTranslocoScope, TranslocoPipe, TranslocoService } from '@jsverse/transloco';

import { ReportEvidence } from '../report-attachments/report-evidence';
import { EvidenceDraft } from '../report-attachments/report-evidence';
import { ReportSubmissionResponse } from './reporting.service';

interface PasswordCredentialData {
  id: string;
  password: string;
  name?: string;
}

type PasswordCredentialConstructor = new (data: PasswordCredentialData) => Credential;

/**
 * `PasswordCredential` is not part of the DOM lib because support is
 * Chromium-only. It is the one standard way to ask the browser explicitly
 * to store the pair, which ADR-0011 permits; everything else stays a manual
 * copy.
 */
function passwordCredentialConstructor(): PasswordCredentialConstructor | null {
  const candidate = (window as unknown as { PasswordCredential?: PasswordCredentialConstructor })
    .PasswordCredential;

  return typeof candidate === 'function' && navigator.credentials ? candidate : null;
}

@Component({
  selector: 'app-report-result',
  standalone: true,
  imports: [ReportEvidence, TranslocoPipe],
  providers: [provideTranslocoScope('report-result')],
  templateUrl: './report-result.html',
  styleUrl: './report-result.scss',
})
export class ReportResult {
  private readonly transloco = inject(TranslocoService);

  readonly submitted = input.required<ReportSubmissionResponse>();
  readonly initialEvidence = input<readonly EvidenceDraft[]>([]);
  protected readonly statusMessage = signal<string | null>(null);
  protected readonly canSaveInBrowser = signal(passwordCredentialConstructor() !== null);
  private readonly accessSecretKept = signal(false);

  @HostListener('window:beforeunload', ['$event'])
  protected warnBeforeLeaving(event: BeforeUnloadEvent): void {
    if (this.accessSecretKept()) {
      return;
    }

    event.preventDefault();
  }

  protected async saveInBrowser(): Promise<void> {
    const credentialConstructor = passwordCredentialConstructor();

    if (credentialConstructor === null) {
      this.statusMessage.set(
        this.transloco.translate('report-result.status.credentialUnsupported'),
      );
      return;
    }

    try {
      await navigator.credentials.store(
        new credentialConstructor({
          id: this.submitted().publicReference,
          password: this.submitted().accessSecret,
          name: this.transloco.translate('report-result.credentialName'),
        }),
      );
      this.accessSecretKept.set(true);
      this.statusMessage.set(this.transloco.translate('report-result.status.credentialRequested'));
    } catch {
      this.statusMessage.set(this.transloco.translate('report-result.status.credentialFailed'));
    }
  }

  protected async copyAccessSecret(): Promise<void> {
    try {
      await navigator.clipboard.writeText(this.submitted().accessSecret);
      this.accessSecretKept.set(true);
      this.statusMessage.set(this.transloco.translate('report-result.status.copySuccess'));
    } catch {
      this.statusMessage.set(this.transloco.translate('report-result.status.copyFailed'));
    }
  }

  protected printAccessReceipt(): void {
    window.print();
  }
}
