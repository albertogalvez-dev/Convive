# Plain-language and cognitive-accessibility standard

This is what "write more simply" means concretely in Convive, so it can be
checked rather than debated. It answers #260.

It is **not** a restatement of the accessibility audit in #167. That covered
structural accessibility — contrast, focus order, names and roles — where the
failure is *a reader cannot operate the page*. This covers comprehension,
where the failure is *a reader operates the page perfectly and misunderstands
what it told them*. A page can pass every WCAG success criterion and still be
unreadable to the child it was written for.

## How readability is measured here

Spanish, not English. Applying the English Flesch formula to Spanish reports
everything as harder than it is, because Spanish carries more syllables per
word by nature; a Spanish sentence scored with an English formula looks
alarming and tells you nothing.

Convive uses **Szigriszt-Pazos *perspicuidad*, read on the INFLESZ scale** —
the Flesch derivative recalibrated for Spanish and the one used in
Spanish-language health and administrative plain-language work:

```
INFLESZ = 206.835 − 62.3 × (syllables ÷ words) − (words ÷ sentences)
```

| INFLESZ | Band |
|---|---|
| < 40 | muy difícil |
| 40–55 | algo difícil |
| 55–65 | normal |
| 65–80 | bastante fácil |
| > 80 | muy fácil |

Syllable counting is approximate — vowel groups, with adjacent strong vowels
split as hiatus. This is stated rather than hidden: the bands are what the
standard rests on, not the third decimal. A scope sitting on a boundary
deserves a human reading, not a recount.

**Measured on prose, not on labels.** INFLESZ assumes sentences. A scope full
of one-word navigation labels — *Accesibilidad*, *Privacidad*, *Contacto* —
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

The case page cites protocols. Precision outranks simplicity here: *equipo de
valoración*, *Informe A*, *días lectivos* are the words the source uses, and
replacing them with easier ones would make the citation wrong. A floor would
push against accuracy, so this tier is governed by sentence length alone —
long sentences hurt every reader regardless of vocabulary.

Professional copy is also where the derived-key fallback in
[ADR-0027](../architecture/decisions/0027-derive-protocol-translation-keys-and-fall-back-to-spanish.md)
applies, which is a separate axis: comprehension is about the wording, not
about which language it arrives in.

## What the measurement actually found

Measured 18 August 2026 across all ten scopes, prose only:

| Scope | INFLESZ | Longest sentence | Against its tier |
|---|---|---|---|
| public-home | **41.6** | 13 | Tier 2 target 65 — **widest gap in the product** |
| public-information | 60.9 | **37** | Tier 3 target ≤ 25 words |
| report-help | 63.0 | 10 | Tier 2 target 65 |
| public-site-footer | **64.9** | **17** | Tier 1 targets 65 / ≤ 15 — misses both |
| professional-case | 65.1 | 23 | Tier 3 — meets ≤ 25 |
| report-evidence | 66.5 | 10 | Tier 2 — meets |
| report-form | 68.2 | 20 | Tier 2 — meets |
| report-sending | 73.1 | 7 | Tier 2 — meets |
| report-result | 73.3 | 11 | Tier 2 — meets |
| report-header | n/a | 4 | labels only, nothing to measure |

**The data contradicted the issue's own priority order, and the data wins.**
#260 named "the reporting form and its validation" as the highest-priority
surface. It is not: at 68.2 the form already meets Tier 2, because the copy
work already done there worked. The gaps are in surfaces nobody had looked at.

Four gaps, recorded rather than rounded away:

- **`public-home` at 41.6** — the front door, and the hardest public prose in
  the product. Its six prose strings are the hero pitch and the card bodies;
  syllable density is what sinks it (*convivencia*, *comunicaciones*,
  *demostración*, *administración*).
- **`public-site-footer` at 64.9 with a 17-word sentence** — Tier 1 by content,
  and it misses its floor **by a tenth of a point**. That tenth is not rounded
  up. This is the text telling a reader that Convive is not an emergency
  channel and reaches no real school; either it clears the bar or it is a gap.
- **`report-help` at 63.0** — the first guidance a child reads.
- **`public-information` with a 37-word sentence** — its reading level is fine;
  its sentence length is not.

## Priority order for the pass

1. **`public-site-footer`** — Tier 1, safety-critical, small enough to fix
   completely and verify.
2. **`public-home` safety strings** — the demonstration disclaimer and the
   privacy card, which make a promise about anonymity a reader must not
   misread.
3. **`public-information` sentence length** — split the long sentences; the
   vocabulary is already fine.
4. **`report-*`** — already meets Tier 2; hold the line with the ratchet rather
   than rewrite.

## The tension this standard does not resolve on its own

`public-home`'s hero is also the pitch to a school deciding whether to adopt
Convive. Rewriting it purely for reading age is a **product and brand
decision**, not a readability one, and it is not made here. What is done here
is narrower and defensible without that decision: the strings on that page
that carry a **safety meaning** — the demonstration disclaimer, the anonymity
promise — are held to Tier 1 regardless of what the hero says.

## Specialist review: what is actually reachable

#260 suggested a Spanish university disability-research partnership and asked
that reachability be verified before committing to it. It is not something a
developer can commit to unilaterally — it needs an institution, a contact and
a timeframe, none of which exist today. Recording it as "the plan" would be
recording a wish.

The reachable path is the Spanish standard itself. **UNE 153101:2018 EX
*Lectura Fácil. Pautas y recomendaciones para la elaboración de documentos***
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
- **Not claimed:** Convive does not describe itself as *Lectura Fácil*
  conformant. The standard reserves that for validated texts. Measuring
  against a scale is not the same as passing a standard, and saying otherwise
  would be exactly the kind of overstatement this project has avoided
  elsewhere.

## Why the copy was not rewritten in this pass

A first pass was attempted and **reverted**, and the reason is worth recording
because it is a defect class rather than a mistake of taste.

Rewriting the Spanish safety strings improved `public-site-footer` from 54.2 to
58.3 and `public-home` from 40.5 to 47.6 on the original composite measure. It
also left **Catalan, Valencian, Galician and Arabic saying the old thing** —
and nothing failed. `checkLocaleCompleteness` compares **key sets, not
meanings**: a translation whose source has been reworded underneath it is
structurally perfect and semantically stale.

For a safety string this is worse than not improving it at all. A reader of
Spanish would be told one thing about what Convive is and is not, and a reader
of Arabic another, with no test anywhere going red.

So the copy pass must change every locale together, which makes it a different
piece of work with a different risk: Arabic is beyond what I can self-review,
the same admission recorded for Basque on #312. That is tracked separately.
The gate's blindness to value drift is a real finding and belongs to the audit.

## The ratchet

`plain-language.spec.ts` enforces two things:

1. **Tier floors**, asserted for the scopes that already meet them, so
   compliance cannot be lost silently.
2. **No scope may get harder than it is today** — on both axes. Each scope
   carries a recorded INFLESZ baseline and its current longest sentence; a
   change that worsens either fails, with the offending sentence quoted in the
   failure message.

Floors are deliberately *not* asserted for the four gaps above. A build that
is red from the moment the standard lands teaches people to skip it; a
documented gap with a ratchet under it does not decay while it waits.

The second point matters more than the first over time. A single rewrite
improves a page once; the ratchet is what stops the next fifty commits from
quietly undoing it, one convenient clause at a time.
