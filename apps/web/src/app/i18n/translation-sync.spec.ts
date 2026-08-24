import { checkTranslationSync, digest, flatten } from './translation-sync';
import { READY_LOCALES } from './i18n-completeness';

import professionalCaseEs from '../../i18n/professional-case/es.json';
import publicHomeEs from '../../i18n/public-home/es.json';
import publicInformationEs from '../../i18n/public-information/es.json';
import publicSiteFooterEs from '../../i18n/public-site-footer/es.json';
import reportEvidenceEs from '../../i18n/report-evidence/es.json';
import reportFormEs from '../../i18n/report-form/es.json';
import reportHeaderEs from '../../i18n/report-header/es.json';
import reportHelpEs from '../../i18n/report-help/es.json';
import reportResultEs from '../../i18n/report-result/es.json';
import reportSendingEs from '../../i18n/report-sending/es.json';

import confirmedAr from '../../i18n/translation-sync/ar.json';
import confirmedCa from '../../i18n/translation-sync/ca.json';
import confirmedCaValencia from '../../i18n/translation-sync/ca-valencia.json';
import confirmedEu from '../../i18n/translation-sync/eu.json';
import confirmedGl from '../../i18n/translation-sync/gl.json';

/**
 * The drift check from #325, run for real against the whole Spanish source.
 *
 * The gate #255 built proves key sets match. It cannot see a source string
 * being reworded underneath a translation that stays put — which is how a
 * safety notice ends up saying one thing in Spanish and the previous thing in
 * four other languages with every check green.
 */
const SOURCE: Readonly<Record<string, unknown>> = {
  'professional-case': professionalCaseEs,
  'public-home': publicHomeEs,
  'public-information': publicInformationEs,
  'public-site-footer': publicSiteFooterEs,
  'report-evidence': reportEvidenceEs,
  'report-form': reportFormEs,
  'report-header': reportHeaderEs,
  'report-help': reportHelpEs,
  'report-result': reportResultEs,
  'report-sending': reportSendingEs,
};

/**
 * Every published locale is confirmed across every current scope. This keeps
 * a source edit from silently leaving one language on an older wording.
 */
const CONFIRMED: Readonly<Record<string, Readonly<Record<string, string>>>> = {
  ar: confirmedAr,
  ca: confirmedCa,
  'ca-valencia': confirmedCaValencia,
  eu: confirmedEu,
  gl: confirmedGl,
};

/**
 * The Spanish source text, scoped to what this locale actually translates.
 * Text, not digests — `checkTranslationSync` hashes it itself, and handing it
 * a pre-hashed map makes every key look reworded.
 */
function sourceTextsFor(confirmed: Readonly<Record<string, string>>): Record<string, string> {
  const translatedScopes = new Set(Object.keys(confirmed).map((key) => key.split('.')[0]));
  const texts: Record<string, string> = {};

  for (const [scope, tree] of Object.entries(SOURCE)) {
    if (!translatedScopes.has(scope)) {
      continue;
    }
    for (const [key, text] of Object.entries(flatten(tree))) {
      texts[`${scope}.${key}`] = text;
    }
  }

  return texts;
}

describe('digest', () => {
  it('changes when a single character of the source changes', () => {
    expect(digest('Convive no es un canal de emergencia')).not.toBe(
      digest('Convive no es un canal de emergencias'),
    );
  });

  it('is stable for identical text', () => {
    expect(digest('Guárdalo en un lugar seguro.')).toBe(digest('Guárdalo en un lugar seguro.'));
  });
});

describe('flatten', () => {
  it('indexes array entries rather than skipping them', () => {
    // report-help stores the first guidance a child reads as an array. A walk
    // that only descended into objects would leave exactly that unchecked.
    expect(flatten({ steps: ['uno', 'dos'], title: 'Hola' })).toEqual({
      'steps[0]': 'uno',
      'steps[1]': 'dos',
      title: 'Hola',
    });
  });
});

describe('checkTranslationSync', () => {
  it('reports the exact reproduction from #325: source reworded, translation left behind', () => {
    // This is the failure that passed every existing check: the key still
    // exists in every locale, so `checkLocaleCompleteness` is satisfied,
    // while four published locales state the previous safety notice.
    const before = { 'public-site-footer.boundaryBody': digest('Nada llega a un centro.') };
    const after = {
      'public-site-footer.boundaryBody': 'Nada de lo que escribas llega a un centro.',
    };

    const result = checkTranslationSync(Object.fromEntries(Object.entries(after)), before);

    expect(result.inSync).toBe(false);
    expect(result.drifted.map((entry) => entry.key)).toEqual(['public-site-footer.boundaryBody']);
  });

  it('reports a source key that was never confirmed for this locale', () => {
    const result = checkTranslationSync({ 'report-form.newField': 'Texto nuevo' }, {});

    expect(result.inSync).toBe(false);
    expect(result.unconfirmed).toEqual(['report-form.newField']);
  });

  it('reports a confirmation left behind for a key the source no longer has', () => {
    const result = checkTranslationSync({}, { 'report-form.removed': 'abcd1234' });

    expect(result.inSync).toBe(false);
    expect(result.stale).toEqual(['report-form.removed']);
  });

  it('is in sync when every confirmed digest matches the current source', () => {
    const source = { 'report-help.title': 'Antes de empezar' };
    const confirmed = { 'report-help.title': digest('Antes de empezar') };

    expect(checkTranslationSync(source, confirmed).inSync).toBe(true);
  });
});

describe('published locales are confirmed against the current Spanish source', () => {
  const published = READY_LOCALES.filter((locale) => locale !== 'es');

  it('has a confirmation record for every published locale', () => {
    // A locale reaching READY_LOCALES without a record would be exempt from
    // this check entirely, which is the one way it could quietly stop working.
    expect(published.every((locale) => locale in CONFIRMED)).toBe(true);
  });

  it.each(published)('%s has not drifted from the Spanish source', (locale) => {
    const confirmed = CONFIRMED[locale];
    const result = checkTranslationSync(sourceTextsFor(confirmed), confirmed);

    expect(
      result,
      result.inSync
        ? ''
        : [
            `"${locale}" is out of date with the Spanish source.`,
            ...result.drifted.map((entry) => `  reworded since confirmation: ${entry.key}`),
            ...result.unconfirmed.map((key) => `  never confirmed: ${key}`),
            ...result.stale.map((key) => `  confirmed but no longer in source: ${key}`),
            '',
            `Re-translate the affected strings, then: npm run i18n:confirm -- ${locale}`,
          ].join('\n'),
    ).toMatchObject({ inSync: true });
  });
});
