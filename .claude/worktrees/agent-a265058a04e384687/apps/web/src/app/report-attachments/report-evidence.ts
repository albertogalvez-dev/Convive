import { HttpErrorResponse, HttpEventType, HttpResponse } from '@angular/common/http';
import {
  Component,
  computed,
  DestroyRef,
  inject,
  input,
  OnInit,
  output,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { provideTranslocoScope, TranslocoPipe, TranslocoService } from '@jsverse/transloco';
import { of, switchMap } from 'rxjs';

import {
  ReportAttachmentService,
  ReporterAttachment,
  ReporterAttachmentCollection,
} from './report-attachment.service';

const MAXIMUM_FILE_BYTES = 5 * 1024 * 1024;
const MAXIMUM_ATTACHMENTS_PER_SELECTION = 3;
const MAXIMUM_ATTACHMENTS_PER_REPORT = 10;
const MAXIMUM_DESCRIPTION_LENGTH = 500;
const ACCEPTED_MEDIA_TYPES = new Set(['application/pdf', 'image/jpeg', 'image/png']);

type AccessState = 'idle' | 'preparing' | 'ready' | 'failed';
type DraftStatus = 'queued' | 'uploading' | 'failed';

interface EvidenceDraft {
  key: number;
  file: File;
  description: string;
  status: DraftStatus;
  loadedBytes: number;
  totalBytes: number | null;
  errorMessage: string | null;
}

@Component({
  selector: 'app-report-evidence',
  standalone: true,
  imports: [TranslocoPipe],
  providers: [provideTranslocoScope('report-evidence')],
  templateUrl: './report-evidence.html',
  styleUrl: './report-evidence.scss',
})
export class ReportEvidence implements OnInit {
  readonly accessSecret = input<string | null>(null);
  readonly autoLoad = input(true);
  readonly accessRejected = output<void>();

  private readonly attachments = inject(ReportAttachmentService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly transloco = inject(TranslocoService);
  private nextDraftKey = 1;

  protected readonly maximumDescriptionLength = MAXIMUM_DESCRIPTION_LENGTH;
  protected readonly expanded = signal(false);
  protected readonly accessState = signal<AccessState>('idle');
  protected readonly remoteAttachments = signal<ReporterAttachment[]>([]);
  protected readonly drafts = signal<EvidenceDraft[]>([]);
  protected readonly selectionError = signal<string | null>(null);
  protected readonly statusMessage = signal<string | null>(null);
  protected readonly refreshing = signal(false);

  protected readonly uploading = computed(() =>
    this.drafts().some((draft) => draft.status === 'uploading'),
  );
  protected readonly hasQueuedDrafts = computed(() =>
    this.drafts().some((draft) => draft.status === 'queued'),
  );
  protected readonly selectionLimit = computed(() =>
    Math.min(
      MAXIMUM_ATTACHMENTS_PER_SELECTION,
      Math.max(
        0,
        MAXIMUM_ATTACHMENTS_PER_REPORT - this.remoteAttachments().length - this.drafts().length,
      ),
    ),
  );

  ngOnInit(): void {
    if (this.autoLoad()) {
      this.prepare();
    }
  }

  protected prepare(): void {
    if (this.accessState() === 'preparing') {
      return;
    }

    this.expanded.set(true);
    this.accessState.set('preparing');
    this.statusMessage.set(null);
    const secret = this.accessSecret();
    const accessRequest =
      secret === null ? of(undefined) : this.attachments.establishReportAccess(secret);

    accessRequest
      .pipe(
        switchMap(() => this.attachments.list()),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe({
        next: ({ items }) => {
          this.remoteAttachments.set(items);
          this.accessState.set('ready');
        },
        error: (error: unknown) => this.failAccess(error),
      });
  }

  protected selectFiles(event: Event): void {
    const inputElement = event.target as HTMLInputElement;
    const files = Array.from(inputElement.files ?? []);

    inputElement.value = '';
    this.selectionError.set(null);
    this.statusMessage.set(null);

    if (files.length === 0) {
      return;
    }

    if (files.length > this.selectionLimit()) {
      this.selectionError.set(this.selectionLimitMessage());
      return;
    }

    if (files.some((file) => !this.isAcceptedSelection(file))) {
      this.selectionError.set(
        this.transloco.translate('report-evidence.errors.unsupportedSelection'),
      );
      return;
    }

    const selected = files.map<EvidenceDraft>((file) => ({
      key: this.nextDraftKey++,
      file,
      description: '',
      status: 'queued',
      loadedBytes: 0,
      totalBytes: null,
      errorMessage: null,
    }));

    this.drafts.update((current) => [...current, ...selected]);
  }

  protected updateDescription(key: number, value: string): void {
    this.updateDraft(key, (draft) => ({
      ...draft,
      description: value.slice(0, MAXIMUM_DESCRIPTION_LENGTH),
    }));
  }

  protected removeDraft(key: number): void {
    this.drafts.update((current) =>
      current.filter((draft) => draft.key !== key || draft.status === 'uploading'),
    );
    this.selectionError.set(null);
  }

  protected startUploads(): void {
    if (this.uploading() || !this.hasQueuedDrafts()) {
      return;
    }

    this.statusMessage.set(null);
    this.uploadNext();
  }

  protected retryDraft(key: number): void {
    if (this.uploading()) {
      return;
    }

    this.updateDraft(key, (draft) => ({
      ...draft,
      status: 'queued',
      loadedBytes: 0,
      totalBytes: null,
      errorMessage: null,
    }));
    this.startUploads();
  }

  protected refreshStatuses(): void {
    if (this.refreshing()) {
      return;
    }

    this.refreshing.set(true);
    this.statusMessage.set(null);
    this.attachments
      .list()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: ({ items }) => {
          this.remoteAttachments.set(items);
          this.refreshing.set(false);
          this.statusMessage.set(
            this.transloco.translate('report-evidence.errors.statusesUpdated'),
          );
        },
        error: (error: unknown) => {
          this.refreshing.set(false);
          if (!this.handleAccessRejected(error)) {
            this.statusMessage.set(
              this.transloco.translate('report-evidence.errors.refreshFailed'),
            );
          }
        },
      });
  }

  protected progressPercentage(draft: EvidenceDraft): number | null {
    if (draft.totalBytes === null || draft.totalBytes < 1) {
      return null;
    }

    return Math.min(100, Math.floor((draft.loadedBytes / draft.totalBytes) * 100));
  }

  protected fileTypeLabel(file: File): string {
    if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
      return this.transloco.translate('report-evidence.fileType.pdf');
    }

    if (file.type === 'image/png' || file.name.toLowerCase().endsWith('.png')) {
      return this.transloco.translate('report-evidence.fileType.imagePng');
    }

    return this.transloco.translate('report-evidence.fileType.imageJpg');
  }

  protected remoteTypeLabel(attachment: ReporterAttachment): string {
    return attachment.mediaType === 'application/pdf'
      ? this.transloco.translate('report-evidence.fileType.pdf')
      : attachment.mediaType === 'image/png'
        ? this.transloco.translate('report-evidence.fileType.imagePng')
        : attachment.mediaType === 'image/jpeg'
          ? this.transloco.translate('report-evidence.fileType.imageJpg')
          : this.transloco.translate('report-evidence.fileType.generic');
  }

  protected formatBytes(bytes: number): string {
    if (bytes >= 1024 * 1024) {
      return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${Math.max(1, Math.ceil(bytes / 1024))} KB`;
  }

  protected remoteStatusLabel(attachment: ReporterAttachment): string {
    switch (attachment.status) {
      case 'processing':
        return this.transloco.translate('report-evidence.status.processing');
      case 'available':
        return this.transloco.translate('report-evidence.status.available');
      case 'unavailable':
        return this.transloco.translate('report-evidence.status.unavailable');
    }
  }

  protected downloadUrl(id: string): string {
    return this.attachments.downloadUrl(id);
  }

  private uploadNext(): void {
    const next = this.drafts().find((draft) => draft.status === 'queued');

    if (next === undefined) {
      this.statusMessage.set(
        this.transloco.translate(
          this.drafts().some((draft) => draft.status === 'failed')
            ? 'report-evidence.errors.reviewFailed'
            : 'report-evidence.errors.allSent',
        ),
      );
      return;
    }

    this.updateDraft(next.key, (draft) => ({
      ...draft,
      status: 'uploading',
      loadedBytes: 0,
      totalBytes: null,
      errorMessage: null,
    }));

    this.attachments
      .upload(next.file, next.description)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (event) => {
          if (event.type === HttpEventType.UploadProgress) {
            this.updateDraft(next.key, (draft) => ({
              ...draft,
              loadedBytes: Math.max(draft.loadedBytes, event.loaded),
              totalBytes: event.total ?? draft.totalBytes,
            }));
            return;
          }

          if (event instanceof HttpResponse) {
            this.completeUpload(next.key, event.body);
          }
        },
        error: (error: unknown) => {
          this.updateDraft(next.key, (draft) => ({
            ...draft,
            status: 'failed',
            errorMessage: this.describeUploadError(error),
          }));
          this.handleAccessRejected(error);

          if (!shouldStopQueue(error)) {
            this.uploadNext();
          }
        },
      });
  }

  private completeUpload(key: number, response: ReporterAttachmentCollection | null): void {
    const attachment = response?.items[0];

    if (attachment === undefined || response?.items.length !== 1) {
      this.updateDraft(key, (draft) => ({
        ...draft,
        status: 'failed',
        errorMessage: this.transloco.translate('report-evidence.errors.confirmFailed'),
      }));
      this.uploadNext();
      return;
    }

    this.remoteAttachments.update((current) => [...current, attachment]);
    this.drafts.update((current) => current.filter((draft) => draft.key !== key));
    this.uploadNext();
  }

  private updateDraft(key: number, update: (draft: EvidenceDraft) => EvidenceDraft): void {
    this.drafts.update((current) =>
      current.map((draft) => (draft.key === key ? update(draft) : draft)),
    );
  }

  private isAcceptedSelection(file: File): boolean {
    if (file.size < 1 || file.size > MAXIMUM_FILE_BYTES) {
      return false;
    }

    const extensionAccepted = /\.(pdf|jpe?g|png)$/i.test(file.name);

    return ACCEPTED_MEDIA_TYPES.has(file.type) || (file.type === '' && extensionAccepted);
  }

  private selectionLimitMessage(): string {
    const remaining = this.selectionLimit();

    if (remaining === 0) {
      return this.transloco.translate('report-evidence.capacityMessage');
    }

    return this.transloco.translate('report-evidence.errors.selectionLimit', { remaining });
  }

  private failAccess(error: unknown): void {
    this.accessState.set('failed');
    if (!this.handleAccessRejected(error)) {
      this.statusMessage.set(this.transloco.translate('report-evidence.errors.prepareFailed'));
    }
  }

  private handleAccessRejected(error: unknown): boolean {
    if (!(error instanceof HttpErrorResponse) || error.status !== 401) {
      return false;
    }

    if (this.accessSecret() === null) {
      this.accessRejected.emit();
    } else {
      this.accessState.set('failed');
      this.statusMessage.set(this.transloco.translate('report-evidence.errors.accessExpired'));
    }

    return true;
  }

  private describeUploadError(error: unknown): string {
    if (error instanceof HttpErrorResponse) {
      switch (error.status) {
        case 401:
          return this.transloco.translate('report-evidence.errors.uploadAccessExpired');
        case 409:
          return this.transloco.translate('report-evidence.errors.uploadLimitReached');
        case 413:
          return this.transloco.translate('report-evidence.errors.fileTooLarge');
        case 415:
          return this.transloco.translate('report-evidence.errors.unsupportedFormat');
        case 422:
          return this.transloco.translate('report-evidence.errors.validationFailed');
        case 429:
          return this.transloco.translate('report-evidence.errors.tooManyAttempts');
      }
    }

    return this.transloco.translate('report-evidence.errors.uploadFailedGeneric');
  }
}

function shouldStopQueue(error: unknown): boolean {
  return error instanceof HttpErrorResponse && [401, 409, 429].includes(error.status);
}
