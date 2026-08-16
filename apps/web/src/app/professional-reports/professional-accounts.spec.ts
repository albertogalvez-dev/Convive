import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ProfessionalAccounts } from './professional-accounts';

describe('ProfessionalAccounts', () => {
  let fixture: ComponentFixture<ProfessionalAccounts>;
  let page: HTMLElement;
  let http: HttpTestingController;

  const organisationId = 'organisation-1';

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProfessionalAccounts],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();
    fixture = TestBed.createComponent(ProfessionalAccounts);
    page = fixture.nativeElement as HTMLElement;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });

  afterEach(() => http.verify());

  const flushAdministration = (continuity: unknown[] = []): void => {
    http
      .expectOne('/api/v1/professional/account-administration')
      .flush({ items: [{ id: organisationId, name: 'IES Ficticio' }] });
    http.expectOne(`/api/v1/professional/organisations/${organisationId}/accounts`).flush({
      items: [],
    });
    http.expectOne(`/api/v1/professional/organisations/${organisationId}/memberships`).flush({
      items: [],
    });
    http.expectOne(`/api/v1/professional/organisations/${organisationId}/case-continuity`).flush({
      items: continuity,
    });
    fixture.detectChanges();
  };

  it('states that the continuity list is operational and grants no case access', () => {
    flushAdministration();

    const note = page.querySelector('.continuity-note')?.textContent ?? '';
    expect(note).toContain('no una valoración del caso');
    expect(note).toContain('no te da acceso a los casos');
    expect(note).toContain('reasignarlo explícitamente');
    expect(page.textContent).toContain('No hay casos pendientes de una decisión de continuidad');
  });

  it('explains why each case needs a decision without naming any case content', () => {
    flushAdministration([
      {
        caseId: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        status: 'active',
        responsible: { id: 'professional-1', name: 'Laura Martín' },
        reason: 'absent_with_overdue_task',
        earliestOverdueAt: '2026-08-10T09:00:00.000+00:00',
      },
    ]);

    const entry = page.querySelector('.continuity-list li');
    expect(entry?.textContent).toContain('AAAAAAAA');
    expect(entry?.textContent).toContain('Laura Martín');
    expect(entry?.textContent).toContain('ausente y hay tareas fuera de plazo');
    // The wording never presents the signal as a judgement about the case.
    expect(entry?.textContent).not.toContain('riesgo');
    expect(entry?.textContent).not.toContain('grave');
  });
});
