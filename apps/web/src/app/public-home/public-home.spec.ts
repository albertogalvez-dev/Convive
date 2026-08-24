import { TestBed } from '@angular/core/testing';

import publicHomeEs from '../../i18n/public-home/es.json';
import publicSiteFooterEs from '../../i18n/public-site-footer/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { professionalAccessUrlFor } from '../site-hosts';

import { PublicHome } from './public-home';

describe('PublicHome', () => {
  beforeEach(() => {
    vi.spyOn(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('uses the isolated application host for professional access from the public host', () => {
    expect(professionalAccessUrlFor('conviveaula.com')).toBe(
      'https://app.conviveaula.com/profesionales/acceso',
    );
    expect(professionalAccessUrlFor('localhost')).toBe('/profesionales/acceso');
  });

  it('offers clear public and professional destinations without a reporting-route detour', async () => {
    await TestBed.configureTestingModule({
      imports: [
        PublicHome,
        i18nTestingModule({
          'public-home': publicHomeEs,
          'public-site-footer': publicSiteFooterEs,
        }),
      ],
    }).compileComponents();

    const fixture = TestBed.createComponent(PublicHome);
    fixture.detectChanges();

    const page = fixture.nativeElement as HTMLElement;
    expect(page.querySelector('h1')?.textContent).toContain('Un canal seguro');
    expect(page.querySelector('video.hero-video source')?.getAttribute('src')).toBe(
      '/assets/public-home/convive-school-community.mp4',
    );
    expect(page.querySelector('video.hero-video')?.getAttribute('poster')).toBe(
      '/assets/public-home/convive-school-community-poster.jpg',
    );
    expect(page.querySelector('.hero-brand')).toBeNull();
    const heroVideo = page.querySelector<HTMLVideoElement>('video.hero-video');
    expect(heroVideo?.hasAttribute('autoplay')).toBe(true);
    expect(heroVideo?.loop).toBe(true);
    expect(heroVideo?.muted).toBe(true);
    expect(page.querySelector('.wordmark img')?.getAttribute('src')).toBe(
      '/convive-logo-reversed.svg',
    );
    expect(page.querySelector('a[href="/blog/"]')).toBeTruthy();
    expect(page.querySelectorAll('a[href="/demostracion/"]').length).toBe(3);
    expect(page.querySelector('.cards')).toBeNull();
    expect(page.querySelector('.journey')?.textContent).toContain(
      'Si algo preocupa, decirlo ayuda.',
    );
    expect(page.textContent).toContain('Explorar la demostración');
    expect(page.querySelector('a[href="/contacto/"]')).toBeTruthy();
    expect(page.querySelector('a[href="/profesionales/acceso"]')).toBeTruthy();
    expect(page.querySelector('[href*="/r/"]')).toBeNull();
    expect(page.querySelector('footer')?.textContent).toContain('datos ficticios');
  });

  it('keeps primary navigation operable through the labelled mobile menu control', async () => {
    await TestBed.configureTestingModule({
      imports: [
        PublicHome,
        i18nTestingModule({
          'public-home': publicHomeEs,
          'public-site-footer': publicSiteFooterEs,
        }),
      ],
    }).compileComponents();

    const fixture = TestBed.createComponent(PublicHome);
    fixture.detectChanges();

    const page = fixture.nativeElement as HTMLElement;
    const toggle = page.querySelector<HTMLButtonElement>('[aria-controls="primary-navigation"]');
    const navigation = page.querySelector<HTMLElement>('#primary-navigation');

    expect(toggle?.getAttribute('aria-label')).toBe('Abrir navegación principal');
    expect(toggle?.getAttribute('aria-expanded')).toBe('false');
    expect(navigation?.classList.contains('is-open')).toBe(false);

    toggle?.click();
    fixture.detectChanges();

    expect(toggle?.getAttribute('aria-label')).toBe('Cerrar navegación principal');
    expect(toggle?.getAttribute('aria-expanded')).toBe('true');
    expect(navigation?.classList.contains('is-open')).toBe(true);

    navigation?.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    fixture.detectChanges();

    expect(toggle?.getAttribute('aria-expanded')).toBe('false');
    expect(navigation?.classList.contains('is-open')).toBe(false);
  });
});
