import { DOCUMENT } from '@angular/common';
import { inject, Injectable } from '@angular/core';
import { Meta, Title } from '@angular/platform-browser';

import { PUBLIC_WEBSITE_HOSTNAME } from './site-hosts';

export interface PublicSeoMetadata {
  readonly title: string;
  readonly description: string;
  readonly path: string;
  readonly type?: 'article' | 'website';
  readonly publishedTime?: string;
  readonly modifiedTime?: string;
  readonly robots?: string;
  readonly image?: string;
  readonly alternates?: ReadonlyArray<{ readonly hrefLang: string; readonly path: string }>;
  readonly structuredData?: Readonly<Record<string, unknown>>;
}

@Injectable({ providedIn: 'root' })
export class PublicSeoService {
  private readonly document = inject(DOCUMENT);
  private readonly meta = inject(Meta);
  private readonly title = inject(Title);

  update(metadata: PublicSeoMetadata): void {
    const canonicalUrl = `https://${PUBLIC_WEBSITE_HOSTNAME}${metadata.path}`;
    const pageTitle = metadata.title === 'Convive' ? 'Convive' : `${metadata.title} | Convive`;
    const type = metadata.type ?? 'website';
    const imageUrl = metadata.image === undefined ? undefined : this.absoluteUrl(metadata.image);

    this.title.setTitle(pageTitle);
    this.meta.updateTag({ name: 'description', content: metadata.description });
    this.meta.updateTag({ property: 'og:title', content: pageTitle });
    this.meta.updateTag({ property: 'og:description', content: metadata.description });
    this.meta.updateTag({ property: 'og:type', content: type });
    this.meta.updateTag({ property: 'og:url', content: canonicalUrl });
    this.meta.updateTag({
      name: 'twitter:card',
      content: imageUrl === undefined ? 'summary' : 'summary_large_image',
    });
    this.meta.updateTag({ name: 'twitter:title', content: pageTitle });
    this.meta.updateTag({ name: 'twitter:description', content: metadata.description });
    this.updateOptionalTag('og:image', imageUrl);
    this.updateOptionalTag('twitter:image', imageUrl, 'name');
    this.updateOptionalTag('article:published_time', metadata.publishedTime);
    this.updateOptionalTag('article:modified_time', metadata.modifiedTime);
    this.meta.updateTag({ name: 'robots', content: metadata.robots ?? 'index, follow' });
    this.updateAlternates(metadata.alternates ?? []);
    this.updateStructuredData(metadata.structuredData);

    let canonical = this.document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]');
    if (canonical === null || canonical === undefined) {
      canonical = this.document.createElement('link');
      canonical.rel = 'canonical';
      this.document.head.appendChild(canonical);
    }
    canonical.href = canonicalUrl;
  }

  private absoluteUrl(path: string): string {
    return path.startsWith('http') ? path : `https://${PUBLIC_WEBSITE_HOSTNAME}${path}`;
  }

  private updateOptionalTag(
    name: string,
    content: string | undefined,
    attribute: 'name' | 'property' = 'property',
  ): void {
    const selector = `meta[${attribute}="${name}"]`;
    const existing = this.document.head.querySelector<HTMLMetaElement>(selector);
    if (content === undefined) {
      existing?.remove();
      return;
    }
    const tag = existing ?? this.document.createElement('meta');
    tag.setAttribute(attribute, name);
    tag.content = content;
    if (existing === null || existing === undefined) {
      this.document.head.appendChild(tag);
    }
  }

  private updateAlternates(
    alternates: ReadonlyArray<{ readonly hrefLang: string; readonly path: string }>,
  ): void {
    this.document.head.querySelectorAll('link[data-public-seo-alternate]').forEach((link) => {
      link.remove();
    });

    for (const alternate of alternates) {
      const link = this.document.createElement('link');
      link.rel = 'alternate';
      link.hreflang = alternate.hrefLang;
      link.href = this.absoluteUrl(alternate.path);
      link.setAttribute('data-public-seo-alternate', 'true');
      this.document.head.appendChild(link);
    }
  }

  private updateStructuredData(
    structuredData: Readonly<Record<string, unknown>> | undefined,
  ): void {
    const existing = this.document.head.querySelector<HTMLScriptElement>(
      'script[data-public-seo-structured-data]',
    );

    if (structuredData === undefined) {
      existing?.remove();
      return;
    }

    const script = existing ?? this.document.createElement('script');
    script.type = 'application/ld+json';
    script.setAttribute('data-public-seo-structured-data', 'true');
    script.textContent = JSON.stringify(structuredData);

    if (existing === null || existing === undefined) {
      this.document.head.appendChild(script);
    }
  }
}
