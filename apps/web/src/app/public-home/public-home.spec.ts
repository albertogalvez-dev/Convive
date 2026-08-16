import { TestBed } from '@angular/core/testing';

import publicSiteFooterEs from '../../i18n/public-site-footer/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { professionalAccessUrlFor } from '../site-hosts';

import { PublicHome } from './public-home';

describe('PublicHome', () => {
  it('uses the isolated application host for professional access from the public host', () => {
    expect(professionalAccessUrlFor('conviveaula.com')).toBe(
      'https://app.conviveaula.com/profesionales/acceso',
    );
    expect(professionalAccessUrlFor('localhost')).toBe('/profesionales/acceso');
  });

  it('offers clear public and professional destinations without a reporting-route detour', async () => {
    await TestBed.configureTestingModule({
      imports: [PublicHome, i18nTestingModule({ 'public-site-footer': publicSiteFooterEs })],
    }).compileComponents();

    const fixture = TestBed.createComponent(PublicHome);
    fixture.detectChanges();

    const page = fixture.nativeElement as HTMLElement;
    expect(page.querySelector('h1')?.textContent).toContain('Un canal seguro');
    expect(page.querySelector('a[href="/blog/"]')).toBeTruthy();
    expect(page.querySelectorAll('a[href="/demostracion/"]').length).toBe(4);
    expect(page.querySelector('a[href="/contacto/"]')).toBeTruthy();
    expect(page.querySelector('a[href="/profesionales/acceso"]')).toBeTruthy();
    expect(page.querySelector('[href*="/r/"]')).toBeNull();
    expect(page.querySelector('footer')?.textContent).toContain('datos ficticios');
  });
});
