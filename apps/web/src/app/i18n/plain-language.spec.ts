import { collectStrings, countSyllables, measure } from './plain-language';

import professionalCase from '../../i18n/professional-case/es.json';
import publicHome from '../../i18n/public-home/es.json';
import publicInformation from '../../i18n/public-information/es.json';
import publicSiteFooter from '../../i18n/public-site-footer/es.json';
import reportEvidence from '../../i18n/report-evidence/es.json';
import reportForm from '../../i18n/report-form/es.json';
import reportHeader from '../../i18n/report-header/es.json';
import reportHelp from '../../i18n/report-help/es.json';
import reportResult from '../../i18n/report-result/es.json';
import reportSending from '../../i18n/report-sending/es.json';

/**
 * The plain-language standard from `docs/content/plain-language-standard.md`,
 * enforced rather than described.
 *
 * Measured on **prose only**. INFLESZ assumes sentences: a scope full of
 * one-word navigation labels ("Accesibilidad", "Privacidad") scores terribly
 * while being trivially readable, and chasing that number would mean
 * rewriting correct labels to satisfy a misapplied formula. Strings shorter
 * than PROSE_MIN_WORDS are labels, not prose, and are excluded.
 */
const PROSE_MIN_WORDS = 6;

const WORD = /[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+/g;

function prose(tree: unknown): string[] {
  return collectStrings(tree).filter((text) => (text.match(WORD) ?? []).length >= PROSE_MIN_WORDS);
}

interface ScopeUnderTest {
  readonly name: string;
  readonly tree: unknown;
  /**
   * The recorded score on 18 August 2026. The ratchet: a scope may improve
   * freely, but may not get harder than it already is. This matters more than
   * any single rewrite — it is what stops the next fifty commits from undoing
   * one improvement a convenient clause at a time.
   */
  readonly baseline: number;
  /** Tier floor, where the scope already meets it. See the standard doc. */
  readonly floor?: number;
  /**
   * Longest sentence permitted, in words — set to today's longest so that
   * sentence length ratchets too. The tier *target* lives in the standard
   * doc; this is the line that must not move the wrong way.
   */
  readonly maxSentenceWords: number;
}

const SCOPES: readonly ScopeUnderTest[] = [
  // Tier 1 — safety-critical. Target: INFLESZ >= 65, sentences <= 15 words.
  // Misses its own floor by a tenth of a point and carries a 17-word
  // sentence. Recorded as a gap rather than rounded up: this is the text
  // telling a reader Convive is not an emergency channel.
  { name: 'public-site-footer', tree: publicSiteFooter, baseline: 64.9, maxSentenceWords: 17 },

  // Tier 2 — child-facing reporting journey. Target: INFLESZ >= 65.
  { name: 'report-result', tree: reportResult, baseline: 73.3, floor: 65, maxSentenceWords: 11 },
  { name: 'report-sending', tree: reportSending, baseline: 73.1, floor: 65, maxSentenceWords: 7 },
  { name: 'report-form', tree: reportForm, baseline: 68.2, floor: 65, maxSentenceWords: 20 },
  {
    name: 'report-evidence',
    tree: reportEvidence,
    baseline: 66.5,
    floor: 65,
    maxSentenceWords: 10,
  },
  // Below the Tier 2 floor today. Tracked as a gap rather than pretended
  // away by lowering the tier to fit.
  { name: 'report-help', tree: reportHelp, baseline: 63.0, maxSentenceWords: 10 },
  // All labels, no prose — nothing for the formula to measure.
  { name: 'report-header', tree: reportHeader, baseline: 0, maxSentenceWords: 4 },

  // Tier 2 by audience and the widest gap in the product: the front door,
  // and its hardest public prose.
  { name: 'public-home', tree: publicHome, baseline: 41.6, maxSentenceWords: 13 },

  // Tier 3 — professional and legal copy. Precision outranks simplicity, so
  // no INFLESZ floor; governed by sentence length. Target <= 25 words.
  { name: 'professional-case', tree: professionalCase, baseline: 65.1, maxSentenceWords: 23 },
  // Meets the reading level but holds the longest sentence in the product.
  { name: 'public-information', tree: publicInformation, baseline: 60.9, maxSentenceWords: 37 },
];

describe('countSyllables', () => {
  it('counts vowel groups as one syllable each', () => {
    expect(countSyllables('centro')).toBe(2);
    expect(countSyllables('protocolo')).toBe(4);
  });

  it('splits two adjacent strong vowels as hiatus', () => {
    // A plain vowel-group count would merge these into one syllable and
    // report the copy as easier than it reads aloud.
    expect(countSyllables('leer')).toBe(2);
    expect(countSyllables('caos')).toBe(2);
  });

  it('keeps a diphthong as a single syllable', () => {
    expect(countSyllables('cuenta')).toBe(2);
    expect(countSyllables('bien')).toBe(1);
  });
});

describe('collectStrings', () => {
  it('descends into arrays as well as objects', () => {
    // report-help stores its steps as an array. A walk that only recursed
    // into objects would skip every step of the first guidance a child reads
    // and report the scope as measured.
    expect(collectStrings({ a: 'one', b: ['two', 'three'], c: { d: 'four' } })).toEqual([
      'one',
      'two',
      'three',
      'four',
    ]);
  });
});

describe('measure', () => {
  it('reads an interpolation as its value, not its placeholder name', () => {
    // "{{current}}" is spoken as a number. Counting its letters would make
    // any string carrying one look longer and harder than a reader finds it.
    const withPlaceholder = measure(['Paso {{current}} de {{total}}']);
    const withValues = measure(['Paso X de X']);

    expect(withPlaceholder.words).toBe(withValues.words);
    expect(withPlaceholder.inflesz).toBeCloseTo(withValues.inflesz, 5);
  });

  it('reports the longest single sentence, which a composite score hides', () => {
    const result = measure(['Corto. Esta oración tiene bastantes más palabras que la anterior.']);

    expect(result.longestSentence.words).toBe(9);
  });
});

describe('plain-language standard', () => {
  it.each(SCOPES.filter((scope) => scope.floor !== undefined))(
    '$name meets its tier floor of $floor INFLESZ',
    ({ tree, floor }) => {
      expect(measure(prose(tree)).inflesz).toBeGreaterThanOrEqual(floor as number);
    },
  );

  it.each(SCOPES)('$name does not get harder than its recorded baseline', ({ tree, baseline }) => {
    const texts = prose(tree);
    if (texts.length === 0) {
      return;
    }

    // Half a point of tolerance: the syllable counter is approximate by
    // design, and a standard that fails on rounding trains people to ignore
    // it.
    expect(measure(texts).inflesz).toBeGreaterThanOrEqual(baseline - 0.5);
  });

  it.each(SCOPES)('$name keeps every sentence within its limit', ({ tree, maxSentenceWords }) => {
    const { longestSentence } = measure(collectStrings(tree));

    expect(
      longestSentence.words,
      `Longest sentence (${longestSentence.words} words): "${longestSentence.text}"`,
    ).toBeLessThanOrEqual(maxSentenceWords);
  });
});
