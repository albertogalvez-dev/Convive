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

# api runs as www-data. Source its mounted environment only inside that
# container; never copy a secret to the host environment or timer output.
compose exec -T api /bin/sh -ec \
    'set -a; . /run/secrets/api_env; set +a; exec php bin/console app:attachments:process-pending --env=prod --no-debug --no-interaction --limit=50'
compose exec -T api /bin/sh -ec \
    'set -a; . /run/secrets/api_env; set +a; exec php bin/console app:attachments:clean-expired --env=prod --no-debug --no-interaction --limit=50'
