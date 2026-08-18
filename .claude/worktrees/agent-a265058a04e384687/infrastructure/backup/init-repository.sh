#!/usr/bin/env bash

set -Eeuo pipefail

readonly SCRIPT_DIRECTORY="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIRECTORY/common.sh"

validate_configuration

if run_restic snapshots --json >/dev/null 2>&1; then
  echo 'The encrypted backup repository is already initialised.'
  exit 0
fi

run_restic init >/dev/null
echo 'The encrypted backup repository was initialised without printing credentials.'
