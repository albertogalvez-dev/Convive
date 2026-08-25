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
        .print-secret,
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

test('keeps the public-home journey truthful and keyboard-operable on mobile', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/');

  await expect(page).toHaveTitle('Convive');

  const navigationToggle = page.getByRole('button', { name: 'Abrir navegación principal' });
  const primaryNavigation = page.getByRole('navigation', { name: 'Navegación principal' });
  await expect(navigationToggle).toBeVisible();
  await expect(navigationToggle).toHaveAttribute('aria-expanded', 'false');
  await expect(primaryNavigation.getByRole('link', { name: 'Blog' })).toBeHidden();

  await navigationToggle.press('Enter');

  await expect(page.getByRole('button', { name: 'Cerrar navegación principal' })).toHaveAttribute(
    'aria-expanded',
    'true',
  );
  await expect(primaryNavigation.getByRole('link', { name: 'Blog' })).toBeVisible();
  await primaryNavigation.getByRole('link', { name: 'Blog' }).press('Escape');
  await expect(page.getByRole('button', { name: 'Abrir navegación principal' })).toBeFocused();

  const demonstrationLink = page.getByRole('link', { name: 'Explorar la demostración' });
  await expect(demonstrationLink).toHaveAttribute('href', '/demostracion/');
  await expect(page.locator('video.hero-video')).toHaveAttribute(
    'poster',
    '/assets/public-home/convive-school-community-poster.jpg',
  );
  await expect(page.locator('video.hero-video')).toHaveAttribute('loop', '');
  expect(await page.locator('video.hero-video').evaluate((video) => video.muted)).toBe(true);
  await expect(page.locator('video.hero-video source')).toHaveAttribute(
    'src',
    '/assets/public-home/convive-school-community.mp4',
  );
  await expect(page.locator('[href*="/r/"]')).toHaveCount(0);
  await expectNoAccessibilityViolations(page);

  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
    ),
  ).toBe(true);
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

  // The optional details step precedes document preparation. Evidence stays
  // only in browser memory until the report has been created.
  await page.getByRole('button', { name: 'Continuar' }).click();

  await page.locator('#evidence-files').setInputFiles({
    name: `private-${runMarker}.pdf`,
    mimeType: 'application/pdf',
    buffer: Buffer.from('%PDF-1.7\nfictional evidence\n'),
  });
  await page.getByLabel('Descripción (opcional)').fill(evidenceDescription);

  // Advance from document preparation to the final review.
  await page.getByRole('button', { name: 'Continuar' }).click();

  await expect(page.getByRole('heading', { name: 'Revisa antes de enviar' })).toBeVisible();
  await page.getByRole('button', { name: 'Enviar' }).click();

  await expect(page.getByRole('heading', { name: 'Comunicación enviada' })).toBeVisible();
  const publicReference = await page.locator('.credential-value.reference code').innerText();
  const accessSecret = await page.locator('.credential-value.secret code').innerText();

  await expect(page.getByRole('button', { name: 'Copiar secreto' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Imprimir o guardar en PDF' })).toBeVisible();
  await expect(page.locator('.print-receipt')).toBeHidden();
  expect(await page.title()).not.toContain(publicReference);
  expect(await page.title()).not.toContain(accessSecret);
  expect(page.url()).not.toContain(publicReference);
  expect(page.url()).not.toContain(accessSecret);

  await page.emulateMedia({ media: 'print' });
  await expect(page.locator('.print-receipt')).toBeVisible();
  await expect(page.locator('.result')).toBeHidden();
  await expect(page.locator('.initial-evidence')).toBeHidden();
  await expect(page.locator('.print-receipt')).toContainText('app.conviveaula.com/seguimiento');
  await expect(page.locator('.print-secret')).toContainText(accessSecret);
  await expect(page.locator('.print-receipt')).not.toContainText(publicReference);
  await expect(page.locator('.print-warning')).toContainText(
    'Cualquiera que tenga este secreto puede entrar',
  );
  await expectNoAccessibilityViolations(page);
  await page.emulateMedia({ media: 'screen' });

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
    await skipWorkspaceIntroduction(professionalPage);
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
    await skipWorkspaceIntroduction(administratorPage);
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

test('renders a fictional-demo reporting profile without exposing a submission form', async ({
  page,
}) => {
  const reporterMutationUrls: string[] = [];

  page.on('request', (request) => {
    if (request.method() !== 'GET' && request.url().includes('/api/v1/')) {
      reporterMutationUrls.push(request.url());
    }
  });
  await page.route(
    `**/api/v1/public/organisations/${PUBLIC_REPORTING_IDENTIFIER}`,
    async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
          name: FICTIONAL_ORGANISATION,
          reportingMode: 'fictional_demo',
        }),
      });
    },
  );

  await page.goto(`/r/${PUBLIC_REPORTING_IDENTIFIER}`);

  await expect(page.getByText('Demostración ficticia', { exact: true })).toBeVisible();
  await expect(
    page.getByRole('heading', { name: 'Aquí no se guardan comunicaciones' }),
  ).toBeVisible();
  await expect(page.locator('form')).toHaveCount(0);
  await expectNoAccessibilityViolations(page);
  expect(reporterMutationUrls).toEqual([]);
});

