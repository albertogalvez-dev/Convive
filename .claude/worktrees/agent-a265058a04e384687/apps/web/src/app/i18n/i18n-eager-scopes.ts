/**
 * Scopes that must be loaded before the application renders its first frame,
 * because they back content shown unconditionally on every page.
 *
 * Discovered the hard way: `TranslocoPipe` renders empty text until its
 * scope's JSON has been fetched, and the shared public footer's navigation
 * links are focusable before that resolves — a real WCAG 4.1.2 failure (a
 * focusable control with no accessible name), caught by the automated axe
 * pass in the End-to-end suite, not invented defensively.
 *
 * Add a scope here only if it is rendered on effectively every page, the way
 * `public-site-footer` is. A scope used on one route can accept its own
 * component-level loading state instead — preloading everything here would
 * defeat the point of scoped, lazy-loaded translations.
 */
export const EAGER_TRANSLOCO_SCOPES: readonly string[] = ['public-site-footer'];
