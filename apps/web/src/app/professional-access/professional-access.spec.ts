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

  it('renders one accessible selector for every real demonstration perspective', () => {
    expect(page.querySelector('h1')?.textContent).toContain('Tu espacio profesional');
    const selector = page.querySelector<HTMLSelectElement>('#demo-role');

    expect(selector?.labels?.[0]?.textContent).toContain('Perspectiva de demostración');
    expect(selector?.options).toHaveLength(2);
    expect(selector?.value).toBe('triage');
    expect(Array.from(selector?.options ?? [], (option) => option.text)).toEqual([
      'Gestión de casos',
      'Administración',
    ]);
    expect(page.querySelectorAll('.demo-role')).toHaveLength(0);
    expect(page.querySelector<HTMLAnchorElement>('.wordmark')?.getAttribute('href')).toBe(
      'https://conviveaula.com/',
    );
    expect(page.querySelector('.wordmark')?.getAttribute('aria-label')).toBe(
      'Convive, inicio público',
    );
    expect(page.querySelector('form')).toBeNull();
  });

  it('opens the real read-only workspace for the selected organisation role', () => {
    const selector = page.querySelector<HTMLSelectElement>('#demo-role');
    selector!.value = 'administrator';
    selector!.dispatchEvent(new Event('change'));
    fixture.detectChanges();

    page.querySelector<HTMLButtonElement>('.demo-entry-button')?.click();

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
