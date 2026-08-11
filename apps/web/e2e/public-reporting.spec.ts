import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const PUBLIC_REPORTING_IDENTIFIER = 'ORG_DEM0000000000000';
const FICTIONAL_ORGANISATION = 'IES Horizonte Ficticio — DEMOSTRACIÓN';
const APP_BASE_URL = process.env['PLAYWRIGHT_BASE_URL'] ?? 'http://127.0.0.1:4200';
const TRIAGE_EMAIL = 'lucia.demo@convive.example';
const ADMINISTRATOR_EMAIL = 'carlos.demo@convive.example';
const PROFESSIONAL_PASSWORD = requiredEnvironmentVariable('E2E_PROFESSIONAL_PASSWORD');
const PERFORMANCE_ATTEMPTS = 5;
const MAX_DEMO_API_MEDIAN_MS = 250;
const MAX_DEMO_API_SAMPLE_MS = 750;
const MAX_PUBLIC_REPORTING_READY_MS = 3_000;
const MAX_PROFESSIONAL_DASHBOARD_READY_MS = 3_000;

test.afterEach(async ({ context, page }, testInfo) => {
  if (testInfo.status === testInfo.expectedStatus) {
    return;
  }

  await page
    .addStyleTag({
      content: `
        .credential-value.secret,
        #accessSecret,
        input[type='password'] {
          visibility: hidden !important;
        }
      `,
    })
    .catch(() => undefined);
  await page
    .screenshot({ path: testInfo.outputPath('failure-redacted.png'), fullPage: true })
    .catch(() => undefined);

  // Closing the context prevents Playwright's automatic accessibility snapshot
  // from retaining a secret that might still exist in the failed page's DOM.
  await context.close();
});

test('completes the fictional reporter-professional conversation loop', async ({
  browser,
  context,
  page,
}) => {
  test.setTimeout(240_000);
  const runMarker = `${Date.now()}-${test.info().retry}`;
  const situationDescription = `Fictional E2E report ${runMarker}`;
  const followUpContent = `Fictional E2E follow-up ${runMarker}`;
  const professionalResponse = `Fictional E2E professional response ${runMarker}`;
  const evidenceDescription = `Fictional E2E evidence ${runMarker}`;

  await page.goto(`/r/${PUBLIC_REPORTING_IDENTIFIER}`);

  await expect(page.getByText(FICTIONAL_ORGANISATION, { exact: true })).toBeVisible();
  await expectNoAccessibilityViolations(page);
  await page.getByLabel('¿Qué ha ocurrido?').fill(situationDescription);
  await page.getByRole('button', { name: 'Continuar' }).click();
  await page.getByLabel('Online').check();
  await page.getByRole('button', { name: 'Continuar' }).click();

  await expect(page.getByRole('heading', { name: 'Revisa antes de enviar' })).toBeVisible();
  await page.getByRole('button', { name: 'Enviar' }).click();

  await expect(page.getByRole('heading', { name: 'Comunicación enviada' })).toBeVisible();
  const publicReference = await page.locator('.credential-value.reference code').innerText();
  const accessSecret = await page.locator('.credential-value.secret code').innerText();

  await page.getByRole('button', { name: 'Añadir pruebas' }).click();
  await page.locator('#evidence-files').setInputFiles({
    name: `private-${runMarker}.pdf`,
    mimeType: 'application/pdf',
    buffer: Buffer.from('%PDF-1.7\nfictional evidence\n'),
  });
  await page.getByLabel('Descripción (opcional)').fill(evidenceDescription);
  await expect(page.getByText(`private-${runMarker}.pdf`, { exact: true })).toHaveCount(0);
  await page.getByRole('button', { name: 'Enviar pruebas' }).click();
  await expect(page.getByText(evidenceDescription, { exact: true })).toBeVisible();
  await expect(page.getByText('En comprobación', { exact: true })).toBeVisible();
  await expectNoAccessibilityViolations(page);

  const credentialWasAbsentAfterSubmission = await credentialIsAbsentFromBrowserState(
    page,
    context,
    accessSecret,
  );

  page.on('dialog', (dialog) => dialog.accept());
  await page.goto('/seguimiento');
  await expectNoAccessibilityViolations(page);
  expect(publicReference).toMatch(/^[0-9A-F]{20}$/);
  expect(accessSecret.length).toBeGreaterThanOrEqual(32);
  expect(credentialWasAbsentAfterSubmission).toBe(true);

  await page.getByLabel('Referencia').fill(publicReference);
  await page.getByLabel('Secreto').fill(accessSecret);
  await page.getByRole('button', { name: 'Abrir comunicación' }).click();
  await page.getByLabel('Secreto').fill('');

  await expect(page.getByRole('heading', { name: 'Tu comunicación' })).toBeVisible();
  await expect(page.getByText(situationDescription, { exact: true })).toBeVisible();
  await expect(page.getByText(evidenceDescription, { exact: true })).toBeVisible();
  await expect(page.getByText('En comprobación', { exact: true })).toBeVisible();
  await expectCredentialAbsentFromBrowserState(page, context, accessSecret);

  const professionalContext = await browser.newContext();
  const administratorContext = await browser.newContext();

  try {
    const professionalPage = await professionalContext.newPage();
    await loginAsProfessional(professionalPage, TRIAGE_EMAIL);
    await expectNoAccessibilityViolations(professionalPage);
    await professionalPage.goto(absoluteUrl('/profesionales/comunicaciones'));
    await professionalPage.getByText(situationDescription, { exact: true }).click();

    await expect(
      professionalPage.getByRole('heading', { name: 'Comunicación', exact: true }),
    ).toBeVisible();
    const professionalDetailUrl = professionalPage.url();
    await professionalPage
      .getByLabel('Mensaje para la persona informante')
      .fill(professionalResponse);
    await professionalPage.getByRole('button', { name: 'Enviar respuesta' }).click();

    await expect(professionalPage.getByText('Respuesta enviada.', { exact: true })).toBeVisible();
    await expect(professionalPage.getByText(professionalResponse, { exact: true })).toBeVisible();

    const administratorPage = await administratorContext.newPage();
    await loginAsProfessional(administratorPage, ADMINISTRATOR_EMAIL);
    await administratorPage.goto(professionalDetailUrl);
    await expect(administratorPage.getByRole('alert')).toContainText('no está disponible');

    await page.reload();
    await page.getByLabel('Referencia').fill(publicReference);
    await page.getByLabel('Secreto').fill(accessSecret);
    await page.getByRole('button', { name: 'Abrir comunicación' }).click();
    await page.getByLabel('Secreto').fill('');
    await expect(page.getByText(professionalResponse, { exact: true })).toBeVisible();
    await expect(page.getByText('El centro', { exact: true })).toBeVisible();
    await expectCredentialAbsentFromBrowserState(page, context, accessSecret);

    await page.getByLabel('Qué quieres añadir').fill(followUpContent);
    await page.getByRole('button', { name: 'Añadir información' }).click();

    await expect(page.getByText(followUpContent, { exact: true })).toBeVisible();
    await expect(
      page.getByText('Hemos añadido tu información a la comunicación.', { exact: true }),
    ).toBeVisible();
  } finally {
    await administratorContext.close();
    await professionalContext.close();
  }

  await page.getByRole('button', { name: 'Cerrar acceso' }).click();
  await expect(page.getByRole('heading', { name: 'Consulta tu comunicación' })).toBeVisible();
  await expect(
    page.getByText(
      'Has cerrado el acceso. Para volver a abrir la comunicación necesitarás el secreto otra vez.',
      { exact: true },
    ),
  ).toBeVisible();
  await expectCredentialAbsentFromBrowserState(page, context, accessSecret);
});

