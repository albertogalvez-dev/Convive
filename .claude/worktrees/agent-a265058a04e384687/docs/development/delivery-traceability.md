# Delivery traceability

Every delivery keeps one evidence chain: issue, focused branch, pull request,
verification, merge and issue closure. This makes a completed checkbox a
reviewable claim rather than a recollection of a past change.

## New work

1. Define observable acceptance criteria in the issue before implementation.
   Bug and feature forms require the same field; roadmap issues must use their
   explicit acceptance section.
2. Create a focused branch named after the issue, for example
   `164-restore-issue-pr-acceptance-traceability`. Keep unrelated changes out
   of its pull request.
3. Use the pull-request template. It requires a closing issue reference, each
   relevant acceptance criterion with specific evidence, security/privacy
   impact, verification notes and explicit out-of-scope boundary.
4. The `PR traceability` check rejects a new pull request that omits the
   closing issue reference or its required evidence sections. It deliberately
   does not infer that a checkbox is true: reviewers inspect the linked
   command, CI run, migration or manual evidence.
5. Before merge, record the actual relevant local checks and wait for all six
   required pull-request checks: `Backend`, `Frontend`, `Infrastructure`,
   `Dependency review`, `Encrypted recovery` and `End-to-end`. `Dependency
   review` is intentionally pull-request-only; it is skipped on a post-merge
   push to `main`.
6. Close the issue only after its linked pull request is merged, acceptance
   boxes have linked evidence and the post-merge run has been inspected. Update
   roadmap #71 when that issue is indexed there.

Documentation-only or narrowly scoped changes do not need invented database,
frontend or end-to-end commands. State why an unrelated local check does not
apply, run the focused inspection instead, and still wait for the required
GitHub checks. Never replace missing evidence with a checked box.

## Evidence-backed historical backfills

Historical acceptance boxes may be corrected only when a reviewer can verify
the exact issue, merged pull request, merge commit and CI run. Add a clearly
labelled `## Evidence-backed backfill` section to the issue with those links
and identify any check intentionally skipped because it ran after merge.

Do not backfill from commit messages, remembered behaviour, an unlinked
screenshot or a green check from a different revision. If evidence cannot be
verified, leave the box unchecked and open a focused follow-up instead of
rewriting history.
