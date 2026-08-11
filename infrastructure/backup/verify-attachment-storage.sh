#!/usr/bin/env sh

set -eu

readonly storage_root="${1:-/attachments}"
readonly expected_paths=/tmp/convive-expected-attachment-paths

: > "$expected_paths"
object_count=0
total_bytes=0

fail() {
  echo 'Private attachment storage is inconsistent with database metadata.' >&2
  exit 1
}

while IFS="$(printf '\t')" read -r id status storage_key byte_size content_hash; do
  [ -n "$id" ] || continue

  echo "$id" | grep -Eq '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' || fail
  echo "$storage_key" | grep -Eq "^(quarantine|available)/${id}$" || fail
  echo "$byte_size" | grep -Eq '^[1-9][0-9]*$' || fail
  echo "$content_hash" | grep -Eq '^[0-9a-f]{64}$' || fail

  relative_path="$storage_key"
  primary_path="$storage_root/$relative_path"

  case "$status" in
    deleted)
      [ ! -e "$primary_path" ] || fail
      [ ! -e "$storage_root/quarantine/$id" ] || fail
      [ ! -e "$storage_root/available/$id" ] || fail
      continue
      ;;
    scanning)
      alternate_path="$storage_root/available/$id"
      if [ -f "$primary_path" ] && [ ! -e "$alternate_path" ]; then
        :
      elif [ ! -e "$primary_path" ] && [ -f "$alternate_path" ]; then
        relative_path="available/$id"
        primary_path="$alternate_path"
      else
        fail
      fi
      ;;
    deletion_pending)
      if [ ! -e "$primary_path" ]; then
        continue
      fi
      ;;
    quarantined|available|rejected)
      ;;
    *)
      fail
      ;;
  esac

  [ -f "$primary_path" ] && [ ! -L "$primary_path" ] || fail
  [ "$(stat -c '%s' "$primary_path")" = "$byte_size" ] || fail
  [ "$(sha256sum "$primary_path" | cut -d ' ' -f 1)" = "$content_hash" ] || fail

  printf '%s\n' "$relative_path" >> "$expected_paths"
  object_count=$((object_count + 1))
  total_bytes=$((total_bytes + byte_size))
done

find "$storage_root/quarantine" "$storage_root/available" -mindepth 1 -maxdepth 1 -print \
  | while IFS= read -r object_path; do
      [ -f "$object_path" ] && [ ! -L "$object_path" ] || fail
      relative_path="${object_path#"$storage_root"/}"
      grep -Fxq "$relative_path" "$expected_paths" || fail
    done

[ "$(wc -l < "$expected_paths" | tr -d ' ')" = "$object_count" ] || fail
printf 'objects=%s;bytes=%s\n' "$object_count" "$total_bytes"
