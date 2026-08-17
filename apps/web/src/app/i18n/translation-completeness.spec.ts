import { checkLocaleCompleteness, TranslationTree } from './i18n-completeness';

import footerEs from '../../i18n/public-site-footer/es.json';
import footerCa from '../../i18n/public-site-footer/ca.json';
import footerCaValencia from '../../i18n/public-site-footer/ca-valencia.json';
import publicHomeEs from '../../i18n/public-home/es.json';
import publicHomeCa from '../../i18n/public-home/ca.json';
import publicHomeCaValencia from '../../i18n/public-home/ca-valencia.json';
import publicInformationEs from '../../i18n/public-information/es.json';
import publicInformationCa from '../../i18n/public-information/ca.json';
import publicInformationCaValencia from '../../i18n/public-information/ca-valencia.json';
import reportFormEs from '../../i18n/report-form/es.json';
import reportFormCa from '../../i18n/report-form/ca.json';
import reportFormCaValencia from '../../i18n/report-form/ca-valencia.json';
import reportHeaderEs from '../../i18n/report-header/es.json';
import reportHeaderCa from '../../i18n/report-header/ca.json';
import reportHeaderCaValencia from '../../i18n/report-header/ca-valencia.json';
import reportHelpEs from '../../i18n/report-help/es.json';
import reportHelpCa from '../../i18n/report-help/ca.json';
import reportHelpCaValencia from '../../i18n/report-help/ca-valencia.json';
import reportResultEs from '../../i18n/report-result/es.json';
import reportResultCa from '../../i18n/report-result/ca.json';
import reportResultCaValencia from '../../i18n/report-result/ca-valencia.json';
import reportSendingEs from '../../i18n/report-sending/es.json';
import reportSendingCa from '../../i18n/report-sending/ca.json';
import reportSendingCaValencia from '../../i18n/report-sending/ca-valencia.json';

/**
 * The completeness gate #255 built (`checkLocaleCompleteness`), run for real
 * against every scope's actual `ca` and `ca-valencia` content, not a
 * synthetic fixture. This is the check a locale's sign-off is backed by
 * (see `docs/content/i18n-process.md`): a passing result here is what makes
 * adding `ca`/`ca-valencia` to `READY_LOCALES` a documented fact rather than
 * an assumption.
 */
// JSON imports include arrays (`sections`, `paragraphs`, `items`, `steps`),
// which TypeScript's structural typing does not consider assignable to
// `TranslationTree`'s object-only shape even though `checkLocaleCompleteness`
// handles them correctly at runtime (`Object.entries` works on arrays too).
// The cast reflects that runtime reality rather than fighting the type.
function asTranslationTree(value: unknown): TranslationTree {
  return value as TranslationTree;
}

const scopes: ReadonlyArray<{
  readonly name: string;
  readonly es: TranslationTree;
  readonly ca: TranslationTree;
  readonly caValencia: TranslationTree;
}> = [
  {
    name: 'public-site-footer',
    es: asTranslationTree(footerEs),
    ca: asTranslationTree(footerCa),
    caValencia: asTranslationTree(footerCaValencia),
  },
  {
    name: 'public-home',
    es: asTranslationTree(publicHomeEs),
    ca: asTranslationTree(publicHomeCa),
    caValencia: asTranslationTree(publicHomeCaValencia),
  },
  {
    name: 'public-information',
    es: asTranslationTree(publicInformationEs),
    ca: asTranslationTree(publicInformationCa),
    caValencia: asTranslationTree(publicInformationCaValencia),
  },
  {
    name: 'report-form',
    es: asTranslationTree(reportFormEs),
    ca: asTranslationTree(reportFormCa),
    caValencia: asTranslationTree(reportFormCaValencia),
  },
  {
    name: 'report-header',
    es: asTranslationTree(reportHeaderEs),
    ca: asTranslationTree(reportHeaderCa),
    caValencia: asTranslationTree(reportHeaderCaValencia),
  },
  {
    name: 'report-help',
    es: asTranslationTree(reportHelpEs),
    ca: asTranslationTree(reportHelpCa),
    caValencia: asTranslationTree(reportHelpCaValencia),
  },
  {
    name: 'report-result',
    es: asTranslationTree(reportResultEs),
    ca: asTranslationTree(reportResultCa),
    caValencia: asTranslationTree(reportResultCaValencia),
  },
  {
    name: 'report-sending',
    es: asTranslationTree(reportSendingEs),
    ca: asTranslationTree(reportSendingCa),
    caValencia: asTranslationTree(reportSendingCaValencia),
  },
];

describe('ca and ca-valencia pass the completeness gate on every scope', () => {
  for (const scope of scopes) {
    it(`${scope.name}: ca matches es key-for-key`, () => {
      const result = checkLocaleCompleteness(scope.es, scope.ca);

      expect(result).toEqual({ complete: true, missing: [], unexpected: [] });
    });

    it(`${scope.name}: ca-valencia matches es key-for-key`, () => {
      const result = checkLocaleCompleteness(scope.es, scope.caValencia);

      expect(result).toEqual({ complete: true, missing: [], unexpected: [] });
    });
  }

  it('ca-valencia is a genuine adaptation, not ca copied unedited', () => {
    // The issue requires "terminology/orthography adaptation of the
    // Catalan translation, not a full retranslation" -- a hard equality
    // check per scope would either force an unmotivated edit into a short
    // scope with no real dialectal divergence (report-header has none) or
    // silently accept an unedited copy everywhere. Checked in aggregate
    // instead: across the whole translation set, ca-valencia demonstrably
    // diverges from ca wherever Valencian usage actually differs (aquest/
    // est, aquí/ací, seva/seua, the -isc/-ix verb forms, eixir vs sortir,
    // and so on) -- this does not by itself prove every string that should
    // differ does; that is what the self-review passes recorded in the PR
    // description cover.
    const combinedCa = scopes.map((scope) => JSON.stringify(scope.ca)).join('\n');
    const combinedCaValencia = scopes.map((scope) => JSON.stringify(scope.caValencia)).join('\n');

    expect(combinedCaValencia).not.toEqual(combinedCa);
  });
});
