#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
# Ejecutar tras traer el código de una versión revisada; no modifica Git.
docker compose build app web
docker compose exec -T app php artisan down --retry=60
printf 'Sistema en mantenimiento. Si falla la actualización, se mantendrá cerrado.\n'
bash scripts/backup.sh
docker compose run --rm app php artisan migrate --force
docker compose up -d --force-recreate app web
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan up
printf 'Actualización aplicada. Revisá /up e ingresá para comprobar la operación.\n'
