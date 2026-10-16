#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
sv_profile=${1:-}
sv_action=${2:-status}
case "$sv_profile" in development|testing|production) ;; *) echo 'Uso: bash scripts/environment.sh development|testing|production init|setup|update|backup|status|artisan|test|check|stop'; exit 1;; esac
shift "$(( $# >= 2 ? 2 : $# ))"
sv_file="deploy/environments/$sv_profile.env"
sv_project="solvet-suite-$sv_profile"
if [ "$sv_action" = init ]; then
  [ ! -e "$sv_file" ] || { echo "Ya existe $sv_file; no se regeneran claves."; exit 1; }
  command -v openssl >/dev/null
  umask 077
  mkdir -p deploy/environments
  case "$sv_profile" in development) sv_port=8089; sv_app_env=local;; testing) sv_port=8090; sv_app_env=staging;; production) sv_port=8091; sv_app_env=production;; esac
  cp .env.example "$sv_file"
  sv_db_secret=$(openssl rand -hex 32)
  sv_admin_secret=$(openssl rand -hex 32)
  sv_app_secret=$(openssl rand -base64 32 | tr -d '\n')
  sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$sv_db_secret|;s|^APP_KEY=.*|APP_KEY=base64:$sv_app_secret|;s|^COMPOSE_PROJECT_NAME=.*|COMPOSE_PROJECT_NAME=$sv_project|;s|^APP_PORT=.*|APP_PORT=$sv_port|;s|^APP_URL=.*|APP_URL=http://localhost:$sv_port|;s|^APP_ENV=.*|APP_ENV=$sv_app_env|" "$sv_file"
  sed -i 's/^DB_USERNAME=.*/DB_USERNAME=solvet_app/' "$sv_file"
  printf '\nDB_ADMIN_PASSWORD=%s\n' "$sv_admin_secret" >> "$sv_file"
  printf '\nSESSION_COOKIE=solvet_%s_session\n' "$sv_profile" >> "$sv_file"
  chmod 600 "$sv_file"
  printf 'Creado %s. Ajustá APP_URL y BIND_ADDRESS antes de instalar. Puerto propuesto: %s.\n' "$sv_file" "$sv_port"
  exit
fi
[ -f "$sv_file" ] || { echo "Primero ejecutá: bash scripts/environment.sh $sv_profile init"; exit 1; }
# Read only fixed scalar fields. Never source an environment file as shell code.
sv_get() { sed -n "s/^$1=//p" "$sv_file"; }
[ "$(sv_get COMPOSE_PROJECT_NAME)" = "$sv_project" ] || { echo 'El nombre de proyecto no coincide con el entorno. No se inicia.'; exit 1; }
[ "$(sv_get SESSION_COOKIE)" = "solvet_${sv_profile}_session" ] || { echo 'La cookie debe ser exclusiva del entorno.'; exit 1; }
[ -n "$(sv_get DB_PASSWORD)" ] && [[ "$(sv_get APP_KEY)" = base64:?* ]] || { echo 'Faltan claves del entorno.'; exit 1; }
if [ "$sv_profile" = production ]; then
  [ "$(sv_get APP_ENV)" = production ] && [ "$(sv_get APP_DEBUG)" = false ] || { echo 'Producción exige APP_ENV=production y APP_DEBUG=false.'; exit 1; }
