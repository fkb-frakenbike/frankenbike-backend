#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/fkb-app"
COMPOSE_PROD="$APP_DIR/docker-compose.prod.yml"
COMPOSE_PROD_OVR="$APP_DIR/docker-compose.prod.override.yml"

FILES=(-f "$COMPOSE_PROD")
[ -f "$COMPOSE_PROD_OVR" ] && FILES+=(-f "$COMPOSE_PROD_OVR")

cd "$APP_DIR"

echo "[deploy] Validate compose config…"
docker compose "${FILES[@]}" config >/dev/null

# Ensure host mountpoints exist (because /var/www/html is bind-mounted :ro)
mkdir -p "$APP_DIR/var" "$APP_DIR/vendor"

# Names of the volumes as they appear in 'docker volume ls'.
# With your compose files these resolve to: fkb-app_var_cache, fkb-app_vendor_cache
VAR_VOL="$(docker compose "${FILES[@]}" config | awk '/volumes:/ {invol=1; next} invol && $1=="var_cache:" {print "var_cache"; exit}')"
VENDOR_VOL="$(docker compose "${FILES[@]}" config | awk '/volumes:/ {invol=1; next} invol && $1=="vendor_cache:" {print "vendor_cache"; exit}')"

# Resolve to actual Docker volume names (project prefix)
VAR_VOL_NAME="$(docker compose "${FILES[@]}" ps -a >/dev/null 2>&1 || true; echo "$(basename "$(pwd)")")_${VAR_VOL:-var_cache}"
VENDOR_VOL_NAME="$(docker compose "${FILES[@]}" ps -a >/dev/null 2>&1 || true; echo "$(basename "$(pwd)")")_${VENDOR_VOL:-vendor_cache}"

echo "[deploy] Pull/build images…"
docker compose "${FILES[@]}" pull || true
docker compose "${FILES[@]}" build --pull php

echo "[deploy] Start/refresh containers…"
docker compose "${FILES[@]}" up -d --remove-orphans php nginx

# Detect the real uid/gid used by the PHP container (www-data inside the image)
PHP_UID="$(docker exec -t fkb-php sh -lc 'id -u' | tr -d '\r\n')"
PHP_GID="$(docker exec -t fkb-php sh -lc 'id -g' | tr -d '\r\n')"
echo "[deploy] Fix volume ownership (container uid:gid = ${PHP_UID}:${PHP_GID})…"

# Chown named volumes from the host using BusyBox to the *actual* uid/gid
docker run --rm -v "${VAR_VOL_NAME}:/mnt"    busybox sh -lc "chown -R ${PHP_UID}:${PHP_GID} /mnt || true"
docker run --rm -v "${VENDOR_VOL_NAME}:/mnt" busybox sh -lc "chown -R ${PHP_UID}:${PHP_GID} /mnt || true"

# Ensure directories exist and have sane perms from *inside* the container
docker exec -u root -t fkb-php sh -lc " \
  set -e; \
  mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/vendor; \
  chown -R ${PHP_UID}:${PHP_GID} /var/www/html/var /var/www/html/vendor; \
  find /var/www/html/var -type d -exec chmod 775 {} \; ; \
  find /var/www/html/var -type f -exec chmod 664 {} \; ; \
  chmod g+s /var/www/html/var/cache \
"

# Install vendors into the vendor named volume using the Composer image
# Code is :ro, vendor volume is writable, perfect for prod
echo "[deploy] Composer install (into vendor volume)…"
docker run --rm \
  -v "${APP_DIR}:/app:ro" \
  -v "${VENDOR_VOL_NAME}:/app/vendor" \
  -w /app \
  composer:2 \
  install --no-dev --prefer-dist --no-interaction --classmap-authoritative --no-progress --no-ansi --no-scripts

# Warm up Symfony cache (now that vendor exists and volumes are writable)
echo "[deploy] Symfony cache warmup…"
docker exec -t fkb-php php /var/www/html/bin/console cache:clear --env=prod
docker exec -t fkb-php php /var/www/html/bin/console cache:warmup --env=prod

# Optional DB backup (kept from your original script; requires client tools + DB_* envs)
if [ "${BACKUP:-0}" = "1" ]; then
  echo "[deploy] DB backup…"
  mkdir -p "$APP_DIR/backups"
  TS=$(date +%Y%m%d-%H%M%S)
  if docker ps --format '{{.Names}}' | grep -q '^fkb-php$'; then
    docker exec fkb-php sh -lc 'which mysqldump >/dev/null 2>&1' && \
      docker exec fkb-php sh -lc 'mysqldump -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"' \
        > "$APP_DIR/backups/db-$TS.sql" || echo "[deploy] mysqldump not available or DB_* not set"
  else
    echo "[deploy] fkb-php not found; skipping mysqldump"
  fi
fi

# Optional migrations — keep OFF until DB VM is aligned
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  echo "[deploy] Doctrine migrations…"
  if docker ps --format '{{.Names}}' | grep -q '^fkb-php$'; then
    docker exec -e APP_ENV=prod -e APP_DEBUG=0 -t fkb-php \
      php /var/www/html/bin/console doctrine:migrations:migrate --no-interaction --env=prod
  else
    echo "[deploy] fkb-php not running; cannot migrate"; exit 1
  fi
else
  echo "[deploy] Skipping DB migrations (RUN_MIGRATIONS=0)"
fi

echo "[deploy] Healthcheck…"
set +e
curl -fsS --max-time 10 http://127.0.0.1/api/_alive >/dev/null || \
curl -fsS --max-time 10 http://127.0.0.1/ >/dev/null
RC=$?
set -e
if [ "$RC" -ne 0 ]; then
  echo "[deploy] Healthcheck failed; tailing logs:"
  docker logs --tail=200 fkb-nginx || true
  docker logs --tail=200 fkb-php   || true
  exit 1
fi

echo "[deploy] OK"
