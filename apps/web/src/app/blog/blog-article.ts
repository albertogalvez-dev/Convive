import { Component, computed, effect, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { provideTranslocoScope, TranslocoService } from '@jsverse/transloco';
import { map } from 'rxjs';

import { PublicSeoService } from '../public-seo.service';
import { PublicSiteFooter } from '../public-site-footer/public-site-footer';
import { LanguageSwitcher } from '../language-switcher/language-switcher';
import {
  articleForSlug,
  BlogTranslation,
  interpolateBlogTemplate,
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

const EMPTY_CONTENT: BlogTranslation = {
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
};

@Component({
  selector: 'app-blog-article',
  standalone: true,
  imports: [LanguageSwitcher, RouterLink, PublicSiteFooter],
  providers: [provideTranslocoScope('blog')],
  templateUrl: './blog-article.html',
  styleUrl: './blog.scss',
})
export class BlogArticle {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly seo = inject(PublicSeoService);
  private readonly transloco = inject(TranslocoService);
  private readonly routeParams = toSignal(this.route.paramMap, {
    initialValue: this.route.snapshot.paramMap,
  });

  private readonly rawContent = toSignal(
    this.transloco
      .selectTranslation('blog')
      .pipe(map(() => this.transloco.translateObject<BlogTranslation>('blog'))),
    { initialValue: EMPTY_CONTENT },
  );

  protected readonly content = computed(() => this.rawContent());
  protected readonly locale = computed(() => blogLocaleFromRoute(this.routeParams().get('locale')));
  protected readonly slug = computed(() => this.routeParams().get('slug') ?? '');
  protected readonly article = computed(() => articleForSlug(this.slug(), this.content()));

  protected readingMeta(article: ResolvedBlogArticle): string {
    return interpolateBlogTemplate(this.content().article.readingMeta, {
      minutes: String(article.readingMinutes),
      updated: article.updatedLabel,
      author: article.authorName,
    });
  }

  protected safetyNotice(article: ResolvedBlogArticle): string {
    return interpolateBlogTemplate(this.content().article.safetyNotice, {
      updated: article.updatedLabel,
    });
  }

  protected blogIndexPath(): string {
    return blogIndexPath(this.locale());
  }

  protected changeLocale(locale: string): void {
    if (isBlogLocale(locale)) {
      void this.router.navigateByUrl(blogArticlePath(locale, this.slug()));
    }
  }

  constructor() {
    effect(() => this.transloco.setActiveLang(this.locale()));

    effect(() => {
      const content = this.content();

      if (!content.index.title) {
        return;
      }

      const article = this.article();
      if (article === undefined) {
        this.seo.update({
          title: content.article.unavailableTitle,
          description: content.article.unavailableDescription,
          path: blogIndexPath(this.locale()),
          alternates: blogAlternatePaths(),
          robots: 'noindex, nofollow',
        });
        return;
      }

      this.seo.update({
        title: article.title,
        description: article.description,
        path: blogArticlePath(this.locale(), article.slug),
        type: 'article',
        publishedTime: article.publishedAt,
        modifiedTime: article.updatedAt,
        image: article.coverSrc,
        alternates: blogAlternatePaths(article.slug),
        structuredData: {
          '@context': 'https://schema.org',
          '@type': 'BlogPosting',
          headline: article.title,
          description: article.description,
          image: `https://conviveaula.com${article.coverSrc}`,
          datePublished: article.publishedAt,
          dateModified: article.updatedAt,
          inLanguage: this.locale(),
          author: { '@type': 'Organization', name: 'Convive' },
          publisher: {
            '@type': 'Organization',
            name: 'Convive',
            url: 'https://conviveaula.com/',
          },
          mainEntityOfPage: `https://conviveaula.com${blogArticlePath(this.locale(), article.slug)}`,
        },
      });
    });
  }
}
