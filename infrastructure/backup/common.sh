#!/usr/bin/env bash

set -Eeuo pipefail

readonly RESTIC_IMAGE='restic/restic:0.19.1@sha256:136600b6ff6843d61d355f7f71f460a166429f35de6fd11b568fece3c9a4d510'
readonly BACKUP_UTILITY_IMAGE='busybox:1.37.0@sha256:9db7b59979c38555a39def84a31fb98b5296952f9e3afd4f6f11f05b07adfab0'
readonly BACKUP_HOST='convive-vps'
readonly FICTIONAL_RECOVERY_ATTACHMENT_ID='00000000-0000-7000-8000-000000000138'
readonly FICTIONAL_RECOVERY_ATTACHMENT_CONTENT="$(printf '%%PDF-1.4\n%% Convive fictional recovery evidence only.\n%%%%EOF\n')"
readonly FICTIONAL_RECOVERY_ATTACHMENT_BYTES="$(printf '%s' "$FICTIONAL_RECOVERY_ATTACHMENT_CONTENT" | wc -c | tr -d ' ')"
readonly FICTIONAL_RECOVERY_ATTACHMENT_HASH="$(printf '%s' "$FICTIONAL_RECOVERY_ATTACHMENT_CONTENT" | sha256sum | cut -d ' ' -f 1)"

require_variable() {
  local name="$1"

  if [[ -z "${!name:-}" ]]; then
    echo "Required configuration is missing: $name" >&2
    return 1
  fi
}

environment_file_value() {
  local name="$1"
  local -a matching_lines
  mapfile -t matching_lines < <(grep --text --regexp="^${name}=" "$CONVIVE_BACKUP_ENV_FILE" || true)

  if [[ "${#matching_lines[@]}" -ne 1 || -z "${matching_lines[0]#*=}" ]]; then
    echo "The backup secret environment file must define $name exactly once." >&2
    return 1
  fi

  printf '%s' "${matching_lines[0]#*=}"
}

