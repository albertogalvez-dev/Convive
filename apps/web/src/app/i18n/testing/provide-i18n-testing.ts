import { Translation, TranslocoTestingModule } from '@jsverse/transloco';

/**
 * Test-time Transloco setup for any spec that renders a component using the
 * i18n layer — directly or through a child that does, such as
 * `PublicSiteFooter`, which every public page includes.
 *
 * Pass the real scoped translation content a test actually needs (import the
 * JSON file directly rather than hand-writing a fixture), so a spec asserting
 * on rendered text exercises the same source file production serves. Leave
 * `scopes` empty for a spec that renders nothing translated itself but needs
 * a child component's Transloco dependency satisfied.
 */
export function i18nTestingModule(
  scopes: Record<string, Translation> = {},
): ReturnType<typeof TranslocoTestingModule.forRoot> {
  return TranslocoTestingModule.forRoot({
    langs: { es: scopes },
    translocoConfig: { availableLangs: ['es'], defaultLang: 'es' },
    preloadLangs: true,
  });
}
