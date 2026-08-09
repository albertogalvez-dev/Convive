import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, Router } from '@angular/router';
import { vi } from 'vitest';

import { ProfessionalDetail } from './professional-detail';

describe('ProfessionalDetail', () => {
  const endpoint = '/api/v1/professional/reports/report-1';
  let fixture: ComponentFixture<ProfessionalDetail>;
  let page: HTMLElement;
  let http: HttpTestingController;
  let navigate: ReturnType<typeof vi.fn>;

  beforeEach(async () => {
    navigate = vi.fn().mockResolvedValue(true);
    await TestBed.configureTestingModule({
      imports: [ProfessionalDetail],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        {
          provide: ActivatedRoute,
          useValue: { snapshot: { paramMap: { get: () => 'report-1' } } },
        },
        { provide: Router, useValue: { navigate } },
      ],
    }).compileComponents();
    fixture = TestBed.createComponent(ProfessionalDetail);
    page = fixture.nativeElement as HTMLElement;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });
  afterEach(() => http.verify());

  it('renders original content and reporter-visible history', () => {
    http.expectOne(endpoint).flush(detail());
    fixture.detectChanges();
    expect(page.textContent).toContain('Fictional original report');
    expect(page.textContent).toContain('Fictional follow-up');
    expect(page.textContent).toContain('Persona informante');
    expect(page.textContent).not.toContain('secret');
  });

  it('validates and records the initial review', () => {
    http.expectOne(endpoint).flush(detail());
    fixture.detectChanges();
    const textarea = page.querySelector<HTMLTextAreaElement>('#review-reason')!;
    textarea.value = 'Reviewed under the fictional safeguarding protocol.';
    textarea.dispatchEvent(new Event('input'));
    fixture.detectChanges();
    page.querySelector<HTMLFormElement>('.review-card form')?.dispatchEvent(new Event('submit'));
    const request = http.expectOne(`${endpoint}/reviews`);
    expect(request.request.body).toEqual({ reason: textarea.value });
    request.flush({
      review: { reason: textarea.value, reviewedAt: '2026-08-09T19:00:00+00:00' },
    });
    fixture.detectChanges();
    expect(page.textContent).toContain('Revisi\u00f3n registrada');
    expect(page.querySelector('.review-card form')).toBeNull();
  });

  it('publishes one reporter-visible response and appends it to history', async () => {
    http.expectOne(endpoint).flush(detail());
    fixture.detectChanges();
    const textarea = page.querySelector<HTMLTextAreaElement>('#professional-response')!;
    const form = page.querySelector<HTMLFormElement>('.response-card form')!;
    textarea.value = 'A fictional response visible to the reporter.';
    textarea.dispatchEvent(new Event('input'));
    form.dispatchEvent(new Event('submit'));
    form.dispatchEvent(new Event('submit'));
    fixture.detectChanges();

    const request = http.expectOne(`${endpoint}/responses`);
    expect(request.request.body).toEqual({ content: textarea.value });
    expect(page.querySelector<HTMLButtonElement>('.response-card button')?.disabled).toBe(true);
    request.flush(
      {
        authorType: 'professional',
        content: textarea.value,
        createdAt: '2026-08-09T19:05:00+00:00',
      },
      { status: 201, statusText: 'Created' },
    );
    fixture.detectChanges();
    await fixture.whenStable();

    expect(page.textContent).toContain('Respuesta del centro');
    expect(page.textContent).toContain('A fictional response visible to the reporter.');
    expect(page.textContent).toContain('Respuesta enviada.');
    expect(textarea.value).toBe('');
    expect(document.activeElement).toBe(textarea);
  });

  it('keeps the draft and shows a safe response failure', () => {
    http.expectOne(endpoint).flush(detail());
    fixture.detectChanges();
    const textarea = page.querySelector<HTMLTextAreaElement>('#professional-response')!;
    textarea.value = 'A response that remains available for retry.';
    textarea.dispatchEvent(new Event('input'));
    page.querySelector<HTMLFormElement>('.response-card form')?.dispatchEvent(new Event('submit'));

    http
      .expectOne(`${endpoint}/responses`)
      .flush(
        { detail: 'Internal information that must not be rendered.' },
        { status: 500, statusText: 'Server Error' },
      );
    fixture.detectChanges();

    expect(page.querySelector('.response-card [role="alert"]')?.textContent).toContain(
      'No hemos podido enviar la respuesta.',
    );
    expect(page.textContent).not.toContain('Internal information');
    expect(textarea.value).toBe('A response that remains available for retry.');
  });

  it('renders the indistinguishable unavailable state', () => {
    http.expectOne(endpoint).flush(null, { status: 404, statusText: 'Not Found' });
    fixture.detectChanges();
    expect(page.querySelector('[role="alert"]')?.textContent).toContain('no est\u00e1 disponible');
  });

  it('redirects after session expiry', () => {
    http.expectOne(endpoint).flush(null, { status: 401, statusText: 'Unauthorized' });
    expect(navigate).toHaveBeenCalledWith(['/profesionales/acceso']);
  });

  function detail() {
    return {
      id: 'report-1',
      publicReference: 'ABC123',
      situationContext: 'bullying',
      status: 'new',
      createdAt: '2026-08-09T18:00:00+00:00',
      situationDescription: 'Fictional original report',
      review: null,
      followUpEntries: [
        {
          authorType: 'reporter',
          content: 'Fictional follow-up',
          createdAt: '2026-08-09T18:30:00+00:00',
        },
      ],
    };
  }
});
