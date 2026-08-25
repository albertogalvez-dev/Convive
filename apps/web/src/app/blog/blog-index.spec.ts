import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import blogEs from '../../i18n/blog/es.json';
import publicSiteFooterEs from '../../i18n/public-site-footer/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { BlogIndex } from './blog-index';

describe('BlogIndex', () => {
  it('publishes reviewed content with a canonical index page', async () => {
    await TestBed.configureTestingModule({
      imports: [
        BlogIndex,
        i18nTestingModule({ blog: blogEs, 'public-site-footer': publicSiteFooterEs }),
      ],
      providers: [provideRouter([])],
    }).compileComponents();

    const fixture = TestBed.createComponent(BlogIndex);
    fixture.detectChanges();

    const page = fixture.nativeElement as HTMLElement;
    expect(page.querySelector('h1')?.textContent).toContain('Convivencia escolar');
    expect(page.querySelector('a[href="/blog/escuchar-y-ordenar-comunicaciones"]')).toBeTruthy();
    expect(page.querySelector('app-language-switcher')?.classList.contains('compact')).toBe(true);
    expect(page.querySelector<HTMLImageElement>('.article-cover img')?.alt).toContain(
      'Pasillo escolar vacío',
    );
    expect(document.title).toBe('Blog | Convive');
    expect(document.querySelector('link[rel="canonical"]')?.getAttribute('href')).toBe(
      'https://conviveaula.com/blog/',
    );
    expect(document.querySelectorAll('link[rel="alternate"][hreflang]')).toHaveLength(7);
  });
});
