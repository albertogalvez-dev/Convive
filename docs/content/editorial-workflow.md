# Public editorial workflow

## Purpose and boundary

The public Convive blog explains product decisions and general educational
context. It supports discovery of the fictional-data demonstration; it is not
an operational reporting channel, legal advice, clinical advice or emergency
guidance. Publication must never encourage people to submit personal,
safeguarding or incident information through the public website.

Published articles are versioned in
`apps/web/src/app/blog/blog-content.ts`. This keeps the rendered text, its
publication metadata and its public source links reviewable in the same pull
request as the route and structured metadata.

## Authoring and review

1. Start with a specific reader question and state only what the source and
   the product implementation support.
2. Record every factual external source as a direct, stable URL. Prefer
   primary public bodies and maintain the source title visible on the article.
3. Include a concise limitation when a subject could be mistaken for legal,
   clinical, safeguarding or emergency instruction.
4. In the pull request, an editorial reviewer verifies accuracy, scope,
   Spanish clarity, source links, date metadata, accessibility and that no
   personal or operational contact data has been introduced.
5. A maintainer publishes only after the normal repository review and CI
   checks have passed. Corrections are made in a new commit and retain the
   article's visible update date.

## Review cadence and withdrawal

Review each article at least every twelve months and earlier when a cited
source, the product behaviour or the legal/safeguarding context changes. A
source that disappears, becomes unreliable or no longer supports the article
requires prompt correction or withdrawal. Do not silently replace a source
with a weaker one.

## Search and crawler controls

Only the public host `conviveaula.com` is listed in the static sitemap. Its
`robots.txt` permits public information routes and excludes operational report,
follow-up, email-verification and professional paths. Article pages set their
canonical URL and social metadata from the same catalog entry; unavailable
article slugs are `noindex` and canonicalise to the blog index.

This is source-controlled technical SEO for the demonstration. It does not
prove that a production domain, DNS, crawler account or search-console setup
has been activated.
