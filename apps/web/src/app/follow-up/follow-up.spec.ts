import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FollowUp } from './follow-up';
import { FollowUpEntry, ReportFollowUpState } from './follow-up.service';

describe('FollowUp', () => {
  const accessSecret = 'a1b2c3d4'.repeat(8);
  const grantEndpoint = '/api/v1/public/report-access-grants';
  const reportEndpoint = '/api/v1/reporter/report';
  const attachmentEndpoint = '/api/v1/reporter/report/attachments';
  const entriesEndpoint = '/api/v1/reporter/report/follow-up-entries';
  const revocationEndpoint = '/api/v1/reporter/access-grant';

  let fixture: ComponentFixture<FollowUp>;
  let page: HTMLElement;
  let httpTesting: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FollowUp],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    httpTesting = TestBed.inject(HttpTestingController);

    fixture = TestBed.createComponent(FollowUp);
    fixture.detectChanges();

    page = fixture.nativeElement as HTMLElement;
  });

  afterEach(() => {
    httpTesting.verify();
  });

  it('should ask for the access secret without contacting the API', () => {
    expect(page.querySelector('#step-title')?.textContent).toContain('Consulta tu comunicación');
    expect(page.querySelector('#accessSecret')).not.toBeNull();
    expect(page.querySelector('app-follow-up-report')).toBeNull();
  });

  it('should offer the reference as a credential label without sending it', () => {
    const reference = page.querySelector<HTMLInputElement>('#reference');

    expect(reference?.getAttribute('autocomplete')).toBe('username');
    expect(secretField().getAttribute('autocomplete')).toBe('current-password');

    if (!reference) {
      throw new Error('The reference field was not found.');
    }

    reference.value = 'CNV-2026-0001';
    reference.dispatchEvent(new Event('input'));
    fixture.detectChanges();

    writeSecret(accessSecret);
    submitSecret();

    const request = httpTesting.expectOne(grantEndpoint);

    expect(request.request.body).toEqual({ accessSecret });

    request.flush(null, { status: 204, statusText: 'No Content' });
    resolveReport();
  });

  it('should keep the secret hidden until the reporter asks to see it', () => {
    const field = secretField();

    expect(field.type).toBe('password');

    visibilityToggle().click();
    fixture.detectChanges();

    expect(secretField().type).toBe('text');
  });

  it('should reject a malformed secret before spending a verification attempt', () => {
    writeSecret('no-es-un-secreto');
    submitSecret();

    expect(page.querySelector('#secret-error')?.textContent).toContain(
      'Este secreto no es válido.',
    );
    expect(page.querySelector('#accessSecret')?.getAttribute('aria-describedby')).toBe(
      'secret-error',
    );
    httpTesting.expectNone(grantEndpoint);
  });

  it('should verify the secret, open the report and never expose it', () => {
    unlockReport();

    expect(page.querySelector('#step-title')?.textContent).toContain('Tu comunicación');
    expect(page.textContent).toContain('CNV-2026-0001');
    expect(page.textContent).toContain('En persona');
    expect(page.textContent).toContain('Una situación ocurrió durante el recreo.');
    expect(page.textContent).toContain('Ya lo estamos revisando.');
    expect(page.textContent).toContain('El centro');
    expect(page.querySelector('.entry-date')?.textContent).toContain('agosto');
    expect(page.querySelector('#accessSecret')).toBeNull();

    expect(window.location.href).not.toContain(accessSecret);
    expect(localStorage.length).toBe(0);
    expect(sessionStorage.length).toBe(0);
  });

  it('should trim a pasted secret before verifying it', () => {
    writeSecret(`  ${accessSecret}\n`);
    submitSecret();

    const request = httpTesting.expectOne(grantEndpoint);

    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ accessSecret });
    expect(request.request.urlWithParams).toBe(grantEndpoint);

    request.flush(null, { status: 204, statusText: 'No Content' });
    resolveReport();
  });

  it('should show the same safe failure when the secret is not accepted', () => {
    writeSecret(accessSecret);
    submitSecret();

    httpTesting.expectOne(grantEndpoint).flush(
      {
        type: 'urn:convive:problem:report-access-denied',
        title: 'Report access denied',
        status: 401,
        detail: 'Internal information that must not be displayed.',
      },
      { status: 401, statusText: 'Unauthorized' },
    );
    fixture.detectChanges();

    expect(page.querySelector('.access-error')?.textContent).toContain(
      'Este secreto no permite acceder a ninguna comunicación.',
    );
    expect(page.textContent).not.toContain('Internal information');
    expect(page.querySelector('#accessSecret')).not.toBeNull();
  });

  it('should explain that too many attempts were made', () => {
    writeSecret(accessSecret);
    submitSecret();

    httpTesting
      .expectOne(grantEndpoint)
      .flush(
        { title: 'Too many requests', status: 429 },
        { status: 429, statusText: 'Too Many Requests' },
      );
    fixture.detectChanges();

    expect(page.querySelector('.access-error')?.textContent).toContain(
      'Has hecho demasiados intentos seguidos.',
    );
  });

  it('should announce that the report is being opened', () => {
    writeSecret(accessSecret);
    submitSecret();

    httpTesting.expectOne(grantEndpoint).flush(null, { status: 204, statusText: 'No Content' });
    fixture.detectChanges();

    expect(page.querySelector('[role="status"]')?.textContent).toContain(
      'Abriendo tu comunicación',
    );
    expect(page.querySelector('#accessSecret')).toBeNull();

    resolveReport();
  });

  it('should allow retrying when the report cannot be loaded', () => {
    writeSecret(accessSecret);
    submitSecret();

    httpTesting.expectOne(grantEndpoint).flush(null, { status: 204, statusText: 'No Content' });
    httpTesting
      .expectOne(reportEndpoint)
      .error(new ProgressEvent('error'), { status: 0, statusText: 'Network Error' });
    fixture.detectChanges();

    expect(page.querySelector('#unavailable-title')?.textContent).toContain(
      'No podemos mostrar tu comunicación',
    );

    page.querySelector<HTMLButtonElement>('.status-card button')?.click();
    fixture.detectChanges();
    resolveReport();

    expect(page.querySelector('#step-title')?.textContent).toContain('Tu comunicación');
  });

  it('should ask for the secret again when access expires while loading the report', () => {
    writeSecret(accessSecret);
    submitSecret();

    httpTesting.expectOne(grantEndpoint).flush(null, { status: 204, statusText: 'No Content' });
    httpTesting.expectOne(reportEndpoint).flush(
      {
        type: 'urn:convive:problem:report-access-capability-rejected',
        title: 'Report access capability rejected',
        status: 401,
      },
      { status: 401, statusText: 'Unauthorized' },
    );
    fixture.detectChanges();

    expect(page.querySelector('.notice')?.textContent).toContain(
      'El acceso ha caducado por seguridad.',
    );
    expect(page.querySelector('#accessSecret')).not.toBeNull();
    expect(page.querySelector('.status-card')).toBeNull();
  });

  it('should lock the report when attachment access expires', () => {
    writeSecret(accessSecret);
    submitSecret();

    httpTesting.expectOne(grantEndpoint).flush(null, { status: 204, statusText: 'No Content' });
    httpTesting.expectOne(reportEndpoint).flush(reportState());
    fixture.detectChanges();

    httpTesting.expectOne(attachmentEndpoint).flush(
      {
        type: 'urn:convive:problem:report-access-capability-rejected',
        title: 'Report access capability rejected',
        status: 401,
      },
      { status: 401, statusText: 'Unauthorized' },
    );
    fixture.detectChanges();

    expect(page.querySelector('.notice')?.textContent).toContain(
      'El acceso ha caducado por seguridad.',
    );
    expect(page.querySelector('app-follow-up-report')).toBeNull();
  });

  it('should append information to the same report', () => {
    unlockReport();

    writeFollowUpContent('He recordado un detalle más.');
    submitFollowUpContent();

    const request = httpTesting.expectOne(entriesEndpoint);

    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ content: 'He recordado un detalle más.' });

    request.flush(reporterEntry('He recordado un detalle más.'), {
      status: 201,
      statusText: 'Created',
    });
    fixture.detectChanges();

    expect(page.querySelector('.entry-confirmation')?.textContent).toContain(
      'Hemos añadido tu información a la comunicación.',
    );
    expect(page.querySelectorAll('.history-list li').length).toBe(2);
    expect(page.textContent).toContain('He recordado un detalle más.');
    expect(followUpContentField().value).toBe('');
  });

  it('should not submit empty information', () => {
    unlockReport();

    writeFollowUpContent('   ');
    submitFollowUpContent();

    expect(page.querySelector('#content-error')?.textContent).toContain(
      'Escribe la información que quieres añadir.',
    );
    httpTesting.expectNone(entriesEndpoint);
  });

  it('should show a safe error when the added information is rejected', () => {
    unlockReport();

    writeFollowUpContent('He recordado un detalle más.');
    submitFollowUpContent();

    httpTesting.expectOne(entriesEndpoint).flush(
      {
        type: 'urn:convive:problem:invalid-follow-up-entry',
        title: 'Invalid follow-up entry',
        status: 422,
        detail: 'Internal validation information that must not be displayed.',
      },
      { status: 422, statusText: 'Unprocessable Content' },
    );
    fixture.detectChanges();

    expect(page.querySelector('.submission-error')?.textContent).toContain(
      'Este texto no es válido.',
    );
    expect(page.textContent).not.toContain('Internal validation information');
    expect(followUpContentField().value).toBe('He recordado un detalle más.');
  });

  it('should ask for the secret again when the access expires while adding information', () => {
    unlockReport();

    writeFollowUpContent('He recordado un detalle más.');
    submitFollowUpContent();

    httpTesting.expectOne(entriesEndpoint).flush(
      {
        type: 'urn:convive:problem:report-access-capability-rejected',
        title: 'Report access capability rejected',
        status: 401,
      },
      { status: 401, statusText: 'Unauthorized' },
    );
    fixture.detectChanges();

    expect(page.querySelector('.notice')?.textContent).toContain(
      'El acceso ha caducado por seguridad.',
    );
    expect(page.querySelector('#accessSecret')).not.toBeNull();
    expect(page.querySelector('app-follow-up-report')).toBeNull();
    expect(page.textContent).not.toContain('Una situación ocurrió durante el recreo.');
  });

  it('should close the access and stop showing the report', () => {
    unlockReport();

    page.querySelector<HTMLButtonElement>('app-report-header .close-access')?.click();
    fixture.detectChanges();

    const request = httpTesting.expectOne(revocationEndpoint);

    expect(request.request.method).toBe('DELETE');

    request.flush(null, { status: 204, statusText: 'No Content' });
    fixture.detectChanges();

    expect(page.querySelector('.notice')?.textContent).toContain('Has cerrado el acceso.');
    expect(page.querySelector('app-follow-up-report')).toBeNull();
    expect(page.textContent).not.toContain('Una situación ocurrió durante el recreo.');
    expect(secretField().value).toBe('');
  });

  it('should report that the access could not be closed', () => {
    unlockReport();

    page.querySelector<HTMLButtonElement>('app-report-header .close-access')?.click();
    fixture.detectChanges();

    httpTesting
      .expectOne(revocationEndpoint)
      .error(new ProgressEvent('error'), { status: 0, statusText: 'Network Error' });
    fixture.detectChanges();

    expect(page.querySelector('.close-error-banner')?.textContent).toContain(
      'No hemos podido cerrar el acceso ahora.',
    );
    expect(page.querySelector('app-follow-up-report')).not.toBeNull();
  });

  function reportState(): ReportFollowUpState {
    return {
      publicReference: 'CNV-2026-0001',
      situationDescription: 'Una situación ocurrió durante el recreo.',
      situationContext: 'in_person',
      status: 'received',
      createdAt: '2026-08-09T12:00:00.000+00:00',
      followUpEntries: [
        {
          authorType: 'professional',
          content: 'Ya lo estamos revisando.',
          createdAt: '2026-08-09T13:00:00.000+00:00',
        },
      ],
    };
  }

  function reporterEntry(content: string): FollowUpEntry {
    return {
      authorType: 'reporter',
      content,
      createdAt: '2026-08-09T14:00:00.000+00:00',
    };
  }

  function unlockReport(): void {
    writeSecret(accessSecret);
    submitSecret();

    httpTesting.expectOne(grantEndpoint).flush(null, { status: 204, statusText: 'No Content' });
    resolveReport();
  }

  function resolveReport(): void {
    const request = httpTesting.expectOne(reportEndpoint);

    expect(request.request.method).toBe('GET');

    request.flush(reportState());
    fixture.detectChanges();

    const attachments = httpTesting.expectOne(attachmentEndpoint);

    expect(attachments.request.method).toBe('GET');
    attachments.flush({ items: [] });
    fixture.detectChanges();
  }

  function secretField(): HTMLInputElement {
    const field = page.querySelector<HTMLInputElement>('#accessSecret');

    if (!field) {
      throw new Error('The access secret field was not found.');
    }

    return field;
  }

  function visibilityToggle(): HTMLButtonElement {
    const button = page.querySelector<HTMLButtonElement>('button.visibility');

    if (!button) {
      throw new Error('The secret visibility button was not found.');
    }

    return button;
  }

  function writeSecret(value: string): void {
    const field = secretField();

    field.value = value;
    field.dispatchEvent(new Event('input'));
    fixture.detectChanges();
  }

  function submitSecret(): void {
    const button = page.querySelector<HTMLButtonElement>(
      'app-follow-up-access button[type=submit]',
    );

    if (!button) {
      throw new Error('The access submit button was not found.');
    }

    button.click();
    fixture.detectChanges();
  }

  function followUpContentField(): HTMLTextAreaElement {
    const field = page.querySelector<HTMLTextAreaElement>('#followUpContent');

    if (!field) {
      throw new Error('The follow-up content field was not found.');
    }

    return field;
  }

  function writeFollowUpContent(value: string): void {
    const field = followUpContentField();

    field.value = value;
    field.dispatchEvent(new Event('input'));
    fixture.detectChanges();
  }

  function submitFollowUpContent(): void {
    const button = page.querySelector<HTMLButtonElement>('.add-information button[type=submit]');

    if (!button) {
      throw new Error('The add information button was not found.');
    }

    button.click();
    fixture.detectChanges();
  }
});
