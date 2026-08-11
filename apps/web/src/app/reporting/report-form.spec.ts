import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { vi } from 'vitest';

import { ReportForm } from './report-form';

describe('ReportForm', () => {
  const organisationIdentifier = 'ORG_6H3K8M5R2W9T4Q7X';
  const organisationEndpoint = `/api/v1/public/organisations/${organisationIdentifier}`;
  const originalPasswordCredential = Object.getOwnPropertyDescriptor(window, 'PasswordCredential');
  const originalCredentials = Object.getOwnPropertyDescriptor(navigator, 'credentials');

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
                publicReportingIdentifier: organisationIdentifier,
              }),
            },
          },
        },
      ],
    }).compileComponents();

    httpTesting = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpTesting.verify();
    restoreOwnProperty(window, 'PasswordCredential', originalPasswordCredential);
    restoreOwnProperty(navigator, 'credentials', originalCredentials);
  });

  it('should resolve the organisation before displaying the form', () => {
    createForm();

    expect(page.querySelector('form')).toBeNull();
    expect(page.querySelector('[role="status"]')).toBeNull();

    resolveOrganisation();

    expect(page.textContent).toContain('IES Valle Sereno');
    expect(page.querySelector('form')).not.toBeNull();
  });

  it('should show a non-persistent message instead of the form in fictional demo mode', () => {
    createForm();
    resolveOrganisation('IES Horizonte Ficticio', 'fictional_demo');

    expect(page.textContent).toContain('Demostración ficticia');
    expect(page.textContent).toContain('Aquí no se guardan comunicaciones');
    expect(page.textContent).toContain('No escribas información personal');
    expect(page.querySelector('form')).toBeNull();
    httpTesting.expectNone(`${organisationEndpoint}/reports`);
  });

  it('should show the invalid-link state when the organisation cannot be found', () => {
    createForm();

    httpTesting.expectOne(organisationEndpoint).flush(
      {
        type: 'urn:convive:problem:reporting-organisation-not-found',
        title: 'Reporting organisation not found',
        status: 404,
        detail: 'Internal information that must not be displayed.',
      },
      { status: 404, statusText: 'Not Found' },
    );
    fixture.detectChanges();

    expect(page.textContent).toContain('Enlace no válido');
    expect(page.textContent).toContain('No podemos abrir este formulario');
    expect(page.textContent).not.toContain('Internal information');
    expect(page.querySelector('form')).toBeNull();
  });

  it('should display the loading form only when profile resolution takes longer', () => {
    vi.useFakeTimers();

    try {
      createForm();

      vi.advanceTimersByTime(249);
      fixture.detectChanges();
      expect(page.querySelector('form')).toBeNull();

      vi.advanceTimersByTime(1);
      fixture.detectChanges();

      const formWhileLoading = page.querySelector('form');

      expect(formWhileLoading?.classList.contains('form-loading')).toBe(true);
      expect((formWhileLoading?.querySelector('textarea') as HTMLTextAreaElement).disabled).toBe(
        true,
      );
      expect(page.querySelector('[role="status"]')?.textContent).toContain(
        'Preparando el formulario',
      );

      resolveOrganisation();
    } finally {
      vi.useRealTimers();
    }
  });

  it('should allow retrying when the organisation profile is temporarily unavailable', () => {
    createForm();

    httpTesting
      .expectOne(organisationEndpoint)
      .error(new ProgressEvent('error'), { status: 0, statusText: 'Network Error' });
    fixture.detectChanges();

    expect(page.textContent).toContain('No podemos cargar el formulario');

    page.querySelector<HTMLButtonElement>('.unavailable-status button')?.click();
    fixture.detectChanges();
    resolveOrganisation();

    expect(page.textContent).toContain('IES Valle Sereno');
    expect(page.querySelector('#situationDescription')).not.toBeNull();
  });

  it('should reject a description containing only whitespace', () => {
    startValidForm();

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
    startValidForm();

    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    expect(page.querySelector('#step-title')?.textContent).toContain('¿Dónde ocurrió?');
    expect(page.querySelector('[role="alert"]')).toBeNull();
  });

  it('should associate the context error with its option group', () => {
    startValidForm();

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
    startValidForm();

    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    selectContext('En persona');
    continueToNextStep();

    expect(page.querySelector('#step-title')?.textContent).toContain('Revisa antes de enviar');

    submitReport();

    const request = httpTesting.expectOne(`${organisationEndpoint}/reports`);

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
    expect(page.textContent).toContain('Cualquiera que tenga este secreto puede entrar.');
    expect(page.textContent).toContain('No puede recuperarse');
    const followUpLink = page.querySelector<HTMLAnchorElement>('.follow-up-hint a');

    expect(followUpLink?.getAttribute('href')).toBe('/seguimiento');
    expect(followUpLink?.getAttribute('target')).toBe('_blank');
    expect(page.querySelector('.save-button')).toBeNull();
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
    expect(page.querySelector('.credential-status')?.textContent).toContain(
      'Secreto copiado. Ya puedes cerrar esta página.',
    );

    const afterCopy = new Event('beforeunload', { cancelable: true });
    window.dispatchEvent(afterCopy);

    expect(afterCopy.defaultPrevented).toBe(false);
    expect(window.location.href).not.toContain('ABC123');
    expect(window.location.href).not.toContain('secret-value');
  });

  it('should print a minimal credential-safe receipt without replacing copy', () => {
    const print = vi.spyOn(window, 'print').mockImplementation(() => undefined);

    submitValidReport();

    const receipt = page.querySelector<HTMLElement>('.print-receipt');
    const printButton = page.querySelector<HTMLButtonElement>('.print-button');

    expect(receipt?.textContent).toContain('app.conviveaula.com/seguimiento');
    expect(receipt?.textContent).toContain('secret-value');
    expect(receipt?.textContent).toContain('Cualquiera que tenga este secreto');
    expect(receipt?.textContent).not.toContain('ABC123');
    expect(document.title).not.toContain('ABC123');
    expect(document.title).not.toContain('secret-value');
    expect(page.querySelector('[download]')).toBeNull();
    expect(page.querySelector('.copy-button')).not.toBeNull();

    printButton?.click();

    expect(print).toHaveBeenCalledOnce();

    const afterPrint = new Event('beforeunload', { cancelable: true });
    window.dispatchEvent(afterPrint);
    expect(afterPrint.defaultPrevented).toBe(true);
    print.mockRestore();
  });

  it('should ask the browser to store the reference and secret when it can', async () => {
    const store = vi.fn<(credential: unknown) => Promise<void>>();
    store.mockResolvedValue();

    class FakePasswordCredential {
      constructor(readonly data: { id: string; password: string; name?: string }) {}
    }

    Object.defineProperty(window, 'PasswordCredential', {
      configurable: true,
      value: FakePasswordCredential,
    });
    Object.defineProperty(navigator, 'credentials', {
      configurable: true,
      value: { store },
    });

    submitValidReport();

    const saveButton = page.querySelector<HTMLButtonElement>('.save-button');

    expect(saveButton).not.toBeNull();

    saveButton?.click();
    await fixture.whenStable();
    fixture.detectChanges();

    expect(store).toHaveBeenCalledOnce();
    expect((store.mock.calls[0][0] as FakePasswordCredential).data).toMatchObject({
      id: 'ABC123',
      password: 'secret-value',
    });
    expect(page.querySelector('.credential-status')?.textContent).toContain('tu navegador');

    const afterSaving = new Event('beforeunload', { cancelable: true });
    window.dispatchEvent(afterSaving);

    expect(afterSaving.defaultPrevented).toBe(false);
  });

  it('should keep warning before leaving when browser credential storage fails', async () => {
    const store = vi.fn<(credential: unknown) => Promise<void>>();
    store.mockRejectedValue(new DOMException('Saving was declined.', 'NotAllowedError'));

    class FakePasswordCredential {
      constructor(readonly data: { id: string; password: string; name?: string }) {}
    }

    Object.defineProperty(window, 'PasswordCredential', {
      configurable: true,
      value: FakePasswordCredential,
    });
    Object.defineProperty(navigator, 'credentials', {
      configurable: true,
      value: { store },
    });

    submitValidReport();

    page.querySelector<HTMLButtonElement>('.save-button')?.click();
    await fixture.whenStable();
    fixture.detectChanges();

    expect(page.querySelector('.credential-status')?.textContent).toContain(
      'No se ha podido guardar en el navegador.',
    );

    const afterFailedSaving = new Event('beforeunload', { cancelable: true });
    window.dispatchEvent(afterFailedSaving);

    expect(afterFailedSaving.defaultPrevented).toBe(true);
  });

  it('should display a safe error when the reporting link is not valid', () => {
    startValidForm();

    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    selectContext('En persona');
    continueToNextStep();
    submitReport();

    const request = httpTesting.expectOne(`${organisationEndpoint}/reports`);

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
    startValidForm();

    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    selectContext('En persona');
    continueToNextStep();
    submitReport();

    const request = httpTesting.expectOne(`${organisationEndpoint}/reports`);

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
    startValidForm();

    writeDescription('Una situación ocurrió en el centro y continuó en internet.');
    continueToNextStep();

    selectContext('En persona');
    selectContext('Online');
    continueToNextStep();

    expect(page.textContent).toContain('En persona y online');

    submitReport();

    const request = httpTesting.expectOne(`${organisationEndpoint}/reports`);

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
    startValidForm();

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
    startValidForm();

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

  it('should establish attachment access only when initial evidence is opened', () => {
    submitValidReport();

    httpTesting.expectNone('/api/v1/reporter/report/attachments');

    page.querySelector<HTMLButtonElement>('app-report-evidence .open-evidence')?.click();
    fixture.detectChanges();

    const grant = httpTesting.expectOne('/api/v1/public/report-access-grants');

    expect(grant.request.method).toBe('POST');
    expect(grant.request.body).toEqual({ accessSecret: 'secret-value' });
    grant.flush(null, { status: 204, statusText: 'No Content' });

    const attachments = httpTesting.expectOne('/api/v1/reporter/report/attachments');

    expect(attachments.request.method).toBe('GET');
    attachments.flush({ items: [] });
    fixture.detectChanges();

    const evidence = page.querySelector('app-report-evidence');

    expect(evidence?.textContent).toContain('Seleccionar archivos');
    expect(evidence?.textContent).not.toContain('secret-value');
  });

  function createForm(): void {
    fixture = TestBed.createComponent(ReportForm);
    fixture.detectChanges();

    page = fixture.nativeElement as HTMLElement;
  }

  function resolveOrganisation(
    name = 'IES Valle Sereno',
    reportingMode: 'operational' | 'fictional_demo' | 'disabled' = 'operational',
  ): void {
    const request = httpTesting.expectOne(organisationEndpoint);

    expect(request.request.method).toBe('GET');

    request.flush({ name, reportingMode });
    fixture.detectChanges();
  }

  function startValidForm(): void {
    createForm();
    resolveOrganisation();
  }

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

  function submitValidReport(): void {
    startValidForm();

    writeDescription('Una situación ocurrió durante el recreo.');
    continueToNextStep();

    selectContext('En persona');
    continueToNextStep();
    submitReport();

    httpTesting.expectOne(`${organisationEndpoint}/reports`).flush(
      {
        publicReference: 'ABC123',
        accessSecret: 'secret-value',
        status: 'received',
        createdAt: '2026-08-04T12:00:00.000+00:00',
      },
      { status: 201, statusText: 'Created' },
    );

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

  function restoreOwnProperty(
    target: object,
    property: PropertyKey,
    descriptor: PropertyDescriptor | undefined,
  ): void {
    if (descriptor) {
      Object.defineProperty(target, property, descriptor);
      return;
    }

    Reflect.deleteProperty(target, property);
  }
});
