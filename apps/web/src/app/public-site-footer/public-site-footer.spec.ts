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

  it('states the demonstration boundary without making the visitor look for it', async () => {
    const footer = await render();

    const text = footer.textContent ?? '';
    expect(text).toContain('datos ficticios');
    expect(text).toContain('no es un canal de emergencia');
    expect(text).toContain('ninguna administración educativa');
  });

  it('signposts every official public resource as a dialable number', async () => {
    const footer = await render();

    for (const resource of PUBLIC_EMERGENCY_RESOURCES) {
      const link = footer.querySelector(`a[href="tel:${resource.dial}"]`);
      expect(link?.textContent).toContain(resource.number);
      expect(footer.textContent).toContain(resource.name);
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
  });

  it('offers a contact route that is an address rather than a form', async () => {
    const footer = await render();

    expect(footer.querySelector('a[href^="mailto:hola@"]')).not.toBeNull();
    expect(footer.querySelector('form')).toBeNull();
  });
});
