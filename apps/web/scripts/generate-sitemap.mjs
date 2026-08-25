import { readFile, writeFile } from 'node:fs/promises';

const hostname = 'https://conviveaula.com';
const locales = ['es', 'ca', 'ca-valencia', 'eu', 'gl', 'ar'];
const now = new Date();
const catalog = JSON.parse(
  await readFile(new URL('../src/app/blog/blog-catalog.json', import.meta.url), 'utf8'),
);

const staticPages = [
  ['/', '2026-08-11'],
  ['/aviso-demostracion/', '2026-08-16'],
  ['/privacidad/', '2026-08-16'],
  ['/cookies/', '2026-08-16'],
  ['/terminos/', '2026-08-16'],
  ['/accesibilidad/', '2026-08-16'],
  ['/contacto/', '2026-08-16'],
];

const url = (path, lastmod) =>
  `  <url><loc>${hostname}${path}</loc><lastmod>${lastmod}</lastmod></url>`;
const blogPath = (locale, slug = '') =>
  `${locale === 'es' ? '' : `/${locale}`}/blog/${slug === '' ? '' : `${slug}/`}`;
const publishedArticles = catalog.filter(
  (article) =>
    article.publicationStatus === 'published' &&
    new Date(`${article.publishedAt}T00:00:00Z`) <= now,
);

const entries = [
  ...staticPages.map(([path, lastmod]) => url(path, lastmod)),
  ...locales.flatMap((locale) => [
    url(blogPath(locale), publishedArticles.at(-1)?.updatedAt ?? '2026-08-11'),
    ...publishedArticles.map((article) => url(blogPath(locale, article.slug), article.updatedAt)),
  ]),
];

await writeFile(
  new URL('../src/sitemap.xml', import.meta.url),
  [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    ...entries,
    '</urlset>',
    '',
  ].join('\n'),
);