test('keeps the fictional professional case workspace assignment-scoped', async ({ browser }) => {
  test.setTimeout(240_000);
  const leadContext = await browser.newContext();
  const administratorContext = await browser.newContext();

  try {
    const leadPage = await leadContext.newPage();
    await loginAsProfessional(leadPage, TRIAGE_EMAIL);
    await skipWorkspaceIntroduction(leadPage);
    await leadPage.goto(absoluteUrl('/profesionales/casos'));
    await expect(leadPage.getByRole('heading', { name: 'Casos', exact: true })).toBeVisible();
    await expect(leadPage.getByRole('button', { name: /Asignados/ })).toContainText('3');
    await expect(leadPage.getByRole('button', { name: /Fuera de plazo/ })).toContainText('1');
    await expect(leadPage.locator('.cases-card li a')).toHaveCount(3);
    await expect(leadPage.locator('.cases-card')).toContainText('00000101');
    await expect(leadPage.locator('.cases-card')).toContainText('00000083');
    await expect(leadPage.locator('.cases-card')).toContainText('00000108');
    await leadPage.getByRole('button', { name: /Fuera de plazo/ }).click();
    await expect(leadPage.locator('.cases-card li a')).toHaveCount(1);
    await leadPage.getByLabel('Ámbito').selectOption('digital');
    await leadPage.getByRole('button', { name: 'Aplicar' }).click();
    await expect(
      leadPage.getByRole('heading', { name: 'No hay casos que coincidan' }),
    ).toBeVisible();
    await expectNoAccessibilityViolations(leadPage);
    await leadPage.setViewportSize({ width: 390, height: 844 });
    expect(
      await leadPage.evaluate(
        () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
      ),
    ).toBe(true);
    await leadPage.setViewportSize({ width: 1280, height: 720 });
    await leadPage.getByRole('button', { name: 'Limpiar' }).click();
    await expect(leadPage.locator('.cases-card li a')).toHaveCount(1);
    await leadPage.locator('.cases-card li a').click();
    await expect(leadPage.getByRole('heading', { name: 'Seguimiento del caso' })).toBeVisible();
    await expect(leadPage.getByRole('heading', { name: 'Tareas' })).toBeVisible();
    await expect(leadPage.getByRole('heading', { name: 'Decisión registrada' })).toBeVisible();
    await expect(leadPage.getByText('Abierto como caso', { exact: true })).toBeVisible();
    await expect(leadPage.getByRole('heading', { name: 'Auditoría' })).toBeVisible();
    await expect(leadPage.getByRole('link', { name: 'Exportar CSV' })).toBeVisible();
    await expect(leadPage.getByText('Todavía no hay acciones auditables.')).toBeVisible();
    await expectNoAccessibilityViolations(leadPage);

    // The task-planning catalogue is the detail page's expandable interactive
    // state. It is an inline form, rather than a dialog, so exercise the
    // surface the professional can actually open.
    await leadPage.getByRole('button', { name: 'Nueva tarea' }).click();
    await expect(leadPage.getByLabel('Plantilla revisada')).toBeVisible();
    await expectNoAccessibilityViolations(leadPage);

    const assignedCaseUrl = leadPage.url();

    const administratorPage = await administratorContext.newPage();
    await loginAsProfessional(administratorPage, ADMINISTRATOR_EMAIL);
    await skipWorkspaceIntroduction(administratorPage);
    await administratorPage.goto(assignedCaseUrl);
    await expect(
      administratorPage.getByRole('heading', { name: 'Caso no disponible' }),
    ).toBeVisible();
  } finally {
    await administratorContext.close();
    await leadContext.close();
  }
});

