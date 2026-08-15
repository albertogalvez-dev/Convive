import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { vi } from 'vitest';

import { ProfessionalNotifications } from './professional-notifications';

describe('ProfessionalNotifications', () => {
  let fixture: ComponentFixture<ProfessionalNotifications>;
  let page: HTMLElement;
  let http: HttpTestingController;

  const notification = {
    id: 'notification-1',
    type: 'case_assigned' as const,
    createdAt: '2026-08-14T10:00:00.000+00:00',
    readAt: null,
    href: '/profesionales/casos/case-1',
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProfessionalNotifications],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();
    fixture = TestBed.createComponent(ProfessionalNotifications);
    page = fixture.nativeElement as HTMLElement;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });

  afterEach(() => http.verify());

  const flushInitialLoad = (
    items: (typeof notification)[] = [notification],
    preferences = [
      { type: 'case_assigned' as const, enabled: true, required: true },
      { type: 'case_lifecycle_changed' as const, enabled: true, required: false },
    ],
  ): void => {
    http
      .expectOne('/api/v1/professional/notifications')
      .flush({ items, unreadCount: items.filter((item) => item.readAt === null).length });
    http.expectOne('/api/v1/professional/notification-preferences').flush({ items: preferences });
    fixture.detectChanges();
  };

  it('states read status in text so it does not rely on colour alone', () => {
    flushInitialLoad();

    const action = page.querySelector<HTMLButtonElement>('ul button');
    expect(action).not.toBeNull();
    expect(action?.textContent).toContain('Se te ha asignado un caso.');
    expect(action?.textContent).toContain('Sin leer');
    // The affordance is a real button, so it is reachable and operable by keyboard.
    expect(action?.tagName).toBe('BUTTON');
  });

  it('marks a notification read before following its authorised deep link', () => {
    const navigate = vi.spyOn(TestBed.inject(Router), 'navigateByUrl').mockResolvedValue(true);
    flushInitialLoad();

    page.querySelector<HTMLButtonElement>('ul button')?.click();
    const request = http.expectOne('/api/v1/professional/notifications/notification-1/read');
    expect(request.request.method).toBe('POST');
    request.flush({ ...notification, readAt: '2026-08-14T11:00:00.000+00:00' });
    fixture.detectChanges();

    expect(navigate).toHaveBeenCalledWith('/profesionales/casos/case-1');
    expect(page.querySelector('ul button')?.textContent).toContain('Leído');
  });

  it('does not offer to disable a safeguarding-required notification', () => {
    flushInitialLoad();

    const checkboxes = page.querySelectorAll<HTMLInputElement>('.preferences input[type=checkbox]');
    expect(checkboxes.length).toBe(2);
    expect(checkboxes[0].disabled).toBe(true);
    expect(checkboxes[1].disabled).toBe(false);
    expect(page.querySelector('.preferences')?.textContent).toContain('(obligatorio)');
  });

  it('saves an optional preference change', () => {
    flushInitialLoad();

    const optional = page.querySelectorAll<HTMLInputElement>(
      '.preferences input[type=checkbox]',
    )[1];
    optional.checked = false;
    optional.dispatchEvent(new Event('change'));

    const request = http.expectOne(
      '/api/v1/professional/notification-preferences/case_lifecycle_changed',
    );
    expect(request.request.method).toBe('PATCH');
    expect(request.request.body).toEqual({ enabled: false });
    request.flush({ type: 'case_lifecycle_changed', enabled: false, required: false });
  });

  it('reports a load failure without leaving the list in a loading state', () => {
    http
      .expectOne('/api/v1/professional/notifications')
      .flush(null, { status: 500, statusText: 'Server Error' });
    http.expectOne('/api/v1/professional/notification-preferences').flush({ items: [] });
    fixture.detectChanges();

    const alert = page.querySelector('[role=alert]');
    expect(alert?.textContent).toContain('No hemos podido cargar los avisos.');
    expect(page.textContent).not.toContain('Cargando avisos…');
  });
});
