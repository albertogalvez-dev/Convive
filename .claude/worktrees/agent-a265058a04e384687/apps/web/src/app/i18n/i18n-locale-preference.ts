import { isLocaleReady } from './i18n-completeness';

/**
 * The visitor's or organisation's explicitly chosen locale.
 *
 * Read and written to `localStorage` only -- never inferred from IP
 * geolocation, `Accept-Language`, or any other automatic signal. #256
 * requires the choice between Catalan and its Valencian variant, in
 * particular, to be explicit: guessing it would get it wrong for a visitor
 * travelling or a school near a regional border, and would read as taking
 * a side in a naming dispute neither region would accept being decided
 * for them automatically.
 */
const STORAGE_KEY = 'convive-locale';

/**
 * @returns the visitor's previously chosen locale, or `null` if none was
 * ever stored, storage is unavailable (private browsing, disabled storage),
 * or the stored value is no longer a signed-off locale.
 */
export function readStoredLocale(): string | null {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);

    return stored !== null && isLocaleReady(stored) ? stored : null;
  } catch {
    return null;
  }
}

/**
 * Persists an explicit locale choice. Failure is silent and non-fatal: the
 * active locale for the rest of this session is set through
 * `TranslocoService` regardless of whether the choice could be remembered
 * for next time.
 */
export function storeLocale(code: string): void {
  try {
    localStorage.setItem(STORAGE_KEY, code);
  } catch {
    // Storage unavailable; nothing to do.
  }
}
