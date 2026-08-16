# ADR-0026: Use Transloco for runtime internationalisation

- **Status:** Accepted
- **Date:** 17 August 2026
- **Related issues:** [#255](https://github.com/albertogalvez-dev/Convive/issues/255), [#256](https://github.com/albertogalvez-dev/Convive/issues/256), [#257](https://github.com/albertogalvez-dev/Convive/issues/257)
- **Depends on:** [ADR-0004](0004-use-angular-for-the-web-frontend.md)

## Context

The recorded linguistic scope for Convive's public site is Spanish, Catalan,
Valencian, Basque, Galician, Aranese and — per the decision recorded on #257 —
Arabic, with Asturian and Aragonese left as an explicit later decision. Two
standing rules govern how any of this is published:

- A public translation must be complete and reviewed before it ships. A
  partial or machine-only public translation is prohibited.
- Nothing here is safe to publish half-finished. This is safeguarding content
  read by children in a difficult moment; a sentence that silently reverts to
  Spanish mid-page, or a raw translation key leaking to the screen, is worse
  than the page simply not existing in that language yet.

Today the frontend (Angular 22, TypeScript 6, standalone components, Signals,
no NgModules) is Spanish-only with no internationalisation library installed.

Arabic adds a requirement none of the other six languages carry: right-to-left
text direction. That is a layout capability, not translation content, and it
has to be accounted for in whatever foundation is chosen, not bolted on later.

## Decision drivers

- A locale that is not complete and signed off must never be visible in
  production, and must never silently fall back to Spanish mid-sentence —
  it must not exist as a reachable option at all.
- Adding the machinery once should make each subsequent locale a
  translation-and-review task, not an engineering one.
- The one-developer constraint: whatever is chosen has to be maintainable by
  one person across up to seven locales, one of them right-to-left, without a
  disproportionate build or release burden.
- Every dependency runs on a permanently free licence — no paid service, ever.

## Options considered

### `@angular/localize` (Angular's built-in i18n, compile-time)

Angular's own solution: string extraction from templates, translation files
per locale, and a separate compiled application bundle produced per locale at
build time.

- Runtime language switching is not possible without navigating to a
  locale-specific build; there is no in-app language switcher without
  significant extra engineering.
- No built-in mechanism to hide an incomplete locale. A build with missing
  translations either fails or silently falls back per a global
  `i18nMissingTranslation` build option — exactly the "silently reverts to
  Spanish mid-sentence" failure mode the standing rule exists to prevent. A
  gate would have to be built by hand, external to the framework's own
  tooling.
- Seven locale bundles (Spanish, Catalan, Valencian, Basque, Galician, Aranese,
  Arabic) means seven separate build artefacts and, in the simplest hosting
  model, seven separate deployed paths or subdomains, each rebuilt on every
  content change.
- RTL for Arabic is a page-level concern independent of which i18n library is
  used; this option carries no particular advantage or disadvantage for it.

### Transloco (runtime, JSON-based, scoped loading)

A widely used, actively maintained, MIT-licensed runtime library for Angular.
The application builds once; translations are JSON files fetched at runtime
and can be scoped per feature area.

- Runtime language switching is native: `TranslocoService.setActiveLang()`
  re-renders without a page reload.
- Its `missingHandler` and per-scope loading give a real completeness gate: a
  locale (or a scope within it) can be withheld from the runtime entirely
  until it is complete, rather than falling back silently key by key. This is
  the deciding factor — it is the only one of the two options where "hide an
  incomplete locale" is a configuration, not a feature to build.
- Scoped, lazy-loaded translation files mean the public site does not ship six
  languages' worth of strings to every visitor; only the active locale's
  files are fetched.
- Signals-compatible; the project already uses Signals throughout, and
  Transloco's reactive primitives fit that pattern rather than requiring a
  separate RxJS-heavy layer.
- One build artefact regardless of locale count, which matches the
  one-developer constraint better than a per-locale build matrix.
- RTL is still a layout concern Transloco does not solve by itself — the
  application must set `dir="rtl"` on the document root when Arabic is active
  and verify every layout, icon and directional affordance under it. Choosing
  Transloco does not reduce this cost, but it does not add to it either: the
  RTL work is the same size regardless of which i18n library sits underneath
  it.

## Decision

Use Transloco for runtime internationalisation of the public site.

The deciding reason is narrow and specific to this project's own rule: a
public translation must never ship partial, and Transloco is the option that
can enforce that as configuration — `missingHandler` plus scoped loading — 
rather than requiring bespoke tooling built and maintained by hand on top of
`@angular/localize`.

## Consequences

- A new production dependency (`@jsverse/transloco` or its current published
  package name — confirm at implementation time) is added. It is MIT-licensed
  and free, consistent with the project's permanently-free-tier constraint.
- Existing Spanish strings move into scoped JSON translation files without
  changing any user-facing copy. This is infrastructure work with no visible
  effect until a second locale is added.
- A locale is invisible in production until it is both complete (every key
  present, checked automatically) and signed off (a named reviewer, a
  recorded date — the process #256 defines). Per the decision recorded on
  #256, the reviewer role is satisfied by the same author performing multiple
  documented self-review passes, not a separate independent reviewer; this
  does not change the completeness gate itself, only who operates it.
- Adding Arabic under #257 requires an RTL capability the foundation does not
  currently have: `dir` attribute switching on the document root tied to the
  active locale, and a layout audit for every RTL-sensitive surface (icon
  direction, text alignment, form-field order). This is scoped as part of
  #257's own work, using the machinery this ADR establishes, not as a change
  to this decision.
- `lang` is set correctly on the document root per the active locale (WCAG
  3.1.1), and any content that mixes languages within a single page (for
  example a Basque school name inside Spanish body text) is marked with its
  own `lang` attribute (WCAG 3.1.2) — this is an implementation requirement
  of #255, not a consequence specific to choosing Transloco.

## Review triggers

This decision should be reviewed if:

- Transloco becomes unmaintained or its licence changes away from a
  permanently free model;
- the completeness-gating mechanism (`missingHandler`, scoped loading) proves
  insufficient in practice to prevent a partial locale reaching production;
- the number of locales or the RTL requirement makes a single-build runtime
  approach measurably worse than a compiled per-locale build would have been;
- Angular's own `@angular/localize` gains an equivalent completeness gate that
  removes the deciding advantage this ADR is based on.

If the technology changes, a later ADR must supersede ADR-0026 and describe
the migration path for already-published locales.

## References

- Transloco documentation: https://jsverse.gitbook.io/transloco
- Angular internationalisation guide: https://angular.dev/guide/i18n
- WCAG 3.1.1 Language of Page: https://www.w3.org/WAI/WCAG22/Understanding/language-of-page.html
- WCAG 3.1.2 Language of Parts: https://www.w3.org/WAI/WCAG22/Understanding/language-of-parts.html
