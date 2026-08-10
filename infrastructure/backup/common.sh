#!/usr/bin/env bash

set -Eeuo pipefail

readonly RESTIC_IMAGE='restic/restic:0.19.1@sha256:136600b6ff6843d61d355f7f71f460a166429f35de6fd11b568fece3c9a4d510'
readonly BACKUP_HOST='convive-vps'

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

latest_snapshot_id() {
  run_restic snapshots \
    --host "$BACKUP_HOST" \
    --tag automated \
    --tag "revision-${CONVIVE_RELEASE_REVISION}" \
    --latest 1 \
    --json \
    | python3 -c '
import json
import sys

snapshots = json.load(sys.stdin)
if len(snapshots) != 1 or not snapshots[0].get("id"):
    raise SystemExit("expected exactly one latest Convive snapshot")
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
