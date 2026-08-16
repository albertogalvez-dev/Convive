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

  const flushAdministration = (continuity: unknown[] = [], accounts: unknown[] = []): void => {
    http
      .expectOne('/api/v1/professional/account-administration')
      .flush({ items: [{ id: organisationId, name: 'IES Ficticio' }] });
    http.expectOne(`/api/v1/professional/organisations/${organisationId}/accounts`).flush({
      items: accounts,
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

  describe('correcting a mistyped email address', () => {
    const account = {
      id: 'professional-1',
      name: 'Laura Martín',
      email: 'laura@ejemplo.invalid',
      status: 'active',
      role: 'triage',
    };

    const openCorrectionForm = async (): Promise<HTMLInputElement> => {
      flushAdministration([], [account]);
      const trigger = Array.from(page.querySelectorAll('button')).find(
        (button) => button.textContent?.trim() === 'Corregir correo',
      );
      trigger?.click();
      fixture.detectChanges();
      // ngModel writes the initial value back to the DOM asynchronously.
      await fixture.whenStable();
      fixture.detectChanges();

      const field = page.querySelector<HTMLInputElement>('.correction input');
      expect(field).not.toBeNull();

      return field as HTMLInputElement;
    };

    const submitWith = (field: HTMLInputElement, email: string): void => {
      field.value = email;
      field.dispatchEvent(new Event('input'));
      fixture.detectChanges();
      page.querySelector('.correction')?.dispatchEvent(new Event('submit'));
    };

    it('warns before the correction that it signs the professional out', async () => {
      const field = await openCorrectionForm();

      expect(page.querySelector('.correction label')?.textContent).toContain('Laura Martín');
      expect(page.querySelector('.correction .hint')?.textContent).toContain(
        'sale de sus sesiones',
      );
      // The field starts from the address currently on file, so a correction is
      // an edit of what is there rather than a retype from nothing.
      expect(field.value).toBe('laura@ejemplo.invalid');
    });

    it('confirms the session effect once the address is corrected', async () => {
      const field = await openCorrectionForm();

      submitWith(field, 'laura.martin@ejemplo.invalid');
      const request = http.expectOne(
        `/api/v1/professional/organisations/${organisationId}/accounts/professional-1/email`,
      );
      expect(request.request.method).toBe('PATCH');
      expect(request.request.body).toEqual({ email: 'laura.martin@ejemplo.invalid' });
      request.flush({ ...account, email: 'laura.martin@ejemplo.invalid', sessionEnded: true });
      http.expectOne(`/api/v1/professional/organisations/${organisationId}/accounts`).flush({
        items: [{ ...account, email: 'laura.martin@ejemplo.invalid' }],
      });
      http
        .expectOne(`/api/v1/professional/organisations/${organisationId}/memberships`)
        .flush({ items: [] });
      http
        .expectOne(`/api/v1/professional/organisations/${organisationId}/case-continuity`)
        .flush({ items: [] });
      fixture.detectChanges();

      expect(page.querySelector('.feedback')?.textContent).toContain('ha salido de sus sesiones');
      expect(page.querySelector('.correction')).toBeNull();
    });

    it('says plainly that the address belongs to someone else instead of failing vaguely', async () => {
      const field = await openCorrectionForm();

      submitWith(field, 'ocupada@ejemplo.invalid');
      http
        .expectOne(
          `/api/v1/professional/organisations/${organisationId}/accounts/professional-1/email`,
        )
        .flush({}, { status: 409, statusText: 'Conflict' });
      fixture.detectChanges();

      expect(page.querySelector('.error')?.textContent).toContain('pertenece a otra cuenta');
    });
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
