import { READY_LOCALES } from '../i18n/i18n-completeness';
import { SOURCE_LOCALE } from '../i18n/i18n-locales';

export type BlogLocale = (typeof READY_LOCALES)[number];

export function isBlogLocale(value: string | null): value is BlogLocale {
  return value !== null && READY_LOCALES.includes(value as BlogLocale);
}

export function blogLocaleFromRoute(value: string | null): BlogLocale {
  return isBlogLocale(value) ? value : SOURCE_LOCALE;
}

export function blogIndexPath(locale: BlogLocale): string {
  return locale === SOURCE_LOCALE ? '/blog/' : `/${locale}/blog/`;
}

export function blogArticlePath(locale: BlogLocale, slug: string): string {
  return `${blogIndexPath(locale)}${encodeURIComponent(slug)}/`;
}

export function blogAlternatePaths(slug?: string): ReadonlyArray<{
  readonly hrefLang: BlogLocale | 'x-default';
  readonly path: string;
}> {
  const pathFor = (locale: BlogLocale) =>
    slug === undefined ? blogIndexPath(locale) : blogArticlePath(locale, slug);

  return [
    ...READY_LOCALES.map((locale) => ({ hrefLang: locale, path: pathFor(locale) })),
    { hrefLang: 'x-default' as const, path: pathFor(SOURCE_LOCALE) },
  ];
}
