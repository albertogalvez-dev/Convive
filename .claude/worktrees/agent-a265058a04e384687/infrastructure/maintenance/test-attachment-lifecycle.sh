#!/usr/bin/env bash

set -Eeuo pipefail

readonly TEST_DIRECTORY=$(mktemp --directory)
trap 'rm --recursive --force "${TEST_DIRECTORY}"' EXIT

readonly RELEASE_DIRECTORY="${TEST_DIRECTORY}/release"
readonly CURRENT_ENV_FILE="${TEST_DIRECTORY}/current.env"
readonly LOCK_FILE="${TEST_DIRECTORY}/attachment-lifecycle.lock"
readonly DOCKER_LOG="${TEST_DIRECTORY}/docker.log"
readonly MOCK_BIN_DIRECTORY="${TEST_DIRECTORY}/bin"

mkdir --parents "${RELEASE_DIRECTORY}" "${MOCK_BIN_DIRECTORY}"
printf '%s\n' 'CONVIVE_SECRET_DIR=/fixture/secrets' > "${RELEASE_DIRECTORY}/compose.production.env"
printf '%s\n' 'services: {}' > "${RELEASE_DIRECTORY}/compose.production.yaml"
printf '%s\n' "${RELEASE_DIRECTORY}/compose.production.env" > "${CURRENT_ENV_FILE}"

printf '%s\n' \
    '#!/usr/bin/env bash' \
    'printf "%s\\n" "$*" >> "${CONVIVE_TEST_DOCKER_LOG}"' \
    > "${MOCK_BIN_DIRECTORY}/docker"
chmod 0700 "${MOCK_BIN_DIRECTORY}/docker"

PATH="${MOCK_BIN_DIRECTORY}:${PATH}" \
CONVIVE_CURRENT_RELEASE_ENV="${CURRENT_ENV_FILE}" \
CONVIVE_ATTACHMENT_LIFECYCLE_LOCK_FILE="${LOCK_FILE}" \
CONVIVE_TEST_DOCKER_LOG="${DOCKER_LOG}" \
    infrastructure/maintenance/attachment-lifecycle.sh

test "$(wc --lines < "${DOCKER_LOG}")" -eq 2
grep --fixed-strings -- "--env-file ${RELEASE_DIRECTORY}/compose.production.env" "${DOCKER_LOG}" > /dev/null
grep --fixed-strings -- "--file ${RELEASE_DIRECTORY}/compose.production.yaml" "${DOCKER_LOG}" > /dev/null
grep --fixed-strings -- 'app:attachments:process-pending' "${DOCKER_LOG}" > /dev/null
grep --fixed-strings -- 'app:attachments:clean-expired' "${DOCKER_LOG}" > /dev/null

echo 'Attachment lifecycle maintenance command test passed.'
