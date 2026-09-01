#!/usr/bin/env bash

set -euo pipefail

readonly workflow_path='.github/workflows/scheduled-editorial-publication.yaml'

require_fragment() {
  local fragment="$1"

  if ! grep --fixed-strings --quiet -- "$fragment" "$workflow_path"; then
    echo "The scheduled editorial workflow is missing: $fragment" >&2
    exit 1
  fi
}

forbid_fragment() {
  local fragment="$1"

  if grep --fixed-strings --quiet -- "$fragment" "$workflow_path"; then
    echo "The scheduled editorial workflow must not contain: $fragment" >&2
    exit 1
  fi
}

require_fragment 'EDITORIAL_BRANCH: editorial/scheduled-publication'
require_fragment 'Reuse an open editorial publication review'
require_fragment 'gh pr list'
require_fragment 'git push --force-with-lease origin "HEAD:$EDITORIAL_BRANCH"'
require_fragment 'gh pr create'
require_fragment '--head "$EDITORIAL_BRANCH"'
require_fragment '--label documentation'
forbid_fragment 'git push origin HEAD:main'

echo 'Scheduled editorial workflow follows the protected-branch publication path.'
