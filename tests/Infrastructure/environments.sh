#!/usr/bin/env bash
# Isolated contract test; never invokes a Docker daemon or installation database.
set -euo pipefail
sv_repo=$(cd "$(dirname "$0")/../.." && pwd)
sv_tmp=$(mktemp -d)
trap 'rm -rf "$sv_tmp"' EXIT
mkdir -p "$sv_tmp/scripts" "$sv_tmp/bin"
cp "$sv_repo/scripts/environment.sh" "$sv_tmp/scripts/"
cp "$sv_repo/.env.example" "$sv_repo/VERSION" "$sv_tmp/"
cat > "$sv_tmp/bin/docker" <<'MOCK'
#!/usr/bin/env bash
printf '%s\n' "$*" >> "$SV_CALLS"
[ -z "${COMPOSE_FILE:-}" ] || exit 8
[ -z "${APP_PORT:-}" ] || exit 8
[[ "${COMPOSE_PROJECT_NAME:-}" = solvet-suite-* ]] || exit 8
[[ "${SOLVET_ENV_FILE:-}" = deploy/environments/*.env ]] || exit 8
if [[ "$*" = *'migrate --force'* && "${SV_FAIL_MIGRATE:-0}" = 1 ]]; then exit 9; fi
MOCK
cat > "$sv_tmp/bin/git" <<'MOCK'
#!/usr/bin/env bash
printf '%040d\n' 1
MOCK
chmod +x "$sv_tmp/bin/"*
export PATH="$sv_tmp/bin:$PATH" SV_CALLS="$sv_tmp/calls"
cd "$sv_tmp"
for sv_env in development testing production; do
  bash scripts/environment.sh "$sv_env" init >/dev/null
  [ "$(stat -c '%a' "deploy/environments/$sv_env.env")" = 600 ]
  cp "deploy/environments/$sv_env.env" "$sv_env.initial"
  if bash scripts/environment.sh "$sv_env" init >/dev/null 2>&1; then exit 1; fi
  cmp "deploy/environments/$sv_env.env" "$sv_env.initial"
  COMPOSE_PROJECT_NAME=wrong COMPOSE_FILE=wrong APP_PORT=1 bash scripts/environment.sh "$sv_env" setup >/dev/null
  grep -q -- "--env-file deploy/environments/$sv_env.env --project-name solvet-suite-$sv_env -f compose.yml" "$SV_CALLS"
  [ -f "deploy/environments/$sv_env.commit" ]
  bash scripts/environment.sh "$sv_env" update >/dev/null
  bash scripts/environment.sh "$sv_env" check
  bash scripts/environment.sh "$sv_env" status >/dev/null
  bash scripts/environment.sh "$sv_env" artisan solvet:user
  bash scripts/environment.sh "$sv_env" stop
  [ "$(find "backups/$sv_env" -name environment.env | wc -l)" = 1 ]
done
# Distinct keys, ports and sessions; no legacy .env is ever created/read.
[ ! -e .env ]
[ "$(sed -n 's/^APP_KEY=//p' deploy/environments/*.env | sort -u | wc -l)" = 3 ]
[ "$(sed -n 's/^DB_PASSWORD=//p' deploy/environments/*.env | sort -u | wc -l)" = 3 ]
[ "$(sed -n 's/^APP_PORT=//p' deploy/environments/*.env | sort -u | wc -l)" = 3 ]
grep -q -- '-f compose.dev.yml' "$SV_CALLS"
if grep 'solvet-suite-testing\|solvet-suite-production' "$SV_CALLS" | grep -q -- '-f compose.dev.yml'; then exit 1; fi
for sv_cmd in migrate:fresh migrate:reset db:wipe solvet:demo; do
  if bash scripts/environment.sh production artisan "$sv_cmd" >/dev/null 2>&1; then exit 1; fi
done
bash scripts/environment.sh testing test
# The automated test job has its own project, not the persistent testing stack.
grep -q -- '--project-name solvet-suite-testing-checks' "$SV_CALLS"
grep -q -- 'run --rm --no-deps app php vendor/bin/phpunit' "$SV_CALLS"
: > "$SV_CALLS"
if SV_FAIL_MIGRATE=1 bash scripts/environment.sh testing update >/dev/null 2>&1; then exit 1; fi
if grep -q 'artisan up' "$SV_CALLS"; then echo 'Unexpected reopening after failed migration'; exit 1; fi
sed -i 's/^APP_DEBUG=false/APP_DEBUG=true/' deploy/environments/production.env
if bash scripts/environment.sh production status >/dev/null 2>&1; then exit 1; fi
sed -i 's/^COMPOSE_PROJECT_NAME=.*/COMPOSE_PROJECT_NAME=solvet-suite/' deploy/environments/testing.env
if bash scripts/environment.sh testing setup >/dev/null 2>&1; then exit 1; fi
printf 'OK: isolated environments, lifecycle, backup, fail-closed update, secret preservation and production guards.\n'