test('keeps the public product homepage accessible and separate from reporting entry', async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/');

  await expect(page.locator('h1')).toContainText('Un canal seguro para escuchar antes.');
  await expect(page.locator('video.hero-video')).toHaveAttribute('autoplay', '');
  await expectNoAccessibilityViolations(page);
  await expect(page.locator('a.professional-access')).toHaveAttribute(
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
    page.getByRole('heading', { name: 'Descubre Convive sin enviar nada' }),
  ).toBeVisible();
  await expectNoAccessibilityViolations(page);

  await page.goto('/');
  const homeLink = page.getByRole('link', { name: 'Convive, inicio' });
  await expect(homeLink).toBeVisible();
  await page.keyboard.press('Tab');
  await expect(homeLink).toBeFocused();
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

test('lets a visitor switch to Catalan, Valencian or Basque by keyboard, with the choice remembered', async ({
  page,
  context,
}) => {
  await page.goto('/');

  await expect(
    page.getByRole('heading', { name: 'Un canal seguro para escuchar antes.' }),
  ).toBeVisible();

  const languageSwitcher = page.getByRole('combobox');

  await expect(languageSwitcher).toHaveValue('es');

  // Keyboard-only: focus the control the same way a screen-reader or
  // keyboard-only visitor would, without ever clicking it.
  await languageSwitcher.focus();
  await expect(languageSwitcher).toBeFocused();
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Enter');

  await expect(
    page.getByRole('heading', { name: 'Un canal segur per escoltar abans.' }),
  ).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.lang)).toBe('ca');
  await expectNoAccessibilityViolations(page);

  // No IP geolocation or other inference decides Catalan vs Valencian --
  // only an explicit choice, persisted for next time. Keep the native
  // combobox locator stable across translations; the text label itself is
  // intentionally re-rendered by the active locale.
  await expect(languageSwitcher).toHaveValue('ca');
  await page.reload();
  await expect(
    page.getByRole('heading', { name: 'Un canal segur per escoltar abans.' }),
  ).toBeVisible();
  await expect(page.getByRole('combobox')).toHaveValue('ca');

  await page.getByRole('combobox').selectOption('ca-valencia');
  await expect(
    page.getByRole('heading', { name: 'Un canal segur per a escoltar abans.' }),
  ).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.lang)).toBe('ca-valencia');
  await expectNoAccessibilityViolations(page);

  await page.getByRole('combobox').selectOption('eu');
  await expect(
    page.getByRole('heading', { name: 'Aurretik entzuteko kanal segurua.' }),
  ).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.lang)).toBe('eu');
  await expect(page.getByRole('combobox')).toHaveValue('eu');
  await expectNoAccessibilityViolations(page);

  // A fresh, unrelated browser context never inherits another visitor's
  // stored choice -- the persistence is per-visitor, not server-side or
  // inferred from anything about this request.
  const freshContext = await context.browser()?.newContext();
  try {
    const freshPage = await freshContext?.newPage();
    await freshPage?.goto(absoluteUrl('/'));
    await expect(freshPage?.getByLabel('Idioma')).toHaveValue('es');
  } finally {
    await freshContext?.close();
  }
});

