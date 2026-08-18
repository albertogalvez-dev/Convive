import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReporterEmailNotifications } from './reporter-email-notifications';

describe('ReporterEmailNotifications', () => {
  const endpoint = '/api/v1/reporter/report/email-notifications';
  let fixture: ComponentFixture<ReporterEmailNotifications>;
  let page: HTMLElement;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ReporterEmailNotifications],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    http = TestBed.inject(HttpTestingController);
    fixture = TestBed.createComponent(ReporterEmailNotifications);
    fixture.detectChanges();
    page = fixture.nativeElement as HTMLElement;
  });

  afterEach(() => http.verify());

  it('stays absent when the environment has no delivery provider', () => {
    http.expectOne(endpoint).flush({ enabled: false, status: 'none' });
    fixture.detectChanges();

    expect(page.querySelector('section')).toBeNull();
  });

  it('requires explicit consent and submits no report information', () => {
    http.expectOne(endpoint).flush({ enabled: true, status: 'none' });
    fixture.detectChanges();

    writeEmail('reporter@example.test');
    page.querySelector<HTMLButtonElement>('button[type=submit]')?.click();
    fixture.detectChanges();
    http.expectNone((request) => request.method === 'PUT' && request.url === endpoint);

    const consent = page.querySelector<HTMLInputElement>('input[type=checkbox]');
    consent?.click();
    fixture.detectChanges();
    page.querySelector<HTMLButtonElement>('button[type=submit]')?.click();

    const request = http.expectOne(endpoint);
    expect(request.request.method).toBe('PUT');
    expect(request.request.body).toEqual({
      email: 'reporter@example.test',
      consentAccepted: true,
    });
    expect(JSON.stringify(request.request.body)).not.toContain('reference');
    expect(JSON.stringify(request.request.body)).not.toContain('secret');
    request.flush({ enabled: true, status: 'pending' }, { status: 202, statusText: 'Accepted' });
    fixture.detectChanges();

    expect(page.textContent).toContain('Revisa tu correo');
    expect(page.textContent).not.toContain('reporter@example.test');
  });

  it('removes a verified contact immediately', () => {
    http.expectOne(endpoint).flush({ enabled: true, status: 'verified' });
    fixture.detectChanges();

    page.querySelector<HTMLButtonElement>('button.secondary')?.click();
    const request = http.expectOne(endpoint);
    expect(request.request.method).toBe('DELETE');
    request.flush(null, { status: 204, statusText: 'No Content' });
    fixture.detectChanges();

    expect(page.textContent).toContain('Ya no recibir');
    expect(page.querySelector('input[type=email]')).not.toBeNull();
  });

  function writeEmail(value: string): void {
    const input = page.querySelector<HTMLInputElement>('input[type=email]');
    if (input === null) {
      throw new Error('Email field was not rendered.');
    }
    input.value = value;
    input.dispatchEvent(new Event('input'));
    fixture.detectChanges();
  }
});
