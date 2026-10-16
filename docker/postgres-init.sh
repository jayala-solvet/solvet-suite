#!/usr/bin/env bash
# Runs only when PostgreSQL initializes an empty volume. Legacy volumes are untouched.
set -euo pipefail
if [ "$APP_DB_USERNAME" = "$POSTGRES_USER" ]; then exit 0; fi
[[ "$APP_DB_USERNAME" =~ ^[a-z][a-z0-9_]{1,40}$ ]] || { echo 'Invalid application database role'; exit 1; }
psql --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" --set ON_ERROR_STOP=1 <<'SQL'
\getenv app_user APP_DB_USERNAME
\getenv app_password APP_DB_PASSWORD
CREATE ROLE :"app_user" LOGIN PASSWORD :'app_password' NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS;
GRANT CONNECT ON DATABASE solvet TO :"app_user";
GRANT USAGE, CREATE ON SCHEMA public TO :"app_user";
SQL
