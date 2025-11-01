#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/fkb-app"
COMPOSE_PROD="$APP_DIR/docker-compose.prod.yml"
COMPOSE_PROD_OVR="$APP_DIR/docker-compose.prod.override.yml"

FILES=(-f "$COMPOSE_PROD")
[ -f "$COMPOSE_PROD_OVR" ] && FILES+=(-f "$COMPOSE_PROD_OVR")

cd "$APP_DIR"

echo "[deploy] Build images…"
docker compose "${FILES[@]}" pull || true
docker compose "${FILES[@]}" build --pull

if [ "${BACKUP:-0}" = "1" ]; then
  echo "[deploy] DB backup…"
  mkdir -p "$APP_DIR/backups"
  TS=$(date +%Y%m%d-%H%M%S)
  if docker ps --format '{{.Names}}' | grep -q '^fkb-php$'; then
    docker exec fkb-php sh -lc 'which mysqldump >/dev/null 2>&1' && \
      docker exec fkb-php sh -lc 'mysqldump -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"' \
        > "$APP_DIR/backups/db-$TS.sql" || echo "[deploy] mysqldump not available"
  else
    echo "[deploy] fkb-php not found; skipping mysqldump"
  fi
fi

echo "[deploy] Up…"
docker compose "${FILES[@]}" up -d --remove-orphans

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  echo "[deploy] Doctrine migrations…"
  if docker ps --format '{{.Names}}' | grep -q '^fkb-php$'; then
    docker exec -e APP_ENV=prod -e APP_DEBUG=0 -t fkb-php \
      php bin/console doctrine:migrations:migrate --no-interaction --env=prod
  else
    echo "[deploy] fkb-php not running; cannot migrate"; exit 1
  fi
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
