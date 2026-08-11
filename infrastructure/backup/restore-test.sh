#!/usr/bin/env bash

set -Eeuo pipefail

readonly SCRIPT_DIRECTORY="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIRECTORY/common.sh"

validate_configuration
require_variable CONVIVE_RESTORE_COMPOSE_PROJECT
require_variable CONVIVE_RESTORE_COMPOSE_FILES
require_variable CONVIVE_RESTORE_API_IMAGE
require_variable CONVIVE_RESTORE_APP_SECRET
require_variable CONVIVE_RESTORE_RUNTIME_MODE

readonly LOCK_FILE="${CONVIVE_BACKUP_LOCK_FILE:-/run/lock/convive-backup.lock}"
exec 9> "$LOCK_FILE"

if ! flock -n 9; then
  echo 'Another Convive backup or restore operation is already running.' >&2
  exit 1
fi

if [[ "$CONVIVE_RESTORE_COMPOSE_PROJECT" != convive-restore-* ]]; then
  echo 'The restore project must use the isolated convive-restore-* prefix.' >&2
  exit 1
fi

if [[ "$CONVIVE_RESTORE_COMPOSE_PROJECT" == "$CONVIVE_COMPOSE_PROJECT" ]]; then
  echo 'Refusing to restore into the source Compose project.' >&2
  exit 1
fi

IFS=':' read -r -a restore_files <<< "$CONVIVE_RESTORE_COMPOSE_FILES"
restore_args=(-p "$CONVIVE_RESTORE_COMPOSE_PROJECT")

expected_restore_file="$(realpath "$SCRIPT_DIRECTORY/compose.restore.yaml")"
expected_local_file="$(realpath "$SCRIPT_DIRECTORY/compose.restore.local.yaml")"

if [[ "$(realpath "${restore_files[0]}")" != "$expected_restore_file" ]]; then
  echo 'The restore exercise must use the dedicated isolated Compose file.' >&2
  exit 1
fi

if [[ "$CONVIVE_RESTORE_RUNTIME_MODE" == 'immutable-image' ]]; then
  if [[ "${#restore_files[@]}" -ne 1 || ! "$CONVIVE_RESTORE_API_IMAGE" =~ @sha256:[0-9a-f]{64}$ ]]; then
    echo 'Production restoration requires one isolated Compose file and an immutable API image digest.' >&2
    exit 1
  fi
elif [[ "$CONVIVE_RESTORE_RUNTIME_MODE" == 'reviewed-source' ]]; then
  if [[ "${#restore_files[@]}" -ne 2 || "$(realpath "${restore_files[1]}")" != "$expected_local_file" ]]; then
    echo 'Reviewed-source restoration requires only the dedicated local Compose override.' >&2
    exit 1
  fi
elif [[ "$CONVIVE_RESTORE_RUNTIME_MODE" != 'immutable-image' ]]; then
  echo 'CONVIVE_RESTORE_RUNTIME_MODE must be immutable-image or reviewed-source.' >&2
  exit 1
fi

for compose_file in "${restore_files[@]}"; do
  if [[ ! -f "$compose_file" ]]; then
    echo "Restore Compose file does not exist: $compose_file" >&2
    exit 1
  fi
  restore_args+=(-f "$compose_file")
done

stage='restore'
trap 'record_evidence failure restore-test "$stage"' ERR

docker compose "${restore_args[@]}" run --rm --no-deps api true

source_attachment_volume="$(attachment_volume_name "$CONVIVE_COMPOSE_PROJECT")"
restore_attachment_volume="$(attachment_volume_name "$CONVIVE_RESTORE_COMPOSE_PROJECT")"

if [[ "$source_attachment_volume" == "$restore_attachment_volume" ]]; then
  echo 'Refusing to restore attachment objects into the source volume.' >&2
  false
fi

existing_tables="$(docker compose "${restore_args[@]}" exec -T database sh -eu -c \
  'psql --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --tuples-only --no-align --set=ON_ERROR_STOP=1 --command="SELECT count(*) FROM pg_tables WHERE schemaname = '\''public'\''"')"

if [[ "$existing_tables" != '0' ]]; then
  echo 'Refusing to restore into a non-empty database.' >&2
  false
fi

read -r snapshot_id generation < <(latest_snapshot_generation)
attachment_snapshot="$(attachment_snapshot_id "$generation")"

run_restic dump "$snapshot_id" convive.dump \
  | docker compose "${restore_args[@]}" exec -T database sh -eu -c \
      'exec pg_restore --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --no-owner --no-acl --exit-on-error'

stage='attachment-restoration'
run_restic_with_attachment_volume "$restore_attachment_volume" readwrite restore \
  "$attachment_snapshot" \
  --target / \
  --verify >/dev/null

stage='credential-invalidation'
docker compose "${restore_args[@]}" exec -T database sh -eu -c \
  'psql --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --set=ON_ERROR_STOP=1' <<'SQL'
TRUNCATE TABLE professional_sessions;
TRUNCATE TABLE report_access_grants;
SQL

stage='verification'
unsafe_credentials="$(docker compose "${restore_args[@]}" exec -T database sh -eu -c \
  'psql --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --tuples-only --no-align --set=ON_ERROR_STOP=1 --command="SELECT (SELECT count(*) FROM professional_sessions) + (SELECT count(*) FROM report_access_grants)"')"

if [[ "$unsafe_credentials" != '0' ]]; then
  echo 'Restored ephemeral credentials were not fully invalidated.' >&2
  false
fi

restored_reports="$(docker compose "${restore_args[@]}" exec -T database sh -eu -c \
  'psql --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --tuples-only --no-align --set=ON_ERROR_STOP=1 --command="SELECT count(*) FROM reports"')"

if [[ ! "$restored_reports" =~ ^[0-9]+$ ]]; then
  echo 'The restored report count is invalid.' >&2
  false
fi

stage='attachment-verification'
restored_attachment_state="$(
  attachment_consistency_state \
    "$CONVIVE_RESTORE_COMPOSE_PROJECT" \
    "$restore_attachment_volume" \
    "${restore_files[@]}"
)"
restored_attachment_summary="${restored_attachment_state#*;}"

stage='application-verification'
docker compose "${restore_args[@]}" run --rm --no-deps api \
  php bin/console doctrine:schema:validate >/dev/null
docker compose "${restore_args[@]}" run --rm --no-deps api \
  php bin/console doctrine:migrations:up-to-date >/dev/null

stage='application-startup'
docker compose "${restore_args[@]}" run --rm --no-deps api php -r '
require "vendor/autoload.php";
$kernel = new App\Kernel("prod", false);
$request = Symfony\Component\HttpFoundation\Request::create("/api/v1/health", "GET");
$response = $kernel->handle($request);
$valid = 200 === $response->getStatusCode() && "{\"status\":\"ok\"}" === $response->getContent();
$kernel->terminate($request, $response);
exit($valid ? 0 : 1);
' >/dev/null

trap - ERR
record_evidence success restore-test "generation=${generation:0:12};reports=$restored_reports;${restored_attachment_summary};credentials=0"
echo "Isolated restore completed: ${restored_reports} reports, ${restored_attachment_summary//;/, } and no revived sessions or capabilities."
