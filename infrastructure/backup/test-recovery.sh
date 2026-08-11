#!/usr/bin/env bash

set -Eeuo pipefail

readonly SCRIPT_DIRECTORY="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly REPOSITORY_ROOT="$(cd -- "$SCRIPT_DIRECTORY/../.." && pwd)"
readonly RESTORE_COMPOSE_FILE="$SCRIPT_DIRECTORY/compose.restore.yaml"
readonly LOCAL_COMPOSE_FILE="$SCRIPT_DIRECTORY/compose.restore.local.yaml"
readonly -a TEST_COMPOSE_ARGUMENTS=(-f "$RESTORE_COMPOSE_FILE" -f "$LOCAL_COMPOSE_FILE")

source "$SCRIPT_DIRECTORY/common.sh"

validate_off_host_repository \
  's3:https://0123456789abcdef0123456789abcdef.eu.r2.cloudflarestorage.com/convive-backups' \
  'auto'

if validate_off_host_repository \
  's3:https://0123456789abcdef0123456789abcdef.r2.cloudflarestorage.com/convive-backups' \
  'auto' >/dev/null 2>&1; then
  echo 'The recovery boundary accepted a non-EU Cloudflare R2 endpoint.' >&2
  exit 1
fi

if validate_off_host_repository \
  's3:https://0123456789abcdef0123456789abcdef.eu.r2.cloudflarestorage.com/convive-backups' \
  'eu-west-1' >/dev/null 2>&1; then
  echo 'The recovery boundary accepted an invalid Cloudflare R2 region.' >&2
  exit 1
fi

if [[ ! -f "$REPOSITORY_ROOT/apps/api/vendor/autoload.php" ]]; then
  echo 'Backend dependencies must be installed before the recovery test.' >&2
  exit 1
fi

temporary_root="$(mktemp -d)"
suffix="$(openssl rand -hex 4)"
source_project="convive-backup-source-$suffix"
restore_project="convive-restore-$suffix"
database_password="$(openssl rand -hex 32)"
demo_password="$(openssl rand -hex 32)"
repository_password="$(openssl rand -hex 32)"
restore_app_secret="$(openssl rand -hex 32)"

if [[ "${GITHUB_ACTIONS:-false}" == 'true' ]]; then
  printf '::add-mask::%s\n' "$database_password" "$demo_password" "$repository_password" "$restore_app_secret"
fi

cleanup() {
  docker compose -p "$restore_project" "${TEST_COMPOSE_ARGUMENTS[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true
  docker compose -p "$source_project" "${TEST_COMPOSE_ARGUMENTS[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true
  case "$temporary_root" in
    "${TMPDIR:-/tmp}"/tmp.*) rm -rf -- "$temporary_root" ;;
    *) echo 'Refusing to remove an unexpected recovery-test directory.' >&2 ;;
  esac
}
trap cleanup EXIT

export CONVIVE_RESTORE_DATABASE_PASSWORD="$database_password"
export DEMO_PROFESSIONAL_PASSWORD="$demo_password"
export CONVIVE_RESTORE_API_IMAGE="convive-restore-api-$suffix:local"
export CONVIVE_RESTORE_APP_SECRET="$restore_app_secret"
export CONVIVE_RESTORE_RUNTIME_MODE=reviewed-source

docker compose -p "$source_project" "${TEST_COMPOSE_ARGUMENTS[@]}" up --detach --build --wait database
docker compose -p "$source_project" "${TEST_COMPOSE_ARGUMENTS[@]}" run --rm --no-deps api \
  php bin/console doctrine:migrations:migrate --no-interaction
docker compose -p "$source_project" "${TEST_COMPOSE_ARGUMENTS[@]}" run --rm --no-deps \
  -e APP_ENV=prod \
  -e APP_DEMO_MODE=1 \
  -e APP_SECRET=recovery-test-only \
  -e DEMO_PROFESSIONAL_PASSWORD \
  api php bin/console app:demo:seed --env=prod --no-debug

seed_fictional_recovery_attachment \
  "$source_project" \
  "$RESTORE_COMPOSE_FILE" \
  "$LOCAL_COMPOSE_FILE"