test('keeps the public reporting form keyboard-operable and responsive', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto(`/r/${PUBLIC_REPORTING_IDENTIFIER}`);

  const description = page.locator('#situationDescription');
  await expect(description).toBeVisible();
  await description.press('Tab');
  await expect(page.getByRole('button', { name: 'Continuar' })).toBeFocused();
  await page.getByRole('button', { name: 'Continuar' }).press('Enter');
  await expect(page.getByRole('alert')).toBeVisible();
  await expectNoAccessibilityViolations(page);

  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
    ),
  ).toBe(true);
  expect(await page.evaluate(() => getComputedStyle(document.documentElement).scrollBehavior)).toBe(
    'auto',
  );
});

test('keeps the public product homepage accessible and separate from reporting entry', async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/');

  await expect(
    page.getByRole('heading', { name: 'Un canal seguro para escuchar antes.' }),
  ).toBeVisible();
  await expectNoAccessibilityViolations(page);
  await expect(page.getByRole('link', { name: 'Área profesional' })).toHaveAttribute(
    'href',
    '/profesionales/acceso',
  );
  await expect(page.getByRole('link', { name: 'Blog' }).first()).toHaveAttribute('href', '/blog/');
  await expect(page.getByRole('link', { name: 'Demostración' }).first()).toHaveAttribute(
    'href',
    '/demostracion/',
  );
  await expect(page.getByRole('link', { name: 'Contacto' }).first()).toHaveAttribute(
    'href',
    '/contacto/',
  );

  await page.goto('/demostracion/');
  await expect(
    page.getByRole('heading', { name: 'La demostración se está preparando.' }),
  ).toBeVisible();
  await expectNoAccessibilityViolations(page);

  await page.goto('/');
  await page.keyboard.press('Tab');
  await expect(page.getByRole('link', { name: 'Convive, inicio' })).toBeFocused();
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
    ),
  ).toBe(true);

  await page.goto(`/r/${PUBLIC_REPORTING_IDENTIFIER}`);
  await expect(page.locator('app-report-form')).toHaveCount(1);
  await expect(
    page.getByRole('heading', { name: 'Un canal seguro para escuchar antes.' }),
  ).toHaveCount(0);
});

