# Architecture Decision Records

This directory contains the Architecture Decision Records (ADRs) for Convive.

An ADR documents a significant technical decision, the alternatives that were considered, the reasons for the selected option and its consequences.

## Status meanings

- **Proposed:** the decision is still being evaluated.
- **Accepted:** the decision has been reviewed and will be applied.
- **Superseded:** a later ADR has replaced the decision.

## Required content

Each ADR should include:

- context and problem;
- decision drivers;
- considered alternatives;
- selected option and rationale;
- positive and negative consequences;
- review triggers.

## Acceptance checklist

Before changing an ADR from **Proposed** to **Accepted**:

1. Read the current product scope, architecture overview and every related ADR.
2. Confirm that the new ADR has one clear decision area and does not silently
   select a concern owned by another ADR.
3. Record its dependencies and keep undecided concerns explicitly deferred.
4. Update the decision index and every affected overview in the same change.
5. Check terminology, repository paths, runtime boundaries and security
   boundaries against the current files.
6. Check relative Markdown links, run `git diff --check` and inspect both the
   working-tree and staged diffs.

**Accepted** means selected and reviewed; it does not mean implemented.

## Decision index

The **Owns** column identifies the subject controlled by each ADR. This prevents
later records from silently selecting the same concern again.

| ADR | Decision | Owns | Status |
| --- | --- | --- | --- |
| [ADR-0001](0001-use-a-monorepository.md) | Use a monorepository | Repository topology | Accepted |
| [ADR-0002](0002-use-a-modular-monolith-for-the-backend.md) | Use a modular monolith for the backend | Backend structure and module boundaries | Accepted |
| [ADR-0003](0003-use-a-separate-web-frontend.md) | Use a separate web frontend | Frontend/backend separation and responsibilities | Accepted |
| [ADR-0004](0004-use-angular-for-the-web-frontend.md) | Use Angular for the web frontend | Frontend framework and initial rendering model | Accepted |
| [ADR-0005](0005-use-docker-compose-for-reproducible-environments.md) | Use Docker Compose for reproducible environments | Environment orchestration | Accepted |
