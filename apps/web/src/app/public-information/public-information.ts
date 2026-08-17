import { Component, computed, effect, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { provideTranslocoScope, TranslocoPipe, TranslocoService } from '@jsverse/transloco';

import { PUBLIC_GENERAL_EMAIL, PUBLIC_PRIVACY_EMAIL } from '../public-identity';
import { PublicSeoService } from '../public-seo.service';
import { PublicSiteFooter } from '../public-site-footer/public-site-footer';
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
  imports: [RouterLink, PublicSiteFooter, TranslocoPipe],
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

  // The key already carries the `public-information.` scope prefix, the same
  // way every other call in this codebase addresses a scoped key (see
  // ReportForm.describeError), so no separate scope argument is passed here
  // -- doing so would have Transloco prefix the scope a second time and the
  // lookup would miss.
  private readonly rawContent = toSignal(
    this.transloco.selectTranslateObject<PublicInformationTranslation>(
      `public-information.${this.meta.id}`,
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
        title: content.title,
        description: content.seoDescription,
        path: this.meta.path,
      });
    });
  }
}
