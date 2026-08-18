import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { ProfessionalCredentialActivation } from './professional-credential-activation';

describe('ProfessionalCredentialActivation', () => {
  let fixture: ComponentFixture<ProfessionalCredentialActivation>;
  let page: HTMLElement;
  let httpTesting: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProfessionalCredentialActivation],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();
    httpTesting = TestBed.inject(HttpTestingController);
    fixture = TestBed.createComponent(ProfessionalCredentialActivation);
    fixture.detectChanges();
    page = fixture.nativeElement as HTMLElement;
  });

  afterEach(() => httpTesting.verify());

  it('requires a one-time code and a 15-character password', () => {
    submit();
    expect(httpTesting.match('/api/v1/professional/account-credentials/accept')).toHaveLength(0);
    expect(page.querySelector('#credential-secret')?.getAttribute('autocomplete')).toBe(
      'one-time-code',
    );
    expect(page.querySelector('#new-password')?.getAttribute('autocomplete')).toBe('new-password');
  });

  it('does not retain the credential after successful activation', () => {
    write('#credential-secret', 'a'.repeat(64));
    write('#new-password', 'fictional secure password');
    submit();
    const request = httpTesting.expectOne('/api/v1/professional/account-credentials/accept');
    expect(request.request.body).toEqual({
      secret: 'a'.repeat(64),
      password: 'fictional secure password',
    });
    request.flush(null);
    fixture.detectChanges();
    expect(page.textContent).toContain('Ya puedes acceder');
    expect(page.querySelector('#credential-secret')).toBeNull();
  });

  function submit(): void {
    page.querySelector<HTMLFormElement>('form')?.dispatchEvent(new Event('submit'));
    fixture.detectChanges();
  }

  function write(selector: string, value: string): void {
    const field = page.querySelector<HTMLInputElement>(selector);
    if (!field) throw new Error(`Missing ${selector}`);
    field.value = value;
    field.dispatchEvent(new Event('input'));
    fixture.detectChanges();
  }
});
