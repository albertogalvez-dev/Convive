/**
 * The recorded linguistic scope for Convive's public site.
 *
 * `es` is the source language every other locale is translated from and
 * reviewed against. Every other entry is a candidate: it exists in the
 * catalogue as soon as its issue is opened, but {@link isLocaleReady} is what
 * actually governs whether a visitor can ever select it. Adding a row here
 * does not publish anything by itself.
 */
export interface Locale {
  readonly code: string;
  readonly label: string;
  readonly direction: 'ltr' | 'rtl';
}

export const SOURCE_LOCALE = 'es';

export const LOCALES: readonly Locale[] = [
  { code: 'es', label: 'Español', direction: 'ltr' },
  { code: 'ca', label: 'Català', direction: 'ltr' },
  { code: 'ca-valencia', label: 'Valencià', direction: 'ltr' },
  { code: 'eu', label: 'Euskara', direction: 'ltr' },
  { code: 'gl', label: 'Galego', direction: 'ltr' },
  { code: 'oc-aranes', label: 'Aranés', direction: 'ltr' },
  { code: 'ar', label: 'العربية', direction: 'rtl' },
];

export function localeDirection(code: string): 'ltr' | 'rtl' {
  return LOCALES.find((locale) => locale.code === code)?.direction ?? 'ltr';
}
