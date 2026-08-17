import { TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';

import publicInformationEs from '../../i18n/public-information/es.json';
import publicSiteFooterEs from '../../i18n/public-site-footer/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import {
  PUBLIC_CONTACT_NOTICE,
  PUBLIC_DEMONSTRATION_NOTICE,
  PUBLIC_PRIVACY_NOTICE,
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

  return fixture.nativeElement as HTMLElement;
}

describe('PublicInformation', () => {
  it('publishes the document body without exposing an operational journey', async () => {
    const page = await renderWith(PUBLIC_DEMONSTRATION_NOTICE);

    expect(page.querySelector('h1')?.textContent).toContain(
      'demostración con información ficticia',
    );
    expect(page.querySelector('h2')?.textContent).toContain('Qué no es Convive');
    expect(page.querySelector('li')?.textContent).toContain('No es un canal de emergencia');
    expect(
      Array.from(page.querySelectorAll('a[href="/"]')).some((link) =>
        link.textContent?.includes('Volver al inicio'),
      ),
    ).toBe(true);
    expect(page.querySelector('[href*="/r/"]')).toBeNull();
  });

  it('states who reviewed the page and what makes it due again', async () => {
    const page = await renderWith(PUBLIC_DEMONSTRATION_NOTICE);

    const review = page.querySelector('.review')?.textContent ?? '';
    expect(review).toContain(PUBLIC_DEMONSTRATION_NOTICE.reviewedOn);
    expect(review).toContain(PUBLIC_DEMONSTRATION_NOTICE.owner);
    expect(review).toContain('Se revisa cada seis meses');
  });

  it('interpolates the contact email into the translated prose rather than leaving a placeholder', async () => {
    const page = await renderWith(PUBLIC_PRIVACY_NOTICE);

    expect(page.textContent).toContain('privacy@conviveaula.com');
    expect(page.textContent).not.toContain('{{privacyEmail}}');
  });

  it('carries the safety footer on every document, not only the demonstration notice', async () => {
    const page = await renderWith(PUBLIC_CONTACT_NOTICE);

    const footer = page.querySelector('footer')?.textContent ?? '';
    expect(footer).toContain('datos ficticios');
    expect(footer).toContain('112');
  });
});
