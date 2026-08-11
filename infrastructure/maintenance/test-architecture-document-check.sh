#!/usr/bin/env bash

set -Eeuo pipefail

readonly TEST_DIRECTORY=$(mktemp --directory)
readonly REPOSITORY_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
readonly CHECK_COMMAND="${REPOSITORY_ROOT}/infrastructure/maintenance/check-architecture-documents.sh"
trap 'rm --recursive --force "${TEST_DIRECTORY}"' EXIT

create_fixture() {
    local fixture_root=$1

    mkdir --parents \
        "${fixture_root}/apps/api" \
        "${fixture_root}/docs/architecture/diagrams"
    cp --recursive "${REPOSITORY_ROOT}/apps/api/src" "${fixture_root}/apps/api/src"
    cp "${REPOSITORY_ROOT}/docs/architecture/data-model.dbml" \
        "${fixture_root}/docs/architecture/data-model.dbml"
    cp "${REPOSITORY_ROOT}/docs/architecture/README.md" \
        "${fixture_root}/docs/architecture/README.md"
    cp "${REPOSITORY_ROOT}/docs/architecture/data-model-review.md" \
        "${fixture_root}/docs/architecture/data-model-review.md"
    cp "${REPOSITORY_ROOT}/docs/architecture/diagrams/README.md" \
        "${fixture_root}/docs/architecture/diagrams/README.md"
    cp "${REPOSITORY_ROOT}/docs/architecture/diagrams/data-model.md" \
        "${fixture_root}/docs/architecture/diagrams/data-model.md"
}

CONVIVE_ARCHITECTURE_CHECK_ROOT="${REPOSITORY_ROOT}" bash "${CHECK_COMMAND}" > /dev/null

readonly MISSING_TABLE_FIXTURE="${TEST_DIRECTORY}/missing-table"
create_fixture "${MISSING_TABLE_FIXTURE}"
sed --in-place 's/^Table case_tasks {/# Table case_tasks intentionally removed/' \
    "${MISSING_TABLE_FIXTURE}/docs/architecture/data-model.dbml"
if CONVIVE_ARCHITECTURE_CHECK_ROOT="${MISSING_TABLE_FIXTURE}" bash "${CHECK_COMMAND}" \
    > "${TEST_DIRECTORY}/missing-table.output" 2>&1; then
    echo 'The check accepted a DBML table inventory that drifts from Doctrine.' >&2
    exit 1
fi
grep --fixed-strings --quiet -- \
    'docs/architecture/data-model.dbml table inventory' \
    "${TEST_DIRECTORY}/missing-table.output"

readonly MISSING_LINK_FIXTURE="${TEST_DIRECTORY}/missing-link"
create_fixture "${MISSING_LINK_FIXTURE}"
sed --in-place \
    's/\[data-model review\](data-model-review.md)/data-model review/' \
    "${MISSING_LINK_FIXTURE}/docs/architecture/README.md"
if CONVIVE_ARCHITECTURE_CHECK_ROOT="${MISSING_LINK_FIXTURE}" bash "${CHECK_COMMAND}" \
    > "${TEST_DIRECTORY}/missing-link.output" 2>&1; then
    echo 'The check accepted a missing maintained architecture link.' >&2
    exit 1
fi
grep --fixed-strings --quiet -- \
    'must link to [data-model review](data-model-review.md)' \
    "${TEST_DIRECTORY}/missing-link.output"

echo 'Architecture documentation consistency check tests passed.'
