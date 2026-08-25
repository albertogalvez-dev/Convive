#!/usr/bin/env bash

set -euo pipefail

readonly caddyfile="${1:-infrastructure/production/Caddyfile}"

if [[ ! -f ${caddyfile} ]]; then
    echo "Production gateway Caddyfile not found: ${caddyfile}" >&2
    exit 1
fi

if ! grep --fixed-strings --quiet 'try_files {path} /index.csr.html' "${caddyfile}"; then
    echo 'The production gateway must fall back to Angular’s emitted browser entry point.' >&2
    exit 1
fi

echo 'Production gateway routes browser paths through the Angular CSR entry point.'
