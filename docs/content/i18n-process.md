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
   something real means at least one more pass follows it. For a
   safety-relevant scope (`public-site-footer`'s emergency resources,
   `public-information`'s privacy and safety-boundary notices in particular),
   a dedicated final pass confirms the translation carries the identical
   meaning as the Spanish source — not just fluent, but not stronger and not
   weaker a claim than the original.
   - **For a right-to-left locale specifically** (Arabic, per
     [#257](https://github.com/albertogalvez-dev/Convive/issues/257)), the
     review additionally includes a real visual check in a running browser:
     switch to the locale through the language switcher and look at the
     reporting form, the shared footer, `public-information`, and the
     switcher itself. A form whose submit button ends up on the wrong side,
     or a footer whose emergency phone numbers read out of order, is a
     layout defect the completeness gate cannot catch, because it checks
     translation keys, not rendered direction. This check is recorded the
     same way a translation pass is: what was actually looked at, and what
     was fixed as a result.
4. **Sign off.** Record, next to the ADR or in the pull request that adds the
   locale: who reviewed it (per step 3), the date, confirmation that step 2's
   completeness check passed for every scoped file the locale covers, and —
   for Arabic specifically, given this project has meaningfully less
   structural certainty in it than in Catalan — that the review was the same
   author's self-review, not an independent native speaker's. Update this
   note if that ever changes.
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

## Keeping a published locale in sync (#325)

Completeness is checked at a moment; staying correct is a second property.

`checkLocaleCompleteness` compares **key sets**. It cannot see a Spanish
string being reworded underneath a translation that stays put: the key still
exists everywhere, so every check passes while published locales state the
previous version of the text. For an ordinary label that is untidy. For the
notice saying Convive is not an emergency channel and reaches no real school,
it means a Spanish reader and an Arabic reader are told different things, with
nothing going red.

`translation-sync.spec.ts` closes that. Each published locale carries a record
in `src/i18n/translation-sync/<locale>.json` of the Spanish text it was last
confirmed against, as a digest per key. If the source is reworded and the
locale is not re-confirmed, the suite fails and names the key.

### When you reword a Spanish string

1. Update `es.json`.
2. Update the same key in every locale that translates that scope.
3. Re-confirm that locale:

   ```
   npm run i18n:confirm -- gl
   npm run i18n:confirm -- gl --dry-run   # list first, write nothing
   ```

The command takes **one locale** and prints every key it is about to
re-confirm. That is deliberate: confirming asserts that someone read the
reworded string *in that language*. A command that blessed every locale at
once would hand back the property the check exists to defend.

### Failing the build, not the publication

Drift fails the **test suite** rather than dropping the locale from
`READY_LOCALES` at runtime. Both were considered. Automatic unpublication
sounds safer and is worse: it would silently remove a language from readers who
depend on it, as a side effect of an unrelated copy edit, and the person who
caused it would never see it happen. Failing the build puts the problem in
front of the person holding the reworded string, at the moment of least cost,
with the affected keys listed.

### Scope coverage is per locale, not per product

A locale need not translate every scope. [ADR-0027](../architecture/decisions/0027-derive-protocol-translation-keys-and-fall-back-to-spanish.md)
gives protocol and professional content a *fallback* guarantee rather than the
public path's all-or-nothing gate, so `ca-valencia` and `ar` translate no
`professional-case` and fall back to Spanish there by design. Confirmation
covers what a locale actually translates; demanding full coverage first would
refuse to protect the scopes it does.
