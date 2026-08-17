/**
 * The completeness gate the standing rule requires: a public translation must
 * be complete and reviewed before it ships, and must never fall back to
 * Spanish silently, key by key, mid-page.
 *
 * Two independent things enforce this together:
 *
 * 1. {@link isLocaleComplete} proves a locale's JSON has exactly the same
 *    keys as the source, recursively. This is what a test runs before a
 *    locale is ever added to {@link READY_LOCALES} — it is the check a
 *    reviewer's sign-off is backed by, not a suggestion.
 * 2. {@link READY_LOCALES} is what the application actually reads to decide
 *    which locales a visitor may select at all. A locale not listed here is
 *    unreachable through the UI regardless of whether translation files for
 *    it exist on disk — adding a JSON file does not publish a locale;
 *    listing it here, after it has passed sign-off, does.
 */

export type TranslationTree = { [key: string]: string | TranslationTree };

/** @returns every dotted key path a translation tree contains, sorted. */
function keyPaths(tree: TranslationTree, prefix = ''): string[] {
  return Object.entries(tree)
    .flatMap(([key, value]) => {
      const path = prefix === '' ? key : `${prefix}.${key}`;

      return typeof value === 'string' ? [path] : keyPaths(value, path);
    })
    .sort();
}

export interface LocaleCompletenessResult {
  readonly complete: boolean;
  /** Present in the source, missing from the candidate. */
  readonly missing: readonly string[];
  /** Present in the candidate, not in the source — usually a stale leftover. */
  readonly unexpected: readonly string[];
}

/**
 * Compares a candidate locale's translation tree against the source tree key
 * by key. A locale is complete only when the key sets match exactly — an
 * extra key is as much a sign of drift as a missing one, since it usually
 * means the source moved on without this locale following.
 */
export function checkLocaleCompleteness(
  source: TranslationTree,
  candidate: TranslationTree,
): LocaleCompletenessResult {
  const sourceKeys = new Set(keyPaths(source));
  const candidateKeys = new Set(keyPaths(candidate));

  const missing = [...sourceKeys].filter((key) => !candidateKeys.has(key));
  const unexpected = [...candidateKeys].filter((key) => !sourceKeys.has(key));

  return { complete: missing.length === 0 && unexpected.length === 0, missing, unexpected };
}

/**
 * Locales a visitor may actually select today, each signed off under the
 * process #256 defines. Adding a code here is the publication step — do it
 * only once {@link checkLocaleCompleteness} passes for that locale's every
 * translation file and a reviewer has recorded sign-off, per #256's process.
 */
export const READY_LOCALES: readonly string[] = ['es', 'ca', 'ca-valencia'];

export function isLocaleReady(code: string): boolean {
  return READY_LOCALES.includes(code);
}
