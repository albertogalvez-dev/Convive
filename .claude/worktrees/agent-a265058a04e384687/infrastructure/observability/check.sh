#!/usr/bin/env bash

set -Eeuo pipefail

readonly OUTPUT_DIR=${CONVIVE_OBSERVABILITY_OUTPUT_DIR:-/var/lib/convive-observability}
readonly EVIDENCE_DIR=${CONVIVE_EVIDENCE_DIR:-/var/lib/convive-backup/evidence}
readonly COMPOSE_PROJECT=${CONVIVE_COMPOSE_PROJECT:-convive}
readonly PUBLIC_URL=${CONVIVE_PUBLIC_URL:-}
readonly RELEASE_ID=${CONVIVE_RELEASE_ID:-unknown}
readonly MAX_BACKUP_AGE_SECONDS=${CONVIVE_MAX_BACKUP_AGE_SECONDS:-172800}
readonly FIXTURE=${CONVIVE_OBSERVABILITY_FIXTURE:-}

mkdir --parents "${OUTPUT_DIR}"
chmod 0700 "${OUTPUT_DIR}"

readonly DETECTED_AT=$(date --utc +%Y-%m-%dT%H:%M:%SZ)
check_names=()
check_outcomes=()
check_details=()
failed=0

json_escape() {
    printf '%s' "$1" | sed 's/\\/\\\\/g; s/"/\\"/g'
}

record_check() {
    local name=$1
    local outcome=$2
    local detail=$3

    check_names+=("${name}")
    check_outcomes+=("${outcome}")
    check_details+=("${detail}")

    if [[ ${outcome} != pass ]]; then
        failed=1
    fi
}

check_api_health() {
    if [[ ${FIXTURE} == healthy ]]; then
        record_check api_health pass 'fixture healthy'
        return
    fi

    if [[ ${FIXTURE} == api-down ]]; then
        record_check api_health fail 'health endpoint unavailable'
        return
    fi

    if [[ -z ${PUBLIC_URL} ]]; then
        record_check api_health fail 'public URL is not configured'
        return
    fi

    if curl --fail --silent --show-error --max-time 10 "${PUBLIC_URL}/api/v1/health" \
        | grep --fixed-strings '{"status":"ok"}' > /dev/null; then
        record_check api_health pass 'status ok'
    else
        record_check api_health fail 'health endpoint returned an unexpected response'
    fi
}

check_containers() {
    if [[ ${FIXTURE} == containers-down ]]; then
        record_check convive_containers fail 'one or more Convive services are unhealthy'
        return
    fi

    if [[ ${FIXTURE} == healthy ]]; then
        record_check convive_containers pass 'fixture healthy'
        return
    fi

    local services unhealthy
    services=$(docker ps --filter "label=com.docker.compose.project=${COMPOSE_PROJECT}" \
        --format '{{.Names}} {{.Status}}')
    if [[ $(printf '%s\n' "${services}" | sed '/^$/d' | wc --lines) -lt 5 ]]; then
        record_check convive_containers fail 'one or more Convive services are missing'
        return
    fi

    unhealthy=$(printf '%s\n' "${services}" | awk '$0 !~ /Up/ || $0 ~ /unhealthy/ {print $0}')

    if [[ -z ${unhealthy} ]]; then
        record_check convive_containers pass 'all running services report healthy'
    else
        record_check convive_containers fail 'one or more Convive services are unhealthy'
    fi
}

check_disk() {
    if [[ ${FIXTURE} == healthy ]]; then
        record_check disk_capacity pass 'fixture healthy'
        return
    fi

    if [[ ${FIXTURE} == disk-full ]]; then
        record_check disk_capacity fail 'root filesystem exceeds the configured threshold'
        return
    fi

    local used
    used=$(df -P / | awk 'NR == 2 {gsub(/%/, "", $5); print $5}')

    if [[ ${used:-100} -lt 85 ]]; then
        record_check disk_capacity pass 'root filesystem below 85 percent'
    else
        record_check disk_capacity fail 'root filesystem exceeds 85 percent'
    fi
}

check_backup_freshness() {
    local evidence_file="${EVIDENCE_DIR}/latest-restore-test.json"

    if [[ ${FIXTURE} == healthy ]]; then
        record_check backup_freshness pass 'fixture healthy'
        return
    fi

    if [[ ${FIXTURE} == backup-stale ]]; then
        record_check backup_freshness fail 'latest restore evidence is stale or missing'
        return
    fi

    if [[ ! -s ${evidence_file} ]]; then
        record_check backup_freshness fail 'latest restore evidence is stale or missing'
        return
    fi

    local age
    age=$(( $(date +%s) - $(stat -c '%Y' "${evidence_file}") ))
    if [[ ${age} -le ${MAX_BACKUP_AGE_SECONDS} ]]; then
        record_check backup_freshness pass 'latest restore evidence is recent'
    else
        record_check backup_freshness fail 'latest restore evidence is stale or missing'
    fi
}

check_api_health
check_containers
check_disk
check_backup_freshness

if [[ ${failed} -eq 0 ]]; then
    outcome=success
else
    outcome=failed
fi

{
    printf '{\n  "detected_at": "%s",\n  "release_id": "%s",\n  "outcome": "%s",\n  "checks": [' \
        "$(json_escape "${DETECTED_AT}")" \
        "$(json_escape "${RELEASE_ID}")" \
        "${outcome}"

    for index in "${!check_names[@]}"; do
        [[ ${index} -gt 0 ]] && printf ','
        printf '\n    {"name":"%s","outcome":"%s","detail":"%s"}' \
            "$(json_escape "${check_names[index]}")" \
            "$(json_escape "${check_outcomes[index]}")" \
            "$(json_escape "${check_details[index]}")"
    done

    printf '\n  ],\n  "runbook": "docs/operations/incident-response.md"\n}\n'
} > "${OUTPUT_DIR}/latest-check.json"
chmod 0600 "${OUTPUT_DIR}/latest-check.json"

if [[ ${failed} -ne 0 ]]; then
    cp --preserve=mode "${OUTPUT_DIR}/latest-check.json" "${OUTPUT_DIR}/latest-alert.json"
    if [[ -n ${CONVIVE_ALERT_WEBHOOK:-} ]]; then
        curl --fail --silent --show-error --max-time 10 \
            --header 'Content-Type: application/json' \
            --data-binary "@${OUTPUT_DIR}/latest-alert.json" \
            "${CONVIVE_ALERT_WEBHOOK}" > /dev/null || true
    fi
    exit 1
fi

rm --force "${OUTPUT_DIR}/latest-alert.json"
