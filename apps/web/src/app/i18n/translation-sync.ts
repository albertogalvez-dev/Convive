/**
 * The second property of the locale gate: translations must not silently fall
 * out of date with the Spanish source.
 *
 * `checkLocaleCompleteness` proves a locale has the same *keys* as the source.
 * That stops a half-translated page reaching a child, which is what it was
 * built for. It cannot see a source string being reworded underneath a
 * translation that stays put — the key sets still match exactly, so every
 * check in the project passes while four published locales state the previous
 * version of, say, the notice that Convive is not an emergency channel.
 *
 * See #325 for the reproduction, and `docs/content/i18n-process.md` for how
 * re-confirmation works.
 */

/** A dotted path and the source text recorded against it. */
export type SourceDigest = Readonly<Record<string, string>>;

/**
 * FNV-1a, 32-bit, hex.
 *
 * Deliberately not a cryptographic hash: nothing here defends against a
 * chosen collision, it detects an *accidental* edit. Over a few hundred
 * strings the chance of a rewording colliding with its own previous value is
 * negligible, and a dependency-free function keeps this check runnable
 * anywhere the suite runs.
 */
export function digest(text: string): string {
  let hash = 0x811c9dc5;

  for (let i = 0; i < text.length; i += 1) {
    hash ^= text.charCodeAt(i);
    // The FNV prime, applied with shifts to stay inside 32-bit integer maths.
    hash = (hash + ((hash << 1) + (hash << 4) + (hash << 7) + (hash << 8) + (hash << 24))) >>> 0;
  }

  return hash.toString(16).padStart(8, '0');
}

/**
 * Every leaf string in a translation tree, keyed by dotted path. Arrays are
 * indexed rather than skipped — `report-help` stores the first guidance a
 * child reads as an array, and a walk that only descended into objects would
 * leave exactly that text unchecked.
 */
export function flatten(tree: unknown, prefix = ''): Record<string, string> {
  if (typeof tree === 'string') {
    return { [prefix]: tree };
  }

  const entries: [string, unknown][] = Array.isArray(tree)
    ? tree.map((value, index) => [`${prefix}[${index}]`, value])
    : tree !== null && typeof tree === 'object'
      ? Object.entries(tree as Record<string, unknown>).map(([key, value]) => [
          prefix === '' ? key : `${prefix}.${key}`,
          value,
        ])
      : [];

  return entries.reduce<Record<string, string>>(
    (all, [path, value]) => ({ ...all, ...flatten(value, path) }),
    {},
  );
}

export interface DriftResult {
  readonly inSync: boolean;
  /** Source text reworded since this locale was last confirmed against it. */
  readonly drifted: readonly { key: string; confirmedDigest: string; currentDigest: string }[];
  /** Present in the source, never confirmed for this locale. */
  readonly unconfirmed: readonly string[];
  /** Confirmed once, but the source key no longer exists. */
  readonly stale: readonly string[];
}

/**
 * Compares the Spanish source against what a locale was last confirmed
 * against.
 *
 * `unconfirmed` and `stale` overlap with what `checkLocaleCompleteness`
 * already reports, and that redundancy is intentional: this check must be
 * able to fail on its own terms rather than assume the other one ran.
 */
export function checkTranslationSync(
  source: Record<string, string>,
  confirmed: SourceDigest,
): DriftResult {
  const drifted: { key: string; confirmedDigest: string; currentDigest: string }[] = [];
  const unconfirmed: string[] = [];

  for (const [key, text] of Object.entries(source)) {
    const recorded = confirmed[key];
    const current = digest(text);

    if (recorded === undefined) {
      unconfirmed.push(key);
    } else if (recorded !== current) {
      drifted.push({ key, confirmedDigest: recorded, currentDigest: current });
    }
  }

  const stale = Object.keys(confirmed).filter((key) => !(key in source));

  return {
    inSync: drifted.length === 0 && unconfirmed.length === 0 && stale.length === 0,
    drifted: drifted.sort((a, b) => a.key.localeCompare(b.key)),
    unconfirmed: unconfirmed.sort(),
    stale: stale.sort(),
  };
}
