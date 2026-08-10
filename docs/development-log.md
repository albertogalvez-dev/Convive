# Convive development log

This is a concise record of material repository and operational decisions. It
is reconstructed from Git history, issues, pull requests and recorded
verification evidence; it is not a transcript of every coding session. Dates
use Europe/Madrid project time. Credentials, payment details and report data
are intentionally absent.

## 2026-08-09 — reporter and professional journeys

- The joined anonymous `/seguimiento` entry and opened-report layout were
  iterated and delivered through PR #97, merge `f272388`. The approved result
  keeps the lateral credential/form composition, a two-column open state,
  grouped report metadata, a compact add-information card and a soft red
  header close action. Decorative `Recibida` status was removed.
- Professional authentication, inbox, detail, review and two-sided response
  work landed through PRs #103–#105 and #111–#112. The protected portal keeps
  organisation-scoped permissions and reporter-visible conversation history.
  Browser evidence remained fictional and outside Git.
- ADR-0012 and the single-VPS topology were accepted through PR #102, merge
  `3a7601b`. The design isolates Convive from the existing ProjectX services,
  uses a named outbound Cloudflare Tunnel and keeps production ports private.

## 2026-08-10 — fictional delivery and recovery

- The deterministic fictional demonstration seed was delivered through PR
  #113, merge `7b45547`. It reconciles reserved demo records, preserves visitor
  created fictional reports and requires an explicit reset confirmation for
  destructive reset.
- ADR-0013 and the encrypted backup/recovery implementation were reviewed
  through PR #115. The real off-host exercise created backup `ea12f4be`,
  restored four fictional reports into an isolated database, invalidated
  session/capability state and left all ProjectX containers healthy. Evidence
  is root-only and secret-free on the VPS.
- The controlled release boundary was delivered through PR #116, merge
  `9e3e5a5`, with post-merge CI run `31395177065` green. The manual workflow
  builds immutable images and keeps the VPS deployment behind the
  `convive-demo` environment and operator secrets.
- Observability and incident response were delivered through PR #117, merge
  `cc85d7c`, with post-merge CI run `31396316952` green. Checks are redacted,
  systemd failure publication is versioned and a controlled failure exercise
  passed; no external alert vendor is enabled.
- Supported maintenance through 2027 was delivered through PR #118, merge
  `3a4d52c`, with post-merge CI run `31397610386` green. Ownership, cadence,
  renewal gates, retirement and transfer are documented without inventing
  provider funding or a public hostname.
- Local Compose test isolation was corrected through PR #125, merge
  `0ce31a7`, with post-merge CI run `31399559501` green. PHPUnit now routes
  professional sessions to the same `convive_test` database as Doctrine while
  development sessions remain in `convive`.

## Evidence and change discipline

Every implementation increment uses a focused branch, small English commits,
local checks, a pull request and post-merge CI before its branch is removed.
The authoritative current state is the repository, GitHub issue/PR history and
the live deployment inspection; this log is a navigational handover aid, not a
replacement for those sources.
