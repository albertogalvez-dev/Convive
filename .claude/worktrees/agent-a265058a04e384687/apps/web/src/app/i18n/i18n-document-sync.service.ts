import { DOCUMENT } from '@angular/common';
import { effect, inject, Injectable } from '@angular/core';
import { TranslocoService } from '@jsverse/transloco';

import { localeDirection } from './i18n-locales';

/**
 * Keeps the document's `lang` and `dir` attributes in step with the active
 * locale, per WCAG 3.1.1 (Language of Page) and the direction Arabic needs
 * once #257 adds it. Mixed-language content within a page (WCAG 3.1.2) is a
 * per-component concern — this service only owns the root.
 */
@Injectable({ providedIn: 'root' })
export class I18nDocumentSync {
  private readonly document = inject(DOCUMENT);
  private readonly transloco = inject(TranslocoService);

  constructor() {
    effect(() => {
      const activeLang = this.transloco.activeLang();
      this.document.documentElement.lang = activeLang;
      this.document.documentElement.dir = localeDirection(activeLang);
    });
  }
}
