import { HttpErrorResponse } from '@angular/common/http';
import {
  Component,
  ElementRef,
  inject,
  input,
  OnDestroy,
  output,
  signal,
  viewChild,
} from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { provideTranslocoScope, TranslocoService } from '@jsverse/transloco';
import { Observable } from 'rxjs';

export type PreviewableAttachment = {
  id: string;
  mediaType: 'application/pdf' | 'image/jpeg' | 'image/png';
};

type PreviewCopyKey = 'open' | 'title' | 'close' | 'loading' | 'error' | 'pdfTitle' | 'imageAlt';

const PROFESSIONAL_PREVIEW_COPY: Readonly<Record<PreviewCopyKey, string>> = {
  open: 'Ver archivo',
  title: 'Vista previa del archivo',
  close: 'Cerrar',
  loading: 'Cargando archivo…',
  error: 'No hemos podido mostrar este archivo. Inténtalo de nuevo.',
  pdfTitle: 'Vista previa del PDF adjunto',
  imageAlt: 'Imagen adjunta',
};

@Component({
  selector: 'app-private-attachment-preview',
  standalone: true,
  providers: [provideTranslocoScope('attachment-preview')],
  templateUrl: './private-attachment-preview.html',
  styleUrl: './private-attachment-preview.scss',
})
export class PrivateAttachmentPreview implements OnDestroy {
  readonly attachment = input.required<PreviewableAttachment>();
  readonly load = input.required<(id: string) => Observable<Blob>>();
  /** The professional workspace is intentionally Spanish-only. */
  readonly spanishOnly = input(false);
  readonly accessRejected = output<void>();

  private readonly sanitizer = inject(DomSanitizer);
  private readonly transloco = inject(TranslocoService);
  private readonly dialog = viewChild.required<ElementRef<HTMLDialogElement>>('dialog');
  private objectUrl: string | null = null;
  private previewSession = 0;

  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly imageUrl = signal<string | null>(null);
  protected readonly documentUrl = signal<SafeResourceUrl | null>(null);

  ngOnDestroy(): void {
    this.revokeObjectUrl();
  }

  protected open(): void {
    if (this.loading()) {
      return;
    }

    this.cancelPreview();
    this.errorMessage.set(null);
    this.openDialog();
    const session = ++this.previewSession;
    this.loading.set(true);

    this.load()(this.attachment().id).subscribe({
      next: (content) => {
        if (session !== this.previewSession) {
          return;
        }

        if (content.type !== this.attachment().mediaType) {
          this.errorMessage.set(this.copy('error'));
          this.loading.set(false);
          return;
        }

        this.objectUrl = URL.createObjectURL(content);
        if (this.attachment().mediaType === 'application/pdf') {
          this.documentUrl.set(this.sanitizer.bypassSecurityTrustResourceUrl(this.objectUrl));
        } else {
          this.imageUrl.set(this.objectUrl);
        }
        this.loading.set(false);
      },
      error: (error: unknown) => {
        if (session !== this.previewSession) {
          return;
        }

        this.loading.set(false);
        if (error instanceof HttpErrorResponse && error.status === 401) {
          this.close();
          this.accessRejected.emit();
          return;
        }
        this.errorMessage.set(this.copy('error'));
      },
    });
  }

  protected close(): void {
    const element = this.dialog().nativeElement;
    this.cancelPreview();
    if (element.open) {
      if (typeof element.close === 'function') {
        element.close();
      } else {
        element.open = false;
      }
    }
  }

  protected closed(): void {
    this.cancelPreview();
  }

  protected isPdf(): boolean {
    return this.attachment().mediaType === 'application/pdf';
  }

  protected copy(key: PreviewCopyKey): string {
    return this.spanishOnly()
      ? PROFESSIONAL_PREVIEW_COPY[key]
      : this.transloco.translate(`attachment-preview.${key}`);
  }

  private openDialog(): void {
    const element = this.dialog().nativeElement;
    if (element.open) {
      return;
    }

    if (typeof element.showModal === 'function') {
      element.showModal();
      return;
    }

    // jsdom has no dialog implementation; retaining this fallback also keeps
    // the control usable on a browser that has not implemented showModal yet.
    element.open = true;
  }

  private revokeObjectUrl(): void {
    if (this.objectUrl !== null) {
      URL.revokeObjectURL(this.objectUrl);
      this.objectUrl = null;
    }
    this.imageUrl.set(null);
    this.documentUrl.set(null);
  }

  private cancelPreview(): void {
    this.previewSession += 1;
    this.loading.set(false);
    this.revokeObjectUrl();
  }
}
