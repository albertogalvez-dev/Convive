# Public information for the fictional demonstration

## Purpose and boundary

The public website publishes a deliberately limited set of documents: what the
demonstration is, what it does with the little information it holds, what it
stores in a browser, the conditions for using the sandbox, and the state of its
accessibility work. Nothing else is published.

No real-service terms, centre contract or commercial-email policy exists,
because none has been decided. Publishing an invented one would present the
project as a service it is not, which is the single failure mode this document
exists to prevent.

The documents are versioned in
`apps/web/src/app/public-information/public-information-content.ts`, so the
published wording, its review metadata and its route are reviewable in the same
pull request.

## The published set

| Route | Document |
|---|---|
| `/aviso-demostracion/` | Fictional-demonstration and non-emergency notice |
| `/privacidad/` | Sandbox privacy notice |
| `/cookies/` | Cookie notice |
| `/terminos/` | Sandbox terms |
| `/accesibilidad/` | Accessibility notice |
| `/contacto/` | Contact routes |

`PUBLIC_INFORMATION_PAGES` is asserted against this exact list in
`public-information-content.spec.ts`. Adding a public document is therefore a
deliberate act with a failing test until the set is updated on purpose.

## Who publishes, and how a visitor gets in touch

The site is published by Alberto Gálvez as a personal project, under his own
name, with no tax identifier and no postal address: the demonstration processes
no visitor personal data, so the heavier identification a real service would
need does not apply.

Two routes exist, both defined in `apps/web/src/app/public-identity.ts` and
derived from the public hostname rather than written out by hand:

- `privacy@conviveaula.com` for privacy questions and exercising rights.
- `hola@conviveaula.com` for everything else, including accessibility barriers.

Both are Cloudflare Email Routing addresses forwarding to the owner's mailbox.
A published address that bounces is worse than no address at all, because a
person trying to exercise a right gets silence rather than a route — so
confirming that both actually receive mail is a release prerequisite, not a
code change.

## Emergency signposting

`apps/web/src/app/public-emergency-resources.ts` holds the official public
numbers shown in the footer of every public page and on the demonstration
notice: 112, 016 and the ANAR helpline 900 20 20 10.

They are listed under their official service names and nothing more. Convive
adds no indication of which one fits which situation. Doing so would present
the project as reviewed official guidance, and reviewed official guidance needs
an accountable content owner that this project does not have.

## Aircury credit

Aircury Summer of Code 2026 requires the project to name and credit Aircury SL.
The credit appears in the footer of every public page and, with its context, on
the demonstration notice. It states that the scholarship funds the work and
explicitly that Aircury SL neither operates the demonstration nor endorses its
content.

## What the notices must never claim

- That the demonstration is an emergency channel, or an official service of
  any education authority, or associated with any administration.
- That a communication is absolutely anonymous. The privacy notice states the
  real limits instead: no account is required, but free text can identify a
  person by what it describes, and ordinary technical records exist.
- Conformance with WCAG 2.2 AA. The manual audit with assistive technology has
  not finished, so the accessibility notice says exactly that. It may only
  claim conformance once issue #167 establishes it.
- Any existing connection, agreement or integration with an educational
  administration system.

## Ownership and review cadence

The repository owner owns this content. Every document carries its reviewer,
its review date and its trigger in the page itself, through the required
`review` field of `PublicInformationContent` — a page cannot be published
without one.

Review every six months, and additionally whenever the scope of the
demonstration, the identity of the publisher or the underlying protocol
changes. A change to any of these is a review trigger even if the six months
have not elapsed.
