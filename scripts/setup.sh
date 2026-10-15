#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
command -v docker >/dev/null || { echo 'Instalá Docker y Docker Compose v2.'; exit 1; }
command -v openssl >/dev/null || { echo 'Instalá openssl para generar las claves locales.'; exit 1; }
docker compose version >/dev/null
if [ ! -f .env ]; then
  umask 077
  cp .env.example .env
  db_secret=$(openssl rand -hex 32)
  app_secret=$(openssl rand -base64 32 | tr -d '\n')
  sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$db_secret|;s|^APP_KEY=.*|APP_KEY=base64:$app_secret|" .env
fi
if ! grep -q '^APP_KEY=base64:.' .env || ! grep -q '^DB_PASSWORD=.' .env; then
 echo 'Completá APP_KEY y DB_PASSWORD en .env; no se regeneran claves existentes.'; exit 1
fi
docker compose build app web
docker compose up -d db
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan solvet:install
docker compose up -d
printf '\nBase instalada. Creá tu usuario:\n  docker compose exec app php artisan solvet:user\n\nDespués abrí http://localhost:8088/app (o el puerto configurado en .env).\n'
