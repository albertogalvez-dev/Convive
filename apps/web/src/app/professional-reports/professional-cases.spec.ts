import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { vi } from 'vitest';

import { ProfessionalCases } from './professional-cases';
import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import professionalCaseEs from '../../i18n/professional-case/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';

describe('ProfessionalCases', () => {
  interface CaseSummaryFixture {
    id: string;
    status: 'assessment';
    modality: 'mixed';
    createdAt: string;
    organisationName: string;
    assignmentRole: 'lead';
    pendingTasks: number;
    overdueTasks: number;
    nextDueAt: string;
  }

  let fixture: ComponentFixture<ProfessionalCases>;
  let page: HTMLElement;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProfessionalCases, i18nTestingModule({ 'professional-case': professionalCaseEs })],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();
    fixture = TestBed.createComponent(ProfessionalCases);
    page = fixture.nativeElement as HTMLElement;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });

  afterEach(() => http.verify());

  it('renders only API-authorised case summaries and their next action', () => {
    flushInitial([summary()]);
    fixture.detectChanges();

    expect(page.textContent).toContain('Caso ABCD1234');
    expect(page.textContent).toContain('Caso ficticio de demostración');
    expect(page.textContent).toContain('Mixto');
    expect(page.textContent).toContain('Responsable');
    expect(page.textContent).toContain('En valoración');
    expect(page.textContent).not.toContain('professional-case.');
    expect(page.textContent).toContain('1 tarea pendiente');
    expect(page.querySelector<HTMLAnchorElement>('li a')?.getAttribute('href')).toContain(
      '/profesionales/casos/case-ABCD1234',
    );
    expect(page.querySelector<HTMLAnchorElement>('.overview-export')?.getAttribute('href')).toBe(
      '/api/v1/professional/cases/operational-overview/export',
    );
  });

  it('shows the accessible empty state and redirects after session expiry', () => {
    flushInitial([]);
    fixture.detectChanges();
    expect(page.textContent).toContain('No tienes casos asignados');

    const second = TestBed.createComponent(ProfessionalCases);
    second.detectChanges();
    const navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
    http
      .expectOne((request) => request.url === '/api/v1/professional/cases')
      .flush(null, { status: 401, statusText: 'Unauthorized' });
    http.expectOne('/api/v1/professional/cases/operational-summary').flush({
      assigned: 0,
      overdue: 0,
      upcoming: 0,
    });
    expect(navigate).toHaveBeenCalledWith(['/profesionales/acceso']);
  });

  it('explains an empty demonstration list without suggesting a missing permission', () => {
    TestBed.inject(ProfessionalSessionService).demonstrationRole.set('triage');
    flushInitial([]);
    fixture.detectChanges();

    expect(page.textContent).toContain('Aún no hay casos preparados');
    expect(page.textContent).toContain('podrás consultar aquí');
    expect(page.querySelector('.overview-export')).toBeNull();
  });

  it('uses operational views, filters and a continuation cursor without client-side data', () => {
    flushInitial([summary()], { nextCursor: 'next-page' });
    fixture.detectChanges();

    page.querySelector<HTMLButtonElement>('.operational-summary button:nth-child(2)')?.click();
    const overdue = http.expectOne(
      (request) =>
        request.url === '/api/v1/professional/cases' && request.params.get('view') === 'overdue',
    );
    overdue.flush({
      items: [summary({ overdueTasks: 1 })],
      pagination: { limit: 20, nextCursor: 'overdue-next' },
    });
    fixture.detectChanges();

    page.querySelector<HTMLButtonElement>('.load-more button')?.click();
    const next = http.expectOne(
      (request) =>
        request.url === '/api/v1/professional/cases' &&
        request.params.get('view') === 'overdue' &&
        request.params.get('cursor') === 'overdue-next',
    );
    next.flush({
      items: [summary({ id: 'case-SECOND01' })],
      pagination: { limit: 20, nextCursor: null },
    });
    fixture.detectChanges();

    expect(page.textContent).toContain('Caso SECOND01');
  });

  it("sends the note and pending filters and explains that notes are the professional's own", () => {
    flushInitial([summary()]);
    fixture.detectChanges();

    const note = page.querySelector<HTMLInputElement>('input[name=note]');
    expect(note).not.toBeNull();
    expect(page.querySelector('#note-filter-hint')?.textContent).toContain(
      'notas de comunicación que has escrito tú',
    );
    expect(note?.getAttribute('aria-describedby')).toBe('note-filter-hint');

    note!.value = 'pasillo';
    note!.dispatchEvent(new Event('input'));
    const pending = page.querySelector<HTMLInputElement>('input[name=pending]');
    pending!.checked = true;
    pending!.dispatchEvent(new Event('change'));
    fixture.detectChanges();
    page.querySelector<HTMLFormElement>('.case-filters')?.dispatchEvent(new Event('submit'));

    const request = http.expectOne(
      (candidate) =>
        candidate.url === '/api/v1/professional/cases' &&
        candidate.params.get('note') === 'pasillo',
    );
    expect(request.request.params.get('pending')).toBe('true');
    request.flush({ items: [], pagination: { limit: 20, nextCursor: null } });
    fixture.detectChanges();

    // An empty result must read as "nothing matched", never as a hint that
    // matching cases exist somewhere the professional cannot see.
    expect(page.textContent).toContain('No hay casos que coincidan');
  });

  function flushInitial(
    items: CaseSummaryFixture[],
    pagination: { nextCursor: string | null } = { nextCursor: null },
  ) {
    const list = http.expectOne(
      (request) =>
        request.url === '/api/v1/professional/cases' && request.params.get('view') === 'assigned',
    );
    list.flush({ items, pagination: { limit: 20, nextCursor: pagination.nextCursor } });
    http.expectOne('/api/v1/professional/cases/operational-summary').flush({
      assigned: items.length,
      overdue: 0,
      upcoming: 0,
    });
  }

  function summary(overrides: Partial<CaseSummaryFixture> = {}): CaseSummaryFixture {
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
      ...overrides,
    };
  }
});
