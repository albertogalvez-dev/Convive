/**
 * The catalogued locales for Convive's public site. `READY_LOCALES` governs
 * which completed locales visitors can select; adding a row here alone does
 * not publish it.
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
