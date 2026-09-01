# Technical report delivery copies

This directory contains the final A4 delivery copies of Convive's technical
report in Spanish and English. Both editions contain 14 pages and describe the
same fictional-demonstration boundary.

The PDFs were audited against repository revision
`8dc1b12e0afacc2859931fab5d97eaffce8b4f9b`. Their source companions are
[`technical-report-es.md`](../technical-report-es.md) and
[`technical-report-en.md`](../technical-report-en.md). The repository,
executable system, migrations and OpenAPI contract remain authoritative when a
later change diverges from these delivery copies.

Before replacing either PDF:

1. Reconcile every technical claim and source link against one exact revision.
2. Render and inspect all 28 pages across both editions.
3. Verify page count, text extraction, bookmarks, internal index navigation,
   external links, language metadata and the absence of placeholder copy.
4. Update [`manifest.json`](manifest.json) with the exact SHA-256 hashes.

Recommended filenames when sharing the documents outside Git are
`Convive_Memoria_Tecnica_2026-2027_ES.pdf` and
`Convive_Technical_Report_2026-2027_EN.pdf`.
