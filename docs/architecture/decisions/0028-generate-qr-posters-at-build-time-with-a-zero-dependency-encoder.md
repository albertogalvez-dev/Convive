# ADR-0028: Generate QR posters at build time with a zero-dependency encoder

- **Status:** Accepted
- **Date:** 18 August 2026
- **Related issues:** [#329](https://github.com/albertogalvez-dev/Convive/issues/329)
- **Depends on:** [ADR-0009](0009-use-public-organisation-reporting-links.md)

## Context

ADR-0009 established the public per-school reporting link and anticipated that
a school would display it as a QR code on a wall. Nothing printable exists
yet, and #329 opened the work.

Producing a QR code requires an encoder. That is the first genuinely new
third-party dependency this product has needed for a while, in a codebase
whose frontend runs on nine runtime dependencies, so it is worth deciding
deliberately rather than reaching for the first result.

The failure mode shapes everything. A wrong QR code is not a build error or a
failing test. It is a poster in a corridor that does not scan, discovered by a
student who was standing in front of it because something was happening to
them — if it is discovered at all, since the likelier outcome is that they walk
away and nobody ever learns the poster was broken.

## Decision drivers

- **A silent failure reaches a child.** Whatever produces the code must be
  verifiable before printing, not trusted.
- **Supply-chain surface matters here more than convenience.** This is a
  safeguarding product. Every transitive package is something that could
  change under us.
- **Permanently free, per the standing constraint on this project.** A
  licence that could become paid is disqualifying regardless of quality.
- **Nothing about QR generation belongs in the running application.** A poster
  is produced once and printed. Shipping an encoder to every visitor's browser
  would be paying a cost forever for something used occasionally.

## Options considered

### Write the encoder by hand

Zero dependencies, complete control. Rejected.

QR encoding is Reed-Solomon error correction, data masking with eight
candidate patterns and a penalty score to choose between them, plus version
and format-information bit sequences. All of it is specified and all of it is
easy to get *subtly* wrong — a mask chosen poorly still produces a valid-looking
code that scans badly on a phone at an angle in bad corridor light.

The temptation is that it feels safer because it adds nothing. It is not
safer. It moves the risk from a dependency many people have exercised to code
one person wrote once, for the exact artefact where failure is silent.

### `qrcode` (MIT, 1.5.4)

The most widely used option. It pulls **three transitive dependencies**:
`pngjs`, `dijkstrajs`, and `yargs` at major version 15 — a CLI argument parser,
several majors behind, present only to support a command-line entry point this
project would not use.

Rejected on surface area. Accepting a stale CLI parser into a safeguarding
product's tree, to draw a square grid, is a poor trade.

### `qrcode-generator` (MIT, 2.0.4) — chosen

**Zero dependencies.** MIT. Long-established, and it emits SVG path data
directly, which is what an SVG poster template needs — no raster step, no
resolution decision, and the QR scales to whatever paper size is printed.

## Decision

Use **`qrcode-generator`** as a **devDependency**, invoked by a build-time
script that renders per-school poster SVGs. It is never imported by the
application and never shipped to a browser.

Posters are generated from a school's `publicReportingIdentifier`, so the QR
and the printed URL derive from **one value** and cannot disagree.

**Every generated poster is verified by decoding its own QR** and asserting the
result equals the expected URL exactly. Generation is not trusted because the
library is reputable; it is checked because the failure is silent and the
person who would notice is a child in difficulty.

## Consequences

### Accepted

- One new devDependency, no transitive packages, no runtime cost.
- Posters are reproducible: same identifier, same output, so a reprint cannot
  quietly differ from what is on the wall.
- If `qrcode-generator` were ever abandoned, replacing it touches one
  build-time script. Nothing in the application depends on it.

### Costs

- A dependency is a dependency. It is pinned and covered by the existing
  dependency-review workflow.
- Poster generation becomes a build step someone must know to run. ADR-0009
  already requires reprinting when an identifier is rotated, so this is
  documented alongside rotation rather than as a separate habit.

### Deliberately not decided here

**Whether a poster names its school.** Naming it helps a student confirm they
are in the right place; it also means a photograph of the poster identifies the
school. That is a product decision, and it is recorded on #329 rather than
settled by whoever writes the template. The generator takes it as an option so
the decision stays reversible.

## Alternatives for the future

If posters ever need to be produced by schools themselves rather than
centrally, this build-time approach does not serve that — it would need a
generated download, and that is a different decision with different privacy
consequences. Recorded so a later reader knows the boundary was seen rather
than missed.
