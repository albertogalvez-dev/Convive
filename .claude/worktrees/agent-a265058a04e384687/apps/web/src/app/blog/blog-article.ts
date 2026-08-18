import { Component, computed, inject, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';

import { PublicSeoService } from '../public-seo.service';
import { PublicSiteFooter } from '../public-site-footer/public-site-footer';
import { articleForSlug } from './blog-content';

@Component({
  selector: 'app-blog-article',
  standalone: true,
  imports: [RouterLink, PublicSiteFooter],
  templateUrl: './blog-article.html',
  styleUrl: './blog.scss',
})
export class BlogArticle implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly seo = inject(PublicSeoService);
  protected readonly article = computed(() =>
    articleForSlug(this.route.snapshot.paramMap.get('slug') ?? ''),
  );

  ngOnInit(): void {
    const article = this.article();
    if (article === undefined) {
      this.seo.update({
        title: 'Artículo no disponible',
        description: 'El artículo solicitado no está disponible.',
        path: '/blog/',
        robots: 'noindex, nofollow',
      });
      return;
    }
    this.seo.update({
      title: article.title,
      description: article.description,
      path: `/blog/${article.slug}/`,
      type: 'article',
      publishedTime: article.publishedAt,
      modifiedTime: article.updatedAt,
    });
  }
}
