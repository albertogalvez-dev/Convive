# Translation pipeline: two guarantees, on purpose

Verified against the codebase on 18 August 2026.

**The property this diagram exists to make obvious: public content and protocol
content are protected differently, and the difference is deliberate.**

Confusing the two is how a half-translated page reaches a child, or how a
professional mid-case is shown a raw translation key. Both are real failures;
they need opposite defences.

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'primaryColor': '#E2E8F0', 'primaryTextColor': '#0F172A', 'primaryBorderColor': '#64748B', 'lineColor': '#64748B'}}}%%
flowchart TB
    subgraph publicPath["Public path — all or nothing"]
        direction TB
        pubSource["es.json per scope"]
        completeness["checkLocaleCompleteness<br/>key sets must match exactly"]
        ready["READY_LOCALES<br/>publication is adding the code here"]
        pubReader["A child reads a complete page,<br/>or that language is not offered"]

        pubSource --> completeness
        completeness -->|"complete + signed off"| ready
        completeness -->|"incomplete"| unreachable["Locale unreachable.<br/>The file on disk publishes nothing."]
        ready --> pubReader
    end

    subgraph protocolPath["Protocol and professional content — fallback"]
        direction TB
        template["WorkflowTaskTemplate"]
        key["Key derived from<br/>(territory, stage)"]
        resolve{"Translation present?"}
        translated["Shown translated"]
        spanish["Shown in Spanish<br/>never the raw key"]

        template --> key
        key --> resolve
        resolve -->|"yes"| translated
        resolve -->|"no"| spanish
    end

    drift["Value-drift check<br/>source reworded, translation left behind → build fails"]
    pubSource -.-> drift
    template -.-> drift

    classDef gate fill:#FEF3C7,stroke:#D97706,color:#78350F,stroke-width:2px
    classDef good fill:#E0F2FE,stroke:#0284C7,color:#0C4A6E,stroke-width:2px
    classDef stop fill:#FEE2E2,stroke:#DC2626,color:#7F1D1D,stroke-width:2px
    classDef check fill:#334155,stroke:#334155,color:#FFFFFF,stroke-width:2px
    class completeness,resolve gate
    class ready,pubReader,translated,spanish good
    class unreachable stop
    class drift check
```

## Why they differ

**Public content is gated** because a child arriving in difficulty must not
meet a page that reverts to Spanish mid-sentence, or leaks a key to the screen.
A page that is not fully translated is worse than a page that does not exist in
that language. So the locale is simply not offered.

**Protocol content falls back** because gating it would mean a professional in
Catalonia could not select Catalan until every template title across nineteen
territories had been translated — including territories they will never open.
And the failure mode is different: a professional reading one Spanish title
inside an otherwise Catalan page has lost nothing. A raw key such as
`caseWorkflow.template.es_md.assessment` mid-case teaches them nothing at all.

The two harms are not the same, so the two defences are not the same.

## The third property: drift

Both gates compare **structure**. Neither can see a Spanish string being
reworded underneath a translation that stays put — the key still exists
everywhere, so every check passes while published locales state the previous
version of a safety notice.

A separate check records the source text each locale was confirmed against and
fails the build when they diverge. Re-confirming is one command per locale, and
it prints every key it is about to bless, because confirming asserts a human
read that string in that language.

## Published today

`es` · `ca` · `ca-valencia` · `ar` · `gl`

Basque is drafted work held on #312: not for lack of effort, but because
comprehension cannot be certified by whoever wrote the text, and Basque is a
language isolate where a mistake reads as fluent Basque saying something
slightly different.

## Related decisions

- [ADR-0026: Use Transloco for runtime internationalisation](../decisions/0026-use-transloco-for-runtime-internationalisation.md)
- [ADR-0027: Derive protocol translation keys and fall back to Spanish](../decisions/0027-derive-protocol-translation-keys-and-fall-back-to-spanish.md)
- [Locale process](../../content/i18n-process.md)
