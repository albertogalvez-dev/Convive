import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { vi } from 'vitest';

import { ProfessionalAccess } from './professional-access';

describe('ProfessionalAccess', () => {
  const endpoint = '/api/v1/professional/session';
  let fixture: ComponentFixture<ProfessionalAccess>;
  let page: HTMLElement;
  let httpTesting: HttpTestingController;
  let navigate: ReturnType<typeof vi.fn>;

  beforeEach(async () => {
    navigate = vi.fn().mockResolvedValue(true);
    await TestBed.configureTestingModule({
      imports: [ProfessionalAccess],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: Router, useValue: { navigate } },
      ],
    }).compileComponents();
    httpTesting = TestBed.inject(HttpTestingController);
    fixture = TestBed.createComponent(ProfessionalAccess);
    fixture.detectChanges();
    page = fixture.nativeElement as HTMLElement;
    httpTesting.expectOne(endpoint).flush(null, { status: 401, statusText: 'Unauthorized' });
    fixture.detectChanges();
  });

  afterEach(() => httpTesting.verify());

  it('renders an accessible professional login after checking the session', () => {
    expect(page.querySelector('h1')?.textContent).toContain('Tu espacio profesional');
    expect(page.querySelector('#professional-email')?.getAttribute('autocomplete')).toBe(
      'username',
    );
    expect(page.querySelector('#professional-password')?.getAttribute('autocomplete')).toBe(
      'current-password',
    );
  });

  it('sends an existing session to the inbox', () => {
    fixture.destroy();
    fixture = TestBed.createComponent(ProfessionalAccess);
    fixture.detectChanges();
    httpTesting.expectOne(endpoint).flush({ professional: identity() });
    expect(navigate).toHaveBeenCalledWith(['/profesionales']);
  });

  it('validates both fields before authentication', () => {
    submit();
    expect(page.querySelector('#email-error')).not.toBeNull();
    expect(page.querySelector('#password-error')).not.toBeNull();
    httpTesting.expectNone(endpoint);
  });

  it('normalises the email, authenticates and opens the inbox', () => {
    write('#professional-email', '  Alex.Rivera@Example.com  ');
    write('#professional-password', 'a long fictional password');
    submit();
    const request = httpTesting.expectOne(endpoint);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({
      email: 'alex.rivera@example.com',
      password: 'a long fictional password',
    });
    request.flush({ professional: identity() });
    expect(navigate).toHaveBeenCalledWith(['/profesionales']);
  });

  it('shows a safe authentication failure and clears the password', () => {
    write('#professional-email', 'alex@example.com');
    write('#professional-password', 'wrong password');
    submit();
    httpTesting
      .expectOne(endpoint)
      .flush({ detail: 'Private detail.' }, { status: 401, statusText: 'Unauthorized' });
    fixture.detectChanges();
    expect(page.querySelector('[role="alert"]')?.textContent).toContain(
      'El correo o la contrase\u00f1a no son correctos.',
    );
    expect(input('#professional-password').value).toBe('');
    expect(page.textContent).not.toContain('Private detail');
  });

  function submit(): void {
    page.querySelector<HTMLFormElement>('form')?.dispatchEvent(new Event('submit'));
    fixture.detectChanges();
  }
  function write(selector: string, value: string): void {
    const field = input(selector);
    field.value = value;
    field.dispatchEvent(new Event('input'));
    fixture.detectChanges();
  }
  function input(selector: string): HTMLInputElement {
    const field = page.querySelector<HTMLInputElement>(selector);
    if (!field) throw new Error(`Missing field: ${selector}`);
    return field;
  }
  function identity() {
    return {
      id: '0192a5c0-3333-7000-8000-000000000030',
      name: 'Alex Rivera',
      email: 'alex.rivera@example.com',
    };
  }
});
