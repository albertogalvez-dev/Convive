import { checkLocaleCompleteness, TranslationTree } from './i18n-completeness';

import footerEs from '../../i18n/public-site-footer/es.json';
import footerCa from '../../i18n/public-site-footer/ca.json';
import footerCaValencia from '../../i18n/public-site-footer/ca-valencia.json';
import footerAr from '../../i18n/public-site-footer/ar.json';
import footerGl from '../../i18n/public-site-footer/gl.json';
import footerEu from '../../i18n/public-site-footer/eu.json';
import publicHomeEs from '../../i18n/public-home/es.json';
import publicHomeCa from '../../i18n/public-home/ca.json';
import publicHomeCaValencia from '../../i18n/public-home/ca-valencia.json';
import publicHomeAr from '../../i18n/public-home/ar.json';
import publicHomeGl from '../../i18n/public-home/gl.json';
import publicHomeEu from '../../i18n/public-home/eu.json';
import publicInformationEs from '../../i18n/public-information/es.json';
import publicInformationCa from '../../i18n/public-information/ca.json';
import publicInformationCaValencia from '../../i18n/public-information/ca-valencia.json';
import publicInformationAr from '../../i18n/public-information/ar.json';
import publicInformationGl from '../../i18n/public-information/gl.json';
import publicInformationEu from '../../i18n/public-information/eu.json';
import reportEvidenceEs from '../../i18n/report-evidence/es.json';
import reportEvidenceCa from '../../i18n/report-evidence/ca.json';
import reportEvidenceCaValencia from '../../i18n/report-evidence/ca-valencia.json';
import reportEvidenceAr from '../../i18n/report-evidence/ar.json';
import reportEvidenceGl from '../../i18n/report-evidence/gl.json';
import reportEvidenceEu from '../../i18n/report-evidence/eu.json';
import reportFormEs from '../../i18n/report-form/es.json';
import reportFormCa from '../../i18n/report-form/ca.json';
import reportFormCaValencia from '../../i18n/report-form/ca-valencia.json';
import reportFormAr from '../../i18n/report-form/ar.json';
import reportFormGl from '../../i18n/report-form/gl.json';
import reportFormEu from '../../i18n/report-form/eu.json';
import reportHeaderEs from '../../i18n/report-header/es.json';
import reportHeaderCa from '../../i18n/report-header/ca.json';
import reportHeaderCaValencia from '../../i18n/report-header/ca-valencia.json';
import reportHeaderAr from '../../i18n/report-header/ar.json';
import reportHeaderGl from '../../i18n/report-header/gl.json';
import reportHeaderEu from '../../i18n/report-header/eu.json';
import reportHelpEs from '../../i18n/report-help/es.json';
import reportHelpCa from '../../i18n/report-help/ca.json';
import reportHelpCaValencia from '../../i18n/report-help/ca-valencia.json';
import reportHelpAr from '../../i18n/report-help/ar.json';
import reportHelpGl from '../../i18n/report-help/gl.json';
import reportHelpEu from '../../i18n/report-help/eu.json';
import reportResultEs from '../../i18n/report-result/es.json';
import reportResultCa from '../../i18n/report-result/ca.json';
import reportResultCaValencia from '../../i18n/report-result/ca-valencia.json';
import reportResultAr from '../../i18n/report-result/ar.json';
import reportResultGl from '../../i18n/report-result/gl.json';
import reportResultEu from '../../i18n/report-result/eu.json';
import reportSendingEs from '../../i18n/report-sending/es.json';
import reportSendingCa from '../../i18n/report-sending/ca.json';
import reportSendingCaValencia from '../../i18n/report-sending/ca-valencia.json';
import reportSendingAr from '../../i18n/report-sending/ar.json';
import reportSendingGl from '../../i18n/report-sending/gl.json';
import reportSendingEu from '../../i18n/report-sending/eu.json';
import professionalCaseEs from '../../i18n/professional-case/es.json';
import professionalCaseAr from '../../i18n/professional-case/ar.json';
import professionalCaseCa from '../../i18n/professional-case/ca.json';
import professionalCaseCaValencia from '../../i18n/professional-case/ca-valencia.json';
import professionalCaseGl from '../../i18n/professional-case/gl.json';
import professionalCaseEu from '../../i18n/professional-case/eu.json';

