import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';

import reportEvidenceEs from '../../i18n/report-evidence/es.json';
import reportFormEs from '../../i18n/report-form/es.json';
import reportHeaderEs from '../../i18n/report-header/es.json';
import reportHelpEs from '../../i18n/report-help/es.json';
import reportResultEs from '../../i18n/report-result/es.json';
import reportSendingEs from '../../i18n/report-sending/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { ReportForm } from './report-form';

describe('ReportForm witness entry', () => {
  const organisationIdentifier = 'ORG_6H3K8M5R2W9T4Q7X';
  const organisationEndpoint = `/api/v1/public/organisations/${organisationIdentifier}`;

  let fixture: ComponentFixture<ReportForm>;
  let page: HTMLElement;
  let httpTesting: HttpTestingController;

  afterEach(() => {
    httpTesting.verify();
  });

  it('asks what the reporter saw, not what happened to them', async () => {
    await createEntry('witnessed');
    resolveOrganisation();

    expect(page.querySelector('#step-title')?.textContent).toContain('¿Qué has visto?');
    expect(page.textContent).toContain('Cuentas algo que le ocurre a otra persona.');
  });

  it('leaves the first-person entry asking exactly what it asks today', async () => {
    await createEntry(undefined);
    resolveOrganisation();

    expect(page.querySelector('#step-title')?.textContent).toContain('¿Qué ha ocurrido?');
    expect(page.textContent).not.toContain('Cuentas algo que le ocurre a otra persona.');
  });

  it('marks a witness submission as witnessed', async () => {
    await createEntry('witnessed');
    const request = submitMinimalReport();

    expect(request.request.body.reporterPerspective).toBe('witnessed');

    flush(request);
  });

  /**
   * The first-person entry must not start declaring a perspective just
   * because the witness entry exists. Its request body stays what it is
   * today and the API's own default covers it.
   */
  it('leaves the first-person submission body untouched', async () => {
    await createEntry(undefined);
    const request = submitMinimalReport();

    expect('reporterPerspective' in request.request.body).toBe(false);

    flush(request);
  });

  /**
   * The two entries must never be conflated. A witness account and a
   * first-person account are distinguishable by exactly one field, and it is
   * set by the route, never by anything the reporter typed.
   */
  it('never lets the two entries produce the same perspective', async () => {
    await createEntry('witnessed');
    const witnessRequest = submitMinimalReport();
    const witnessPerspective = witnessRequest.request.body.reporterPerspective;
    flush(witnessRequest);

    await createEntry(undefined);
    const firstPersonRequest = submitMinimalReport();
    const firstPersonPerspective = firstPersonRequest.request.body.reporterPerspective;
    flush(firstPersonRequest);

    expect(witnessPerspective).toBe('witnessed');
    expect(firstPersonPerspective).toBeUndefined();
    expect(witnessPerspective).not.toBe(firstPersonPerspective);
  });

  /**
   * A witness report is not more anonymous than a first-person one. Both
   * entries render the same help component, so the wording is identical by
   * construction -- this pins that it stays that way.
   */
  it('makes no different privacy claim than the first-person entry', async () => {
    await createEntry('witnessed');
    resolveOrganisation();
    const witnessText = page.textContent ?? '';

    await createEntry(undefined);
    resolveOrganisation();
    const firstPersonText = page.textContent ?? '';

    for (const promise of ['anónim', 'anonim', 'nadie sabrá', 'no sabrá nadie']) {
      expect(witnessText.toLowerCase()).not.toContain(promise);
      expect(firstPersonText.toLowerCase()).not.toContain(promise);
    }
  });

  it('offers a route to the other entry for the same organisation', async () => {
    await createEntry('witnessed');
    resolveOrganisation();

    const link = page.querySelector<HTMLAnchorElement>('a.other-entry');
    expect(link?.getAttribute('href')).toBe(`/r/${organisationIdentifier}`);
    expect(link?.textContent).toContain('Me ocurre a mí');

    await createEntry(undefined);
    resolveOrganisation();

    const witnessLink = page.querySelector<HTMLAnchorElement>('a.other-entry');
    expect(witnessLink?.getAttribute('href')).toBe(`/r/${organisationIdentifier}/testigo`);
    expect(witnessLink?.textContent).toContain('Le ocurre a otra persona');
  });

  async function createEntry(perspective: 'witnessed' | undefined): Promise<void> {
    TestBed.resetTestingModule();

    await TestBed.configureTestingModule({
      imports: [
        ReportForm,
        i18nTestingModule({
          'report-evidence': reportEvidenceEs,
          'report-form': reportFormEs,
          'report-header': reportHeaderEs,
          'report-help': reportHelpEs,
          'report-result': reportResultEs,
          'report-sending': reportSendingEs,
        }),
      ],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: convertToParamMap({
                publicReportingIdentifier: organisationIdentifier,
              }),
              data: perspective === undefined ? {} : { reporterPerspective: perspective },
            },
          },
        },
      ],
    }).compileComponents();

    httpTesting = TestBed.inject(HttpTestingController);
    fixture = TestBed.createComponent(ReportForm);
    page = fixture.nativeElement as HTMLElement;
    fixture.detectChanges();
  }

  function resolveOrganisation(): void {
    httpTesting
      .expectOne(organisationEndpoint)
      .flush({ name: 'IES Valle Sereno', reportingMode: 'operational' });
    fixture.detectChanges();
  }

  function submitMinimalReport() {
    resolveOrganisation();

    writeDescription('Una situación ocurrió durante el recreo.');
    clickPrimary();
    selectContext('En persona');
    clickPrimary();
    clickSubmit();

    return httpTesting.expectOne(`${organisationEndpoint}/reports`);
  }

  function flush(request: ReturnType<typeof submitMinimalReport>): void {
    request.flush(
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

  function writeDescription(value: string): void {
    const textarea = page.querySelector<HTMLTextAreaElement>('#situationDescription');

    if (!textarea) {
      throw new Error('The description field was not found.');
    }

    textarea.value = value;
    textarea.dispatchEvent(new Event('input'));
    fixture.detectChanges();
  }

  function selectContext(labelText: string): void {
    const labels = Array.from(page.querySelectorAll<HTMLLabelElement>('.context-options label'));
    const label = labels.find((candidate) => candidate.textContent?.includes(labelText));
    const checkbox = label?.querySelector<HTMLInputElement>('input');

    if (!checkbox) {
      throw new Error(`The "${labelText}" context option was not found.`);
    }

    checkbox.click();
    fixture.detectChanges();
  }

  function clickPrimary(): void {
    const button = page.querySelector<HTMLButtonElement>('button.primary');

    if (!button) {
      throw new Error('The continue button was not found.');
    }

    button.click();
    fixture.detectChanges();
  }

  function clickSubmit(): void {
    const button = page.querySelector<HTMLButtonElement>('button[type="submit"]');

    if (!button) {
      throw new Error('The submit button was not found.');
    }

    button.click();
    fixture.detectChanges();
  }
});
