import { checkLocaleCompleteness, isLocaleReady, READY_LOCALES } from './i18n-completeness';

describe('checkLocaleCompleteness', () => {
  it('is complete when every key path matches exactly, nested keys included', () => {
    const source = { a: 'x', nested: { b: 'y' } };
    const candidate = { a: 'traducido', nested: { b: 'traducido' } };

    expect(checkLocaleCompleteness(source, candidate)).toEqual({
      complete: true,
      missing: [],
      unexpected: [],
    });
  });

  it('reports every key missing from the candidate, not just the first', () => {
    const source = { a: 'x', b: 'y', nested: { c: 'z' } };
    const candidate = { a: 'traducido' };

    const result = checkLocaleCompleteness(source, candidate);

    expect(result.complete).toBe(false);
    expect(result.missing).toEqual(['b', 'nested.c']);
  });

  it('flags an unexpected key as incompleteness too, not only a missing one', () => {
    // A locale drifting ahead of the source is exactly as broken as one
    // falling behind it: both mean the two texts no longer say the same
    // thing, which is what this check exists to catch.
    const source = { a: 'x' };
    const candidate = { a: 'traducido', leftover: 'stale from a removed key' };

    const result = checkLocaleCompleteness(source, candidate);

    expect(result.complete).toBe(false);
    expect(result.unexpected).toEqual(['leftover']);
  });

  it('treats an empty source and an empty candidate as trivially complete', () => {
    expect(checkLocaleCompleteness({}, {})).toEqual({
      complete: true,
      missing: [],
      unexpected: [],
    });
  });
});

describe('isLocaleReady', () => {
  it('accepts only locales explicitly listed in READY_LOCALES', () => {
    for (const code of READY_LOCALES) {
      expect(isLocaleReady(code)).toBe(true);
    }
  });

  it('refuses a locale that has translation files but has not been added to READY_LOCALES', () => {
    // Publishing a locale is adding its code here after sign-off, not
    // creating the JSON file — a file existing on disk must not be enough.
    // `ca`/`ca-valencia` are signed off as of #256, `ar` as of #257 and `gl`
    // as of #322; `eu` is still not.
    expect(isLocaleReady('eu')).toBe(false);
  });

  it('refuses a locale that does not exist at all', () => {
    expect(isLocaleReady('xx')).toBe(false);
  });
});
