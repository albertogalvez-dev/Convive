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

  it('keeps account details in settings and closes the current session', () => {
    const navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true);
    expect(page.textContent).toContain('Laura Martin');
    expect(page.textContent).toContain('laura@example.com');

    page.querySelector<HTMLButtonElement>('button')?.click();
    http.expectOne('/api/v1/professional/session').flush(null);

    expect(TestBed.inject(ProfessionalSessionService).professional()).toBeNull();
    expect(navigate).toHaveBeenCalledWith(['/profesionales/acceso']);
  });
});
