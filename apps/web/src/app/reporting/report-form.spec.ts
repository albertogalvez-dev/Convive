import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { vi } from 'vitest';

import { ReportForm } from './report-form';

describe('ReportForm', () => {
  let fixture: ComponentFixture<ReportForm>;
  let page: HTMLElement;
  let httpTesting: HttpTestingController;
  const writeText = vi.fn<(value: string) => Promise<void>>();

  beforeEach(async () => {
    writeText.mockReset();
    writeText.mockResolvedValue();
    Object.defineProperty(navigator, 'clipboard', {
      configurable: true,
      value: { writeText },
    });

    await TestBed.configureTestingModule({
      imports: [ReportForm],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: convertToParamMap({
                publicReportingIdentifier: 'ORG_TEST',
              }),
            },
          },
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(ReportForm);
    fixture.detectChanges();

    page = fixture.nativeElement as HTMLElement;
    httpTesting = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpTesting.verify();
  });

  it('should reject a description containing only whitespace', () => {
    expect(page.querySelector('#situationDescription')?.getAttribute('aria-describedby')).toBe(
      'description-counter',
    );

    writeDescription('   ');
    continueToNextStep();

    expect(page.querySelector('#situationDescription')?.getAttribute('aria-describedby')).toBe(
      'description-counter description-error',
    );
    expect(page.querySelector('[role="alert"]')?.textContent).toContain(
      'Escribe qué ha ocurrido para continuar.',
    );
    expect(page.querySelector('#step-title')?.textContent).toContain('¿Qué ha ocurrido?');
  });

  it('should continue when the description contains text', () => {
    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    expect(page.querySelector('#step-title')?.textContent).toContain('¿Dónde ocurrió?');
    expect(page.querySelector('[role="alert"]')).toBeNull();
  });

  it('should associate the context error with its option group', () => {
    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    expect(page.querySelector('fieldset')?.getAttribute('aria-describedby')).toBeNull();

    continueToNextStep();

    expect(page.querySelector('fieldset')?.getAttribute('aria-describedby')).toBe('context-error');
    expect(page.querySelector('#context-error')?.textContent).toContain(
      'Selecciona una opción para continuar.',
    );
  });

  it('should submit the report and display the access credentials', async () => {
    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    selectContext('En persona');
    continueToNextStep();

    expect(page.querySelector('#step-title')?.textContent).toContain('Revisa antes de enviar');

    submitReport();

    const request = httpTesting.expectOne('/api/v1/public/organisations/ORG_TEST/reports');

    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({
      situationDescription: 'Una situación ocurrió durante el recreo.',
      situationContext: 'in_person',
    });
    expect(page.querySelector('#step-title')?.textContent).toContain(
      'Estamos enviando tu comunicación',
    );
    expect(page.querySelector('app-report-sending')?.textContent).toContain(
      'Conectando con el centro de forma segura…',
    );

    request.flush(
      {
        publicReference: 'ABC123',
        accessSecret: 'secret-value',
        status: 'received',
        createdAt: '2026-08-04T12:00:00.000+00:00',
      },
      {
        status: 201,
        statusText: 'Created',
      },
    );

    fixture.detectChanges();

    expect(page.querySelector('#step-title')?.textContent).toContain('Comunicación enviada');
    expect(page.textContent).toContain('ABC123');
    expect(page.textContent).toContain('secret-value');
    expect(page.textContent).toContain('Este secreto no puede recuperarse.');
    expect(page.textContent).toContain('Guárdalo antes de cerrar o perderás el acceso.');
    expect(page.textContent).not.toContain('Cómo funciona');
    expect(page.textContent).not.toContain('Paso 3 de 3');

    const beforeCopy = new Event('beforeunload', { cancelable: true });
    window.dispatchEvent(beforeCopy);

    expect(beforeCopy.defaultPrevented).toBe(true);

    const copyButton = page.querySelector<HTMLButtonElement>('button.copy-button');
    copyButton?.click();
    await fixture.whenStable();
    fixture.detectChanges();

    expect(writeText).toHaveBeenCalledWith('secret-value');
    expect(writeText).not.toHaveBeenCalledWith(expect.stringContaining('ABC123'));
    expect(page.querySelector('.copy-status')?.textContent).toContain(
      'Secreto copiado. Ya puedes cerrar esta página.',
    );

    const afterCopy = new Event('beforeunload', { cancelable: true });
    window.dispatchEvent(afterCopy);

    expect(afterCopy.defaultPrevented).toBe(false);
    expect(window.location.href).not.toContain('ABC123');
    expect(window.location.href).not.toContain('secret-value');
  });

  it('should display a safe error when the reporting link is not valid', () => {
    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    selectContext('En persona');
    continueToNextStep();
    submitReport();

    const request = httpTesting.expectOne('/api/v1/public/organisations/ORG_TEST/reports');

    request.flush(
      {
        type: 'urn:convive:problem:reporting-organisation-not-found',
        title: 'Reporting organisation not found',
        status: 404,
        detail: 'Internal information that must not be displayed.',
      },
      {
        status: 404,
        statusText: 'Not Found',
      },
    );

    fixture.detectChanges();

    expect(page.querySelector('[role="alert"]')?.textContent).toContain(
      'Este enlace de comunicación no es válido. Compruébalo e inténtalo de nuevo.',
    );
    expect(page.textContent).not.toContain('Internal information that must not be displayed.');
    expect(page.querySelector('#step-title')?.textContent).toContain('Revisa antes de enviar');
  });

  it('should display a safe error when the server rejects the report information', () => {
    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    selectContext('En persona');
    continueToNextStep();
    submitReport();

    const request = httpTesting.expectOne('/api/v1/public/organisations/ORG_TEST/reports');

    request.flush(
      {
        type: 'urn:convive:problem:request-validation-failed',
        title: 'Request validation failed',
        status: 422,
        detail: 'Internal validation information that must not be displayed.',
      },
      {
        status: 422,
        statusText: 'Unprocessable Content',
      },
    );

    fixture.detectChanges();

    expect(page.querySelector('[role="alert"]')?.textContent).toContain(
      'Alguna información no es válida. Revísala e inténtalo de nuevo.',
    );
    expect(page.textContent).not.toContain(
      'Internal validation information that must not be displayed.',
    );
    expect(page.querySelector('#step-title')?.textContent).toContain('Revisa antes de enviar');
  });

  it('should submit in-person and online contexts as mixed', () => {
    writeDescription('Una situación ocurrió en el centro y continuó en internet.');
    continueToNextStep();

    selectContext('En persona');
    selectContext('Online');
    continueToNextStep();

    expect(page.textContent).toContain('En persona y online');

    submitReport();

    const request = httpTesting.expectOne('/api/v1/public/organisations/ORG_TEST/reports');

    expect(request.request.body).toEqual({
      situationDescription: 'Una situación ocurrió en el centro y continuó en internet.',
      situationContext: 'mixed',
    });

    request.flush(
      {
        publicReference: 'MIXED123',
        accessSecret: 'mixed-secret',
        status: 'received',
        createdAt: '2026-08-04T12:00:00.000+00:00',
      },
      {
        status: 201,
        statusText: 'Created',
      },
    );
  });

  it('should keep the unknown context exclusive', () => {
    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    selectContext('En persona');
    selectContext('Online');
    selectContext('Prefiero no decirlo');

    expect(contextCheckbox('En persona').checked).toBe(false);
    expect(contextCheckbox('Online').checked).toBe(false);
    expect(contextCheckbox('Prefiero no decirlo').checked).toBe(true);

    selectContext('Online');

    expect(contextCheckbox('En persona').checked).toBe(false);
    expect(contextCheckbox('Online').checked).toBe(true);
    expect(contextCheckbox('Prefiero no decirlo').checked).toBe(false);
  });

  it('should manage focus and keyboard interaction in the help dialog', () => {
    const helpButton = page.querySelector<HTMLButtonElement>('button.help');

    if (!helpButton) {
      throw new Error('The help button was not found.');
    }

    helpButton.focus();
    helpButton.click();
    fixture.detectChanges();

    const dialog = page.querySelector<HTMLElement>('[role="dialog"]');
    const closeButton = page.querySelector<HTMLButtonElement>('button.close');

    expect(dialog).not.toBeNull();
    expect(document.activeElement).toBe(dialog);

    closeButton?.focus();
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab' }));

    expect(document.activeElement).toBe(closeButton);

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    fixture.detectChanges();

    expect(page.querySelector('[role="dialog"]')).toBeNull();
    expect(document.activeElement).toBe(helpButton);
  });

  function writeDescription(value: string): void {
    const textarea = page.querySelector<HTMLTextAreaElement>('#situationDescription');

    if (!textarea) {
      throw new Error('The situation description field was not found.');
    }

    textarea.value = value;
    textarea.dispatchEvent(new Event('input'));
    fixture.detectChanges();
  }

  function selectContext(labelText: string): void {
    const checkbox = contextCheckbox(labelText);

    checkbox.click();
    fixture.detectChanges();
  }

  function contextCheckbox(labelText: string): HTMLInputElement {
    const labels = Array.from(page.querySelectorAll<HTMLLabelElement>('.context-options label'));
    const label = labels.find((candidate) => candidate.textContent?.includes(labelText));
    const checkbox = label?.querySelector<HTMLInputElement>('input');

    if (!checkbox) {
      throw new Error(`The "${labelText}" context option was not found.`);
    }

    return checkbox;
  }

  function continueToNextStep(): void {
    const button = page.querySelector<HTMLButtonElement>('button.primary');

    if (!button) {
      throw new Error('The continue button was not found.');
    }

    button.click();
    fixture.detectChanges();
  }

  function submitReport(): void {
    const button = page.querySelector<HTMLButtonElement>('button[type="submit"]');

    if (!button) {
      throw new Error('The submit button was not found.');
    }

    button.click();
    fixture.detectChanges();
  }
});
