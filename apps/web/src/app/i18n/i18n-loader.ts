import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Translation, TranslocoLoader } from '@jsverse/transloco';
import { Observable, throwError } from 'rxjs';

import { isLocaleReady } from './i18n-completeness';

/**
 * Fetches a locale's scoped JSON files at runtime.
 *
 * Refuses a locale that is not in {@link isLocaleReady} before making any
 * request. This is the completeness gate acting at the one point that
 * matters — nothing downstream can render a locale this loader has already
 * refused to fetch.
 */
@Injectable({ providedIn: 'root' })
export class HttpTranslocoLoader implements TranslocoLoader {
  private readonly http = inject(HttpClient);

  getTranslation(langPath: string): Observable<Translation> {
    const [locale, scope] = langPath.split('/');

    if (!isLocaleReady(locale)) {
      return throwError(
        () => new Error(`Locale "${locale}" is not signed off and cannot be loaded.`),
      );
    }

    const path = scope === undefined ? locale : `${scope}/${locale}`;

    return this.http.get<Translation>(`/i18n/${path}.json`);
  }
}
