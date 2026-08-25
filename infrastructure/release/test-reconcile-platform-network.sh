#!/usr/bin/env bash

set -Eeuo pipefail

readonly REPOSITORY_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
readonly TEST_DIRECTORY="$(mktemp -d)"
readonly MOCK_BIN_DIRECTORY="${TEST_DIRECTORY}/bin"
readonly SECRET_DIRECTORY="${TEST_DIRECTORY}/secrets"
readonly EVIDENCE_DIRECTORY="${TEST_DIRECTORY}/evidence"
readonly RUNTIME_DIRECTORY="${TEST_DIRECTORY}/runtime"
readonly RELEASE_DIRECTORY="${TEST_DIRECTORY}/release"

cleanup() {
    rm -rf "${TEST_DIRECTORY}"
}
trap cleanup EXIT

mkdir --parents "${MOCK_BIN_DIRECTORY}" "${SECRET_DIRECTORY}" "${EVIDENCE_DIRECTORY}" "${RELEASE_DIRECTORY}"
printf '%s\n' 'APP_SECRET=test-only' > "${SECRET_DIRECTORY}/api.env"
printf '%s\n' 'database-password' > "${SECRET_DIRECTORY}/database_password"
printf '%s\n' 'requirepass redis-password' > "${SECRET_DIRECTORY}/redis.conf"
chmod 0400 "${SECRET_DIRECTORY}/api.env" "${SECRET_DIRECTORY}/database_password" "${SECRET_DIRECTORY}/redis.conf"
printf '%s\n' '{}' > "${EVIDENCE_DIRECTORY}/latest-restore-test.json"
printf '%s\n' 'services: {}' > "${RELEASE_DIRECTORY}/compose.production.yaml"

cat > "${MOCK_BIN_DIRECTORY}/docker" <<'EOF'
#!/usr/bin/env bash
set -Eeuo pipefail

if [[ $1 == network && $2 == inspect ]]; then
    if [[ $4 == '{{.Internal}}' ]]; then
        printf '%s\n' "${MOCK_NETWORK_INTERNAL:-true}"
    else
        printf '%s\n' "${MOCK_NETWORK_CIDR:-172.26.16.0/28}"
    fi
    exit 0
fi

if [[ $1 == compose ]]; then
    exit 0
fi

echo "Unexpected docker invocation: $*" >&2
exit 1
EOF
chmod 0755 "${MOCK_BIN_DIRECTORY}/docker"

run_prepare() {
    local release_id=$1
    PATH="${MOCK_BIN_DIRECTORY}:${PATH}" \
        CONVIVE_SECRET_DIR="${SECRET_DIRECTORY}" \
        CONVIVE_EVIDENCE_DIR="${EVIDENCE_DIRECTORY}" \
        CONVIVE_RUNTIME_DIR="${RUNTIME_DIRECTORY}" \
        CONVIVE_PUBLIC_URL=https://app.conviveaula.test \
        CONVIVE_COMPOSE_PROJECT=convive-platform-network-test \
        bash "${REPOSITORY_ROOT}/infrastructure/release/reconcile.sh" \
        "${RELEASE_DIRECTORY}" "${release_id}" \
        "example.test/api@sha256:$(printf 'a%.0s' {1..64})" \
        "example.test/gateway@sha256:$(printf 'b%.0s' {1..64})" \
        none prepare
}

if [[ ${EUID} -ne 0 ]]; then
    echo 'This focused release test must run as root.' >&2
    exit 1
fi

run_prepare prepared-network
grep --fixed-strings 'CONVIVE_TRUSTED_PROXIES=172.26.16.0/28' \
    "${RUNTIME_DIRECTORY}/releases/prepared-network/compose.production.env" > /dev/null

if MOCK_NETWORK_INTERNAL=false run_prepare rejected-network; then
    echo 'Expected a non-internal platform network to be rejected.' >&2
    exit 1
fi

echo 'Release reconciliation derives a narrow trusted-proxy CIDR from the platform network.'
