#!/bin/bash
# Blocks edits to tracked files on main and develop: work happens on a feature or hotfix branch.
# The branch checked is the one of the repository holding the edited file (so editing a file of a
# feature worktree is fine whatever branch the main checkout is on). Files that git ignores
# (docs-private/, local env files...) and files outside any repository are allowed.
FILE_PATH=$(jq -r '.tool_input.file_path // .tool_input.filePath // empty' 2>/dev/null)

DIR=.
[ -n "$FILE_PATH" ] && DIR=$(dirname "$FILE_PATH")
# the file or its folder may not exist yet: climb to an existing folder
while [ ! -d "$DIR" ] && [ "$DIR" != "/" ] && [ "$DIR" != "." ]; do DIR=$(dirname "$DIR"); done

git -C "$DIR" rev-parse --git-dir >/dev/null 2>&1 || exit 0
if [ -n "$FILE_PATH" ] && git -C "$DIR" check-ignore -q -- "$FILE_PATH"; then
  exit 0
fi

BRANCH=$(git -C "$DIR" branch --show-current 2>/dev/null || echo "")
case "$BRANCH" in
  main|master|develop)
    echo "Edit blocked on '$BRANCH'. Create a feature/ or hotfix/ branch first (see CONTRIBUTING.md)." >&2
    exit 2 ;;
esac
exit 0