docker compose -p "$source_project" "${TEST_COMPOSE_ARGUMENTS[@]}" exec -T \
  database sh -eu -c \
  'psql --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --set=ON_ERROR_STOP=1' <<'SQL'
INSERT INTO professional_sessions (sess_id, sess_data, sess_lifetime, sess_time)
VALUES ('recovery-test-session', '\x00', 3600, 1786320000);

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
    '00000000-0000-7000-8000-000000000066'::uuid,
    id,
    repeat('a', 64),
    now(),
    now(),
    now() + interval '2 hours',
    NULL
FROM reports
ORDER BY id
LIMIT 1;
SQL

source_attachment_volume="$(attachment_volume_name "$source_project")"

restore_fictional_attachment() {
  write_fictional_recovery_attachment "$source_attachment_volume"
}

expect_attachment_validation_failure() {
  local scenario="$1"
  local failure_log="$temporary_root/expected-attachment-failure.log"

  if attachment_consistency_state \
    "$source_project" \
    "$source_attachment_volume" \
    "$RESTORE_COMPOSE_FILE" \
    "$LOCAL_COMPOSE_FILE" > "$failure_log" 2>&1; then
    echo "Invalid private attachment storage unexpectedly passed the $scenario consistency check." >&2
    exit 1
  fi

  if grep --fixed-strings --quiet "$FICTIONAL_RECOVERY_ATTACHMENT_ID" "$failure_log" \
    || grep --fixed-strings --quiet "$FICTIONAL_RECOVERY_ATTACHMENT_HASH" "$failure_log" \
    || grep --fixed-strings --quiet 'Fictional recovery evidence' "$failure_log"; then
    echo 'Attachment consistency failure output disclosed private metadata.' >&2
    exit 1
  fi
}

docker run --rm \
  --env FICTIONAL_ATTACHMENT_ID="$FICTIONAL_RECOVERY_ATTACHMENT_ID" \
  --mount "type=volume,source=$source_attachment_volume,target=/attachments" \
  "$BACKUP_UTILITY_IMAGE" sh -eu -c \
  'printf corrupt > "/attachments/available/$FICTIONAL_ATTACHMENT_ID"'
expect_attachment_validation_failure corruption
restore_fictional_attachment

docker run --rm \
  --env FICTIONAL_ATTACHMENT_ID="$FICTIONAL_RECOVERY_ATTACHMENT_ID" \
  --mount "type=volume,source=$source_attachment_volume,target=/attachments" \
  "$BACKUP_UTILITY_IMAGE" sh -eu -c \
  'rm "/attachments/available/$FICTIONAL_ATTACHMENT_ID"'
docker run --rm \
  --env FICTIONAL_ATTACHMENT_ID="$FICTIONAL_RECOVERY_ATTACHMENT_ID" \
  --mount "type=volume,source=$source_attachment_volume,target=/attachments,readonly" \
  "$BACKUP_UTILITY_IMAGE" sh -eu -c \
  'test ! -e "/attachments/available/$FICTIONAL_ATTACHMENT_ID"'
expect_attachment_validation_failure missing-object
restore_fictional_attachment

docker run --rm \
  --mount "type=volume,source=$source_attachment_volume,target=/attachments" \
  "$BACKUP_UTILITY_IMAGE" sh -eu -c \
  'printf unexpected > /attachments/available/00000000-0000-7000-8000-000000000999'
expect_attachment_validation_failure unexpected-object
docker run --rm \
  --mount "type=volume,source=$source_attachment_volume,target=/attachments" \
  "$BACKUP_UTILITY_IMAGE" rm /attachments/available/00000000-0000-7000-8000-000000000999

secret_environment="$temporary_root/repository.env"
printf '%s\n' \
  'RESTIC_REPOSITORY=/repository' \
  "RESTIC_PASSWORD=$repository_password" > "$secret_environment"
chmod 0400 "$secret_environment"