test('switches to Arabic, flips the document to right-to-left and reflows the layout', async ({
  page,
}) => {
  await page.goto('/');

  await expect(
    page.getByRole('heading', { name: 'Un canal seguro para escuchar antes.' }),
  ).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.dir)).toBe('ltr');

  const wordmarkBeforeSwitch = await page.locator('header .wordmark').boundingBox();
  const navBeforeSwitch = await page.locator('header nav').boundingBox();

  if (wordmarkBeforeSwitch === null || navBeforeSwitch === null) {
    throw new Error('The header wordmark or nav could not be measured before switching locale.');
  }

  // In the default left-to-right layout the wordmark sits at the header's
  // start (left) and the nav cluster is pushed to its end (right).
  expect(wordmarkBeforeSwitch.x).toBeLessThan(navBeforeSwitch.x);

  await page.getByLabel('Idioma').selectOption('ar');

  await expect(page.getByRole('heading', { name: 'قناة آمنة للاستماع أولًا.' })).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.lang)).toBe('ar');
  expect(await page.evaluate(() => document.documentElement.dir)).toBe('rtl');

  // The same `header nav { margin-inline-start: auto }` rule that pushes the
  // nav cluster to the header's end in Spanish pushes it to the *opposite*
  // physical side once the document is right-to-left: this is a real layout
  // reflow driven by the `dir` attribute, not just translated text on an
  // unchanged page.
  const wordmarkAfterSwitch = await page.locator('header .wordmark').boundingBox();
  const navAfterSwitch = await page.locator('header nav').boundingBox();

  if (wordmarkAfterSwitch === null || navAfterSwitch === null) {
    throw new Error('The header wordmark or nav could not be measured after switching locale.');
  }

  expect(wordmarkAfterSwitch.x).toBeGreaterThan(navAfterSwitch.x);

  // The switcher's own visible label is now translated too: re-locate it by
  // its Arabic label rather than reusing the stale "Idioma" locator, the
  // same way the Catalan/Valencian test re-locates by "Llengua".
  await expect(page.getByRole('combobox')).toHaveValue('ar');
  await expectNoAccessibilityViolations(page);

  await page.reload();
  expect(await page.evaluate(() => document.documentElement.dir)).toBe('rtl');
  await expect(page.getByRole('combobox')).toHaveValue('ar');
});

test('keeps reviewed blog content accessible, responsive and attributable', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/blog/');

  await expect(
    page.getByRole('heading', { name: 'Convivencia escolar, explicada con rigor.' }),
  ).toBeVisible();
  await expectNoAccessibilityViolations(page);
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
    ),
  ).toBe(true);

  await page
    .getByRole('link', { name: 'Escuchar y ordenar comunicaciones sin prometer más de lo posible' })
    .click();
  await expect(
    page.getByRole('heading', {
      name: 'Escuchar y ordenar comunicaciones sin prometer más de lo posible',
    }),
  ).toBeVisible();
  await expect(page.locator('.source-list a')).toHaveCount(2);
  await expect(page.locator('link[rel="canonical"]')).toHaveAttribute(
    'href',
    'https://conviveaula.com/blog/escuchar-y-ordenar-comunicaciones/',
  );
  await expectNoAccessibilityViolations(page);
});

