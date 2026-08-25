#!/usr/bin/env bash

set -Eeuo pipefail

# The current release pointer is written atomically by reconcile.sh only after
# its migration and health gates succeed. Reusing its exact Compose env file
# prevents a maintenance run from targeting mutable tags or another project.
readonly CURRENT_ENV_FILE=${CONVIVE_CURRENT_RELEASE_ENV:-/srv/convive/releases/current.env}
readonly COMPOSE_PROJECT=${CONVIVE_COMPOSE_PROJECT:-convive}
readonly LOCK_FILE=${CONVIVE_ATTACHMENT_LIFECYCLE_LOCK_FILE:-/run/lock/convive-attachment-lifecycle.lock}

if [[ ! -s ${CURRENT_ENV_FILE} ]]; then
    echo "The current Convive release environment pointer is missing: ${CURRENT_ENV_FILE}" >&2
    exit 1
fi

readonly ENV_FILE=$(<"${CURRENT_ENV_FILE}")
readonly COMPOSE_FILE="$(dirname "${ENV_FILE}")/compose.production.yaml"

if [[ ! -f ${ENV_FILE} || ! -f ${COMPOSE_FILE} ]]; then
    echo 'The current Convive release does not contain a usable Compose configuration.' >&2
    exit 1
fi

exec 9>"${LOCK_FILE}"

if ! flock --nonblock 9; then
    echo 'Another Convive attachment lifecycle run is already active.' >&2
    exit 0
fi

compose() {
    docker compose \
        --project-name "${COMPOSE_PROJECT}" \
        --env-file "${ENV_FILE}" \
        --file "${COMPOSE_FILE}" \
        "$@"
}

# Docker Compose reads the root-only api.env file on the host and injects it
# into this unprivileged API container. Keep the secret out of the host shell,
# command line and timer output; the application already has its own runtime
# environment and must not source a second bind-mounted copy.
compose exec -T api php bin/console app:attachments:process-pending --env=prod --no-debug --no-interaction --limit=50
compose exec -T api php bin/console app:attachments:clean-expired --env=prod --no-debug --no-interaction --limit=50
