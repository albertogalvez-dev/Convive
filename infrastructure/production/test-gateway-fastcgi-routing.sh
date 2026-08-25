#!/usr/bin/env bash

set -Eeuo pipefail

readonly REPOSITORY_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
readonly GATEWAY_IMAGE=convive-production-gateway-fastcgi-check:ci

grep --fixed-strings 'php_fastcgi api:9000' \
    "${REPOSITORY_ROOT}/infrastructure/production/Caddyfile" > /dev/null
grep --fixed-strings 'root * /app/public' \
    "${REPOSITORY_ROOT}/infrastructure/production/Caddyfile" > /dev/null
grep --fixed-strings 'COPY apps/api/public /app/public' \
    "${REPOSITORY_ROOT}/infrastructure/production/gateway.Dockerfile" > /dev/null

docker build \
    --file "${REPOSITORY_ROOT}/infrastructure/production/gateway.Dockerfile" \
    --tag "${GATEWAY_IMAGE}" \
    "${REPOSITORY_ROOT}"
docker run --rm --entrypoint sh "${GATEWAY_IMAGE}" -ec '
    test -f /app/public/index.php
    test -f /srv/web/index.csr.html
'

echo 'Production gateway resolves FastCGI from its API front-controller root.'
