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
}

@Injectable({ providedIn: 'root' })
export class PublicSeoService {
  private readonly document = inject(DOCUMENT);
  private readonly meta = inject(Meta);
  private readonly title = inject(Title);

  update(metadata: PublicSeoMetadata): void {
    const canonicalUrl = `https://${PUBLIC_WEBSITE_HOSTNAME}${metadata.path}`;
    const pageTitle = `${metadata.title} | Convive`;
    const type = metadata.type ?? 'website';

    this.title.setTitle(pageTitle);
    this.meta.updateTag({ name: 'description', content: metadata.description });
    this.meta.updateTag({ property: 'og:title', content: pageTitle });
    this.meta.updateTag({ property: 'og:description', content: metadata.description });
    this.meta.updateTag({ property: 'og:type', content: type });
    this.meta.updateTag({ property: 'og:url', content: canonicalUrl });
    this.meta.updateTag({ name: 'twitter:card', content: 'summary' });
    this.meta.updateTag({ name: 'twitter:title', content: pageTitle });
    this.meta.updateTag({ name: 'twitter:description', content: metadata.description });
    this.updateOptionalTag('article:published_time', metadata.publishedTime);
    this.updateOptionalTag('article:modified_time', metadata.modifiedTime);
    this.meta.updateTag({ name: 'robots', content: metadata.robots ?? 'index, follow' });

    let canonical = this.document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]');
    if (canonical === null) {
      canonical = this.document.createElement('link');
      canonical.rel = 'canonical';
      this.document.head.append(canonical);
    }
    canonical.href = canonicalUrl;
  }

  private updateOptionalTag(property: string, content: string | undefined): void {
    const selector = `meta[property="${property}"]`;
    const existing = this.document.head.querySelector<HTMLMetaElement>(selector);
    if (content === undefined) {
      existing?.remove();
      return;
    }
    const tag = existing ?? this.document.createElement('meta');
    tag.setAttribute('property', property);
    tag.content = content;
    if (existing === null) {
      this.document.head.append(tag);
    }
  }
}
