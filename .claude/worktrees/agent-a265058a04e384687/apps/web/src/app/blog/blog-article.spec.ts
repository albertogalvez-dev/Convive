import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';

import publicSiteFooterEs from '../../i18n/public-site-footer/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { BlogArticle } from './blog-article';

describe('BlogArticle', () => {
  it('renders attributed content and article metadata for a published slug', async () => {
    await TestBed.configureTestingModule({
      imports: [BlogArticle, i18nTestingModule({ 'public-site-footer': publicSiteFooterEs })],
      providers: [
        provideRouter([]),
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: convertToParamMap({ slug: 'escuchar-y-ordenar-comunicaciones' }),
            },
          },
        },
      ],
    }).compileComponents();

    const fixture = TestBed.createComponent(BlogArticle);
    fixture.detectChanges();

    const page = fixture.nativeElement as HTMLElement;
    expect(page.querySelector('h1')?.textContent).toContain('Escuchar y ordenar');
    expect(page.querySelectorAll('.source-list a')).toHaveLength(2);
    expect(document.querySelector('meta[property="og:type"]')?.getAttribute('content')).toBe(
      'article',
    );
    expect(document.querySelector('link[rel="canonical"]')?.getAttribute('href')).toBe(
      'https://conviveaula.com/blog/escuchar-y-ordenar-comunicaciones/',
    );
  });

  it('does not index an unknown article slug', async () => {
    await TestBed.configureTestingModule({
      imports: [BlogArticle, i18nTestingModule({ 'public-site-footer': publicSiteFooterEs })],
      providers: [
        provideRouter([]),
        {
          provide: ActivatedRoute,
          useValue: { snapshot: { paramMap: convertToParamMap({ slug: 'missing' }) } },
        },
      ],
    }).compileComponents();

    const fixture = TestBed.createComponent(BlogArticle);
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('h1')?.textContent).toContain('no disponible');
    expect(document.querySelector('meta[name="robots"]')?.getAttribute('content')).toBe(
      'noindex, nofollow',
    );
  });
});
