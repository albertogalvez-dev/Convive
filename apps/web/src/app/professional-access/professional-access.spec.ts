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
  let navigateByUrl: ReturnType<typeof vi.fn>;

  beforeEach(async () => {
    navigateByUrl = vi.fn().mockResolvedValue(true);
    await TestBed.configureTestingModule({
      imports: [ProfessionalAccess],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: Router, useValue: { navigateByUrl } },
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

  it('renders an accessible selector for every real demonstration perspective', () => {
    expect(page.querySelector('h1')?.textContent).toContain('Tu espacio profesional');
    expect(page.querySelectorAll('.demo-role')).toHaveLength(5);
    expect(page.textContent).toContain('Profesional de bienestar');
    expect(page.textContent).toContain('Responsable de caso');
    expect(page.textContent).toContain('Colaborador de caso');
    expect(page.textContent).toContain('Observador de caso');
    expect(page.querySelector('form')).toBeNull();
  });

  it('opens the real read-only workspace for the selected organisation role', () => {
    const role = Array.from(page.querySelectorAll<HTMLButtonElement>('.demo-role')).find((button) =>
      button.textContent?.includes('Administración'),
    );

    role?.click();

    const request = httpTesting.expectOne('/api/v1/demo/professional-session');
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ role: 'administrator' });
    request.flush({ professional: identity(), demonstrationRole: 'administrator' });
    expect(navigateByUrl).toHaveBeenCalledWith('/profesionales/cuentas');
  });

  it('sends an existing session to the inbox', () => {
    fixture.destroy();
    fixture = TestBed.createComponent(ProfessionalAccess);
    fixture.detectChanges();
    httpTesting.expectOne(endpoint).flush({ professional: identity(), demonstrationRole: null });
    expect(navigateByUrl).toHaveBeenCalledWith('/profesionales');
  });
  function identity() {
    return {
      id: '0192a5c0-3333-7000-8000-000000000030',
      name: 'Alex Rivera',
      email: 'alex.rivera@example.com',
    };
  }
});
