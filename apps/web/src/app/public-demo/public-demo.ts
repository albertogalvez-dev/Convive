import { Component, effect, ElementRef, inject, signal, ViewChild } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { provideTranslocoScope, TranslocoPipe, TranslocoService } from '@jsverse/transloco';

import { LanguageSwitcher } from '../language-switcher/language-switcher';
import { PublicSeoService } from '../public-seo.service';
import { PublicSiteFooter } from '../public-site-footer/public-site-footer';
import { professionalAccessUrlFor, publicReportingUrlFor } from '../site-hosts';
import { DEMO_PUBLIC_REPORTING_IDENTIFIER } from './demo-public-reporting';

export { DEMO_PUBLIC_REPORTING_IDENTIFIER } from './demo-public-reporting';

@Component({
  selector: 'app-public-demo',
  standalone: true,
  imports: [LanguageSwitcher, RouterLink, PublicSiteFooter, TranslocoPipe],
  providers: [provideTranslocoScope('public-demo')],
  templateUrl: './public-demo.html',
  styleUrl: './public-demo.scss',
})
export class PublicDemo {
  @ViewChild('navigationToggle') private navigationToggle?: ElementRef<HTMLButtonElement>;

  private readonly seo = inject(PublicSeoService);
  private readonly transloco = inject(TranslocoService);

  readonly professionalAccessUrl = professionalAccessUrlFor(globalThis.location.hostname);
  readonly reporterExampleUrl = publicReportingUrlFor(
    globalThis.location.hostname,
    DEMO_PUBLIC_REPORTING_IDENTIFIER,
  );
  readonly activeLanguage = toSignal(this.transloco.langChanges$, {
    initialValue: this.transloco.getActiveLang(),
  });
  private readonly translations = toSignal(this.transloco.selectTranslation('public-demo'), {
    initialValue: null,
  });
  protected readonly mobileNavigationOpen = signal(false);

  constructor() {
    effect(() => {
      if (this.translations() === null) {
        return;
      }

      this.seo.update({
        title: this.transloco.translate('public-demo.seoTitle'),
        description: this.transloco.translate('public-demo.seoDescription'),
        path: '/demostracion/',
      });
    });
  }

  protected posterUrl(): string {
    return `/assets/public-demo/convive-poster-${DEMO_PUBLIC_REPORTING_IDENTIFIER}-${this.activeLanguage()}.png?v=3`;
  }

  protected toggleMobileNavigation(): void {
    this.mobileNavigationOpen.update((open) => !open);
  }

  protected closeMobileNavigation(returnFocus = false): void {
    this.mobileNavigationOpen.set(false);

    if (returnFocus) {
      queueMicrotask(() => this.navigationToggle?.nativeElement.focus());
    }
  }
}
