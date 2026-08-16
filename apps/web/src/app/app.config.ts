import { provideHttpClient } from '@angular/common/http';
import { ApplicationConfig, provideBrowserGlobalErrorListeners } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideTransloco } from '@jsverse/transloco';

import { routes } from './app.routes';
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
  ],
};
