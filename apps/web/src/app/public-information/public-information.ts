import { Component, inject, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';

import { PublicSeoService } from '../public-seo.service';
import { PublicSiteFooter } from '../public-site-footer/public-site-footer';
import { professionalAccessUrlFor } from '../site-hosts';

export interface PublicInformationSection {
  readonly heading: string;
  readonly paragraphs: readonly string[];
  readonly items?: readonly string[];
}

/**
 * Who owns a published page and what makes it due for review again.
 *
 * Every public document carries one. It is part of the content model rather than a
 * footnote someone remembers to add, so a page cannot be published without an
 * assigned review trigger.
 */
export interface PublicInformationReview {
  readonly owner: string;
  readonly reviewedOn: string;
  readonly trigger: string;
}

export interface PublicInformationContent {
  readonly path: string;
  readonly eyebrow: string;
  readonly title: string;
  readonly seoDescription: string;
  readonly description: string;
  readonly notice: string;
  readonly sections?: readonly PublicInformationSection[];
  readonly review: PublicInformationReview;
}

@Component({
  selector: 'app-public-information',
  standalone: true,
  imports: [RouterLink, PublicSiteFooter],
  templateUrl: './public-information.html',
  styleUrl: './public-information.scss',
})
export class PublicInformation implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly seo = inject(PublicSeoService);

  readonly content = this.route.snapshot.data['content'] as PublicInformationContent;
  readonly professionalAccessUrl = professionalAccessUrlFor(globalThis.location.hostname);

  ngOnInit(): void {
    this.seo.update({
      title: this.content.title,
      description: this.content.seoDescription,
      path: this.content.path,
    });
  }
}