/**
 * The completeness gate #255 built (`checkLocaleCompleteness`), run for real
 * against every current published locale's actual content, not a synthetic
 * fixture. A passing result is what makes adding a locale to `READY_LOCALES`
 * a documented fact rather than an assumption.
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
  readonly ar: TranslationTree;
  readonly gl: TranslationTree;
  readonly eu: TranslationTree;
}> = [
  {
    name: 'public-site-footer',
    es: asTranslationTree(footerEs),
    ca: asTranslationTree(footerCa),
    caValencia: asTranslationTree(footerCaValencia),
    ar: asTranslationTree(footerAr),
    gl: asTranslationTree(footerGl),
    eu: asTranslationTree(footerEu),
  },
  {
    name: 'public-home',
    es: asTranslationTree(publicHomeEs),
    ca: asTranslationTree(publicHomeCa),
    caValencia: asTranslationTree(publicHomeCaValencia),
    ar: asTranslationTree(publicHomeAr),
    gl: asTranslationTree(publicHomeGl),
    eu: asTranslationTree(publicHomeEu),
  },
  {
    name: 'public-information',
    es: asTranslationTree(publicInformationEs),
    ca: asTranslationTree(publicInformationCa),
    caValencia: asTranslationTree(publicInformationCaValencia),
    ar: asTranslationTree(publicInformationAr),
    gl: asTranslationTree(publicInformationGl),
    eu: asTranslationTree(publicInformationEu),
  },
  {
    name: 'report-evidence',
    es: asTranslationTree(reportEvidenceEs),
    ca: asTranslationTree(reportEvidenceCa),
    caValencia: asTranslationTree(reportEvidenceCaValencia),
    ar: asTranslationTree(reportEvidenceAr),
    gl: asTranslationTree(reportEvidenceGl),
    eu: asTranslationTree(reportEvidenceEu),
  },
  {
    name: 'report-form',
    es: asTranslationTree(reportFormEs),
    ca: asTranslationTree(reportFormCa),
    caValencia: asTranslationTree(reportFormCaValencia),
    ar: asTranslationTree(reportFormAr),
    gl: asTranslationTree(reportFormGl),
    eu: asTranslationTree(reportFormEu),
  },
  {
    name: 'report-header',
    es: asTranslationTree(reportHeaderEs),
    ca: asTranslationTree(reportHeaderCa),
    caValencia: asTranslationTree(reportHeaderCaValencia),
    ar: asTranslationTree(reportHeaderAr),
    gl: asTranslationTree(reportHeaderGl),
    eu: asTranslationTree(reportHeaderEu),
  },
  {
    name: 'report-help',
    es: asTranslationTree(reportHelpEs),
    ca: asTranslationTree(reportHelpCa),
    caValencia: asTranslationTree(reportHelpCaValencia),
    ar: asTranslationTree(reportHelpAr),
    gl: asTranslationTree(reportHelpGl),
    eu: asTranslationTree(reportHelpEu),
  },
  {
    name: 'report-result',
    es: asTranslationTree(reportResultEs),
    ca: asTranslationTree(reportResultCa),
    caValencia: asTranslationTree(reportResultCaValencia),
    ar: asTranslationTree(reportResultAr),
    gl: asTranslationTree(reportResultGl),
    eu: asTranslationTree(reportResultEu),
  },
  {
    name: 'report-sending',
    es: asTranslationTree(reportSendingEs),
    ca: asTranslationTree(reportSendingCa),
    caValencia: asTranslationTree(reportSendingCaValencia),
    ar: asTranslationTree(reportSendingAr),
    gl: asTranslationTree(reportSendingGl),
    eu: asTranslationTree(reportSendingEu),
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

describe('ar passes the completeness gate on every scope (#257)', () => {
  for (const scope of scopes) {
    it(`${scope.name}: ar matches es key-for-key`, () => {
      const result = checkLocaleCompleteness(scope.es, scope.ar);

      expect(result).toEqual({ complete: true, missing: [], unexpected: [] });
    });
  }

  it('professional-case: ar matches es key-for-key', () => {
    const result = checkLocaleCompleteness(
      asTranslationTree(professionalCaseEs),
      asTranslationTree(professionalCaseAr),
    );

    expect(result).toEqual({ complete: true, missing: [], unexpected: [] });
  });
});

describe('eu passes the completeness gate on every scope (#312)', () => {
  for (const scope of scopes) {
    it(`${scope.name}: eu matches es key-for-key`, () => {
      const result = checkLocaleCompleteness(scope.es, scope.eu);

      expect(result).toEqual({ complete: true, missing: [], unexpected: [] });
    });
  }

  it('professional-case: eu matches es key-for-key', () => {
    const result = checkLocaleCompleteness(
      asTranslationTree(professionalCaseEs),
      asTranslationTree(professionalCaseEu),
    );

    expect(result).toEqual({ complete: true, missing: [], unexpected: [] });
  });

  it('keeps every interpolation parameter the source declares', () => {
    const params = (tree: unknown): string[] =>
      [...JSON.stringify(tree).matchAll(/\{\{\s*(\w+)\s*\}\}/g)].map((match) => match[1]).sort();

    for (const scope of scopes) {
      expect(params(scope.eu), scope.name).toEqual(params(scope.es));
    }

    expect(params(professionalCaseEu)).toEqual(params(professionalCaseEs));
  });
});

describe('professional-case: ca matches es key-for-key (#320)', () => {
  // This scope sits outside the public `scopes` list above on purpose. It is
  // professional-facing, so it degrades to Spanish per ADR-0027 rather than
  // being gated all-or-nothing like the public reporting path a child reads.
  // Degrading gracefully is not licence to drift, though: a key added to `es`
  // and forgotten in `ca` would silently leave a Catalan professional reading
  // Spanish, and nothing would fail. This is what makes that fail.
  it('ca matches es key-for-key', () => {
    const result = checkLocaleCompleteness(
      asTranslationTree(professionalCaseEs),
      asTranslationTree(professionalCaseCa),
    );

    expect(result).toEqual({ complete: true, missing: [], unexpected: [] });
  });

  it('ca-valencia matches es key-for-key', () => {
    const result = checkLocaleCompleteness(
      asTranslationTree(professionalCaseEs),
      asTranslationTree(professionalCaseCaValencia),
    );

    expect(result).toEqual({ complete: true, missing: [], unexpected: [] });
  });

  it('keeps every interpolation parameter the source declares', () => {
    // A translated sentence that drops `{{version}}` still reads fluently and
    // still cites nothing. Fluency is exactly why this needs a test rather
    // than a reading.
    const params = (tree: unknown): string[] =>
      [...JSON.stringify(tree).matchAll(/\{\{\s*(\w+)\s*\}\}/g)].map((match) => match[1]).sort();

    expect(params(professionalCaseCa)).toEqual(params(professionalCaseEs));
    expect(params(professionalCaseCaValencia)).toEqual(params(professionalCaseEs));
    expect(params(professionalCaseAr)).toEqual(params(professionalCaseEs));
  });
});

describe('gl passes the completeness gate on every scope (#322)', () => {
  for (const scope of scopes) {
    it(`${scope.name}: gl matches es key-for-key`, () => {
      const result = checkLocaleCompleteness(scope.es, scope.gl);

      expect(result).toEqual({ complete: true, missing: [], unexpected: [] });
    });
  }

  it('professional-case: gl matches es key-for-key', () => {
    const result = checkLocaleCompleteness(
      asTranslationTree(professionalCaseEs),
      asTranslationTree(professionalCaseGl),
    );

    expect(result).toEqual({ complete: true, missing: [], unexpected: [] });
  });

  it('keeps every interpolation parameter the source declares', () => {
    // A public notice that loses {{privacyEmail}} still reads as a complete
    // sentence and leaves a reader with no way to exercise a right.
    const params = (tree: unknown): string[] =>
      [...JSON.stringify(tree).matchAll(/\{\{\s*(\w+)\s*\}\}/g)].map((match) => match[1]).sort();

    for (const scope of scopes) {
      expect(params(scope.gl), scope.name).toEqual(params(scope.es));
    }

    expect(params(professionalCaseGl)).toEqual(params(professionalCaseEs));
  });
});
