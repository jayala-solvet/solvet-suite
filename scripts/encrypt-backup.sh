#!/usr/bin/env bash
# Encrypt a freshly created backup with a public recipient; retain plaintext on failure.
set -euo pipefail
cd "$(dirname "$0")/.."
sv_dir=${1:?Falta carpeta de respaldo}
sv_recipient=${2:?Falta destinatario age}
case "$sv_dir" in backups/*) ;; *) echo 'El respaldo debe estar dentro de backups.'; exit 1;; esac
command -v age >/dev/null || { echo 'Instalá age en el host antes de habilitar cifrado de respaldos.'; exit 1; }
[ -f "$sv_dir/database.dump" ] && [ -f "$sv_dir/environment.env" ] && [ -f "$sv_dir/documents.tar.gz" ] && [ -f "$sv_dir/VERSION" ] || exit 1
[ ! -e "$sv_dir/backup.tar.gz.age" ] || { echo 'Ya existe el respaldo cifrado; no se sobrescribe.'; exit 1; }
sv_backup_root=$(realpath backups)
sv_target=$(realpath "$sv_dir")
[[ "$sv_target" = "$sv_backup_root/"* ]] || { echo 'Ruta de respaldo fuera del directorio autorizado.'; exit 1; }
umask 077
sv_files=(database.dump documents.tar.gz environment.env VERSION)
for sv_meta in COMMIT CHECKOUT_COMMIT; do if [ -f "$sv_dir/$sv_meta" ]; then sv_files+=("$sv_meta"); fi; done
tar -czf - -C "$sv_dir" -- "${sv_files[@]}" | age --recipient "$sv_recipient" --output "$sv_dir/backup.tar.gz.age.partial"
mv -- "$sv_dir/backup.tar.gz.age.partial" "$sv_dir/backup.tar.gz.age"
# Remove only this backup's known temporary plaintext files, after encryption succeeds.
for sv_file in "${sv_files[@]}"; do rm -- "$sv_dir/$sv_file"; done
printf 'Respaldo cifrado: %s/backup.tar.gz.age\n' "$sv_dir"
