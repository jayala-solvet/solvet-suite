#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
umask 077
backup_recipient=$(sed -n 's/^BACKUP_AGE_RECIPIENT=//p' .env)
if [ -n "$backup_recipient" ]; then command -v age >/dev/null || { echo 'Falta age para cifrar el respaldo.'; exit 1; }; fi
backup_dir="backups/$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$backup_dir"
docker compose exec -T db pg_dump -U solvet -d solvet -Fc > "$backup_dir/database.dump"
docker compose exec -T app tar -czf - -C /var/www/html/storage app > "$backup_dir/documents.tar.gz"
cp .env "$backup_dir/environment.env"
cp VERSION "$backup_dir/VERSION"
if [ -n "$backup_recipient" ]; then bash scripts/encrypt-backup.sh "$backup_dir" "$backup_recipient"; fi
printf 'Respaldo creado: %s\n' "$backup_dir"