export CONVIVE_BACKUP_ENV_FILE="$secret_environment"
export CONVIVE_BACKUP_EVIDENCE_DIRECTORY="$temporary_root/evidence"
export CONVIVE_BACKUP_LOCK_FILE="$temporary_root/backup.lock"
export CONVIVE_BACKUP_STORAGE_MODE=test-local
export CONVIVE_RESTIC_REPOSITORY_DIRECTORY="$temporary_root/repository"
export CONVIVE_COMPOSE_PROJECT="$source_project"
export CONVIVE_COMPOSE_FILES="$RESTORE_COMPOSE_FILE:$LOCAL_COMPOSE_FILE"
export CONVIVE_RELEASE_REVISION="$(git -C "$REPOSITORY_ROOT" rev-parse HEAD)"
export CONVIVE_RESTIC_CHECK_SUBSET=100%
export CONVIVE_RESTORE_COMPOSE_PROJECT="$restore_project"
export CONVIVE_RESTORE_COMPOSE_FILES="$RESTORE_COMPOSE_FILE:$LOCAL_COMPOSE_FILE"

"$SCRIPT_DIRECTORY/init-repository.sh"

incorrect_secret='deliberately-wrong-recovery-test-secret'
incorrect_environment="$temporary_root/incorrect-repository.env"
printf '%s\n' \
  'RESTIC_REPOSITORY=/repository' \
  "RESTIC_PASSWORD=$incorrect_secret" > "$incorrect_environment"
chmod 0400 "$incorrect_environment"

CONVIVE_BACKUP_ENV_FILE="$incorrect_environment" \
  "$SCRIPT_DIRECTORY/backup.sh" > "$temporary_root/expected-failure.log" 2>&1 && {
    echo 'A backup with an incorrect repository password unexpectedly succeeded.' >&2
    exit 1
  }

if grep --fixed-strings --quiet "$incorrect_secret" "$temporary_root/expected-failure.log"; then
  echo 'The failed backup leaked its repository password.' >&2
  exit 1
fi

python3 - "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY/latest-backup.json" <<'PYTHON'
import json
import sys

with open(sys.argv[1], encoding="utf-8") as stream:
    evidence = json.load(stream)
if evidence["outcome"] != "failure" or evidence["detail"] != "database-backup":
    raise SystemExit("failed backup evidence is incomplete")
PYTHON

"$SCRIPT_DIRECTORY/backup.sh"

docker compose -p "$restore_project" "${TEST_COMPOSE_ARGUMENTS[@]}" up --detach --build --wait database
"$SCRIPT_DIRECTORY/restore-test.sh"

restore_attachment_directory="$(
  docker compose -p "$restore_project" "${TEST_COMPOSE_ARGUMENTS[@]}" run --rm --no-deps api \
    php -r 'echo getenv("ATTACHMENT_STORAGE_DIRECTORY");'
)"

if [[ "$restore_attachment_directory" != '/var/lib/convive/attachments' ]]; then
  echo 'The isolated recovery environment did not provide its private attachment boundary.' >&2
  exit 1
fi

if grep --recursive --fixed-strings --quiet "$FICTIONAL_RECOVERY_ATTACHMENT_ID" "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY" \
  || grep --recursive --fixed-strings --quiet "$FICTIONAL_RECOVERY_ATTACHMENT_HASH" "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY" \
  || grep --recursive --fixed-strings --quiet 'Fictional recovery evidence' "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY"; then
  echo 'Recovery evidence disclosed private attachment metadata.' >&2
  exit 1
fi

python3 - "$CONVIVE_BACKUP_EVIDENCE_DIRECTORY" <<'PYTHON'
import json
import pathlib
import sys

evidence_directory = pathlib.Path(sys.argv[1])
for operation in ("backup", "restore-test"):
    path = evidence_directory / f"latest-{operation}.json"
    with path.open(encoding="utf-8") as stream:
        evidence = json.load(stream)
    if evidence["outcome"] != "success" or evidence["operation"] != operation:
        raise SystemExit(f"invalid {operation} evidence")

history = [
    json.loads(path.read_text(encoding="utf-8"))
    for path in evidence_directory.glob("20*.json")
]
expected = {
    ("backup", "failure"),
    ("backup", "success"),
    ("restore-test", "success"),
}
observed = {(item["operation"], item["outcome"]) for item in history}
if not expected.issubset(observed):
    raise SystemExit("recovery evidence history is incomplete")
PYTHON

echo 'Encrypted backup and isolated recovery test passed.'
