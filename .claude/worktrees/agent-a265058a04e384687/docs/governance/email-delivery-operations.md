# Real-email delivery and notification operations

**Status: unapproved draft. Outbound email is disabled and stays disabled. No
provider is selected, no sender identity exists, and nothing here authorises
sending anything to a real recipient.**

**Prepared by:** the repository maintainer, as preparatory material only.
**Last updated:** 16 August 2026.
**Review trigger:** before any provider is evaluated, and whenever the delivery
design or its governance dependencies change.

Read [the directory README](README.md) first. This design depends on
[the controller decisions](controller-and-processing-decisions.md),
[the impact assessment](dpia-and-safeguarding-assessment.md),
[vendor governance](vendor-and-access-governance.md) and
[retention](retention-deletion-and-rights.md). It is a design, not an
authorisation, and implementing any of it before those are settled would invert
the order this family exists to protect.

## The position already decided

Two product decisions are recorded on #190 and are not reopened here.

**Email may be considered solely as a neutral update notice.** It may say that
an update is available through the secure channel and nothing else. It carries
no case content, no sensitive detail, no name and no indication of what the
matter concerns. It is never an emergency channel and never a guarantee that
anyone is attending to anything.

**The public fictional sandbox sends no real email.** Whatever is designed here
applies to a separately authorised real-data pilot, never to the public
demonstration.

## What exists today, as fact

| Aspect | Current state |
|---|---|
| Production transport | `MAILER_DSN` is `null://null`; `REPORTER_EMAIL_ENABLED=0` |
| Development transport | Pinned Mailpit container, SMTP confined to the Compose backend network, interface bound to `127.0.0.1:8025`, no internet relay |
| Design of record | [ADR-0015](../architecture/decisions/0015-use-verified-email-only-for-generic-reporter-notifications.md): reporter email is optional, verified, and used only for generic report-update notifications |
| Verification token handling | Delivered in the URL fragment, removed by the browser and submitted in a JSON body, so it never reaches an HTTP URL or a server log |
| Delivery worker | Resident process claiming jobs with `FOR UPDATE SKIP LOCKED`; at most three attempts at 1, 5 and 15 minutes; a failed send never affects the report response transaction |
| Log content | Internal delivery UUID, generic kind, attempt and exception class only — never an address, never message content |
| Retention | Pending contacts removed after 24 hours, completed delivery evidence after 30 days, removal through the reporter API cascades queued work immediately |
| Restore behaviour | A restore deletes every reporter contact and notification job before the restored application starts, so a backup cannot issue stale mail or revive a verification token |

This is a considered design already carrying the properties that make email
survivable. What it lacks is a governance decision and a provider.

## Decisions required

### E-01 Whether real email happens at all

**[DECISION REQUIRED]** Whether the pilot needs email, given that the reporter
journey is fully usable without it by design.

Asked first and deliberately. Every decision below exists only if the answer is
yes, and the cheapest way to avoid the risks in this document is not to take
them.

### E-02 Provider and processing configuration

**[DECISION REQUIRED]** Which provider, in which region, under what processor
arrangement.

**The free-tier problem, stated plainly.** The project runs on free tiers as a
hard constraint. Transactional email providers' free tiers typically come with
standard terms, no negotiated processing agreement, and a right to inspect
message content for abuse detection. For a notice that contains nothing but
"there is an update", the content exposure is genuinely minimal — but the
recipient address is still personal data of a child or their family, and it
still sits with a processor nobody appointed. See V-02.

### E-03 Lawful basis for holding an address

**[DECISION REQUIRED]** The basis for collecting and holding a reporter's
address, and how it interacts with the deliberate design property that no
account is required.

**The tension to resolve rather than paper over:** the channel's value is that a
person can report without identifying themselves. An email address is an
identifier. ADR-0015 already requires explicit, provable consent before linking
a mailbox to a report, which is a strong engineering answer; whether consent is
the right lawful basis for a child in a school context is a controller question
and may not be.

### E-04 Sender identity and domain authentication

**[DECISION REQUIRED]** The sending identity and its SPF, DKIM and DMARC
position.

**Factual input:** `conviveaula.com` currently carries Cloudflare Email Routing
records for inbound forwarding of the public contact addresses. Those exist for
receiving public correspondence. Reusing that domain for outbound notices is a
decision with deliverability and reputation consequences, and the public
mailboxes must not become a route for report content either way.

### E-05 Bounce, complaint and suppression handling

**[DECISION REQUIRED]** What happens on a hard bounce, a soft bounce and a spam
complaint, how a suppression list is maintained, and how long it is kept.

**The safeguarding angle:** a suppression list is a record that a particular
address was once linked to a report. That is arguably more sensitive than the
notice itself, and it is the kind of by-product that gets built without anyone
deciding it should exist.

### E-06 Delivery failure, both operator and user behaviour

**[DECISION REQUIRED]** What the operator does and what the reporter sees when
delivery fails.

**Constraint that must survive any answer:** the reporter journey stays fully
usable without email. A failure is a missed convenience, never a lost report and
never a lost route back to a conversation.

### E-07 Unsubscribe, removal and rights

**[DECISION REQUIRED]** How removal is offered and how it interacts with the
rights procedure in R-06.

**Factual input:** removal through the reporter API already cascades queued and
retrying work immediately, so removal means removal rather than a flag.

### E-08 Monitoring and its own privacy cost

**[DECISION REQUIRED]** What delivery monitoring exists, who sees it, and what
it retains.

**Factual input:** logs today contain no address and no content by design. Any
monitoring added must not quietly reverse that.

### E-09 Test boundary for a supported provider

**[DECISION REQUIRED]** How the provider boundary is exercised end to end
without ever sending to an arbitrary real recipient.

Reserved `.example` addresses and a contained transport are the current
practice, and whatever is adopted must keep that guarantee at the same strength.

## What stays true until these are answered

- `MAILER_DSN` stays `null://null` in production and `REPORTER_EMAIL_ENABLED`
  stays `0`.
- No provider credential is created, configured or stored.
- The public demonstration sends no email of any kind.
- The public contact mailboxes carry public correspondence only.
- No email ever contains report content, a name, a case reference or any
  indication of what a matter concerns.
