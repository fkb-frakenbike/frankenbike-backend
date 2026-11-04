#!/bin/bash
# After a PHP edit: format with php-cs-fixer; if a test file, run it. Skips quietly without a host php.
# ponytail: tests run in the fkb-php container for some devs; extend with docker exec if that becomes the norm.
command -v jq >/dev/null || exit 0
FILE_PATH=$(jq -r '.tool_input.file_path // .tool_input.filePath // empty')
case "$FILE_PATH" in *.php) ;; *) exit 0 ;; esac
command -v php >/dev/null || exit 0

[ -x vendor/bin/php-cs-fixer ] && php vendor/bin/php-cs-fixer fix "$FILE_PATH" --quiet >&2

case "$FILE_PATH" in
  */tests/*Test.php)
    [ -f vendor/bin/phpunit ] && php vendor/bin/phpunit "$FILE_PATH" 2>&1 | tail -30 >&2 ;;
esac
exit 0
