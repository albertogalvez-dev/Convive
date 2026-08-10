#!/usr/bin/env bash

set -Eeuo pipefail

readonly OUTPUT_DIR=${CONVIVE_OBSERVABILITY_OUTPUT_DIR:-/var/lib/convive-observability}
readonly ALERT_FILE=${OUTPUT_DIR}/latest-alert.json

if [[ ! -s ${ALERT_FILE} ]]; then
    exit 0
fi

if command -v logger > /dev/null 2>&1; then
    logger --tag convive-observability --file "${ALERT_FILE}"
fi

if [[ -n ${CONVIVE_ALERT_WEBHOOK:-} ]]; then
    curl --fail --silent --show-error --max-time 10 \
        --header 'Content-Type: application/json' \
        --data-binary "@${ALERT_FILE}" \
        "${CONVIVE_ALERT_WEBHOOK}" > /dev/null
fi
