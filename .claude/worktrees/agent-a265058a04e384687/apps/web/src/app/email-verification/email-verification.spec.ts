import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EmailVerification } from './email-verification';

describe('EmailVerification', () => {
  let fixture: ComponentFixture<EmailVerification>;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [EmailVerification],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    history.replaceState(null, '', '/');
    http.verify();
  });

  it('removes the fragment before sending the token in the request body', () => {
    const token = 'a'.repeat(64);
    history.replaceState(null, '', `/verificar-correo#token=${token}`);
    fixture = TestBed.createComponent(EmailVerification);
    fixture.detectChanges();

    expect(window.location.hash).toBe('');
    const request = http.expectOne('/api/v1/public/reporter-email-verifications');
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({ token });
    request.flush({ verified: true });
    fixture.detectChanges();

    expect((fixture.nativeElement as HTMLElement).textContent).toContain('Avisos activados');
  });

  it('shows the same safe state for any rejected token', () => {
    history.replaceState(null, '', '/verificar-correo#token=invalid');
    fixture = TestBed.createComponent(EmailVerification);
    fixture.detectChanges();
    http
      .expectOne('/api/v1/public/reporter-email-verifications')
      .flush(
        { title: 'Invalid verification' },
        { status: 422, statusText: 'Unprocessable Content' },
      );
    fixture.detectChanges();

    expect((fixture.nativeElement as HTMLElement).textContent).toContain(
      'No hemos podido confirmar el correo',
    );
    expect((fixture.nativeElement as HTMLElement).textContent).not.toContain('invalid');
  });
});
