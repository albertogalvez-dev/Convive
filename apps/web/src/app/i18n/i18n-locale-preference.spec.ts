import { readStoredLocale, storeLocale } from './i18n-locale-preference';

describe('i18n locale preference', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  afterEach(() => {
    localStorage.clear();
  });

  it('reads back a stored, signed-off locale', () => {
    storeLocale('ca');

    expect(readStoredLocale()).toBe('ca');
  });

  it('returns null when nothing has ever been stored', () => {
    expect(readStoredLocale()).toBeNull();
  });

  it('refuses a stored value that is not a signed-off locale', () => {
    // Not `storeLocale()`: this simulates a stale or tampered value, not
    // the normal write path, which only ever writes a ready locale.
    localStorage.setItem('convive-locale', 'xx');

    expect(readStoredLocale()).toBeNull();
  });

  it('refuses a stored value for a locale that exists but is not ready', () => {
    localStorage.setItem('convive-locale', 'ar');

    expect(readStoredLocale()).toBeNull();
  });

  it('does not throw when storage is unavailable', () => {
    const original = window.localStorage;

    Object.defineProperty(window, 'localStorage', {
      configurable: true,
      get(): Storage {
        throw new DOMException('Storage disabled', 'SecurityError');
      },
    });

    try {
      expect(readStoredLocale()).toBeNull();
      expect(() => storeLocale('ca')).not.toThrow();
    } finally {
      Object.defineProperty(window, 'localStorage', {
        configurable: true,
        value: original,
      });
    }
  });
});
