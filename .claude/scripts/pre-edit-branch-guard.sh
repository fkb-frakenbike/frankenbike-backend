#!/bin/bash
# Blocks edits on main and develop: work happens on a feature or hotfix branch.
BRANCH=$(git branch --show-current 2>/dev/null || echo "")
case "$BRANCH" in
  main|master|develop)
    echo "Edit blocked on '$BRANCH'. Create a feature/ or hotfix/ branch first (see CONTRIBUTING.md)." >&2
    exit 2 ;;
esac
exit 0
