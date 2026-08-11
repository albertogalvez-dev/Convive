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

stage='attachment-consistency'
backup_output="$(mktemp)"
attachment_output="$(mktemp)"
generation="$(openssl rand -hex 16)"
generation_time="$(date --utc +'%Y-%m-%d %H:%M:%S')"
verification_attachment_volume=""

cleanup() {
  rm -f "$backup_output" "$attachment_output"
  if [[ -n "$verification_attachment_volume" ]]; then
    docker volume rm "$verification_attachment_volume" >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

backup_failed() {
  trap - ERR
  run_restic forget \
    --host "$BACKUP_HOST" \
    --tag "generation-$generation" >/dev/null 2>&1 || true
  record_evidence failure backup "$stage"
}
trap backup_failed ERR

compose_arguments compose_args
source_attachment_volume="$(attachment_volume_name "$CONVIVE_COMPOSE_PROJECT")"
initial_attachment_state="$(
  attachment_consistency_state \
    "$CONVIVE_COMPOSE_PROJECT" \
    "$source_attachment_volume" \
    "${COMPOSE_FILE_LIST[@]}"
)"

stage='database-backup'
docker compose "${compose_args[@]}" exec -T database sh -eu -c \
  'exec pg_dump --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --format=custom --no-owner --no-acl' \
  | run_restic backup \
      --stdin \
      --stdin-filename convive.dump \
      --host "$BACKUP_HOST" \
      --tag automated \
      --tag database \
      --tag "revision-${CONVIVE_RELEASE_REVISION}" \
      --tag "generation-$generation" \
      --time "$generation_time" \
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

stage='attachment-consistency'
database_attachment_state="$(
  attachment_consistency_state \
    "$CONVIVE_COMPOSE_PROJECT" \
    "$source_attachment_volume" \
    "${COMPOSE_FILE_LIST[@]}"
)"

if [[ "$initial_attachment_state" != "$database_attachment_state" ]]; then
  echo 'Private attachment metadata changed while the database snapshot was created.' >&2
  false
fi

stage='attachment-backup'
run_restic_with_attachment_volume "$source_attachment_volume" readonly backup \
  /attachments \
  --host "$BACKUP_HOST" \
  --tag automated \
  --tag attachments \
  --tag "revision-${CONVIVE_RELEASE_REVISION}" \
  --tag "generation-$generation" \
  --time "$generation_time" \
  --json > "$attachment_output"

attachment_snapshot_id_value="$(python3 - "$attachment_output" <<'PYTHON'
import json
import sys

snapshot_id = ""
with open(sys.argv[1], encoding="utf-8") as stream:
    for line in stream:
        event = json.loads(line)
        snapshot_id = event.get("snapshot_id", snapshot_id)
if not snapshot_id:
    raise SystemExit("restic did not report an attachment snapshot identifier")
print(snapshot_id)
PYTHON
)"

stage='attachment-consistency'
final_attachment_manifest="$(
  database_attachment_manifest \
    "$CONVIVE_COMPOSE_PROJECT" \
    "${COMPOSE_FILE_LIST[@]}"
)"
final_attachment_state="$(
  attachment_manifest_state \
    "$source_attachment_volume" \
    "$final_attachment_manifest"
)"

if [[ "$database_attachment_state" != "$final_attachment_state" ]]; then
  echo 'Private attachment metadata changed while its object snapshot was created.' >&2
  false
fi

stage='attachment-snapshot-verification'
verification_attachment_volume="convive-backup-verify-$generation"
docker volume create \
  --label 'com.convive.backup-purpose=attachment-verification' \
  "$verification_attachment_volume" >/dev/null
run_restic_with_attachment_volume "$verification_attachment_volume" readwrite restore \
  "$attachment_snapshot_id_value" \
  --target / \
  --verify >/dev/null
verified_snapshot_state="$(
  attachment_manifest_state \
    "$verification_attachment_volume" \
    "$final_attachment_manifest"
)"

if [[ "$final_attachment_state" != "$verified_snapshot_state" ]]; then
  echo 'The private attachment snapshot does not match its database metadata.' >&2
  false
fi

docker volume rm "$verification_attachment_volume" >/dev/null
verification_attachment_volume=""

stage='generation-publication'
run_restic tag --add complete "$snapshot_id" "$attachment_snapshot_id_value" >/dev/null

stage='repository-check'
run_restic check --read-data-subset="${CONVIVE_RESTIC_CHECK_SUBSET:-5%}" >/dev/null

stage='retention'
run_restic forget \
  --host "$BACKUP_HOST" \
  --tag automated,complete \
  --keep-daily "${CONVIVE_BACKUP_KEEP_DAILY:-14}" \
  --keep-weekly "${CONVIVE_BACKUP_KEEP_WEEKLY:-8}" \
  --keep-monthly "${CONVIVE_BACKUP_KEEP_MONTHLY:-12}" \
  --prune >/dev/null

trap - ERR
record_evidence success backup "generation=${generation:0:12}"
echo "Convive backup generation completed successfully: ${generation:0:8}."
