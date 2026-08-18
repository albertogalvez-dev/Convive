# ADR-0027: Derive protocol translation keys and fall back to Spanish

- **Status:** Accepted
- **Date:** 18 August 2026
- **Related issues:** [#310](https://github.com/albertogalvez-dev/Convive/issues/310), [#311](https://github.com/albertogalvez-dev/Convive/issues/311), [#312](https://github.com/albertogalvez-dev/Convive/issues/312), [#318](https://github.com/albertogalvez-dev/Convive/issues/318)
- **Depends on:** [ADR-0026](0026-use-transloco-for-runtime-internationalisation.md)
- Diagram: [Translation pipeline: two guarantees, on purpose](../diagrams/translation-pipeline.md)

## Context

ADR-0026 set the rule for the public site: a locale is complete and signed off
before it is reachable, or it does not exist as an option at all. That rule
protects a child reading safeguarding content in a difficult moment, and
`checkLocaleCompleteness` plus `READY_LOCALES` enforce it.

Territorial protocol content does not fit that rule, and #310 found out why by
looking rather than assuming. Task template titles were stored in the database
and rendered raw. All ninety-seven of them, across sixteen territorial scopes,
were **English** — developer-facing seed text that had quietly become
user-facing on a page with zero Transloco usage, inside a product whose users
are Spanish-speaking school staff. Nobody had decided to show English to a
Spanish school; it survived because noticing was nobody's job.

Two questions had to be answered together: how a template title acquires a
translation key, and what a reader sees when that key has no translation yet.

## Decision drivers

- Twenty territorial migrations exist and more will follow. Whatever is chosen
  must be impossible for the next migration to forget.
- A professional is reading this **mid-case**. A raw key such as
  `caseWorkflow.template.es_md.assessment` on screen while deciding what to do
  about a bullying report is worse than any wording problem.
- Template titles carry verified facts — which body acts, which deadline the
  source states, and crucially whether it counts school days, working days,
  calendar days or hours. A mechanism that invites re-typing invites drift.
- The public-site rule must not be weakened to accommodate this.

## Considered alternatives

**Hand-assign a key per template.** Rejected: with twenty migrations already
merged it guarantees an eventual omission, and an omitted key is invisible
until a reader hits that one row.

**Store translations in the database alongside the title.** Rejected: it puts
user-facing copy in migrations rather than in the reviewed translation files,
so a translation change would require a migration and would bypass the review
process #256 established.

**Apply the public all-or-nothing gate to protocol content too.** Rejected,
and this is the substantive call. It would mean a professional in Catalonia
cannot select Catalan at all until every one of ~200 template titles across
twenty territories is translated — including territories whose protocols they
will never open. The public rule exists because a child must never meet a
half-translated page; a professional meeting one Spanish title inside an
otherwise Catalan page is a different and far smaller harm than being unable
to work in their own language at all.

## Decision

**Derive the key from the data.** `WorkflowTaskTemplate::deriveTitleKey()`
computes `caseWorkflow.template.<territory>.<stage>` from the source's
territory and the template's stage. `(territory, stage)` was verified unique
across all existing templates before choosing it. A new territorial migration
gets its keys by construction and cannot forget one.

**Fall back to the Spanish source, never to the key.**
`resolveTemplateTitle()` compares Transloco's result against the key it was
given; when they match — Transloco's signal that no translation exists — it
renders the Spanish title from the database instead. A half-finished locale
degrades to correct Spanish, never to noise.

**Keep the two guarantees separate and say so.** The public path stays
all-or-nothing under `READY_LOCALES`. Protocol and professional-facing content
degrades gracefully. The `professional-case` scope is therefore deliberately
not part of the public completeness suite.

## Consequences

**Positive.** A new territorial migration needs no translation work to be
correct; it inherits the key rule and the fallback. Translations live in
reviewed JSON, not in migrations. A professional can work in their own
language before every territory's titles are translated.

**Negative, and accepted.** A professional may see an occasional Spanish title
inside an otherwise translated page — the trade made knowingly above. Missing
protocol translations are also less visible than missing public ones, since
nothing fails loudly; the mitigation is that translation coverage is tracked
on its issue rather than discovered in production.

**A rule this creates.** Every territorial migration must supply `title_key`
explicitly, because #311 made the column `NOT NULL` with a unique index. The
value must be exactly what `deriveTitleKey()` would compute.

## Review triggers

- A territory needs more than one template for the same stage, breaking the
  `(territory, stage)` uniqueness the derived key depends on.
- Protocol titles start being read by anyone other than case-handling
  professionals — a public audience would pull this content back under the
  all-or-nothing rule.
- Translation coverage stops being tracked, at which point the quiet fallback
  becomes a way to not notice rather than a way to stay readable.
