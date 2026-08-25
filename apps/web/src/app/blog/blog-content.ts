export interface BlogArticleCopy {
  readonly category: string;
  readonly title: string;
  readonly description: string;
  readonly updatedLabel: string;
  readonly coverAlt: string;
  readonly introduction: string;
  readonly sections: ReadonlyArray<{
    readonly heading: string;
    readonly paragraphs: ReadonlyArray<string>;
  }>;
  readonly sources: ReadonlyArray<{ readonly label: string; readonly href: string }>;
}

export interface BlogTranslation {
  readonly homeLinkLabel: string;
  readonly professionalAccess: string;
  readonly authorName: string;
  readonly index: {
    readonly eyebrow: string;
    readonly title: string;
    readonly description: string;
    readonly publishedArticlesLabel: string;
    readonly readingMeta: string;
  };
  readonly article: {
    readonly backToBlog: string;
    readonly readingMeta: string;
    readonly sourcesTitle: string;
    readonly safetyNotice: string;
    readonly unavailableTitle: string;
    readonly unavailableDescription: string;
    readonly unavailableBack: string;
  };
  readonly articles: Readonly<Record<string, BlogArticleCopy>>;
}

export interface BlogArticle {
  readonly slug: string;
  readonly publicationStatus: 'draft' | 'published';
  readonly publishedAt: string;
  readonly updatedAt: string;
  readonly readingMinutes: number;
  readonly coverSrc: string;
}

export interface ResolvedBlogArticle extends BlogArticle, BlogArticleCopy {
  readonly authorName: string;
}

export const BLOG_ARTICLES = blogCatalog as ReadonlyArray<BlogArticle>;

export function publishedArticleMetadata(now = new Date()): ReadonlyArray<BlogArticle> {
  return BLOG_ARTICLES.filter(
    (article) =>
      article.publicationStatus === 'published' &&
      new Date(article.publishedAt + 'T00:00:00Z').getTime() <= now.getTime(),
  );
}

export function publishedArticles(
  content: BlogTranslation,
  now = new Date(),
): ReadonlyArray<ResolvedBlogArticle> {
  return publishedArticleMetadata(now).flatMap((article) => {
    const copy = content.articles[article.slug];
    return copy ? [{ ...article, ...copy, authorName: content.authorName }] : [];
  });
}

export function articleForSlug(
  slug: string,
  content: BlogTranslation,
  now = new Date(),
): ResolvedBlogArticle | undefined {
  return publishedArticles(content, now).find((article) => article.slug === slug);
}

export function interpolateBlogTemplate(
  template: string,
  values: Readonly<Record<string, string>>,
): string {
  return template.replace(/\{\{\s*(\w+)\s*\}\}/g, (match, name: string) =>
    Object.hasOwn(values, name) ? values[name] : match,
  );
}
import blogCatalog from './blog-catalog.json';
