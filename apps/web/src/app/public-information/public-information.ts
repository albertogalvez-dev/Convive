import { Component, computed, effect, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { provideTranslocoScope, TranslocoPipe, TranslocoService } from '@jsverse/transloco';
import { map } from 'rxjs';

import {
  PUBLIC_GENERAL_EMAIL,
  PUBLIC_OPERATOR_LINKEDIN_URL,
  PUBLIC_PRIVACY_EMAIL,
} from '../public-identity';
import { PublicSeoService } from '../public-seo.service';
import { PublicSiteFooter } from '../public-site-footer/public-site-footer';
import { LanguageSwitcher } from '../language-switcher/language-switcher';
import { professionalAccessUrlFor } from '../site-hosts';
import { PublicInformationPageMeta } from './public-information-content';

export interface PublicInformationSection {
  readonly heading: string;
  readonly paragraphs: readonly string[];
  readonly items?: readonly string[];
}

/**
 * The translated shape of one document, resolved from the
 * `public-information` Transloco scope for the active locale.
 */
export interface PublicInformationTranslation {
  readonly eyebrow: string;
  readonly seoTitle: string;
  readonly title: string;
  readonly seoDescription: string;
  readonly description: string;
  readonly notice: string;
  readonly sections?: readonly PublicInformationSection[];
  readonly reviewTrigger: string;
}

/**
 * Substitutes `{{name}}` placeholders throughout a translated value tree.
 *
 * Transloco's own params support for `translateObject`/`selectTranslateObject`
 * expects params keyed by the *path* of the string they apply to (e.g.
 * `{'sections.0.notice': {value: '...'}}`), which would tie every document's
 * JSON to the exact shape of its own content. A flat, path-independent
 * substitution is more robust here: the same three placeholders --
 * `generalEmail`, `privacyEmail`, `operatorName` -- can appear anywhere in
 * any document's prose without the substitution needing to know where.
 */
function substitutePlaceholders<T>(value: T, vars: Readonly<Record<string, string>>): T {
  if (typeof value === 'string') {
    return value.replace(/\{\{\s*(\w+)\s*\}\}/g, (match, name: string) =>
      Object.hasOwn(vars, name) ? vars[name] : match,
    ) as T;
  }

  if (Array.isArray(value)) {
    return value.map((item) => substitutePlaceholders(item, vars)) as T;
  }

  if (value !== null && typeof value === 'object') {
    return Object.fromEntries(
      Object.entries(value).map(([key, entry]) => [key, substitutePlaceholders(entry, vars)]),
    ) as T;
  }

  return value;
}

@Component({
  selector: 'app-public-information',
  standalone: true,
  imports: [LanguageSwitcher, RouterLink, PublicSiteFooter, TranslocoPipe],
  providers: [provideTranslocoScope('public-information')],
  templateUrl: './public-information.html',
  styleUrl: './public-information.scss',
})
export class PublicInformation {
  private readonly route = inject(ActivatedRoute);
  private readonly seo = inject(PublicSeoService);
  private readonly transloco = inject(TranslocoService);

  readonly meta = this.route.snapshot.data['meta'] as PublicInformationPageMeta;
  readonly professionalAccessUrl = professionalAccessUrlFor(globalThis.location.hostname);
  readonly operatorLinkedinUrl = PUBLIC_OPERATOR_LINKEDIN_URL;

  splitOwnerLink(paragraph: string): { readonly before: string; readonly after: string } | null {
    const ownerIndex = paragraph.indexOf(this.meta.owner);

    if (ownerIndex === -1) {
      return null;
    }

    return {
      before: paragraph.slice(0, ownerIndex),
      after: paragraph.slice(ownerIndex + this.meta.owner.length),
    };
  }

  splitContactEmail(
    item: string,
  ): { readonly email: string; readonly before: string; readonly description: string } | null {
    const email = [PUBLIC_GENERAL_EMAIL, PUBLIC_PRIVACY_EMAIL].find((candidate) =>
      item.includes(candidate),
    );

    if (!email) {
      return null;
    }

    const emailIndex = item.indexOf(email);

    const description = item.slice(emailIndex + email.length).replace(/^\s*·\s*/, '');

    return {
      email,
      before: item.slice(0, emailIndex),
      description: this.sentenceCase(description),
    };
  }

  private sentenceCase(value: string): string {
    return value ? value[0].toLocaleUpperCase() + value.slice(1) : value;
  }

  // Two separate steps, deliberately not combined into one
  // selectTranslateObject(key, params, scope) call:
  //
  // 1. selectTranslation('public-information') re-subscribes through
  //    Transloco's own scope-loading path (the same one TranslocoPipe uses)
  //    on every locale change, which is what actually fetches that locale's
  //    public-information/*.json. A key with no scope argument at all only
  //    reloads the (empty, unused) root translation file when the locale
  //    changes, so the content would silently keep showing the previous
  //    locale after a switch -- confirmed by hand against a running app.
  // 2. Once that resolves, translateObject() reads the now-loaded tree with
  //    the scope prefix already folded into the key string, the same
  //    pattern every synchronous lookup elsewhere in this codebase uses
  //    (e.g. ReportForm.describeError). Passing the scope as a separate
  //    argument to *this* call, instead, hits Transloco's scope-casing
  //    normalisation (`scopes.keepCasing`) a second time and the two
  //    normalisations disagree -- also confirmed by hand.
  private readonly rawContent = toSignal(
    this.transloco
      .selectTranslation('public-information')
      .pipe(
        map(() =>
          this.transloco.translateObject<PublicInformationTranslation>(
            `public-information.${this.meta.id}`,
          ),
        ),
      ),
    { initialValue: {} as PublicInformationTranslation },
  );

  // Emails are component-bound data interpolated into the translated prose,
  // not copy that needs translating per locale -- the same boundary the
  // shared footer draws for its own contact addresses.
  readonly content = computed(() =>
    substitutePlaceholders(this.rawContent(), {
      generalEmail: PUBLIC_GENERAL_EMAIL,
      privacyEmail: PUBLIC_PRIVACY_EMAIL,
      operatorName: this.meta.owner,
    }),
  );

  constructor() {
    // The scope loads asynchronously, so the SEO tags are (re)applied once
    // translated content actually resolves rather than once at construction
    // time, when `content()` would still be empty.
    effect(() => {
      const content = this.content();

      if (!content.title) {
        return;
      }

      this.seo.update({
        title: content.seoTitle,
        description: content.seoDescription,
        path: this.meta.path,
      });
    });
  }
}
