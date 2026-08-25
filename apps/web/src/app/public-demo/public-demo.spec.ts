import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import publicDemoEs from '../../i18n/public-demo/es.json';
import publicSiteFooterEs from '../../i18n/public-site-footer/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { PublicDemo } from './public-demo';

describe('PublicDemo', () => {
  it('offers the real fictional paths without simulating a submission', async () => {
    await TestBed.configureTestingModule({
      imports: [
        PublicDemo,
        i18nTestingModule({
          'public-demo': publicDemoEs,
          'public-site-footer': publicSiteFooterEs,
        }),
      ],
      providers: [provideRouter([])],
    }).compileComponents();

    const fixture = TestBed.createComponent(PublicDemo);
    fixture.detectChanges();
    const page = fixture.nativeElement as HTMLElement;

    expect(page.textContent).toContain('CENTRO DEMO CONVIVE');
    expect(page.querySelector('textarea')).toBeNull();
    expect(page.querySelector('form')).toBeNull();
    expect(page.querySelector('a[href="/r/ORG_DEM0000000000000"]')).not.toBeNull();
    expect(page.querySelector('a[href="/r/ORG_DEM0000000000000?ejemplo=completo"]')).not.toBeNull();
    const posterLink = page.querySelector('a.poster-link') as HTMLAnchorElement;

    expect(posterLink).toBeTruthy();
    expect(posterLink.target).toBe('_blank');
    expect(posterLink.href).toContain('convive-poster-ORG_DEM0000000000000-es.png');
    expect(page.querySelector('a[href="/profesionales/acceso"]')).not.toBeNull();
  });
});
