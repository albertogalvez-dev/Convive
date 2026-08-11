## Summary

<!-- What changed and why. Put the most important point first. -->

## Issue and acceptance criteria

<!-- Link one delivery issue. Copy every relevant acceptance criterion from it,
then replace each placeholder with concise, linked evidence before merge. Do
not check a criterion merely because a similarly named test exists. -->

Closes #

- [ ] Criterion: <!-- exact issue criterion -->
  Evidence: <!-- command, test, review result or CI URL -->

## Security and privacy

<!-- State the relevant effect on anonymity, credentials, secrets, personal
data, access control, deployment or supply chain. Write "Not applicable" only
after considering those boundaries. -->

## Verification evidence

<!-- Check only commands and checks actually run. For a narrowly scoped or
documentation-only change, mark unrelated local checks as not applicable and
say why. Every pull request still waits for the required GitHub checks below. -->

### Local

- [ ] Relevant focused test or inspection: <!-- command and result -->
- [ ] Static analysis, format, build, OpenAPI or migration checks: <!-- applicable commands and results -->

### Required GitHub checks

- [ ] `Backend`
- [ ] `Frontend`
- [ ] `Infrastructure`
- [ ] `Dependency review` (pull-request event only)
- [ ] `Encrypted recovery`
- [ ] `End-to-end`

<!-- Link the completed CI run and record any intentionally skipped
push-only check after merge. Do not mark a required PR check complete without
its actual result. -->

CI run: <!-- URL -->

## Out of scope

<!-- What this PR deliberately does not do, so reviewers do not infer it. -->
