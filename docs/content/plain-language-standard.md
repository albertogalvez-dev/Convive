# Plain-language and cognitive-accessibility standard

This is what "write more simply" means concretely in Convive, so it can be
checked rather than debated. It answers #260.

It is **not** a restatement of the accessibility audit in #167. That covered
structural accessibility — contrast, focus order, names and roles — where the
failure is _a reader cannot operate the page_. This covers comprehension,
where the failure is _a reader operates the page perfectly and misunderstands
what it told them_. A page can pass every WCAG success criterion and still be
unreadable to the child it was written for.

## How readability is measured here

Spanish, not English. Applying the English Flesch formula to Spanish reports
everything as harder than it is, because Spanish carries more syllables per
word by nature; a Spanish sentence scored with an English formula looks
alarming and tells you nothing.

Convive uses **Szigriszt-Pazos _perspicuidad_, read on the INFLESZ scale** —
the Flesch derivative recalibrated for Spanish and the one used in
Spanish-language health and administrative plain-language work:

```
INFLESZ = 206.835 − 62.3 × (syllables ÷ words) − (words ÷ sentences)
```

| INFLESZ | Band           |
| ------- | -------------- |
| < 40    | muy difícil    |
| 40–55   | algo difícil   |
| 55–65   | normal         |
| 65–80   | bastante fácil |
| > 80    | muy fácil      |

Syllable counting is approximate — vowel groups, with adjacent strong vowels
split as hiatus. This is stated rather than hidden: the bands are what the
standard rests on, not the third decimal. A scope sitting on a boundary
deserves a human reading, not a recount.

**Measured on prose, not on labels.** INFLESZ assumes sentences. A scope full
of one-word navigation labels — _Accesibilidad_, _Privacidad_, _Contacto_ —
scores terribly while being trivially readable, and the first measurement of
`public-site-footer` was dragged from 74.7 down to 54.2 by exactly that. A
formula misapplied does not become right by producing an alarming number:
chasing it would have meant rewriting correct labels. Strings under six words
are labels and are excluded.

The measurement runs in the test suite (`plain-language.spec.ts`), against the
real `es.json` files. A standard that lives only in a document decays; this one
fails the build.

## The standard

Three tiers, because the same floor for all three would be wrong in both
directions — it would let the front door off too lightly and make protocol
citations less precise than they must be.

### Tier 1 — Safety-critical copy: INFLESZ ≥ 65, sentences ≤ 15 words

Copy a reader must understand **while distressed**, where a misunderstanding
has a real-world cost: the boundary notice saying this is a demonstration and
reaches no real school, the notice that Convive is not an emergency channel,
the emergency resources, and the access-secret warnings a reporter must act on
before closing the page.

One idea per sentence. No subordinate clause carrying a second obligation.

### Tier 2 — Child-facing public copy: INFLESZ ≥ 65, sentences ≤ 20 words

The reporting journey end to end: form, validation, evidence, sending, result,
help. A child under stress is the assumed reader, not an adult skim-reading.

### Tier 3 — Professional-facing copy: sentences ≤ 25 words, no INFLESZ floor

The case page cites protocols. Precision outranks simplicity here: _equipo de
valoración_, _Informe A_, _días lectivos_ are the words the source uses, and
replacing them with easier ones would make the citation wrong. A floor would
push against accuracy, so this tier is governed by sentence length alone —
long sentences hurt every reader regardless of vocabulary.

Professional copy is also where the derived-key fallback in
[ADR-0027](../architecture/decisions/0027-derive-protocol-translation-keys-and-fall-back-to-spanish.md)
applies, which is a separate axis: comprehension is about the wording, not
about which language it arrives in.

## What the measurement actually found

Measured 18 August 2026 across all ten scopes, prose only:

| Scope              | INFLESZ | Longest sentence | Against its tier                |
| ------------------ | ------- | ---------------- | ------------------------------- |
| public-home        | 72.3    | 7                | Tier 2 — meets                  |
| public-information | 62.7    | 24               | Tier 3 — meets ≤ 25 words       |
| report-help        | 81.0    | 8                | Tier 2 — meets                  |
| public-site-footer | 70.6    | 12               | Tier 1 — meets                  |
| professional-case  | 65.1    | 23               | Tier 3 — meets ≤ 25             |
| report-evidence    | 66.5    | 10               | Tier 2 — meets                  |
| report-form        | 68.2    | 20               | Tier 2 — meets                  |
| report-sending     | 73.1    | 7                | Tier 2 — meets                  |
| report-result      | 73.3    | 11               | Tier 2 — meets                  |
| report-header      | n/a     | 4                | labels only, nothing to measure |

The four recorded gaps are now closed. The safety boundary retains its exact
commitments while using shorter sentences, the reporting help uses direct
actions, the home copy avoids unnecessary jargon, and every public-information
sentence stays within the Tier 3 limit. The ratchet now treats those measured
values as the delivery baseline.

## The tension this standard does not resolve on its own

`public-home`'s hero is also the pitch to a school deciding whether to adopt
Convive. Its current wording is intentionally direct and keeps the product
claim narrow: it says that Convive helps a centre listen and respond with care;
it does not make an operational or safeguarding promise beyond the documented
demonstration boundary and anonymity limits.

## Specialist review: what is actually reachable

#260 suggested a Spanish university disability-research partnership and asked
that reachability be verified before committing to it. It is not something a
developer can commit to unilaterally — it needs an institution, a contact and
a timeframe, none of which exist today. Recording it as "the plan" would be
recording a wish.

The reachable path is the Spanish standard itself. **UNE 153101:2018 EX
_Lectura Fácil. Pautas y recomendaciones para la elaboración de documentos_**
(AENOR, May 2018, experimental) is the first Spanish technical standard for
Easy-to-Read, and it specifies not only writing and design guidance but a
**validation methodology**. Its central requirement is the one that matters
here: an Easy-to-Read text is validated by **people with reading difficulties
from the target audience**, not by an expert declaring it simple.

That reframes the ask. The blocker was never finding a linguist; it is that
comprehension cannot be certified by the person who wrote the text — the same
reason Basque translation is held on #312. So:

- **In-house, now:** the INFLESZ gate below, which catches regression
  automatically and needs nobody's calendar.
- **Before any real-school deployment:** validation with target readers per
  UNE 153101, which for a school product means students. That needs a centre,
  consent, and a safeguarding frame — a decision for Alberto, not a task to
  schedule.
- **Not claimed:** Convive does not describe itself as _Lectura Fácil_
  conformant. The standard reserves that for validated texts. Measuring
  against a scale is not the same as passing a standard, and saying otherwise
  would be exactly the kind of overstatement this project has avoided
  elsewhere.

## Keeping translated copy aligned

The copy pass changes every published locale together. Structural completeness
alone cannot detect a reworded Spanish value under an unchanged translation;
the source-digest check records the source text each locale has been updated
against and fails the suite when it drifts. This keeps the safety boundary and
plain-language improvements consistent throughout the product.

## The ratchet

`plain-language.spec.ts` enforces two things:

1. **Tier floors**, asserted for the scopes that already meet them, so
   compliance cannot be lost silently.
2. **No scope may get harder than it is today** — on both axes. Each scope
   carries a recorded INFLESZ baseline and its current longest sentence; a
   change that worsens either fails, with the offending sentence quoted in the
   failure message.

The second point matters more than the first over time. A single rewrite
improves a page once; the ratchet is what stops the next fifty commits from
quietly undoing it, one convenient clause at a time.
