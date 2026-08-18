import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import { ProfessionalShell } from './professional-shell';

describe('ProfessionalShell', () => {
  let fixture: ComponentFixture<ProfessionalShell>;
  let page: HTMLElement;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProfessionalShell],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();
    TestBed.inject(ProfessionalSessionService).professional.set({
      id: 'professional-1',
      name: 'Laura Martin',
      email: 'laura@example.com',
    });
    fixture = TestBed.createComponent(ProfessionalShell);
    page = fixture.nativeElement as HTMLElement;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });

  afterEach(() => http.verify());

  it('shows a compact identity, a real notification count and an accessible collapse action', () => {
    const requests = http.match((request) => request.url === '/api/v1/professional/reports');
    requests
      .find((request) => request.request.params.get('status') === 'new')
      ?.flush(pageOf([summary('new-1', 'new')]));
    requests
      .find((request) => request.request.params.get('status') === 'reviewed')
      ?.flush(pageOf([]));
    http.expectOne('/api/v1/professional/notifications').flush({ items: [], unreadCount: 1 });
    fixture.detectChanges();

    const profile = page.querySelector('.sidebar-profile');
    expect(profile?.textContent).toContain('Laura Martin');
    expect(profile?.textContent).not.toContain('laura@example.com');
    expect(
      page.querySelector<HTMLAnchorElement>('.notification-button')?.getAttribute('href'),
    ).toBe('/profesionales/avisos');
    expect(page.querySelector('.notification-button span')?.textContent).toBe('1');
    expect(page.querySelectorAll('.mobile-header nav a[aria-label]')).toHaveLength(5);
    expect(
      page.querySelector<HTMLAnchorElement>('.mobile-header nav a[href="/profesionales/cuentas"]'),
    )?.not.toBeNull();

    page.querySelector<HTMLButtonElement>('.collapse-button')?.click();
    fixture.detectChanges();
    expect(page.querySelector('.professional-portal')?.classList).toContain('sidebar-collapsed');
    expect(page.querySelector('.collapse-button')?.getAttribute('aria-label')).toBe(
      'Abrir menú lateral',
    );
  });

  function pageOf(items: unknown[]) {
    return { items, pagination: { limit: 50, nextCursor: null } };
  }

  function summary(id: string, status: 'new' | 'reviewed') {
    return {
      id,
      publicReference: id,
      situationPreview: 'Fictional situation',
      situationContext: 'digital',
      status,
      createdAt: '2026-08-09T18:00:00+00:00',
    };
  }
});
