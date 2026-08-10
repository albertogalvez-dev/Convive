#!/usr/bin/env bash

set -Eeuo pipefail

readonly SCRIPT_DIRECTORY="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIRECTORY/common.sh"

validate_configuration

readonly LOCK_FILE="${CONVIVE_BACKUP_LOCK_FILE:-/run/lock/convive-backup.lock}"
exec 9> "$LOCK_FILE"

if ! flock -n 9; then
  echo 'Another Convive backup operation is already running.' >&2
  exit 1
fi

stage='database-backup'
backup_output="$(mktemp)"
trap 'rm -f "$backup_output"' EXIT
trap 'record_evidence failure backup "$stage"' ERR

compose_arguments compose_args

docker compose "${compose_args[@]}" exec -T database sh -eu -c \
  'exec pg_dump --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --format=custom --no-owner --no-acl' \
  | run_restic backup \
      --stdin \
      --stdin-filename convive.dump \
      --host "$BACKUP_HOST" \
      --tag automated \
      --tag database \
      --tag "revision-${CONVIVE_RELEASE_REVISION}" \
      --json > "$backup_output"

snapshot_id="$(python3 - "$backup_output" <<'PYTHON'
import json
import sys

snapshot_id = ""
with open(sys.argv[1], encoding="utf-8") as stream:
    for line in stream:
        event = json.loads(line)
        snapshot_id = event.get("snapshot_id", snapshot_id)
if not snapshot_id:
    raise SystemExit("restic did not report a snapshot identifier")
print(snapshot_id)
PYTHON
)"

stage='repository-check'
run_restic check --read-data-subset="${CONVIVE_RESTIC_CHECK_SUBSET:-5%}" >/dev/null

stage='retention'
run_restic forget \
  --host "$BACKUP_HOST" \
  --tag automated \
  --keep-daily "${CONVIVE_BACKUP_KEEP_DAILY:-14}" \
  --keep-weekly "${CONVIVE_BACKUP_KEEP_WEEKLY:-8}" \
  --keep-monthly "${CONVIVE_BACKUP_KEEP_MONTHLY:-12}" \
  --prune >/dev/null

trap - ERR
record_evidence success backup "$snapshot_id"
echo "Convive backup completed successfully: ${snapshot_id:0:8}."
