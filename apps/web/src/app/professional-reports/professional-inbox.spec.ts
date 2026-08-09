import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { vi } from 'vitest';

import { ProfessionalInbox } from './professional-inbox';

describe('ProfessionalInbox', () => {
  let fixture: ComponentFixture<ProfessionalInbox>;
  let page: HTMLElement;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProfessionalInbox],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();
    fixture = TestBed.createComponent(ProfessionalInbox);
    page = fixture.nativeElement as HTMLElement;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });
  afterEach(() => http.verify());

  it('lists new reports without exposing report content', () => {
    const request = http.expectOne((request) => request.url === '/api/v1/professional/reports');
    expect(request.request.params.get('status')).toBe('new');
    request.flush(
      pageOf([
        {
          id: 'report-1',
          publicReference: 'ABC123',
          situationContext: 'digital',
          status: 'new',
          createdAt: '2026-08-09T18:00:00+00:00',
        },
      ]),
    );
    fixture.detectChanges();
    expect(page.textContent).toContain('Entorno digital');
    expect(page.textContent).toContain('ABC123');
    expect(page.querySelector('a')?.getAttribute('href')).toContain('report-1');
  });

  it('shows the empty reviewed state after changing filter', () => {
    http.expectOne((request) => request.url === '/api/v1/professional/reports').flush(pageOf([]));
    fixture.detectChanges();
    page.querySelectorAll<HTMLButtonElement>('.filters button')[1].click();
    const request = http.expectOne((request) => request.url === '/api/v1/professional/reports');
    expect(request.request.params.get('status')).toBe('reviewed');
    request.flush(pageOf([]));
    fixture.detectChanges();
    expect(page.textContent).toContain('No hay comunicaciones revisadas');
  });

  it('appends the next cursor page', () => {
    http
      .expectOne((request) => request.url === '/api/v1/professional/reports')
      .flush({ ...pageOf([summary('report-1')]), pagination: { limit: 20, nextCursor: 'opaque' } });
    fixture.detectChanges();
    page.querySelector<HTMLButtonElement>('.load-more button')?.click();
    const request = http.expectOne((request) => request.params.get('cursor') === 'opaque');
    request.flush(pageOf([summary('report-2')]));
    fixture.detectChanges();
    expect(page.querySelectorAll('li')).toHaveLength(2);
  });

  it('redirects when the session expires', () => {
    const navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
    http
      .expectOne((request) => request.url === '/api/v1/professional/reports')
      .flush(null, { status: 401, statusText: 'Unauthorized' });
    expect(navigate).toHaveBeenCalledWith(['/profesionales/acceso']);
  });

  function pageOf(items: unknown[]) {
    return { items, pagination: { limit: 20, nextCursor: null } };
  }
  function summary(id: string) {
    return {
      id,
      publicReference: id,
      situationContext: 'other',
      status: 'new',
      createdAt: '2026-08-09T18:00:00+00:00',
    };
  }
});
