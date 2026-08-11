import { Component, inject, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';

import { PublicSeoService } from '../public-seo.service';
import { BLOG_ARTICLES } from './blog-content';

@Component({
  selector: 'app-blog-index',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './blog-index.html',
  styleUrl: './blog.scss',
})
export class BlogIndex implements OnInit {
  private readonly seo = inject(PublicSeoService);
  protected readonly articles = BLOG_ARTICLES;

  ngOnInit(): void {
    this.seo.update({
      title: 'Blog',
      description:
        'Contenido revisado sobre convivencia escolar y el enfoque de producto de Convive.',
      path: '/blog/',
    });
  }
}
