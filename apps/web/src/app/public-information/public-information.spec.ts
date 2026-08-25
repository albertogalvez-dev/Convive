import { TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';

import publicInformationEs from '../../i18n/public-information/es.json';
import publicSiteFooterEs from '../../i18n/public-site-footer/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import {
  PUBLIC_CONTACT_NOTICE,
  PUBLIC_COOKIE_NOTICE,
  PUBLIC_DEMONSTRATION_NOTICE,
  PUBLIC_PRIVACY_NOTICE,
  PUBLIC_SANDBOX_TERMS,
  PUBLIC_ACCESSIBILITY_NOTICE,
  PublicInformationPageMeta,
} from './public-information-content';
import { PublicInformation } from './public-information';

async function renderWith(meta: PublicInformationPageMeta): Promise<HTMLElement> {
  await TestBed.configureTestingModule({
    imports: [
      PublicInformation,
      i18nTestingModule({
        'public-information': publicInformationEs,
        'public-site-footer': publicSiteFooterEs,
      }),
    ],
    providers: [{ provide: ActivatedRoute, useValue: { snapshot: { data: { meta } } } }],
  }).compileComponents();

  const fixture = TestBed.createComponent(PublicInformation);
  fixture.detectChanges();
  await fixture.whenStable();
  fixture.detectChanges();

  return fixture.nativeElement as HTMLElement;
}

describe('PublicInformation', () => {
  it('publishes the document body without exposing an operational journey', async () => {
    const page = await renderWith(PUBLIC_DEMONSTRATION_NOTICE);

    expect(page.querySelector('h1')?.textContent).toContain(
      'demostración con información ficticia',
    );
    expect(page.querySelector('.document-body h2')?.textContent).toContain('Qué no es Convive');
    expect(page.querySelector('.document-body li')?.textContent).toContain(
      'No es un canal de emergencia',
    );
    expect(page.querySelector('a.back')).toBeNull();
    expect(page.querySelector('[href*="/r/"]')).toBeNull();
    expect(page.querySelector('article')?.classList.contains('demonstration-notice')).toBe(true);
    expect(page.querySelector('.demonstration-resources h2')?.textContent).toContain(
      'Teléfonos públicos de ayuda',
    );
  });

  it('keeps the public document focused on its current guidance', async () => {
    const page = await renderWith(PUBLIC_DEMONSTRATION_NOTICE);

    expect(page.querySelector('.review')).toBeNull();
    expect(page.textContent).not.toContain('Revisado el');
  });

  it('puts the cookie guarantees beside the introduction without dropping the technical explanation', async () => {
    const page = await renderWith(PUBLIC_COOKIE_NOTICE);

    expect(page.querySelector('article')?.classList.contains('cookie-notice')).toBe(true);
    expect(page.querySelector('.cookie-guarantees h2')?.textContent).toContain('Qué no se guarda');
    expect(page.querySelector('.document-body h2')?.textContent).toContain(
      'Qué se guarda en tu navegador',
    );
  });

  it('keeps the sandbox safety boundary visible beside the terms introduction', async () => {
    const page = await renderWith(PUBLIC_SANDBOX_TERMS);

    expect(page.querySelector('article')?.classList.contains('terms-notice')).toBe(true);
    expect(page.querySelector('.terms-safety h2')?.textContent).toContain(
      'Esto no es un canal de ayuda',
    );
    expect(page.querySelector('.document-body h2')?.textContent).toContain('Información inventada');
  });

  it('keeps accessibility feedback visible beside the honest review state', async () => {
    const page = await renderWith(PUBLIC_ACCESSIBILITY_NOTICE);

    expect(page.querySelector('article')?.classList.contains('accessibility-notice')).toBe(true);
    expect(page.querySelector('.accessibility-feedback h2')?.textContent).toContain(
      'Qué hacemos con lo que nos cuentas',
    );
    expect(page.querySelector('.document-body h2')?.textContent).toContain('Estado de la revisión');
  });

  it('makes each contact route an explicit email action beside the introduction', async () => {
    const page = await renderWith(PUBLIC_CONTACT_NOTICE);

    expect(page.querySelector('article')?.classList.contains('contact-notice')).toBe(true);
    expect(page.querySelector('.contact-methods h2')?.textContent).toContain('Dos direcciones');
    expect(page.querySelectorAll<HTMLAnchorElement>('.contact-email')).toHaveLength(2);
    expect(page.querySelector<HTMLAnchorElement>('.contact-email')?.href).toBe(
      'mailto:hola@conviveaula.com',
    );
    expect(page.querySelector('.contact-methods')?.textContent).not.toContain(' · ');
    expect(page.querySelector('.contact-methods')?.textContent).toContain(
      'Dudas sobre el proyecto',
    );
    expect(page.querySelector('.document-body')).toBeNull();
  });

  it('interpolates the contact email into the translated prose rather than leaving a placeholder', async () => {
    const page = await renderWith(PUBLIC_PRIVACY_NOTICE);

    expect(page.textContent).toContain('privacy@conviveaula.com');
    expect(page.textContent).not.toContain('{{privacyEmail}}');
    expect(page.querySelector('article')?.classList.contains('privacy-notice')).toBe(true);
    expect(page.querySelector('.privacy-principles h2')?.textContent).toContain('Qué no se hace');
    expect(page.querySelector<HTMLAnchorElement>('.operator-link')?.href).toBe(
      'https://www.linkedin.com/in/alberto-galvez-aguado/',
    );
  });

  it('carries the safety footer on every document, not only the demonstration notice', async () => {
    const page = await renderWith(PUBLIC_CONTACT_NOTICE);

    const footer = page.querySelector('footer')?.textContent ?? '';
    expect(footer).not.toContain('datos ficticios');
    expect(footer).toContain('112');
  });

  it('keeps the language selector as a compact utility after professional access', async () => {
    const page = await renderWith(PUBLIC_DEMONSTRATION_NOTICE);
    const headerChildren = Array.from(page.querySelector('header')?.children ?? []);

    expect(headerChildren.map((child) => child.tagName)).toEqual([
      'A',
      'A',
      'APP-LANGUAGE-SWITCHER',
    ]);
    expect(headerChildren[1]?.classList.contains('professional-access')).toBe(true);
    expect(headerChildren[2]?.classList.contains('compact')).toBe(true);
  });
});
