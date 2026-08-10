#!/usr/bin/env bash

set -Eeuo pipefail

readonly SCRIPT_DIRECTORY="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly REPOSITORY_ROOT="$(cd -- "$SCRIPT_DIRECTORY/../.." && pwd)"
readonly RESTORE_COMPOSE_FILE="$SCRIPT_DIRECTORY/compose.restore.yaml"
readonly LOCAL_COMPOSE_FILE="$SCRIPT_DIRECTORY/compose.restore.local.yaml"
readonly -a EXERCISE_COMPOSE_ARGUMENTS=(-f "$RESTORE_COMPOSE_FILE" -f "$LOCAL_COMPOSE_FILE")

source "$SCRIPT_DIRECTORY/common.sh"

if [[ "$(id -u)" -ne 0 ]]; then
  echo 'The off-host recovery exercise must run as root to use the protected runtime configuration.' >&2
  exit 1
fi

if [[ "${CONVIVE_BACKUP_ENV_FILE:-}" != '/etc/convive/backup-repository.env' ]]; then
  echo 'The off-host recovery exercise requires /etc/convive/backup-repository.env.' >&2
  exit 1
fi

if [[ -n "$(git -C "$REPOSITORY_ROOT" status --porcelain)" ]]; then
  echo 'The off-host recovery exercise requires a clean reviewed checkout.' >&2
  exit 1
fi

if [[ ! -f "$REPOSITORY_ROOT/apps/api/vendor/autoload.php" ]]; then
  echo 'Backend dependencies must be installed before the off-host recovery exercise.' >&2
  exit 1
fi

temporary_root="$(mktemp -d)"
suffix="$(openssl rand -hex 4)"
source_project="convive-backup-source-$suffix"
restore_project="convive-restore-$suffix"
database_password="$(openssl rand -hex 32)"
demo_password="$(openssl rand -hex 32)"
source_app_secret="$(openssl rand -hex 32)"
restore_app_secret="$(openssl rand -hex 32)"

cleanup() {
  docker compose -p "$restore_project" "${EXERCISE_COMPOSE_ARGUMENTS[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true
  docker compose -p "$source_project" "${EXERCISE_COMPOSE_ARGUMENTS[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true
  case "$temporary_root" in
    /tmp/tmp.*) rm -rf -- "$temporary_root" ;;
    *) echo 'Refusing to remove an unexpected off-host recovery directory.' >&2 ;;
  esac
}
trap cleanup EXIT

export CONVIVE_BACKUP_EVIDENCE_DIRECTORY=/var/lib/convive-backup/evidence
export CONVIVE_BACKUP_LOCK_FILE=/run/lock/convive-backup.lock
export CONVIVE_BACKUP_STORAGE_MODE=off-host
export CONVIVE_COMPOSE_PROJECT="$source_project"
export CONVIVE_COMPOSE_FILES="$RESTORE_COMPOSE_FILE:$LOCAL_COMPOSE_FILE"
export CONVIVE_RELEASE_REVISION="$(git -C "$REPOSITORY_ROOT" rev-parse HEAD)"
export CONVIVE_RESTIC_CHECK_SUBSET=100%
export CONVIVE_BACKUP_KEEP_DAILY=14
export CONVIVE_BACKUP_KEEP_WEEKLY=8
export CONVIVE_BACKUP_KEEP_MONTHLY=12
export CONVIVE_RESTORE_COMPOSE_PROJECT="$restore_project"
export CONVIVE_RESTORE_COMPOSE_FILES="$RESTORE_COMPOSE_FILE:$LOCAL_COMPOSE_FILE"
export CONVIVE_RESTORE_API_IMAGE="convive-restore-api-$suffix:local"
export CONVIVE_RESTORE_RUNTIME_MODE=reviewed-source
export CONVIVE_RESTORE_DATABASE_PASSWORD="$database_password"
export CONVIVE_RESTORE_APP_SECRET="$restore_app_secret"
export DEMO_PROFESSIONAL_PASSWORD="$demo_password"

validate_configuration

docker compose -p "$source_project" "${EXERCISE_COMPOSE_ARGUMENTS[@]}" up --detach --build --wait database
docker compose -p "$source_project" "${EXERCISE_COMPOSE_ARGUMENTS[@]}" run --rm --no-deps api \
  php bin/console doctrine:migrations:migrate --no-interaction
docker compose -p "$source_project" "${EXERCISE_COMPOSE_ARGUMENTS[@]}" run --rm --no-deps \
  -e APP_ENV=prod \
  -e APP_DEMO_MODE=1 \
  -e APP_SECRET="$source_app_secret" \
  -e DEMO_PROFESSIONAL_PASSWORD \
  api php bin/console app:demo:seed --env=prod --no-debug

docker compose -p "$source_project" "${EXERCISE_COMPOSE_ARGUMENTS[@]}" exec -T database sh -eu -c \
  'psql --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --set=ON_ERROR_STOP=1' <<'SQL'
INSERT INTO professional_sessions (sess_id, sess_data, sess_lifetime, sess_time)
VALUES ('off-host-recovery-session', '\x00', 3600, 1786320000);

INSERT INTO report_access_grants (
    id,
    report_id,
    capability_hash,
    issued_at,
    last_used_at,
    absolute_expires_at,
    revoked_at
)
SELECT
    '00000000-0000-7000-8000-000000000067'::uuid,
    id,
    repeat('b', 64),
    now(),
    now(),
    now() + interval '2 hours',
    NULL
FROM reports
ORDER BY id
LIMIT 1;
SQL

"$SCRIPT_DIRECTORY/init-repository.sh"
"$SCRIPT_DIRECTORY/backup.sh"

docker compose -p "$restore_project" "${EXERCISE_COMPOSE_ARGUMENTS[@]}" up --detach --build --wait database
"$SCRIPT_DIRECTORY/restore-test.sh"

python3 - "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY" "$CONVIVE_RELEASE_REVISION" <<'PYTHON'
import json
import pathlib
import sys

evidence_directory = pathlib.Path(sys.argv[1])
revision = sys.argv[2]

for operation in ("backup", "restore-test"):
    path = evidence_directory / f"latest-{operation}.json"
    with path.open(encoding="utf-8") as stream:
        evidence = json.load(stream)
    if (
        evidence["outcome"] != "success"
        or evidence["operation"] != operation
        or evidence["revision"] != revision
    ):
        raise SystemExit(f"invalid off-host {operation} evidence")
PYTHON

echo 'Off-host encrypted backup and isolated recovery exercise passed.'
