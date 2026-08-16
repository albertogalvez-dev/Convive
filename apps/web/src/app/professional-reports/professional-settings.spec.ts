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
  const flushProfile = async (): Promise<void> => {
    http.expectOne('/api/v1/professional/profile').flush(profile);
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

  it('warns that changing the email ends the session, and signs out when it does', async () => {
    await flushProfile();

    expect(page.querySelector('#email-hint')?.textContent).toContain('se cerrará tu sesión');
    expect(page.querySelector('input[name=email]')?.getAttribute('aria-describedby')).toBe(
      'email-hint',
    );

    const navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
    page.querySelector<HTMLFormElement>('form')?.dispatchEvent(new Event('submit'));
    http
      .expectOne('/api/v1/professional/profile')
      .flush({ ...profile, email: 'nuevo@example.com', sessionEnded: true });
    fixture.detectChanges();

    expect(TestBed.inject(ProfessionalSessionService).professional()).toBeNull();
    expect(navigate).toHaveBeenCalledWith(['/profesionales/acceso']);
  });

  it('explains a duplicate email without losing what was typed', async () => {
    await flushProfile();

    page.querySelector<HTMLFormElement>('form')?.dispatchEvent(new Event('submit'));
    http
      .expectOne('/api/v1/professional/profile')
      .flush(null, { status: 409, statusText: 'Conflict' });
    fixture.detectChanges();

    expect(page.querySelector('[role=alert]')?.textContent).toContain(
      'Ese correo ya pertenece a otra cuenta',
    );
    expect(page.querySelector<HTMLInputElement>('input[name=email]')?.value).toBe(
      'laura@example.com',
    );
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