test('keeps the fictional demo critical paths within performance budgets', async ({
  browser,
  page,
  request,
}) => {
  const healthMeasurements = await measureApiResponseTimes(request, '/api/v1/health');
  const publicProfileMeasurements = await measureApiResponseTimes(
    request,
    `/api/v1/public/organisations/${PUBLIC_REPORTING_IDENTIFIER}`,
  );

  expectApiBudget('health', healthMeasurements);
  expectApiBudget('public reporting profile', publicProfileMeasurements);

  const publicReportingStartedAt = performance.now();
  await page.goto(`/r/${PUBLIC_REPORTING_IDENTIFIER}`);
  await expect(page.getByText(FICTIONAL_ORGANISATION, { exact: true })).toBeVisible();
  const publicReportingReadyInMs = performance.now() - publicReportingStartedAt;

  console.info(
    `[performance] public reporting ready in ${formatMilliseconds(publicReportingReadyInMs)} ms`,
  );
  expect(publicReportingReadyInMs).toBeLessThanOrEqual(MAX_PUBLIC_REPORTING_READY_MS);

  const professionalContext = await browser.newContext();

  try {
    const professionalPage = await professionalContext.newPage();
    const professionalDashboardStartedAt = performance.now();
    await loginAsProfessional(professionalPage, TRIAGE_EMAIL);
    const professionalDashboardReadyInMs = performance.now() - professionalDashboardStartedAt;

    console.info(
      `[performance] professional dashboard ready in ${formatMilliseconds(professionalDashboardReadyInMs)} ms`,
    );
    expect(professionalDashboardReadyInMs).toBeLessThanOrEqual(MAX_PROFESSIONAL_DASHBOARD_READY_MS);
  } finally {
    await professionalContext.close();
  }
});

async function loginAsProfessional(
  page: import('@playwright/test').Page,
  email: string,
): Promise<void> {
  await page.goto(absoluteUrl('/profesionales/acceso'));
  await page.getByLabel('Correo profesional').fill(email);
  await page.getByLabel('Contraseña').fill(PROFESSIONAL_PASSWORD);
  await page.getByRole('button', { name: 'Acceder' }).click();
  await expect(page.getByRole('heading', { name: /Hola,/ })).toBeVisible();
}

function absoluteUrl(path: string): string {
  return new URL(path, APP_BASE_URL).toString();
}

function requiredEnvironmentVariable(name: string): string {
  const value = process.env[name];

  if (!value) {
    throw new Error(`${name} is required for the isolated fictional E2E journey.`);
  }

  return value;
}

async function credentialIsAbsentFromBrowserState(
  page: import('@playwright/test').Page,
  context: import('@playwright/test').BrowserContext,
  accessSecret: string,
): Promise<boolean> {
  const browserStorage = await page.evaluate(async () => ({
    localStorage: Object.entries(localStorage),
    sessionStorage: Object.entries(sessionStorage),
    indexedDatabaseNames: (await indexedDB.databases()).map((database) => database.name),
  }));

  return ![
    page.url(),
    JSON.stringify(browserStorage),
    JSON.stringify(await context.cookies()),
  ].some((candidate) => candidate.includes(accessSecret));
}

async function expectCredentialAbsentFromBrowserState(
  page: import('@playwright/test').Page,
  context: import('@playwright/test').BrowserContext,
  accessSecret: string,
): Promise<void> {
  expect(await credentialIsAbsentFromBrowserState(page, context, accessSecret)).toBe(true);
}

async function expectNoAccessibilityViolations(
  page: import('@playwright/test').Page,
): Promise<void> {
  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
    .analyze();

  expect(results.violations).toEqual([]);
}

async function measureApiResponseTimes(
  request: import('@playwright/test').APIRequestContext,
  path: string,
): Promise<number[]> {
  const measurements: number[] = [];

  for (let attempt = 0; attempt < PERFORMANCE_ATTEMPTS; attempt += 1) {
    const startedAt = performance.now();
    const response = await request.get(absoluteUrl(path));
    const duration = performance.now() - startedAt;

    expect(response.ok()).toBe(true);
    measurements.push(duration);
  }

  return measurements;
}

function expectApiBudget(name: string, measurements: number[]): void {
  const sortedMeasurements = [...measurements].sort((left, right) => left - right);
  const median = sortedMeasurements[Math.floor(sortedMeasurements.length / 2)];
  const maximum = sortedMeasurements.at(-1);

  if (median === undefined || maximum === undefined) {
    throw new Error(`No performance measurements were collected for ${name}.`);
  }

  console.info(
    `[performance] ${name}: median ${formatMilliseconds(median)} ms, maximum ${formatMilliseconds(maximum)} ms`,
  );
  expect(median).toBeLessThanOrEqual(MAX_DEMO_API_MEDIAN_MS);
  expect(maximum).toBeLessThanOrEqual(MAX_DEMO_API_SAMPLE_MS);
}

function formatMilliseconds(value: number): string {
  return value.toFixed(1);
}
