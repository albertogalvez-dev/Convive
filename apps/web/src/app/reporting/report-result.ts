import { Component, HostListener, input, signal } from '@angular/core';

import { ReportEvidence } from '../report-attachments/report-evidence';
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
  imports: [ReportEvidence],
  templateUrl: './report-result.html',
  styleUrl: './report-result.scss',
})
export class ReportResult {
  readonly submitted = input.required<ReportSubmissionResponse>();
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
      this.statusMessage.set('Este navegador no puede guardarlo. Copia el secreto y guárdalo tú.');
      return;
    }

    try {
      await navigator.credentials.store(
        new credentialConstructor({
          id: this.submitted().publicReference,
          password: this.submitted().accessSecret,
          name: 'Convive · seguimiento',
        }),
      );
      this.accessSecretKept.set(true);
      this.statusMessage.set('Se lo hemos pedido a tu navegador. Acepta para no perder el acceso.');
    } catch {
      this.statusMessage.set(
        'No se ha podido guardar en el navegador. Copia el secreto y guárdalo tú.',
      );
    }
  }

  protected async copyAccessSecret(): Promise<void> {
    try {
      await navigator.clipboard.writeText(this.submitted().accessSecret);
      this.accessSecretKept.set(true);
      this.statusMessage.set('Secreto copiado. Ya puedes cerrar esta página.');
    } catch {
      this.statusMessage.set('No se ha podido copiar. Selecciona y copia el secreto manualmente.');
    }
  }

  protected printAccessReceipt(): void {
    window.print();
  }
}
