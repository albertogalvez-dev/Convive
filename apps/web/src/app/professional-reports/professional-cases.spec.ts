import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { vi } from 'vitest';

import { ProfessionalCases } from './professional-cases';

describe('ProfessionalCases', () => {
  let fixture: ComponentFixture<ProfessionalCases>;
  let page: HTMLElement;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProfessionalCases],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();
    fixture = TestBed.createComponent(ProfessionalCases);
    page = fixture.nativeElement as HTMLElement;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });

  afterEach(() => http.verify());

  it('renders only API-authorised case summaries and their next action', () => {
    http.expectOne('/api/v1/professional/cases').flush({ items: [summary()] });
    fixture.detectChanges();

    expect(page.textContent).toContain('Caso ABCD1234');
    expect(page.textContent).toContain('Caso ficticio de demostración');
    expect(page.textContent).toContain('1 tarea pendiente');
    expect(page.querySelector<HTMLAnchorElement>('li a')?.getAttribute('href')).toContain(
      '/profesionales/casos/case-ABCD1234',
    );
  });

  it('shows the accessible empty state and redirects after session expiry', () => {
    http.expectOne('/api/v1/professional/cases').flush({ items: [] });
    fixture.detectChanges();
    expect(page.textContent).toContain('No tienes casos asignados');

    const second = TestBed.createComponent(ProfessionalCases);
    second.detectChanges();
    const navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
    http
      .expectOne('/api/v1/professional/cases')
      .flush(null, { status: 401, statusText: 'Unauthorized' });
    expect(navigate).toHaveBeenCalledWith(['/profesionales/acceso']);
  });

  function summary() {
    return {
      id: 'case-ABCD1234',
      status: 'assessment',
      modality: 'mixed',
      createdAt: '2026-08-11T09:00:00+00:00',
      organisationName: 'Caso ficticio de demostración',
      assignmentRole: 'lead',
      pendingTasks: 1,
      overdueTasks: 0,
      nextDueAt: '2026-08-12T09:00:00+00:00',
    };
  }
});
