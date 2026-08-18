#!/usr/bin/env bash

set -Eeuo pipefail

# This is intentionally a source-only check. It compares the stable table
# identifiers declared in Doctrine, DBML and Mermaid without booting Docker,
# connecting to PostgreSQL or reading application data.
readonly REPOSITORY_ROOT="${CONVIVE_ARCHITECTURE_CHECK_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
readonly DOCTRINE_DIRECTORY="${REPOSITORY_ROOT}/apps/api/src"
readonly DBML_FILE="${REPOSITORY_ROOT}/docs/architecture/data-model.dbml"
readonly MERMAID_FILE="${REPOSITORY_ROOT}/docs/architecture/diagrams/data-model.md"
readonly ARCHITECTURE_README="${REPOSITORY_ROOT}/docs/architecture/README.md"
readonly DIAGRAM_CATALOGUE="${REPOSITORY_ROOT}/docs/architecture/diagrams/README.md"
readonly DATA_MODEL_REVIEW="${REPOSITORY_ROOT}/docs/architecture/data-model-review.md"
readonly TEMPORARY_DIRECTORY=$(mktemp --directory)
trap 'rm --recursive --force "${TEMPORARY_DIRECTORY}"' EXIT

for artefact in \
    "${DOCTRINE_DIRECTORY}" \
    "${DBML_FILE}" \
    "${MERMAID_FILE}" \
    "${ARCHITECTURE_README}" \
    "${DIAGRAM_CATALOGUE}" \
    "${DATA_MODEL_REVIEW}"; do
    if [[ ! -e ${artefact} ]]; then
        echo "Required architecture artefact is missing: ${artefact}" >&2
        exit 1
    fi
done

extract_inventory() {
    local source_name=$1
    local output_file=$2

    case ${source_name} in
        doctrine)
            find "${DOCTRINE_DIRECTORY}" -type f -name '*.php' -exec \
                sed -nE "s/.*#\\[ORM\\\\Table\\(name: '([a-z_]+)'.*/\\1/p" {} + \
                > "${output_file}"
            ;;
        dbml)
            tr --delete '\r' < "${DBML_FILE}" | \
                sed -nE 's/^Table ([a-z_]+) \{$/\1/p' > "${output_file}"
            ;;
        mermaid)
            tr --delete '\r' < "${MERMAID_FILE}" | \
                sed -nE 's/^    ([a-z_]+) \{$/\1/p' > "${output_file}"
            ;;
        *)
            echo "Unsupported architecture inventory source: ${source_name}" >&2
            exit 1
            ;;
    esac
}

normalise_inventory() {
    local source_label=$1
    local raw_inventory=$2
    local normalised_inventory=$3
    local duplicates

    if [[ ! -s ${raw_inventory} ]]; then
        echo "${source_label} did not yield any table identifiers." >&2
        exit 1
    fi

    duplicates=$(LC_ALL=C sort "${raw_inventory}" | uniq --repeated)
    if [[ -n ${duplicates} ]]; then
        echo "${source_label} repeats table identifiers:" >&2
        printf '%s\n' "${duplicates}" >&2
        exit 1
    fi

    LC_ALL=C sort --unique "${raw_inventory}" > "${normalised_inventory}"
}

assert_matching_inventory() {
    local documented_label=$1
    local documented_inventory=$2
    local doctrine_inventory=$3

    if ! diff --unified \
        --label 'Doctrine ORM table inventory' "${doctrine_inventory}" \
        --label "${documented_label} table inventory" "${documented_inventory}"; then
        echo "Reconcile ${documented_label} with Doctrine mappings and committed migrations." >&2
        exit 1
    fi
}

assert_documented_link() {
    local document=$1
    local expected_link=$2

    if ! grep --fixed-strings --quiet -- "${expected_link}" "${document}"; then
        echo "${document} must link to ${expected_link}." >&2
        exit 1
    fi
}

extract_inventory doctrine "${TEMPORARY_DIRECTORY}/doctrine.raw"
extract_inventory dbml "${TEMPORARY_DIRECTORY}/dbml.raw"
extract_inventory mermaid "${TEMPORARY_DIRECTORY}/mermaid.raw"

normalise_inventory 'Doctrine ORM mappings' \
    "${TEMPORARY_DIRECTORY}/doctrine.raw" \
    "${TEMPORARY_DIRECTORY}/doctrine.tables"
normalise_inventory 'docs/architecture/data-model.dbml' \
    "${TEMPORARY_DIRECTORY}/dbml.raw" \
    "${TEMPORARY_DIRECTORY}/dbml.tables"
normalise_inventory 'docs/architecture/diagrams/data-model.md' \
    "${TEMPORARY_DIRECTORY}/mermaid.raw" \
    "${TEMPORARY_DIRECTORY}/mermaid.tables"

assert_matching_inventory 'docs/architecture/data-model.dbml' \
    "${TEMPORARY_DIRECTORY}/dbml.tables" \
    "${TEMPORARY_DIRECTORY}/doctrine.tables"
assert_matching_inventory 'docs/architecture/diagrams/data-model.md' \
    "${TEMPORARY_DIRECTORY}/mermaid.tables" \
    "${TEMPORARY_DIRECTORY}/doctrine.tables"

assert_documented_link "${ARCHITECTURE_README}" \
    '[data model diagram](diagrams/data-model.md)'
assert_documented_link "${ARCHITECTURE_README}" \
    '[`data-model.dbml`](data-model.dbml)'
assert_documented_link "${ARCHITECTURE_README}" \
    '[data-model review](data-model-review.md)'
assert_documented_link "${DIAGRAM_CATALOGUE}" \
    '[Data model](data-model.md)'
assert_documented_link "${DATA_MODEL_REVIEW}" \
    '`check-architecture-documents.sh`'

table_count=$(wc --lines < "${TEMPORARY_DIRECTORY}/doctrine.tables")
echo "Architecture documentation consistency check passed for ${table_count} Doctrine-owned tables."
