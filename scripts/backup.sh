#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
umask 077
backup_dir="backups/$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$backup_dir"
docker compose exec -T db pg_dump -U solvet -d solvet -Fc > "$backup_dir/database.dump"
docker compose exec -T app tar -czf - -C /var/www/html/storage app > "$backup_dir/documents.tar.gz"
cp .env "$backup_dir/environment.env"
cp VERSION "$backup_dir/VERSION"
printf 'Respaldo creado: %s\n' "$backup_dir"
