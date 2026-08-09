import { expect, test } from '@playwright/test';

const PUBLIC_REPORTING_IDENTIFIER = 'ORG_E2E0000000000000';
const FICTIONAL_ORGANISATION = 'Convive E2E School';
const APP_BASE_URL = process.env['PLAYWRIGHT_BASE_URL'] ?? 'http://127.0.0.1:4200';
const TRIAGE_EMAIL = 'triage.e2e@convive.example';
const ADMINISTRATOR_EMAIL = 'administrator.e2e@convive.example';
const PROFESSIONAL_PASSWORD = 'Fictional professional 35!';

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

  await page.goto(`/r/${PUBLIC_REPORTING_IDENTIFIER}`);

  await expect(page.getByText(FICTIONAL_ORGANISATION, { exact: true })).toBeVisible();
  await page.getByLabel('¿Qué ha ocurrido?').fill(situationDescription);
  await page.getByRole('button', { name: 'Continuar' }).click();
  await page.getByLabel('Online').check();
  await page.getByRole('button', { name: 'Continuar' }).click();

  await expect(page.getByRole('heading', { name: 'Revisa antes de enviar' })).toBeVisible();
  await page.getByRole('button', { name: 'Enviar' }).click();

  await expect(page.getByRole('heading', { name: 'Comunicación enviada' })).toBeVisible();
  const publicReference = await page.locator('.credential-value.reference code').innerText();
  const accessSecret = await page.locator('.credential-value.secret code').innerText();
  const credentialWasAbsentAfterSubmission = await credentialIsAbsentFromBrowserState(
    page,
    context,
    accessSecret,
  );

  page.on('dialog', (dialog) => dialog.accept());
  await page.goto('/seguimiento');
  expect(publicReference).toMatch(/^[0-9A-F]{20}$/);
  expect(accessSecret.length).toBeGreaterThanOrEqual(32);
  expect(credentialWasAbsentAfterSubmission).toBe(true);

  await page.getByLabel('Referencia').fill(publicReference);
  await page.getByLabel('Secreto').fill(accessSecret);
  await page.getByRole('button', { name: 'Abrir comunicación' }).click();
  await page.getByLabel('Secreto').fill('');

  await expect(page.getByRole('heading', { name: 'Tu comunicación' })).toBeVisible();
  await expect(page.getByText(situationDescription, { exact: true })).toBeVisible();
  await expectCredentialAbsentFromBrowserState(page, context, accessSecret);

  const professionalContext = await browser.newContext();
  const administratorContext = await browser.newContext();

  try {
    const professionalPage = await professionalContext.newPage();
    await loginAsProfessional(professionalPage, TRIAGE_EMAIL);
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
