#!/usr/bin/env bash

set -euo pipefail

readonly compose_file="${1:-infrastructure/production/compose.production.yaml}"

if [[ ! -f $compose_file ]]; then
    echo "Production Compose file not found: ${compose_file}" >&2
    exit 1
fi

service_definition() {
    local service_name="$1"

    awk -v service_name="$service_name" '
        $0 == "  " service_name ":" { capture = 1; next }
        capture && /^  [a-z-]+:$/ { exit }
        capture { print }
    ' "$compose_file"
}

assert_capabilities() {
    local service_name="$1"
    local expected_capabilities="$2"
    local service
    service=$(service_definition "$service_name")

    if [[ $(grep --fixed-strings --count 'cap_add:' <<<"$service") -ne 1 ]]; then
        echo "${service_name} must declare one explicit minimal cap_add set." >&2
        exit 1
    fi

    if ! grep --fixed-strings --quiet "cap_add: ${expected_capabilities}" <<<"$service"; then
        echo "${service_name} must retain exactly ${expected_capabilities}." >&2
        exit 1
    fi

    if ! grep --fixed-strings --quiet 'cap_drop: ["ALL"]' <<<"$service"; then
        echo "${service_name} must drop every non-essential Linux capability." >&2
        exit 1
    fi
}

# Caddy's upstream binary has a NET_BIND_SERVICE file capability. Docker must
# keep it in the bounding set for the binary to execute with no-new-privileges.
assert_capabilities gateway '["NET_BIND_SERVICE"]'

# The upstream ClamAV entrypoint creates/chowns its private runtime and
# signature directories. clamd opens its configured log before it transitions
# to `clamav`, which requires the narrowly scoped DAC override capability.
assert_capabilities clamav '["CHOWN", "DAC_OVERRIDE", "FOWNER", "SETGID", "SETUID"]'
