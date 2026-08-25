import { DOCUMENT } from '@angular/common';
import { provideHttpClient } from '@angular/common/http';
import {
  ApplicationConfig,
  inject,
  provideAppInitializer,
  provideBrowserGlobalErrorListeners,
} from '@angular/core';
import { provideRouter, TitleStrategy } from '@angular/router';
import { forkJoin, of } from 'rxjs';
import { provideTransloco, TranslocoService } from '@jsverse/transloco';

import { routes } from './app.routes';
import { ConviveTitleStrategy } from './convive-title.strategy';
import { EAGER_TRANSLOCO_SCOPES } from './i18n/i18n-eager-scopes';
import { HttpTranslocoLoader } from './i18n/i18n-loader';
import { READY_LOCALES } from './i18n/i18n-completeness';
import { readStoredLocale } from './i18n/i18n-locale-preference';
import { localeDirection, SOURCE_LOCALE } from './i18n/i18n-locales';

// The visitor's or organisation's explicitly stored choice, read once at
// bootstrap -- never inferred from geolocation or any other automatic
// signal (#256). Falls back to the source locale when nothing was ever
// chosen, storage is unavailable, or the stored value is no longer a
// published locale.
const initialLocale = readStoredLocale() ?? SOURCE_LOCALE;

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideHttpClient(),
    provideRouter(routes),
    { provide: TitleStrategy, useClass: ConviveTitleStrategy },
    provideTransloco({
      config: {
        // Only published locales are ever offered as the active language;
        // reReRenderOnLangChange keeps every translated view in sync when a
        // visitor switches without a page reload.
        availableLangs: [...READY_LOCALES],
        defaultLang: initialLocale,
        fallbackLang: SOURCE_LOCALE,
        reRenderOnLangChange: true,
        prodMode: true,
        // Without this, Transloco camelCases scope names internally
        // ('public-site-footer' -> 'publicSiteFooter') while templates and
        // file paths keep the hyphenated form, so every lookup silently
        // misses. Keep the scope name identical everywhere.
        scopes: { keepCasing: true },
      },
      loader: HttpTranslocoLoader,
    }),
    // Blocks first render until every eager scope's JSON for the initial
    // locale has resolved, so a focusable control backed by translated text
    // (the shared footer's navigation, in particular) never has an empty
    // accessible name during the fetch. See i18n-eager-scopes.ts for why
    // this list is short. Loads `initialLocale`, not always the source
    // locale, so a returning visitor who chose Catalan does not see a flash
    // of Spanish before the switch takes effect.
    provideAppInitializer(() => {
      const document = inject(DOCUMENT);
      const transloco = inject(TranslocoService);

      // Angular creates the root component after application initializers.
      // Apply the persisted locale to the root document here as well as in
      // I18nDocumentSync, so an Arabic reload is RTL from its first render.
      document.documentElement.lang = initialLocale;
      document.documentElement.dir = localeDirection(initialLocale);

      return forkJoin(
        EAGER_TRANSLOCO_SCOPES.length === 0
          ? [of(null)]
          : EAGER_TRANSLOCO_SCOPES.map((scope) => transloco.load(`${scope}/${initialLocale}`)),
      );
    }),
  ],
};
