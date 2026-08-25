#!/usr/bin/env bash

set -euo pipefail

readonly REPOSITORY_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
readonly PRODUCTION_COMPOSE="${REPOSITORY_ROOT}/infrastructure/production/compose.production.yaml"

# A scalar Compose command is tokenised before an explicit list-form shell
# entrypoint receives it. Keep this low-cost source guard alongside the runtime
# Compose check in CI, which executes the service for the definitive proof.
if ! awk '
  /^  attachment-store-init:$/ { in_initializer = 1; next }
  in_initializer && /^  [[:alnum:]_-]+:$/ { exit }
  in_initializer && /^    entrypoint: \["\/bin\/sh", "-ec"\]$/ { shell_entrypoint = 1 }
  in_initializer && /^    command:$/ { command_list = 1; next }
  command_list && /^      - \|$/ { one_script_argument = 1; next }
  one_script_argument && /^        mkdir -p \/attachments$/ { make_directory = 1 }
  one_script_argument && /^        chown 33:33 \/attachments$/ { set_owner = 1 }
  one_script_argument && /^        chmod 0700 \/attachments$/ { set_mode = 1 }
  END {
    exit !(shell_entrypoint && command_list && one_script_argument && make_directory && set_owner && set_mode)
  }
' "${PRODUCTION_COMPOSE}"; then
  echo 'The production attachment initializer must pass its complete setup script as one BusyBox shell argument.' >&2
  exit 1
fi

echo 'Production attachment initializer keeps its complete BusyBox setup script.'
