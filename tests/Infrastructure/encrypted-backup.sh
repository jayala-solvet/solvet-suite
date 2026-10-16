#!/usr/bin/env bash
# Optional integration check: uses real age with disposable data and keys.
set -euo pipefail
sv_repo=$(cd "$(dirname "$0")/../.." && pwd)
command -v age >/dev/null && command -v age-keygen >/dev/null || { echo 'Requiere age y age-keygen.'; exit 1; }
sv_tmp=$(mktemp -d)
trap 'rm -rf "$sv_tmp"' EXIT
mkdir -p "$sv_tmp/scripts" "$sv_tmp/backups/qa"
cp "$sv_repo/scripts/encrypt-backup.sh" "$sv_tmp/scripts/"
cd "$sv_tmp"
umask 077
age-keygen -o identity.txt >/dev/null 2>&1
sv_recipient=$(age-keygen -y identity.txt)
for sv_file in database.dump documents.tar.gz environment.env VERSION;do printf 'Contenido ficticio para %s\n' "$sv_file" > "backups/qa/$sv_file";done
bash scripts/encrypt-backup.sh backups/qa "$sv_recipient" >/dev/null
[ -f backups/qa/backup.tar.gz.age ] && [ ! -f backups/qa/environment.env ]
age --decrypt -i identity.txt backups/qa/backup.tar.gz.age | tar -xz
[ "$(cat environment.env)" = 'Contenido ficticio para environment.env' ]
# Failed encryption retains the original plaintext in its protected temporary folder.
mkdir -p backups/failure
for sv_file in database.dump documents.tar.gz environment.env VERSION;do printf 'QA\n' > "backups/failure/$sv_file";done
if bash scripts/encrypt-backup.sh backups/failure invalid-key >/dev/null 2>&1;then exit 1;fi
[ -f backups/failure/environment.env ]
printf 'OK: age encrypt/decrypt round trip; plaintext removed only after success.\n'
