import { HttpEventType, provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReportEvidence } from './report-evidence';

describe('ReportEvidence', () => {
  const attachmentEndpoint = '/api/v1/reporter/report/attachments';
  const grantEndpoint = '/api/v1/public/report-access-grants';

  let fixture: ComponentFixture<ReportEvidence>;
  let page: HTMLElement;
  let httpTesting: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ReportEvidence],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    httpTesting = TestBed.inject(HttpTestingController);
  });

  afterEach(() => httpTesting.verify());

  it('opens initial evidence only on request and exchanges the one-time secret privately', () => {
    createComponent({ accessSecret: 'secret-value', autoLoad: false });

    expect(page.querySelector('input[type=file]')).toBeNull();
    httpTesting.expectNone(grantEndpoint);

    page.querySelector<HTMLButtonElement>('.open-evidence')?.click();
    fixture.detectChanges();

    const grant = httpTesting.expectOne(grantEndpoint);

    expect(grant.request.method).toBe('POST');
    expect(grant.request.body).toEqual({ accessSecret: 'secret-value' });
    grant.flush(null, { status: 204, statusText: 'No Content' });

    resolveAttachmentList([]);

    expect(page.querySelector('input[type=file]')).not.toBeNull();
    expect(page.textContent).not.toContain('secret-value');
  });

  it('loads existing attachment states with no original filename or unsafe preview', () => {
    createComponent();
    resolveAttachmentList([
      {
        id: '0192a5c0-9999-7000-8000-000000000038',
        status: 'available',
        description: 'Captura del canal de clase.',
        mediaType: 'image/png',
        byteSize: 2048,
        createdAt: '2026-08-11T00:00:00.000+00:00',
      },
    ]);

    expect(page.textContent).toContain('Imagen PNG');
    expect(page.textContent).toContain('Captura del canal de clase.');
    expect(page.textContent).toContain('Disponible');
    expect(page.querySelector('img')).toBeNull();
    expect(page.querySelector<HTMLAnchorElement>('.remote-actions a')?.getAttribute('href')).toBe(
      `${attachmentEndpoint}/0192a5c0-9999-7000-8000-000000000038/download`,
    );
  });

  it('queues permitted files without exposing their local filenames and removes them before upload', () => {
    createReadyComponent();
    selectFiles([
      new File(['%PDF-1.7\nfictional'], 'student-name-in-filename.pdf', {
        type: 'application/pdf',
      }),
    ]);

    expect(page.textContent).toContain('PDF');
    expect(page.textContent).toContain('Preparado para enviar');
    expect(page.textContent).not.toContain('student-name-in-filename.pdf');

    page.querySelector<HTMLButtonElement>('.remove')?.click();
    fixture.detectChanges();

    expect(page.querySelector('.draft-list')).toBeNull();
    httpTesting.expectNone(
      (request) => request.method === 'POST' && request.url === attachmentEndpoint,
    );
  });

  it('reports genuine byte progress monotonically and uploads selected files sequentially', () => {
    createReadyComponent();
    selectFiles([
      new File(['%PDF-1.7\nfirst'], 'first.pdf', { type: 'application/pdf' }),
      new File(['\u0089PNG\r\n\u001a\nsecond'], 'second.png', { type: 'image/png' }),
    ]);
    writeFirstDescription('  Captura inicial.  ');

    page.querySelector<HTMLButtonElement>('.evidence-actions .primary')?.click();
    fixture.detectChanges();

    const first = httpTesting.expectOne(attachmentEndpoint);
    const firstBody = first.request.body as FormData;

    expect(first.request.method).toBe('POST');
    expect(first.request.reportProgress).toBe(true);
    expect(firstBody.getAll('attachments[]')).toHaveLength(1);
    expect(firstBody.getAll('descriptions[]')).toEqual(['  Captura inicial.  ']);
    first.event({ type: HttpEventType.UploadProgress, loaded: 60, total: 100 });
    fixture.detectChanges();
    expect(page.querySelector('.upload-progress span')?.textContent).toContain('60 %');

    first.event({ type: HttpEventType.UploadProgress, loaded: 40, total: 100 });
    fixture.detectChanges();
    expect(page.querySelector('.upload-progress span')?.textContent).toContain('60 %');
    first.flush({
      items: [processingAttachment('0192a5c0-9999-7000-8000-000000000101', 'Captura inicial.')],
    });
    fixture.detectChanges();

    const second = httpTesting.expectOne(attachmentEndpoint);
    const secondBody = second.request.body as FormData;

    expect(secondBody.getAll('attachments[]')).toHaveLength(1);
    expect(secondBody.getAll('descriptions[]')).toEqual(['']);
    second.flush({
      items: [processingAttachment('0192a5c0-9999-7000-8000-000000000102', null)],
    });
    fixture.detectChanges();

    expect(page.querySelectorAll('.remote-list li')).toHaveLength(2);
    expect(page.textContent).toContain('En comprobación');
    expect(page.querySelector('.draft-list')).toBeNull();
    expect(page.querySelector('.status-message')?.textContent).toContain(
      'Las pruebas seleccionadas se han enviado.',
    );
  });

  it('keeps a failed file for an explicit deterministic retry', () => {
    createReadyComponent();
    selectFiles([new File(['%PDF-1.7\nretry'], 'retry.pdf', { type: 'application/pdf' })]);

    page.querySelector<HTMLButtonElement>('.evidence-actions .primary')?.click();
    fixture.detectChanges();
    httpTesting
      .expectOne(attachmentEndpoint)
      .error(new ProgressEvent('error'), { status: 0, statusText: 'Network Error' });
    fixture.detectChanges();

    expect(page.querySelector('.upload-failure')?.textContent).toContain(
      'No hemos podido subir este archivo.',
    );

    page.querySelector<HTMLButtonElement>('.retry')?.click();
    fixture.detectChanges();
    httpTesting.expectOne(attachmentEndpoint).flush({
      items: [processingAttachment('0192a5c0-9999-7000-8000-000000000103', null)],
    });
    fixture.detectChanges();

    expect(page.querySelector('.upload-failure')).toBeNull();
    expect(page.querySelectorAll('.remote-list li')).toHaveLength(1);
  });

  it('rejects unsupported selections before spending an upload request', () => {
    createReadyComponent();
    selectFiles([new File(['fictional'], 'notes.txt', { type: 'text/plain' })]);

    expect(page.querySelector('[role=alert]')?.textContent).toContain(
      'Selecciona únicamente PDF, JPG o PNG de hasta 5 MB.',
    );
    expect(page.querySelector('.draft-list')).toBeNull();
    httpTesting.expectNone(
      (request) => request.method === 'POST' && request.url === attachmentEndpoint,
    );
  });

  function createComponent(inputs: { accessSecret?: string; autoLoad?: boolean } = {}): void {
    fixture = TestBed.createComponent(ReportEvidence);

    if (inputs.accessSecret !== undefined) {
      fixture.componentRef.setInput('accessSecret', inputs.accessSecret);
    }

    if (inputs.autoLoad !== undefined) {
      fixture.componentRef.setInput('autoLoad', inputs.autoLoad);
    }

    fixture.detectChanges();
    page = fixture.nativeElement as HTMLElement;
  }

  function createReadyComponent(): void {
    createComponent();
    resolveAttachmentList([]);
  }

  function resolveAttachmentList(items: object[]): void {
    const request = httpTesting.expectOne(attachmentEndpoint);

    expect(request.request.method).toBe('GET');
    request.flush({ items });
    fixture.detectChanges();
  }

  function selectFiles(files: File[]): void {
    const input = page.querySelector<HTMLInputElement>('input[type=file]');

    if (input === null) {
      throw new Error('The evidence file input was not found.');
    }

    Object.defineProperty(input, 'files', { configurable: true, value: files });
    input.dispatchEvent(new Event('change'));
    fixture.detectChanges();
  }

  function writeFirstDescription(value: string): void {
    const textarea = page.querySelector<HTMLTextAreaElement>('.draft-list textarea');

    if (textarea === null) {
      throw new Error('The first evidence description was not found.');
    }

    textarea.value = value;
    textarea.dispatchEvent(new Event('input'));
    fixture.detectChanges();
  }

  function processingAttachment(id: string, description: string | null): object {
    return {
      id,
      status: 'processing',
      description,
      createdAt: '2026-08-11T00:00:00.000+00:00',
    };
  }
});
