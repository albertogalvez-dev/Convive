#!/usr/bin/env bash

set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
api_source="$repository_root/apps/api"
production_compose="$repository_root/infrastructure/production/compose.production.yaml"
secret_template="$repository_root/infrastructure/production/secrets/api.env.example"

mapfile -t referenced_variables < <(
  grep -Rh --exclude=reference.php -oE '%env\([^)]*\)%' \
    "$api_source/config" "$api_source/src" \
    | sed -E 's/^%env\(([^)]*:)?([^):]+)\)%$/\2/' \
    | grep -vE '^(SESSION_DATABASE_URL_TEST|TEST_TOKEN)$' \
    | sort -u
)

mapfile -t compose_variables < <(
  awk '
    /^  api:$/ { in_api = 1; next }
    in_api && /^  [[:alnum:]_-]+:$/ { exit }
    in_api && /^    environment:$/ { in_environment = 1; next }
    in_environment && /^    [[:alnum:]_-]+:/ { exit }
    in_environment && /^      [A-Z][A-Z0-9_]*:/ {
      name = $1
      sub(/:$/, "", name)
      print name
    }
  ' "$production_compose" | sort -u
)

mapfile -t secret_variables < <(
  sed -nE 's/^([A-Z][A-Z0-9_]*)=.*/\1/p' "$secret_template" | sort -u
)

missing=0
for variable in "${referenced_variables[@]}"; do
  in_compose=0
  in_secret=0
  printf '%s\n' "${compose_variables[@]}" | grep -Fxq "$variable" && in_compose=1
  printf '%s\n' "${secret_variables[@]}" | grep -Fxq "$variable" && in_secret=1

  if (( in_compose == 0 && in_secret == 0 )); then
    printf 'Production API environment variable is undeclared: %s\n' "$variable" >&2
    missing=1
  fi
  if (( in_compose == 1 && in_secret == 1 )); then
    printf 'Production API environment variable is declared twice: %s\n' "$variable" >&2
    missing=1
  fi
done

for variable in APP_DEMO_MODE DEFAULT_URI; do
  if ! printf '%s\n' "${compose_variables[@]}" | grep -Fxq "$variable"; then
    printf 'Non-secret production variable must be declared in Compose: %s\n' "$variable" >&2
    missing=1
  fi
done

for variable in APP_SECRET DATABASE_URL SESSION_DATABASE_URL REDIS_DSN DEMO_PROFESSIONAL_PASSWORD; do
  if ! printf '%s\n' "${secret_variables[@]}" | grep -Fxq "$variable"; then
    printf 'Production secret variable is missing from api.env.example: %s\n' "$variable" >&2
    missing=1
  fi
done

if printf '%s\n' "${secret_variables[@]}" | grep -Fxq APP_DEMO_MODE; then
  printf 'APP_DEMO_MODE is not secret and must not be declared in api.env.example.\n' >&2
  missing=1
fi

if ! grep -Eq '^# .*at least 20 characters' "$secret_template"; then
  printf 'DEMO_PROFESSIONAL_PASSWORD must document its 20-character minimum.\n' >&2
  missing=1
fi

if (( missing != 0 )); then
  exit 1
fi

printf 'Production API environment declarations cover every application dependency.\n'
