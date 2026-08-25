import { describe, expect, it } from 'vitest';

import { blogAlternatePaths, blogArticlePath, blogIndexPath } from './blog-locales';

describe('blog locale paths', () => {
  it('keeps Spanish at the established URL and gives every other published locale its own URL', () => {
    expect(blogIndexPath('es')).toBe('/blog/');
    expect(blogIndexPath('ca')).toBe('/ca/blog/');
    expect(blogArticlePath('ar', 'escuchar-y-ordenar-comunicaciones')).toBe(
      '/ar/blog/escuchar-y-ordenar-comunicaciones/',
    );
  });

  it('declares every localized alternate plus an Spanish x-default', () => {
    expect(blogAlternatePaths('escuchar-y-ordenar-comunicaciones')).toEqual(
      expect.arrayContaining([
        {
          hrefLang: 'ar',
          path: '/ar/blog/escuchar-y-ordenar-comunicaciones/',
        },
        {
          hrefLang: 'x-default',
          path: '/blog/escuchar-y-ordenar-comunicaciones/',
        },
      ]),
    );
  });
});
