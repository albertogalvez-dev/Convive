# Publishing an additional locale

## The rule this process exists to enforce

A public translation must be complete and reviewed before it ships. A partial
or machine-only public translation is prohibited. This is safeguarding content
read by children in a difficult moment — a sentence that silently reverts to
Spanish mid-page, or a key leaking to the screen, is worse than the page not
existing in that language at all.

The mechanism behind the rule is [ADR-0026](../architecture/decisions/0026-use-transloco-for-runtime-internationalisation.md):
a locale is invisible in production until it is added to `READY_LOCALES` in
`apps/web/src/app/i18n/i18n-completeness.ts`. Adding a translation JSON file to
disk does not publish anything by itself — that list is the actual gate.

## Steps to add a locale

1. **Draft.** Translate every scoped JSON file for the locale from the current
   Spanish source. Do not translate a subset — a partial file cannot pass step
   2, by design.
2. **Prove completeness.** Run `checkLocaleCompleteness(source, candidate)`
   for every scoped file. It must report `complete: true` — no missing key,
   no unexpected leftover key. A test that calls this function for the new
   locale's files against the current Spanish source is required before the
   locale can move to step 3; it is what future changes to the source will
   keep honest.
3. **Review.** Per the decision recorded on
   [#256](https://github.com/albertogalvez-dev/Convive/issues/256), the
   reviewer role is satisfied by the same author performing multiple genuine
   self-review passes — re-reading against the source, checking any
   naming/regional sensitivity the language carries, checking reading age on
   child-facing copy — documented the same way code-review iteration is
   documented elsewhere in this project. Each pass records what it actually
   changed. A pass that changes nothing ends the process; a pass that finds
   something real means at least one more pass follows it.
4. **Sign off.** Record, next to the ADR or in the pull request that adds the
   locale: who reviewed it (per step 3), the date, and confirmation that step
   2's completeness check passed for every scoped file the locale covers.
5. **Publish.** Add the locale's code to `READY_LOCALES`. This is the only
   step that makes the locale reachable through the UI.

## Withdrawal

**Any drift withdraws the locale from `READY_LOCALES` immediately** — there is
no tolerated threshold of "close enough" for safety and legal copy. When a
Spanish source string changes in a scope that has a published translation:

1. The completeness check for that scope, for every published locale, is run
   again as part of the same change that edited the Spanish source.
2. Any locale whose check now fails is removed from `READY_LOCALES` in the
   same pull request — not left visible while a translator catches up. A
   locale a visitor can select must always be complete, never approximately
   complete.
3. The locale is restored to `READY_LOCALES` only after steps 2–4 of
   publishing are repeated for the changed keys.

This makes "the Spanish copy changed" and "a locale went dark" the same
commit, rather than two events that can drift apart in time.