fi
# Explicit project/files; exported interpolation variables cannot redirect the deployment.
sv_compose() (
  unset COMPOSE_FILE COMPOSE_PROFILES COMPOSE_ENV_FILES COMPOSE_PROJECT_NAME APP_VERSION APP_PORT BIND_ADDRESS DB_PASSWORD DB_ADMIN_PASSWORD DB_USERNAME
  export SOLVET_ENV_FILE="$sv_file" COMPOSE_PROJECT_NAME="$sv_project"
  sv_cmd=(docker compose --env-file "$sv_file" --project-name "$sv_project" -f compose.yml)
  if [ "$sv_profile" = development ]; then sv_cmd+=(-f compose.dev.yml); fi
  "${sv_cmd[@]}" "$@"
)
sv_backup() {
  umask 077
  sv_recipient=$(sv_get BACKUP_AGE_RECIPIENT)
  if [ -n "$sv_recipient" ]; then command -v age >/dev/null || { echo 'Falta age en el host para cifrar el respaldo.'; exit 1; }; fi
  sv_backup_dir="backups/$sv_profile/$(date -u +%Y%m%dT%H%M%SZ)-$$"
  mkdir -p "$sv_backup_dir"
  sv_compose exec -T db pg_dump -U solvet -d solvet -Fc > "$sv_backup_dir/database.dump"
  sv_compose exec -T app tar -czf - -C /var/www/html/storage app > "$sv_backup_dir/documents.tar.gz"
  cp "$sv_file" "$sv_backup_dir/environment.env"
  git rev-parse HEAD > "$sv_backup_dir/CHECKOUT_COMMIT"
  if [ -f "deploy/environments/$sv_profile.commit" ]; then
    cp "deploy/environments/$sv_profile.commit" "$sv_backup_dir/COMMIT"
  else
    printf 'unknown: no installed commit marker\n' > "$sv_backup_dir/COMMIT"
  fi
  cp VERSION "$sv_backup_dir/VERSION"
  if [ -n "$sv_recipient" ]; then bash scripts/encrypt-backup.sh "$sv_backup_dir" "$sv_recipient"; fi
  printf 'Respaldo: %s\n' "$sv_backup_dir"
}
case "$sv_action" in
  setup)
    if [ "$sv_profile" = production ]; then git diff --quiet && git diff --cached --quiet || { echo 'Producción requiere código sin cambios locales.'; exit 1; }; fi
    sv_compose build app web
    sv_compose up -d db
    sv_compose run --rm app php artisan migrate --force
    sv_compose run --rm app php artisan solvet:install
    sv_compose up -d
    git rev-parse HEAD > "deploy/environments/$sv_profile.commit"
    echo "Instalado $sv_profile. Creá usuario: bash scripts/environment.sh $sv_profile artisan solvet:user"
    ;;
  update)
    if [ "$sv_profile" = production ]; then git diff --quiet && git diff --cached --quiet || { echo 'Producción requiere código sin cambios locales.'; exit 1; }; fi
    sv_compose build app web
    sv_compose exec -T app php artisan down --retry=60
    echo 'En mantenimiento; un fallo conserva cerrado el entorno.'
    sv_backup
    sv_compose run --rm app php artisan migrate --force
    sv_compose up -d --force-recreate app web worker
    sv_compose exec -T app php artisan optimize:clear
    sv_compose exec -T app php artisan up
    git rev-parse HEAD > "deploy/environments/$sv_profile.commit"
    echo "Actualizado $sv_profile. Comprobá status, /up y el flujo comercial."
    ;;
  test)
    # Dedicated project and image; never reuse the profile's storage or database.
    sv_project="$sv_project-checks"
    sv_profile=development
    sv_compose build app
    sv_compose run --rm --no-deps app php vendor/bin/phpunit
    ;;
  backup) sv_backup;;
  status) sv_compose ps; if [ -f "deploy/environments/$sv_profile.commit" ]; then printf 'Commit instalado registrado: '; cat "deploy/environments/$sv_profile.commit"; fi;;
  check) sv_compose config --quiet;;
  artisan)
    [ "$#" -gt 0 ] || { echo 'Falta comando Artisan.'; exit 1; }
    case "${1}" in migrate:fresh|migrate:reset|db:wipe|solvet:demo)
      [ "$sv_profile" != production ] || { echo 'Comando de borrado/demo bloqueado en producción.'; exit 1; };;
    esac
    sv_compose exec app php artisan "$@";;
  stop) sv_compose stop;;
  *) echo 'Acción desconocida. Usá init/setup/update/backup/status/artisan/test/check/stop.'; exit 1;;
esac
