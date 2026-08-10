#!/usr/bin/env bash

set -Eeuo pipefail

readonly OUTPUT_DIR=$(mktemp --directory)
trap 'rm --recursive --force "${OUTPUT_DIR}"' EXIT

if CONVIVE_OBSERVABILITY_FIXTURE=api-down \
    CONVIVE_OBSERVABILITY_OUTPUT_DIR="${OUTPUT_DIR}" \
    CONVIVE_RELEASE_ID=fixture-release \
    CONVIVE_PUBLIC_URL=https://demo.invalid \
    infrastructure/observability/check.sh; then
    echo 'The controlled API failure was expected to fail the check.' >&2
    exit 1
fi

test -s "${OUTPUT_DIR}/latest-alert.json"
grep --fixed-strings '"outcome": "failed"' "${OUTPUT_DIR}/latest-alert.json" > /dev/null
if grep --extended-regexp 'report|secret|password|token|fixture-sensitive-marker' "${OUTPUT_DIR}/latest-alert.json" > /dev/null; then
    echo 'The alert evidence contains a forbidden sensitive field.' >&2
    exit 1
fi

CONVIVE_OBSERVABILITY_OUTPUT_DIR="${OUTPUT_DIR}" infrastructure/observability/alert.sh

CONVIVE_OBSERVABILITY_FIXTURE=healthy \
    CONVIVE_OBSERVABILITY_OUTPUT_DIR="${OUTPUT_DIR}" \
    CONVIVE_RELEASE_ID=fixture-release \
    CONVIVE_PUBLIC_URL=https://demo.invalid \
    infrastructure/observability/check.sh

grep --fixed-strings '"outcome": "success"' "${OUTPUT_DIR}/latest-check.json" > /dev/null
test ! -e "${OUTPUT_DIR}/latest-alert.json"
echo 'Controlled observability failure exercise passed.'