validate_off_host_repository() {
  local repository="$1"
  local region="$2"

  if [[ ! "$repository" =~ ^s3:https://[0-9a-f]{32}\.eu\.r2\.cloudflarestorage\.com/[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$ ]]; then
    echo 'Off-host storage requires the reviewed private Cloudflare R2 EU repository endpoint.' >&2
    return 1
  fi

  if [[ "$region" != 'auto' ]]; then
    echo 'Cloudflare R2 requires AWS_DEFAULT_REGION=auto.' >&2
    return 1
  fi
}

validate_configuration() {
  require_variable CONVIVE_BACKUP_ENV_FILE
  require_variable CONVIVE_BACKUP_EVIDENCE_DIRECTORY
  require_variable CONVIVE_COMPOSE_PROJECT
  require_variable CONVIVE_COMPOSE_FILES
  require_variable CONVIVE_RELEASE_REVISION
  require_variable CONVIVE_BACKUP_STORAGE_MODE

  if [[ ! "$CONVIVE_RELEASE_REVISION" =~ ^[0-9a-fA-F]{7,64}$ ]]; then
    echo 'CONVIVE_RELEASE_REVISION must be a Git commit identifier.' >&2
    return 1
  fi

  if [[ "$CONVIVE_BACKUP_STORAGE_MODE" != 'off-host' && "$CONVIVE_BACKUP_STORAGE_MODE" != 'test-local' ]]; then
    echo 'CONVIVE_BACKUP_STORAGE_MODE must be off-host or test-local.' >&2
    return 1
  fi

  if [[ "$CONVIVE_BACKUP_STORAGE_MODE" == 'test-local' && -z "${CONVIVE_RESTIC_REPOSITORY_DIRECTORY:-}" ]]; then
    echo 'test-local storage requires CONVIVE_RESTIC_REPOSITORY_DIRECTORY.' >&2
    return 1
  fi

  if [[ "$CONVIVE_BACKUP_STORAGE_MODE" == 'off-host' && -n "${CONVIVE_RESTIC_REPOSITORY_DIRECTORY:-}" ]]; then
    echo 'Off-host backups cannot use a host-local repository directory.' >&2
    return 1
  fi

  if [[ "$CONVIVE_BACKUP_STORAGE_MODE" == 'off-host' ]]; then
    if [[ "$CONVIVE_BACKUP_ENV_FILE" != '/etc/convive/backup-repository.env' ]]; then
      echo 'Off-host backup secrets must use the reviewed /etc/convive path.' >&2
      return 1
    fi
    if [[ "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY" != '/var/lib/convive-backup/evidence' ]]; then
      echo 'Off-host recovery evidence must use the reviewed protected directory.' >&2
      return 1
    fi
    if [[ "${CONVIVE_BACKUP_LOCK_FILE:-/run/lock/convive-backup.lock}" != '/run/lock/convive-backup.lock' ]]; then
      echo 'Off-host backup locking must use the reviewed runtime path.' >&2
      return 1
    fi
  fi

  if [[ ! -f "$CONVIVE_BACKUP_ENV_FILE" ]]; then
    echo 'The backup secret environment file does not exist.' >&2
    return 1
  fi

  local mode
  mode="$(stat -c '%a' "$CONVIVE_BACKUP_ENV_FILE")"
  if [[ "$mode" != '600' && "$mode" != '400' ]]; then
    echo 'The backup secret environment file must use mode 0600 or 0400.' >&2
    return 1
  fi

  if [[ "$CONVIVE_BACKUP_STORAGE_MODE" == 'off-host' && "$(stat -c '%u' "$CONVIVE_BACKUP_ENV_FILE")" != '0' ]]; then
    echo 'The production backup secret environment file must be owned by root.' >&2
    return 1
  fi

  local repository repository_password
  repository="$(environment_file_value RESTIC_REPOSITORY)"
  repository_password="$(environment_file_value RESTIC_PASSWORD)"

  if [[ "${#repository_password}" -lt 32 ]]; then
    echo 'RESTIC_PASSWORD must contain at least 32 characters.' >&2
    return 1
  fi

  if [[ "$CONVIVE_BACKUP_STORAGE_MODE" == 'test-local' && "$repository" != '/repository' ]]; then
    echo 'test-local storage requires RESTIC_REPOSITORY=/repository.' >&2
    return 1
  fi

  if [[ "$CONVIVE_BACKUP_STORAGE_MODE" == 'off-host' ]]; then
    validate_off_host_repository "$repository" "$(environment_file_value AWS_DEFAULT_REGION)"
    environment_file_value AWS_ACCESS_KEY_ID >/dev/null
    environment_file_value AWS_SECRET_ACCESS_KEY >/dev/null
  fi

  IFS=':' read -r -a COMPOSE_FILE_LIST <<< "$CONVIVE_COMPOSE_FILES"

  if [[ "${#COMPOSE_FILE_LIST[@]}" -eq 0 ]]; then
    echo 'At least one Compose file is required.' >&2
    return 1
  fi

  for compose_file in "${COMPOSE_FILE_LIST[@]}"; do
    if [[ ! -f "$compose_file" ]]; then
      echo "Compose file does not exist: $compose_file" >&2
      return 1
    fi
  done

  mkdir -p "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY"

  local evidence_mode
  evidence_mode="$(stat -c '%a' "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY")"
  if [[ "$evidence_mode" != '700' ]]; then
    chmod 0700 "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY"
  fi
}

compose_arguments() {
  local -n result="$1"
  result=(-p "$CONVIVE_COMPOSE_PROJECT")

  for compose_file in "${COMPOSE_FILE_LIST[@]}"; do
    result+=(-f "$compose_file")
  done
}

seed_fictional_recovery_attachment() {
  local compose_project="$1"
  shift
  local -a compose_files=("$@") compose_args=(-p "$compose_project")

  for compose_file in "${compose_files[@]}"; do
    compose_args+=(-f "$compose_file")
  done

  docker compose "${compose_args[@]}" run --rm --no-deps \
    -e FICTIONAL_ATTACHMENT_ID="$FICTIONAL_RECOVERY_ATTACHMENT_ID" \
    -e FICTIONAL_ATTACHMENT_CONTENT="$FICTIONAL_RECOVERY_ATTACHMENT_CONTENT" \
    api sh -eu -c '
      mkdir -p "$ATTACHMENT_STORAGE_DIRECTORY/available"
      printf "%s" "$FICTIONAL_ATTACHMENT_CONTENT" > "$ATTACHMENT_STORAGE_DIRECTORY/available/$FICTIONAL_ATTACHMENT_ID"
      chmod 0600 "$ATTACHMENT_STORAGE_DIRECTORY/available/$FICTIONAL_ATTACHMENT_ID"
    '

  docker compose "${compose_args[@]}" exec -T \
    -e FICTIONAL_ATTACHMENT_ID="$FICTIONAL_RECOVERY_ATTACHMENT_ID" \
    -e FICTIONAL_ATTACHMENT_BYTES="$FICTIONAL_RECOVERY_ATTACHMENT_BYTES" \
    -e FICTIONAL_ATTACHMENT_HASH="$FICTIONAL_RECOVERY_ATTACHMENT_HASH" \
    database sh -eu -c \
    'psql --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --set=ON_ERROR_STOP=1 \
      --set=attachment_id="$FICTIONAL_ATTACHMENT_ID" \
      --set=attachment_bytes="$FICTIONAL_ATTACHMENT_BYTES" \
      --set=attachment_hash="$FICTIONAL_ATTACHMENT_HASH"' <<'SQL'
BEGIN;
WITH selected_report AS (
  SELECT id
  FROM reports
  ORDER BY id
  LIMIT 1
), inserted_attachment AS (
  INSERT INTO report_attachments (
      id,
      report_id,
      media_type,
      byte_size,
      content_hash,
      storage_key,
      description,
      status,
      created_at,
      resolved_at
  )
  SELECT
      :'attachment_id'::uuid,
      id,
      'application/pdf',
      :attachment_bytes,
      :'attachment_hash',
      'available/' || :'attachment_id',
      'Fictional recovery evidence',
      'available',
      now(),
      now()
  FROM selected_report
  RETURNING report_id
)
UPDATE reports
SET attachment_count = attachment_count + 1,
    attachment_bytes = attachment_bytes + :attachment_bytes
WHERE id = (SELECT report_id FROM inserted_attachment);
COMMIT;
SQL
}

write_fictional_recovery_attachment() {
  local volume_name="$1"
  docker run --rm \
    --env FICTIONAL_ATTACHMENT_ID="$FICTIONAL_RECOVERY_ATTACHMENT_ID" \
    --env FICTIONAL_ATTACHMENT_CONTENT="$FICTIONAL_RECOVERY_ATTACHMENT_CONTENT" \
    --mount "type=volume,source=$volume_name,target=/attachments" \
    "$BACKUP_UTILITY_IMAGE" sh -eu -c '
      mkdir -p /attachments/available
      printf "%s" "$FICTIONAL_ATTACHMENT_CONTENT" > "/attachments/available/$FICTIONAL_ATTACHMENT_ID"
      chmod 0600 "/attachments/available/$FICTIONAL_ATTACHMENT_ID"
    '
}

run_restic() {
  local arguments=(run --rm --interactive --env-file "$CONVIVE_BACKUP_ENV_FILE")

  if [[ -n "${CONVIVE_RESTIC_REPOSITORY_DIRECTORY:-}" ]]; then
    mkdir -p "$CONVIVE_RESTIC_REPOSITORY_DIRECTORY"
    arguments+=(
      --user "$(id -u):$(id -g)"
      --mount
      "type=bind,source=$CONVIVE_RESTIC_REPOSITORY_DIRECTORY,target=/repository"
    )
  fi

  docker "${arguments[@]}" "$RESTIC_IMAGE" --no-cache "$@"
}

attachment_volume_name() {
  local compose_project="$1"
  local -a volumes
  mapfile -t volumes < <(
    docker volume ls \
      --filter "label=com.docker.compose.project=$compose_project" \
      --filter 'label=com.docker.compose.volume=attachment-data' \
      --quiet
  )

  if [[ "${#volumes[@]}" -ne 1 || -z "${volumes[0]}" ]]; then
    echo 'Expected exactly one private attachment volume for the Compose project.' >&2
    return 1
  fi

  printf '%s\n' "${volumes[0]}"
}

database_attachment_manifest() {
  local compose_project="$1"
  shift
  local -a compose_files=("$@") compose_args=(-p "$compose_project")

  for compose_file in "${compose_files[@]}"; do
    compose_args+=(-f "$compose_file")
  done

  docker compose "${compose_args[@]}" exec -T database sh -eu -c \
    'psql --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --set=ON_ERROR_STOP=1 --quiet' <<'SQL'
COPY (
  SELECT id::text, status, storage_key, byte_size, content_hash
  FROM report_attachments
  ORDER BY id
) TO STDOUT WITH (FORMAT csv, DELIMITER E'\t');
SQL
}

verify_attachment_storage() {
  local volume_name="$1"
  local manifest="$2"
  local verifier
  verifier="$(realpath "$SCRIPT_DIRECTORY/verify-attachment-storage.sh")"

  if [[ ! -f "$verifier" ]]; then
    echo 'The private attachment verifier is unavailable.' >&2
    return 1
  fi

  # Command substitution strips the manifest's final newline. Add it back so
  # POSIX `read` processes the last (or only) metadata row.
  printf '%s\n' "$manifest" | docker run --rm --interactive \
    --read-only \
    --tmpfs /tmp:rw,noexec,nosuid,nodev,mode=0700 \
    --mount "type=volume,source=$volume_name,target=/attachments,readonly" \
    --mount "type=bind,source=$verifier,target=/verify-attachment-storage.sh,readonly" \
    "$BACKUP_UTILITY_IMAGE" \
    /verify-attachment-storage.sh /attachments
}

attachment_consistency_state() {
  local compose_project="$1"
  local volume_name="$2"
  shift 2
  local manifest
  manifest="$(database_attachment_manifest "$compose_project" "$@")"

  attachment_manifest_state "$volume_name" "$manifest"
}

attachment_manifest_state() {
  local volume_name="$1"
  local manifest="$2"
  local summary digest
  summary="$(verify_attachment_storage "$volume_name" "$manifest")"

  if [[ ! "$summary" =~ ^objects=[0-9]+\;bytes=[0-9]+$ ]]; then
    echo 'The private attachment verifier returned an invalid summary.' >&2
    return 1
  fi

  digest="$(printf '%s' "$manifest" | sha256sum | cut -d ' ' -f 1)"
  printf '%s;%s\n' "$digest" "$summary"
}

run_restic_with_attachment_volume() {
  local volume_name="$1"
  local access_mode="$2"
  shift 2
  local arguments=(run --rm --interactive --read-only --tmpfs /tmp:rw,noexec,nosuid,nodev --env-file "$CONVIVE_BACKUP_ENV_FILE")

  if [[ "$access_mode" != 'readonly' && "$access_mode" != 'readwrite' ]]; then
    echo 'Attachment Restic access must be readonly or readwrite.' >&2
    return 1
  fi

  if [[ -n "${CONVIVE_RESTIC_REPOSITORY_DIRECTORY:-}" ]]; then
    mkdir -p "$CONVIVE_RESTIC_REPOSITORY_DIRECTORY"
    arguments+=(
      --mount
      "type=bind,source=$CONVIVE_RESTIC_REPOSITORY_DIRECTORY,target=/repository"
    )
  fi

  local volume_mount="type=volume,source=$volume_name,target=/attachments"
  if [[ "$access_mode" == 'readonly' ]]; then
    volume_mount+=',readonly'
  fi
  arguments+=(--mount "$volume_mount")

  local restic_status=0
  docker "${arguments[@]}" "$RESTIC_IMAGE" --no-cache "$@" || restic_status=$?

  if [[ -n "${CONVIVE_RESTIC_REPOSITORY_DIRECTORY:-}" ]]; then
    docker run --rm \
      --mount "type=bind,source=$CONVIVE_RESTIC_REPOSITORY_DIRECTORY,target=/repository" \
      "$BACKUP_UTILITY_IMAGE" \
      chown -R "$(id -u):$(id -g)" /repository
  fi

  return "$restic_status"
}

latest_snapshot_generation() {
  run_restic snapshots \
    --host "$BACKUP_HOST" \
    --tag "automated,complete,database,revision-${CONVIVE_RELEASE_REVISION}" \
    --latest 1 \
    --json \
    | python3 -c '
import json
import sys

snapshots = json.load(sys.stdin)
if len(snapshots) != 1 or not snapshots[0].get("id"):
    raise SystemExit("expected exactly one latest complete Convive database snapshot")
generation_tags = [
    tag.removeprefix("generation-")
    for tag in snapshots[0].get("tags", [])
    if tag.startswith("generation-")
]
if len(generation_tags) != 1 or not generation_tags[0]:
    raise SystemExit("the latest Convive snapshot has no unique generation")
print(snapshots[0]["id"], generation_tags[0])
'
}

attachment_snapshot_id() {
  local generation="$1"
  run_restic snapshots \
    --host "$BACKUP_HOST" \
    --tag "automated,complete,attachments,revision-${CONVIVE_RELEASE_REVISION},generation-$generation" \
    --json \
    | python3 -c '
import json
import sys

snapshots = json.load(sys.stdin)
if len(snapshots) != 1 or not snapshots[0].get("id"):
    raise SystemExit("expected exactly one matching complete Convive attachment snapshot")
print(snapshots[0]["id"])
'
}

record_evidence() {
  local outcome="$1"
  local operation="$2"
  local detail="$3"
  local timestamp filename_timestamp temporary_file history_file latest_file latest_temporary_file
  timestamp="$(date --utc +'%Y-%m-%dT%H:%M:%SZ')"
  filename_timestamp="$(date --utc +'%Y%m%dT%H%M%S%NZ')"
  temporary_file="$(mktemp "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY/.evidence.XXXXXX")"
  history_file="$CONVIVE_BACKUP_EVIDENCE_DIRECTORY/${filename_timestamp}-${operation}-${outcome}.json"
  latest_file="$CONVIVE_BACKUP_EVIDENCE_DIRECTORY/latest-${operation}.json"
  latest_temporary_file="$(mktemp "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY/.latest.XXXXXX")"

  python3 - "$timestamp" "$CONVIVE_RELEASE_REVISION" "$outcome" "$operation" "$detail" > "$temporary_file" <<'PYTHON'
import json
import sys

timestamp, revision, outcome, operation, detail = sys.argv[1:]
json.dump(
    {
        "timestamp": timestamp,
        "revision": revision,
        "outcome": outcome,
        "operation": operation,
        "detail": detail,
    },
    sys.stdout,
    separators=(",", ":"),
)
sys.stdout.write("\n")
PYTHON
  chmod 0600 "$temporary_file"
  mv "$temporary_file" "$history_file"
  cp --preserve=mode "$history_file" "$latest_temporary_file"
  mv -f "$latest_temporary_file" "$latest_file"
}
