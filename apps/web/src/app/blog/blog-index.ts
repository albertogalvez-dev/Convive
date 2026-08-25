import { Component, computed, effect, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { provideTranslocoScope, TranslocoService } from '@jsverse/transloco';
import { map } from 'rxjs';

import { PublicSeoService } from '../public-seo.service';
import { PublicSiteFooter } from '../public-site-footer/public-site-footer';
import { LanguageSwitcher } from '../language-switcher/language-switcher';
import {
  BlogTranslation,
  interpolateBlogTemplate,
  publishedArticles,
  ResolvedBlogArticle,
} from './blog-content';
import {
  blogAlternatePaths,
  blogArticlePath,
  blogIndexPath,
  blogLocaleFromRoute,
  BlogLocale,
  isBlogLocale,
} from './blog-locales';

@Component({
  selector: 'app-blog-index',
  standalone: true,
  imports: [LanguageSwitcher, RouterLink, PublicSiteFooter],
  providers: [provideTranslocoScope('blog')],
  templateUrl: './blog-index.html',
  styleUrl: './blog.scss',
})
export class BlogIndex {
  private readonly seo = inject(PublicSeoService);
  private readonly transloco = inject(TranslocoService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly routeLocale = toSignal(
    this.route.paramMap.pipe(map((params) => blogLocaleFromRoute(params.get('locale')))),
    { initialValue: blogLocaleFromRoute(this.route.snapshot.paramMap.get('locale')) },
  );

  private readonly rawContent = toSignal(
    this.transloco
      .selectTranslation('blog')
      .pipe(map(() => this.transloco.translateObject<BlogTranslation>('blog'))),
    {
      initialValue: {
        homeLinkLabel: '',
        professionalAccess: '',
        authorName: '',
        index: {
          eyebrow: '',
          title: '',
          description: '',
          publishedArticlesLabel: '',
          readingMeta: '',
        },
        article: {
          backToBlog: '',
          readingMeta: '',
          sourcesTitle: '',
          safetyNotice: '',
          unavailableTitle: '',
          unavailableDescription: '',
          unavailableBack: '',
        },
        articles: {},
      },
    },
  );

  protected readonly content = computed(() => this.rawContent());
  protected readonly articles = computed(() => publishedArticles(this.content()));
  protected readonly locale = computed(() => this.routeLocale());

  protected readingMeta(article: ResolvedBlogArticle): string {
    return interpolateBlogTemplate(this.content().index.readingMeta, {
      minutes: String(article.readingMinutes),
      updated: article.updatedLabel,
      author: article.authorName,
    });
  }

  protected articlePath(article: ResolvedBlogArticle): string {
    return blogArticlePath(this.locale(), article.slug);
  }

  protected changeLocale(locale: string): void {
    if (isBlogLocale(locale)) {
      void this.router.navigateByUrl(blogIndexPath(locale));
    }
  }

  constructor() {
    effect(() => this.transloco.setActiveLang(this.locale()));

    effect(() => {
      const content = this.content();

      if (!content.index.title) {
        return;
      }

      this.seo.update({
        title: 'Blog',
        description: content.index.description,
        path: blogIndexPath(this.locale()),
        alternates: blogAlternatePaths(),
      });
    });
  }
}
