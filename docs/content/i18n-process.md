# Publishing an additional locale

## The rule this process exists to enforce

A public translation must be complete before it ships. A partial public
translation is prohibited. This is safeguarding content
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
3. **Verify the rendered language.** Re-read safety-relevant content against
   Spanish, especially emergency resources, privacy notices and the fictional
   data boundary. For a right-to-left locale, use the language selector and
   verify the reporting form, shared footer, public information and selector
   render in the correct direction.
4. **Publish.** Add the locale's code to `READY_LOCALES`. This is the only
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
3. The locale is restored to `READY_LOCALES` only after steps 2–3 of
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
reworded string _in that language_. A command that blessed every locale at
once would hand back the property the check exists to defend.

### Failing the build, not the publication

Drift fails the **test suite** rather than dropping the locale from
`READY_LOCALES` at runtime. Both were considered. Automatic unpublication
sounds safer and is worse: it would silently remove a language from readers who
depend on it, as a side effect of an unrelated copy edit, and the person who
caused it would never see it happen. Failing the build puts the problem in
front of the person holding the reworded string, at the moment of least cost,
with the affected keys listed.

### Complete current scope coverage

Every published locale covers every current translation scope, including
professional case work and territorial protocol titles. The source-language
fallback from [ADR-0027](../architecture/decisions/0027-derive-protocol-translation-keys-and-fall-back-to-spanish.md)
remains a defensive rendering safeguard, never the intended published result.
