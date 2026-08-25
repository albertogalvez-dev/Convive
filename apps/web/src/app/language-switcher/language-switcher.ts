import { Component, inject, input, output } from '@angular/core';
import { TranslocoPipe, TranslocoService } from '@jsverse/transloco';

import { READY_LOCALES } from '../i18n/i18n-completeness';
import { LOCALES } from '../i18n/i18n-locales';
import { storeLocale } from '../i18n/i18n-locale-preference';

/**
 * Lets a visitor or a school pick their locale explicitly.
 *
 * Every option's label is that locale's own name for itself (`LOCALES`'s
 * autoglossonyms -- "Català", "Valencià") rather than a translation of
 * "Catalan"/"Valencian" into the currently active language: this is the
 * same convention most multilingual sites use, and it means a visitor can
 * find their language regardless of which one happens to be showing.
 *
 * The choice is never inferred -- not from IP geolocation, not from
 * `Accept-Language`, nothing automatic decides between Catalan and its
 * Valencian variant on a visitor's behalf (#256). A native `<select>` with
 * an associated `<label>` is used deliberately over a custom widget: both
 * keyboard operability and announcing the current selection to a screen
 * reader come from the browser for free, rather than from ARIA this
 * project would have to get right and keep right.
 */
@Component({
  selector: 'app-language-switcher',
  standalone: true,
  imports: [TranslocoPipe],
  host: {
    '[class.inverse]': "appearance() === 'inverse'",
    '[class.compact]': 'compact()',
  },
  templateUrl: './language-switcher.html',
  styleUrl: './language-switcher.scss',
})
export class LanguageSwitcher {
  private readonly transloco = inject(TranslocoService);

  readonly appearance = input<'default' | 'inverse'>('default');
  readonly compact = input(false);
  readonly languageChanged = output<string>();

  protected readonly availableLocales = LOCALES.filter((locale) =>
    READY_LOCALES.includes(locale.code),
  );
  protected readonly activeLang = this.transloco.activeLang;

  protected onChange(event: Event): void {
    const code = (event.target as HTMLSelectElement).value;

    if (!READY_LOCALES.includes(code)) {
      return;
    }

    this.transloco.setActiveLang(code);
    storeLocale(code);
    this.languageChanged.emit(code);
  }
}
