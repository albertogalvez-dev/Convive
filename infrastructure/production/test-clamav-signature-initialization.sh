#!/usr/bin/env bash

set -euo pipefail

readonly compose_file="${1:-infrastructure/production/compose.production.yaml}"

if [[ ! -f $compose_file ]]; then
    echo "Production Compose file not found: ${compose_file}" >&2
    exit 1
fi

clamav_service=$(awk '
    /^  clamav:$/ { capture = 1 }
    capture { print }
    capture && /^volumes:$/ { exit }
' "$compose_file")

if ! grep --fixed-strings --quiet 'cap_add: ["CHOWN"]' <<<"$clamav_service"; then
    echo 'ClamAV must retain CAP_CHOWN to initialise its private signature volume.' >&2
    exit 1
fi

if ! grep --fixed-strings --quiet 'cap_drop: ["ALL"]' <<<"$clamav_service"; then
    echo 'ClamAV must drop every non-essential Linux capability.' >&2
    exit 1
fi
