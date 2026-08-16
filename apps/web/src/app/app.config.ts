import { provideHttpClient } from '@angular/common/http';
import {
  ApplicationConfig,
  inject,
  provideAppInitializer,
  provideBrowserGlobalErrorListeners,
} from '@angular/core';
import { provideRouter } from '@angular/router';
import { forkJoin, of } from 'rxjs';
import { provideTransloco, TranslocoService } from '@jsverse/transloco';

import { routes } from './app.routes';
import { EAGER_TRANSLOCO_SCOPES } from './i18n/i18n-eager-scopes';
import { HttpTranslocoLoader } from './i18n/i18n-loader';
import { READY_LOCALES } from './i18n/i18n-completeness';
import { SOURCE_LOCALE } from './i18n/i18n-locales';

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideHttpClient(),
    provideRouter(routes),
    provideTransloco({
      config: {
        // Only signed-off locales are ever offered as the active language;
        // reReRenderOnLangChange keeps every translated view in sync when a
        // visitor switches without a page reload.
        availableLangs: [...READY_LOCALES],
        defaultLang: SOURCE_LOCALE,
        fallbackLang: SOURCE_LOCALE,
        reRenderOnLangChange: true,
        prodMode: true,
      },
      loader: HttpTranslocoLoader,
    }),
    // Blocks first render until every eager scope's default-locale JSON has
    // resolved, so a focusable control backed by translated text (the shared
    // footer's navigation, in particular) never has an empty accessible name
    // during the fetch. See i18n-eager-scopes.ts for why this list is short.
    provideAppInitializer(() => {
      const transloco = inject(TranslocoService);

      return forkJoin(
        EAGER_TRANSLOCO_SCOPES.length === 0
          ? [of(null)]
          : EAGER_TRANSLOCO_SCOPES.map((scope) => transloco.load(`${SOURCE_LOCALE}/${scope}`)),
      );
    }),
  ],
};
