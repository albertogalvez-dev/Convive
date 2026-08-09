import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { vi } from 'vitest';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import { ProfessionalDashboard } from './professional-dashboard';

describe('ProfessionalDashboard', () => {
  let fixture: ComponentFixture<ProfessionalDashboard>;
  let page: HTMLElement;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProfessionalDashboard],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();
    TestBed.inject(ProfessionalSessionService).professional.set({
      id: 'professional-1',
      name: 'Laura Martin',
      email: 'laura@example.com',
    });
    fixture = TestBed.createComponent(ProfessionalDashboard);
    page = fixture.nativeElement as HTMLElement;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });

  afterEach(() => http.verify({ ignoreCancelled: true }));

  it('shows real protected counts and recent activity', () => {
    flushPages([summary('new-1', 'new')], [summary('reviewed-1', 'reviewed')]);
    fixture.detectChanges();
    expect(page.querySelector('h1')?.textContent).toContain('Laura Martin');
    expect(
      [...page.querySelectorAll('.summary-card strong')].map((node) => node.textContent),
    ).toEqual(['1', '1']);
    expect(page.querySelectorAll('.activity-card li')).toHaveLength(2);
    expect(
      page.querySelector<HTMLAnchorElement>('.summary-card.reviewed')?.getAttribute('href'),
    ).toContain('estado=reviewed');
  });

  it('marks a capped metric without claiming an exact total', () => {
    const requests = http.match((request) => request.url === '/api/v1/professional/reports');
    requests
      .find((request) => request.request.params.get('status') === 'new')
      ?.flush({
        items: [summary('new-1', 'new')],
        pagination: { limit: 50, nextCursor: 'opaque' },
      });
    requests
      .find((request) => request.request.params.get('status') === 'reviewed')
      ?.flush(pageOf([]));
    fixture.detectChanges();
    expect(page.querySelector('.summary-card.new strong')?.textContent).toBe('1+');
  });

  it('redirects when the professional session expires', () => {
    const navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
    const requests = http.match((request) => request.url === '/api/v1/professional/reports');
    requests[0].flush(null, { status: 401, statusText: 'Unauthorized' });
    expect(navigate).toHaveBeenCalledWith(['/profesionales/acceso']);
  });

  function flushPages(newItems: unknown[], reviewedItems: unknown[]): void {
    const requests = http.match((request) => request.url === '/api/v1/professional/reports');
    expect(requests.every((request) => request.request.params.get('limit') === '50')).toBe(true);
    requests
      .find((request) => request.request.params.get('status') === 'new')
      ?.flush(pageOf(newItems));
    requests
      .find((request) => request.request.params.get('status') === 'reviewed')
      ?.flush(pageOf(reviewedItems));
  }

  function pageOf(items: unknown[]) {
    return { items, pagination: { limit: 50, nextCursor: null } };
  }
  function summary(id: string, status: 'new' | 'reviewed') {
    return {
      id,
      publicReference: id,
      situationPreview: `Fictional situation ${id}`,
      situationContext: 'digital',
      status,
      createdAt: status === 'new' ? '2026-08-09T18:00:00+00:00' : '2026-08-08T18:00:00+00:00',
    };
  }
});
