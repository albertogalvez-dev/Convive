import { CanMatchFn } from '@angular/router';

import { isBlogLocale } from './blog-locales';

/** Prevents arbitrary first path segments from becoming duplicate Blog URLs. */
export const blogLocaleRouteGuard: CanMatchFn = (_route, segments) =>
  isBlogLocale(segments[0]?.path ?? null);
