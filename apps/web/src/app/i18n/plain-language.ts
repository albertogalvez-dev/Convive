/**
 * Spanish readability measurement for Convive's user-facing copy.
 *
 * The standard this implements, and why the numbers are what they are, is in
 * `docs/content/plain-language-standard.md`. In short: comprehension is a
 * different failure mode from the structural accessibility #167 covered, and
 * a standard that lives only in a document decays, so this one runs in the
 * suite.
 *
 * Szigriszt-Pazos *perspicuidad*, read on the INFLESZ scale — the Flesch
 * derivative recalibrated for Spanish. The English formula applied to Spanish
 * reports everything as harder than it is, because Spanish carries more
 * syllables per word by nature.
 */

const VOWELS = /[aeiouáéíóúüAEIOUÁÉÍÓÚÜ]+/g;
const STRONG = 'aeoáéóAEOÁÉÓ';
const WORD = /[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+/g;
const SENTENCE_END = /[.!?…]+/;
/** Interpolations are read aloud as their value, not their name. */
const INTERPOLATION = /\{\{[^}]*\}\}/g;

/**
 * Approximate Spanish syllable count: vowel groups, with two adjacent strong
 * vowels split as hiatus (*le-er*, *ca-os*), which a plain vowel-group count
 * would merge.
 *
 * Deliberately approximate. The standard rests on bands, not on the third
 * decimal, and a scope sitting on a boundary deserves a human reading rather
 * than a more elaborate syllabifier.
 */
export function countSyllables(word: string): number {
  const groups = word.match(VOWELS) ?? [];
  let count = 0;

  for (const group of groups) {
    count += 1;
    for (let i = 0; i < group.length - 1; i += 1) {
      if (STRONG.includes(group[i]) && STRONG.includes(group[i + 1])) {
        count += 1;
      }
    }
  }

  return Math.max(count, 1);
}

/** Every string in a translation tree, descending into arrays as well as objects. */
export function collectStrings(value: unknown): string[] {
  if (typeof value === 'string') {
    return [value];
  }
  if (Array.isArray(value)) {
    return value.flatMap(collectStrings);
  }
  if (value !== null && typeof value === 'object') {
    return Object.values(value as Record<string, unknown>).flatMap(collectStrings);
  }

  return [];
}

export interface Readability {
  readonly words: number;
  readonly sentences: number;
  readonly wordsPerSentence: number;
  readonly inflesz: number;
  /** The longest single sentence, which a composite score hides. */
  readonly longestSentence: { readonly text: string; readonly words: number };
}

export function measure(texts: readonly string[]): Readability {
  const words: string[] = [];
  let sentences = 0;
  let longestSentence = { text: '', words: 0 };

  for (const raw of texts) {
    const text = raw.replace(INTERPOLATION, 'X');
    const parts = text.split(SENTENCE_END).filter((part) => part.trim() !== '');
    sentences += Math.max(parts.length, 1);

    for (const part of parts) {
      const count = (part.match(WORD) ?? []).length;
      if (count > longestSentence.words) {
        longestSentence = { text: part.trim(), words: count };
      }
    }

    words.push(...(text.match(WORD) ?? []));
  }

  const wordCount = words.length;
  const sentenceCount = Math.max(sentences, 1);

  if (wordCount === 0) {
    return { words: 0, sentences: 0, wordsPerSentence: 0, inflesz: 100, longestSentence };
  }

  const syllables = words.reduce((total, word) => total + countSyllables(word), 0);

  return {
    words: wordCount,
    sentences: sentenceCount,
    wordsPerSentence: wordCount / sentenceCount,
    inflesz: 206.835 - 62.3 * (syllables / wordCount) - wordCount / sentenceCount,
    longestSentence,
  };
}
