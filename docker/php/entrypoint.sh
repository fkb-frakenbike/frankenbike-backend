#!/usr/bin/env sh
set -e
if [ -n "$DATABASE_URL" ]; then
  echo "Waiting for database…"
  ATTEMPTS=30
  until php -r "try { new PDO(getenv('DATABASE_URL')); } catch (\Throwable $e) { exit(1); }" >/dev/null 2>&1; do
    ATTEMPTS=$((ATTEMPTS - 1))
    [ "$ATTEMPTS" -le 0 ] && echo "DB not reachable, continuing…" && break
    sleep 2
  done
fi
# Optional migrations (disabled by default)
# php bin/console doctrine:migrations:migrate --no-interaction --env=prod || true

php bin/console cache:clear --env=prod --no-warmup || true
php bin/console cache:warmup --env=prod || true
exec "$@"