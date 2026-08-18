# Screen-reader checklist

The rows of the [manual verification matrix](accessibility.md) that cannot be
checked structurally or visually. They need a person with assistive technology,
and they are the difference between "the markup is right" and "a person can use
this".

Use fictional data only. Record the result against the audit record for the pass
you are completing — the most recent is
[16 August 2026](accessibility-audit-2026-08-16.md).

## Before you start

Any of these combinations is enough for a pass; note which one you used, because
behaviour differs between them:

- NVDA with Firefox or Chrome on Windows
- JAWS with Chrome on Windows
- VoiceOver with Safari on macOS or iOS

Turn the screen off, or close your eyes for each item. Reading the screen while
listening defeats the check.

## Public website

1. **Landing on the home page.** Is it clear what this is, and that it is a
   fictional demonstration, before you reach the main content?
2. **The footer boundary statement.** Navigate by landmark to the footer. Is the
   "not an emergency channel, not an official Junta service" statement reachable
   and understandable on its own?
3. **The helplines.** Are 112, 016 and the ANAR number announced as a list of
   three items, each with its service name, and are they dialable links?
4. **The public information pages.** On `/privacidad/`, navigate by heading.
   Does the structure let you find "Los límites del anonimato" without reading
   the whole page?
5. **The review line.** At the end of each public document, is the reviewer,
   date and review trigger announced as ordinary text rather than skipped?

## Reporting journey

6. **The form's first question.** Is the textarea's label announced, along with
   the character counter and its limit?
7. **A validation error.** Submit the step empty. Is the error announced without
   moving focus, and does it identify which field it belongs to?
8. **The invalid-link state.** Open `/r/` with a broken identifier. **This is the
   one added in F-02 of the 16 August audit** — is the error announced when it
   replaces the loading state, and is the announcement useful rather than a wall
   of text?
9. **The fictional-demonstration state.** Same question, for the `role="status"`
   variant.
10. **The receipt.** After submitting, are the reference and the access secret
    announced clearly enough to write down, and is the warning about the secret
    announced before it?

## Professional workspace

11. **The introduction dialog.** On a first visit, is the dialog announced as a
    dialog, with its step position ("Paso 1 de 4")? Does `Escape` leave it, and
    does focus land somewhere sensible afterwards?
12. **Tab confinement.** With the dialog open, does Tab cycle inside it, or can
    you reach the workspace behind it?
13. **Navigation landmarks.** Can you reach the sidebar navigation, the notices
    link and the sign-out control by landmark and by heading?
14. **The sidebar collapse control.** Is its expanded state announced, and does
    the announcement change when you activate it?
15. **A case list.** Are the case rows announced in a way that makes their
    status and responsible person clear, without needing colour or position?
16. **The account-administration email correction.** Open it. Is the field's
    label announced with the professional's name, and is the warning that the
    person will be signed out announced before the field?

## What to record

For each item: the combination you used, whether it passed, and if it failed,
what you heard and what you expected. A failure recorded as "does not work" is
not actionable; "it announced the heading but never the error" is.

Anything that blocks a task becomes an issue in this repository before public
release. Anything that is awkward but usable is recorded as accepted with a
reason.
