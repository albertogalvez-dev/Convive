import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { vi } from 'vitest';

import { ProfessionalSessionService } from '../professional-access/professional-session.service';
import { ProfessionalSettings } from './professional-settings';

describe('ProfessionalSettings', () => {
  let fixture: ComponentFixture<ProfessionalSettings>;
  let page: HTMLElement;
  let http: HttpTestingController;

  const profile = {
    id: 'professional-1',
    name: 'Laura Martin',
    email: 'laura@example.com',
    memberships: [
      {
        organisation: { id: 'organisation-1', name: 'IES Ficticio' },
        role: 'triage' as const,
        managedByAdministrator: true,
      },
    ],
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProfessionalSettings],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();
    TestBed.inject(ProfessionalSessionService).professional.set({
      id: 'professional-1',
      name: 'Laura Martin',
      email: 'laura@example.com',
    });
    fixture = TestBed.createComponent(ProfessionalSettings);
    page = fixture.nativeElement as HTMLElement;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });

  afterEach(() => http.verify());

  // ngModel writes to the DOM asynchronously, so the fixture has to settle
  // before an input's value reflects the loaded profile.
  const flushProfile = async (absences: unknown[] = []): Promise<void> => {
    http.expectOne('/api/v1/professional/profile').flush(profile);
    http.expectOne('/api/v1/professional/absences').flush({ items: absences });
    fixture.detectChanges();
    await fixture.whenStable();
    fixture.detectChanges();
  };

  it('states that the centre and role are administrator-controlled', async () => {
    await flushProfile();

    expect(page.textContent).toContain('IES Ficticio');
    expect(page.textContent).toContain('Gestionado por dirección');
    expect(page.textContent).toContain('los gestiona la dirección del centro');
    // Nothing in the page offers to change role or organisation.
    expect(page.querySelector('select[name=role]')).toBeNull();
  });

  it('saves a corrected name and keeps the session', async () => {
    await flushProfile();

    const name = page.querySelector<HTMLInputElement>('input[name=name]');
    expect(name?.value).toBe('Laura Martin');
    name!.value = 'Laura Martín Ruiz';
    name!.dispatchEvent(new Event('input'));
    fixture.detectChanges();
    page.querySelector<HTMLFormElement>('form')?.dispatchEvent(new Event('submit'));

    const request = http.expectOne('/api/v1/professional/profile');
    expect(request.request.method).toBe('PATCH');
    expect(request.request.body).toEqual({
      name: 'Laura Martín Ruiz',
      email: 'laura@example.com',
    });
    request.flush({ ...profile, name: 'Laura Martín Ruiz', sessionEnded: false });
    fixture.detectChanges();

    expect(page.querySelector('[role=status]')?.textContent).toContain('Hemos guardado tus datos');
  });

  const typeEmail = (value: string): void => {
    const email = page.querySelector<HTMLInputElement>('input[name=email]');
    email!.value = value;
    email!.dispatchEvent(new Event('input'));
    fixture.detectChanges();
  };

  it('confirms an email change before saving and warns it can lock the account', async () => {
    await flushProfile();

    expect(page.querySelector('#email-hint')?.textContent).toContain('se cerrará tu sesión');
    expect(page.querySelector('input[name=email]')?.getAttribute('aria-describedby')).toBe(
      'email-hint',
    );

    typeEmail('nuevo@example.com');
    page.querySelector<HTMLFormElement>('form')?.dispatchEvent(new Event('submit'));
    fixture.detectChanges();

    // Nothing is saved until the professional confirms.
    http.expectNone('/api/v1/professional/profile');
    const dialog = page.querySelector('[role=alertdialog]');
    expect(dialog?.textContent).toContain('nuevo@example.com');
    expect(dialog?.textContent).toContain('no podrás volver a entrar por tu cuenta');
    expect(dialog?.textContent).toContain('dirección de tu centro');
  });

  it('restores the previous email when the change is cancelled', async () => {
    await flushProfile();

    typeEmail('equivocado@example.com');
    page.querySelector<HTMLFormElement>('form')?.dispatchEvent(new Event('submit'));
    fixture.detectChanges();
    page.querySelector<HTMLButtonElement>('.confirm-cancel')?.click();
    fixture.detectChanges();
    await fixture.whenStable();
    fixture.detectChanges();

    expect(page.querySelector('[role=alertdialog]')).toBeNull();
    expect(page.querySelector<HTMLInputElement>('input[name=email]')?.value).toBe(
      'laura@example.com',
    );
  });

  it('signs the professional out once the confirmed email change is saved', async () => {
    await flushProfile();
    const navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);

    typeEmail('nuevo@example.com');
    page.querySelector<HTMLFormElement>('form')?.dispatchEvent(new Event('submit'));
    fixture.detectChanges();
    page.querySelectorAll<HTMLButtonElement>('.confirm-actions button')[1].click();
    http
      .expectOne('/api/v1/professional/profile')
      .flush({ ...profile, email: 'nuevo@example.com', sessionEnded: true });
    fixture.detectChanges();

    expect(TestBed.inject(ProfessionalSessionService).professional()).toBeNull();
    expect(navigate).toHaveBeenCalledWith(['/profesionales/acceso']);
  });

  it('explains a duplicate email without losing what was typed', async () => {
    await flushProfile();

    typeEmail('ocupado@example.com');
    page.querySelector<HTMLFormElement>('form')?.dispatchEvent(new Event('submit'));
    fixture.detectChanges();
    page.querySelectorAll<HTMLButtonElement>('.confirm-actions button')[1].click();
    http
      .expectOne('/api/v1/professional/profile')
      .flush(null, { status: 409, statusText: 'Conflict' });
    fixture.detectChanges();
    await fixture.whenStable();
    fixture.detectChanges();

    expect(page.querySelector('[role=alert]')?.textContent).toContain(
      'Ese correo ya pertenece a otra cuenta',
    );
    expect(page.querySelector<HTMLInputElement>('input[name=email]')?.value).toBe(
      'ocupado@example.com',
    );
  });

  it('states that recording an absence transfers nothing, and warns against personal reasons', async () => {
    await flushProfile();

    const card = [...page.querySelectorAll('.settings-card')].find((section) =>
      section.textContent?.includes('Ausencias planificadas'),
    );
    expect(card?.textContent).toContain('no traspasa tus casos ni te quita el acceso');
    expect(card?.querySelector('#absence-note-hint')?.textContent).toContain(
      'No escribas motivos de salud ni datos personales',
    );
  });

  it('records an absence and lists it with a way to cancel', async () => {
    await flushProfile([
      { id: 'absence-1', startsOn: '2026-09-01', endsOn: '2026-09-05', note: null },
    ]);

    expect(page.querySelector('.absences li')?.textContent).toContain('2026-09-01');

    page.querySelector<HTMLButtonElement>('.absences button')?.click();
    const cancelled = http.expectOne('/api/v1/professional/absences/absence-1');
    expect(cancelled.request.method).toBe('DELETE');
    cancelled.flush(null);
    http.expectOne('/api/v1/professional/absences').flush({ items: [] });
    fixture.detectChanges();

    expect(page.querySelector('.absences')).toBeNull();
  });

  it('closes the current session', async () => {
    await flushProfile();
    const navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);

    page.querySelector<HTMLButtonElement>('.session-row button')?.click();
    http.expectOne('/api/v1/professional/session').flush(null);

    expect(TestBed.inject(ProfessionalSessionService).professional()).toBeNull();
    expect(navigate).toHaveBeenCalledWith(['/profesionales/acceso']);
  });
});
