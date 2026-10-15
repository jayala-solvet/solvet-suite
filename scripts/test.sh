#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
COMPOSE_PROJECT_NAME=solvet-suite-tests docker compose -f compose.yml -f compose.dev.yml build app
COMPOSE_PROJECT_NAME=solvet-suite-tests docker compose -f compose.yml -f compose.dev.yml run --rm --no-deps app php vendor/bin/phpunit