test('offers the fictional demonstration through real, isolated example paths', async ({
  page,
}) => {
  await page.route('**/api/**', (route) => route.abort());
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/demostracion/');

  await expect(page.getByText('CENTRO DEMO CONVIVE')).toBeVisible();
  await expect(
    page.getByRole('heading', { name: 'Descubre Convive sin enviar nada' }),
  ).toBeVisible();
  await expect(page).toHaveTitle('Demostración | Convive');
  await expect(page.getByRole('link', { name: 'Empezar una comunicación' })).toHaveAttribute(
    'href',
    `/r/${PUBLIC_REPORTING_IDENTIFIER}`,
  );
  await expect(page.getByRole('link', { name: 'Ver un ejemplo completado' })).toHaveAttribute(
    'href',
    `/r/${PUBLIC_REPORTING_IDENTIFIER}?ejemplo=completo`,
  );
  await expect(page.getByRole('link', { name: 'Abrir cartel a tamaño completo' })).toHaveAttribute(
    'target',
    '_blank',
  );
  await expect(page.getByRole('link', { name: 'Entrar al área profesional' })).toHaveAttribute(
    'href',
    '/profesionales/acceso',
  );
  await expect(page.locator('textarea')).toHaveCount(0);
  await expect(page.locator('form')).toHaveCount(0);
  await expectNoAccessibilityViolations(page);
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= document.documentElement.clientWidth,
    ),
  ).toBe(true);

  await page.getByRole('link', { name: 'Entrar al área profesional' }).click();
  await expect(page).toHaveURL(/\/profesionales\/acceso$/);
  await expect(page.getByRole('heading', { name: 'Elige un rol' })).toBeVisible();
  await expect(page.getByLabel('Perspectiva de demostración')).toBeVisible();
  await expect(page.locator('#demo-role option')).toHaveCount(2);
  await expect(page.getByRole('button', { name: 'Entrar con esta perspectiva' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Convive, inicio' })).toHaveAttribute('href', '/');

  // The selected perspective intentionally calls the protected-workspace demo
  // endpoint. Its complete browser journey runs against the seeded API stack,
  // not an aborted API.
  await expectNoAccessibilityViolations(page);
});

test('opens the prepared case-management workspace as a read-only selected perspective', async ({
  page,
}) => {
  await page.goto('/profesionales/acceso');

  await expect(page.getByRole('heading', { name: 'Elige un rol' })).toBeVisible();
  await page.getByLabel('Perspectiva de demostración').selectOption('triage');
  await page.getByRole('button', { name: 'Entrar con esta perspectiva' }).click();

  await expect(page).toHaveURL(/\/profesionales$/);
  await expect(page.getByRole('heading', { name: /Hola,/ })).toBeVisible();
  await expect(page.getByText('Vista de demostración', { exact: false })).toHaveCount(0);
});

test('ends the selected demonstration session and returns to professional access', async ({
  page,
}) => {
  await page.goto('/profesionales/acceso');
  await page.getByLabel('Perspectiva de demostración').selectOption('administrator');
  await page.getByRole('button', { name: 'Entrar con esta perspectiva' }).click();
  await expect(page).toHaveURL(/\/profesionales\/cuentas$/);

  await skipWorkspaceIntroduction(page);

  await page.getByRole('button', { name: 'Cerrar sesión' }).click();

  await expect(page).toHaveURL(/\/profesionales\/acceso$/);
  await expect(page.getByRole('heading', { name: 'Elige un rol' })).toBeVisible();
});

test('opens a prepared communication from the professional inbox', async ({ page }) => {
  test.setTimeout(240_000);

  await page.goto('/profesionales/acceso');
  await page.getByLabel('Perspectiva de demostración').selectOption('triage');
  await page.getByRole('button', { name: 'Entrar con esta perspectiva' }).click();
  await expect(page).toHaveURL(/\/profesionales$/);
  await skipWorkspaceIntroduction(page);

  await page.getByRole('link', { name: 'Comunicaciones' }).first().click();
  await expect(page).toHaveURL(/\/profesionales\/comunicaciones$/);
  await page
    .getByText(
      'Una alumna ficticia ha dejado de participar en el recreo y parece preocupada desde hace varios días.',
      { exact: true },
    )
    .click();

  await expect(page).toHaveURL(/\/profesionales\/comunicaciones\/[0-9a-f-]+$/);
  await expect(page.getByRole('heading', { name: 'Comunicación', exact: true })).toBeVisible();
  await expect(page.getByText('Contenido original', { exact: true })).toBeVisible();
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
  const response = await page.context().request.post(absoluteUrl('/api/v1/professional/session'), {
    data: { email, password: PROFESSIONAL_PASSWORD },
    headers: {
      Origin: APP_BASE_URL,
      'Sec-Fetch-Site': 'same-origin',
    },
  });

  expect(response.ok()).toBe(true);
  await page.goto(absoluteUrl('/profesionales'));
  await expect(page.getByRole('heading', { name: /Hola,/ })).toBeVisible();
}

/**
 * A professional meeting the workspace for the first time is introduced to it,
 * and every browser context here is a first visit. Leaving the introduction is
 * part of arriving at the workspace.
 *
 * This is deliberately separate from the login helper rather than folded into
 * it: the performance test measures that helper, and dismissing a dialog is not
 * part of how long the dashboard takes to become usable.
 */
async function skipWorkspaceIntroduction(page: import('@playwright/test').Page): Promise<void> {
  const introduction = page.getByRole('dialog');
  await expect(introduction).toBeVisible();
  await expectNoAccessibilityViolations(page);
  await page.getByRole('button', { name: 'Saltar la introducción' }).click();
  await expect(introduction).toBeHidden();
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
  // Scoped translations (the shared footer's, in particular) resolve over a
  // follow-up HTTP request after the initial page load, so scanning right on
  // 'load' can catch a focusable link before its accessible name has arrived.
  // Not every page includes the footer (the reporting form deliberately
  // doesn't), so only wait for it when it's actually present on this page.
  const footerTitle = page.locator('#footer-boundary-title');

  if ((await footerTitle.count()) > 0) {
    await footerTitle.filter({ hasText: /\S/ }).waitFor();
  }

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
