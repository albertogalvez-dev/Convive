#!/usr/bin/env bash
set -euo pipefail

# Proves check-production-environment.sh both passes on the real repository and
# fails when a required variable is removed.
#
# A check that has never been seen to fail is a check nobody can trust: it is
# indistinguishable from one that always passes.
readonly REPOSITORY_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
readonly CHECK="${REPOSITORY_ROOT}/infrastructure/maintenance/check-production-environment.sh"
readonly FIXTURE=$(mktemp --directory)
trap 'rm --recursive --force "${FIXTURE}"' EXIT

if ! bash "${CHECK}" >/dev/null; then
    echo "The production environment check fails against the real repository." >&2
    exit 1
fi

# A copy of the repository's relevant parts, so the negative case never edits
# tracked files.
mkdir --parents "${FIXTURE}/apps/api/config" \
    "${FIXTURE}/apps/api/src" \
    "${FIXTURE}/infrastructure/production/secrets"
cp --recursive "${REPOSITORY_ROOT}/apps/api/config/." "${FIXTURE}/apps/api/config/"
cp --recursive "${REPOSITORY_ROOT}/apps/api/src/." "${FIXTURE}/apps/api/src/"
cp "${REPOSITORY_ROOT}/infrastructure/production/compose.production.yaml" \
    "${FIXTURE}/infrastructure/production/compose.production.yaml"
cp "${REPOSITORY_ROOT}/infrastructure/production/secrets/api.env.example" \
    "${FIXTURE}/infrastructure/production/secrets/api.env.example"

grep -v '^DEMO_PROFESSIONAL_PASSWORD=' \
    "${FIXTURE}/infrastructure/production/secrets/api.env.example" \
    >"${FIXTURE}/trimmed" &&
    mv "${FIXTURE}/trimmed" "${FIXTURE}/infrastructure/production/secrets/api.env.example"

if CONVIVE_ENVIRONMENT_CHECK_ROOT="${FIXTURE}" bash "${CHECK}" >/dev/null 2>&1; then
    echo "The check passed with DEMO_PROFESSIONAL_PASSWORD removed; it detects nothing." >&2
    exit 1
fi

echo "Production environment check tests passed."
