import { RenderMode, ServerRoute } from '@angular/ssr';

import { publishedArticleMetadata } from './blog/blog-content';
import { READY_LOCALES } from './i18n/i18n-completeness';

export const serverRoutes: ServerRoute[] = [
  {
    path: 'blog',
    renderMode: RenderMode.Prerender,
  },
  {
    path: 'blog/:slug',
    renderMode: RenderMode.Prerender,
    async getPrerenderParams() {
      return publishedArticleMetadata().map((article) => ({ slug: article.slug }));
    },
  },
  {
    path: ':locale/blog',
    renderMode: RenderMode.Prerender,
    async getPrerenderParams() {
      return READY_LOCALES.filter((locale) => locale !== 'es').map((locale) => ({ locale }));
    },
  },
  {
    path: ':locale/blog/:slug',
    renderMode: RenderMode.Prerender,
    async getPrerenderParams() {
      return READY_LOCALES.filter((locale) => locale !== 'es').flatMap((locale) =>
        publishedArticleMetadata().map((article) => ({ locale, slug: article.slug })),
      );
    },
  },
  {
    path: '**',
    renderMode: RenderMode.Client,
  },
];
