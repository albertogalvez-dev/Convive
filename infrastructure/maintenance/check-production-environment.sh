#!/usr/bin/env bash
set -euo pipefail

# Every environment variable the API reads must be provided by the production
# topology, not by a sentence in a runbook.
#
# The failure this prevents is quiet and badly timed: an operator copies
# api.env.example, deploys a correct-looking release, and discovers a missing
# variable from a command that refuses to run — after the release is live.
#
# Source-only. No Docker, no database, no network.
readonly REPOSITORY_ROOT="${CONVIVE_ENVIRONMENT_CHECK_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
readonly API_SOURCE="${REPOSITORY_ROOT}/apps/api"
readonly COMPOSE_FILE="${REPOSITORY_ROOT}/infrastructure/production/compose.production.yaml"
readonly SECRET_TEMPLATE="${REPOSITORY_ROOT}/infrastructure/production/secrets/api.env.example"

for artefact in "${API_SOURCE}" "${COMPOSE_FILE}" "${SECRET_TEMPLATE}"; do
    if [[ ! -e ${artefact} ]]; then
        echo "Required artefact is missing: ${artefact}" >&2
        exit 1
    fi
done

# Variables Symfony resolves itself, or that belong to development and test
# tooling rather than to a production deployment.
readonly IGNORED='^(APP_ENV|APP_DEBUG|SYMFONY_[A-Z_]+|TEST_TOKEN|[A-Z_]+_TEST)$'

required=$(
    grep -rhoE "%env\(([a-zA-Z]+:)*[A-Z_]{4,}\)%|env\('[A-Z_]{4,}'\)" \
        "${API_SOURCE}/config" "${API_SOURCE}/src" 2>/dev/null |
        grep -oE '[A-Z_]{4,}' |
        sort -u |
        grep -vE "${IGNORED}" || true
)

# A variable declared with a default (%env(default::NAME)%) is optional by
# construction: the application states its own fallback.
optional=$(
    grep -rhoE "%env\(default::[A-Z_]{4,}\)%" \
        "${API_SOURCE}/config" "${API_SOURCE}/src" 2>/dev/null |
        grep -oE '[A-Z_]{4,}' |
        grep -v '^default$' |
        sort -u || true
)

missing=()
while read -r variable; do
    [[ -z ${variable} ]] && continue
    if grep -qxF "${variable}" <<<"${optional}"; then
        continue
    fi
    if grep -qE "^[[:space:]]*${variable}:" "${COMPOSE_FILE}"; then
        continue
    fi
    if grep -qE "^${variable}=" "${SECRET_TEMPLATE}"; then
        continue
    fi
    missing+=("${variable}")
done <<<"${required}"

if ((${#missing[@]} > 0)); then
    echo "Environment variables the API reads but production provides nowhere:" >&2
    printf '  %s\n' "${missing[@]}" >&2
    echo >&2
    echo "Add each to compose.production.yaml if it is configuration, or to" >&2
    echo "secrets/api.env.example if it is a secret. A runbook sentence is not" >&2
    echo "enough: the operator copies the template, not the prose." >&2
    exit 1
fi

echo "Production environment check passed: every required variable is provided."
