import { TestBed } from '@angular/core/testing';

import publicSiteFooterEs from '../../i18n/public-site-footer/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { PUBLIC_EMERGENCY_RESOURCES } from '../public-emergency-resources';
import { PublicSiteFooter } from './public-site-footer';

describe('PublicSiteFooter', () => {
  async function render(): Promise<HTMLElement> {
    await TestBed.configureTestingModule({
      imports: [PublicSiteFooter, i18nTestingModule({ 'public-site-footer': publicSiteFooterEs })],
    }).compileComponents();

    const fixture = TestBed.createComponent(PublicSiteFooter);
    fixture.detectChanges();

    return fixture.nativeElement as HTMLElement;
  }

  it('keeps the footer focused on routes and public resources', async () => {
    const footer = await render();

    const text = footer.textContent ?? '';
    expect(text).not.toContain('Demostración con datos ficticios');
    expect(footer.querySelector('.footer-disclaimer')).toBeNull();
  });

  it('signposts every official public resource as a dialable number', async () => {
    const footer = await render();

    for (const resource of PUBLIC_EMERGENCY_RESOURCES) {
      const link = footer.querySelector(`a[href="tel:${resource.dial}"]`);
      expect(link?.textContent).toContain(resource.number);
      expect(footer.textContent).toContain(
        resource.translationKey === 'emergency'
          ? 'Emergencias'
          : resource.translationKey === 'violenceAgainstWomen'
            ? 'violencia contra las mujeres'
            : 'ANAR',
      );
    }
  });

  it('reaches every published public document', async () => {
    const footer = await render();

    for (const path of [
      '/aviso-demostracion/',
      '/privacidad/',
      '/cookies/',
      '/terminos/',
      '/accesibilidad/',
    ]) {
      expect(footer.querySelector(`a[href="${path}"]`)).not.toBeNull();
    }
  });

  it('credits Aircury SL on every public page, as the scholarship requires', async () => {
    const footer = await render();

    expect(footer.textContent).toContain('Aircury SL');
    expect(footer.querySelector('a[href="https://www.aircury.es/"]')?.textContent).toContain(
      'Aircury SL',
    );
  });

  it('keeps the footer focused on public routes rather than a personal contact address', async () => {
    const footer = await render();

    expect(footer.querySelector('a[href^="mailto:"]')).toBeNull();
    expect(footer.querySelector('form')).toBeNull();
  });
});
