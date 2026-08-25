#!/usr/bin/env bash

set -Eeuo pipefail

readonly RELEASE_DIR=${1:?release directory is required}
readonly RELEASE_ID=${2:?release identifier is required}
readonly API_IMAGE=${3:?API image digest is required}
readonly GATEWAY_IMAGE=${4:?gateway image digest is required}
readonly MIGRATION_CLASS=${5:-backward-compatible}
readonly RELEASE_PHASE=${6:-prepare}
readonly RUNTIME_DIR=${CONVIVE_RUNTIME_DIR:-/srv/platform/projects/convive}
readonly SECRET_DIR=${CONVIVE_SECRET_DIR:-/srv/platform/secrets/convive}
readonly PUBLIC_URL=${CONVIVE_PUBLIC_URL:?CONVIVE_PUBLIC_URL is required}
readonly COMPOSE_PROJECT=${CONVIVE_COMPOSE_PROJECT:-px-convive}

if [[ ${EUID} -ne 0 ]]; then
    echo 'The Convive release must run as root.' >&2
    exit 1
fi

if [[ ! ${API_IMAGE} =~ ^.+@sha256:[0-9a-f]{64}$ || ! ${GATEWAY_IMAGE} =~ ^.+@sha256:[0-9a-f]{64}$ ]]; then
    echo 'Both release images must use immutable SHA-256 digests.' >&2
    exit 1
fi

case ${MIGRATION_CLASS} in
    none|backward-compatible|incompatible) ;;
    *)
        echo 'Migration class must be none, backward-compatible or incompatible.' >&2
        exit 1
        ;;
esac

case ${RELEASE_PHASE} in
    prepare|verify) ;;
    *)
        echo 'Release phase must be prepare or verify.' >&2
        exit 1
        ;;
esac

readonly COMPOSE_FILE=${RELEASE_DIR}/compose.production.yaml
readonly ENV_FILE=${RELEASE_DIR}/compose.production.env
readonly EVIDENCE_DIR=${CONVIVE_EVIDENCE_DIR:-/var/lib/convive-backup/evidence}
readonly RELEASES_DIR=${RUNTIME_DIR}/releases
readonly CURRENT_ENV=${RELEASES_DIR}/current.env

for required_file in \
    "${COMPOSE_FILE}" \
    "${SECRET_DIR}/api.env" \
    "${SECRET_DIR}/database_password" \
    "${SECRET_DIR}/redis.conf"; do
    if [[ ! -f ${required_file} ]]; then
        echo "Required release file is missing: ${required_file}" >&2
        exit 1
    fi
done

for secret_file in "${SECRET_DIR}/api.env" "${SECRET_DIR}/database_password" "${SECRET_DIR}/redis.conf"; do
    if [[ $(stat -c '%U:%G %a' "${secret_file}") != root:root\ 400 && $(stat -c '%U:%G %a' "${secret_file}") != root:root\ 600 ]]; then
        echo "Secret file must be root-owned with mode 0400 or 0600: ${secret_file}" >&2
        exit 1
    fi
done

if [[ ! -s ${EVIDENCE_DIR}/latest-restore-test.json ]]; then
    echo 'A successful encrypted restore-test evidence file is required before release.' >&2
    exit 1
fi

mkdir --parents "${RELEASES_DIR}"
chmod 0700 "${RELEASES_DIR}"

readonly RELEASE_PATH=${RELEASES_DIR}/${RELEASE_ID}
if [[ ${RELEASE_PHASE} == prepare ]]; then
    if [[ -e ${RELEASE_PATH} ]]; then
        echo "Release already exists: ${RELEASE_ID}" >&2
        exit 1
    fi

    install --directory --owner=root --group=root --mode=0700 "${RELEASE_PATH}"
    install --owner=root --group=root --mode=0600 "${COMPOSE_FILE}" "${RELEASE_PATH}/compose.production.yaml"
    cat > "${RELEASE_PATH}/compose.production.env" <<EOF
CONVIVE_SECRET_DIR=${SECRET_DIR}
CONVIVE_API_IMAGE=${API_IMAGE}
CONVIVE_GATEWAY_IMAGE=${GATEWAY_IMAGE}
EOF
    chmod 0600 "${RELEASE_PATH}/compose.production.env"
elif [[ ! -f ${RELEASE_PATH}/compose.production.env || ! -f ${RELEASE_PATH}/compose.production.yaml ]]; then
    echo "Prepared release files are missing: ${RELEASE_PATH}" >&2
    exit 1
fi

if [[ ${RELEASE_PHASE} == verify ]]; then
    # The verification invocation must attest to precisely the images prepared
    # earlier; otherwise an operator could accidentally verify a different
    # mutable candidate under the same release identifier.
    # shellcheck disable=SC1090
    source "${RELEASE_PATH}/compose.production.env"
    if [[ ${CONVIVE_API_IMAGE} != "${API_IMAGE}" || ${CONVIVE_GATEWAY_IMAGE} != "${GATEWAY_IMAGE}" ]]; then
        echo 'Verification image digests do not match the prepared release.' >&2
        exit 1
    fi
fi

compose() {
    docker compose \
        --project-name "${COMPOSE_PROJECT}" \
        --env-file "${RELEASE_PATH}/compose.production.env" \
        --file "${RELEASE_PATH}/compose.production.yaml" \
        "$@"
}

if [[ ${RELEASE_PHASE} == prepare ]]; then
    compose config --quiet
    compose pull
    compose up --detach database redis
    compose run --rm --no-deps --entrypoint /bin/sh api -c 'set -a; . /run/secrets/api_env; set +a; php bin/console doctrine:migrations:migrate --env=prod --no-debug --no-interaction --allow-no-migration'
    compose up --detach --remove-orphans --wait api gateway clamav
    echo "Convive release ${RELEASE_ID} is healthy inside its project boundary."
    echo 'Install and validate the reviewed platform Caddy route, then run this command again with the verify phase.'
    exit 0
fi

if [[ -f $CURRENT_ENV ]]; then
    readonly PREVIOUS_ENV=$(cat "${CURRENT_ENV}")
else
    readonly PREVIOUS_ENV=''
fi

if ! curl --fail --silent --show-error --retry 10 --retry-delay 2 "${PUBLIC_URL}/api/v1/health" | grep --fixed-strings '{"status":"ok"}' > /dev/null; then
    echo 'Public health smoke test failed.' >&2
    if [[ ${MIGRATION_CLASS} != incompatible && -n ${PREVIOUS_ENV} && -f ${PREVIOUS_ENV} ]]; then
        docker compose --project-name "${COMPOSE_PROJECT}" --env-file "${PREVIOUS_ENV}" --file "$(dirname "${PREVIOUS_ENV}")/compose.production.yaml" up --detach --remove-orphans
    fi
    exit 1
fi

readonly RELEASE_TIME=$(date --utc +%Y-%m-%dT%H:%M:%SZ)
cat > "${RELEASE_PATH}/release-record.json" <<EOF
{
  "release_id": "${RELEASE_ID}",
  "released_at": "${RELEASE_TIME}",
  "api_image": "${API_IMAGE}",
  "gateway_image": "${GATEWAY_IMAGE}",
  "migration_class": "${MIGRATION_CLASS}",
  "backup_evidence": "${EVIDENCE_DIR}/latest-restore-test.json",
  "outcome": "success"
}
EOF
chmod 0600 "${RELEASE_PATH}/release-record.json"
printf '%s\n' "${RELEASE_PATH}/compose.production.env" > "${CURRENT_ENV}"
chmod 0600 "${CURRENT_ENV}"

echo "Convive release ${RELEASE_ID} completed successfully."
