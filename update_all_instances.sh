#!/usr/bin/env bash
set -euo pipefail

SOURCE="/www/wwwroot/app_zapmatic_app"
BACKUP_ROOT="/www/backup_zapmatic_multi_update"
DATE_TAG="$(date +%Y%m%d_%H%M%S)"

TARGETS=(
  "/www/wwwroot/app_paulo_app"
  "/www/wwwroot/app_elias_app"
  "/www/wwwroot/renovo_app"
  "/www/wwwroot/app.astroscomunicacaodigital.com"
)

PRESERVE=(
  ".env"
  "app_zapmatic_api/config.js"
  "app_zapmatic_api/sessions"
  "app_zapmatic_api/files"
  "app_zapmatic_api/store"
  "writable"
)

RSYNC_EXCLUDES=(
  "--exclude=.env"
  "--exclude=app_zapmatic_api/config.js"
  "--exclude=app_zapmatic_api/sessions"
  "--exclude=app_zapmatic_api/files"
  "--exclude=app_zapmatic_api/store"
  "--exclude=writable"
  "--exclude=schema_diff.py"
  "--exclude=*_schema_incremental_REVIEW.sql"
  "--exclude=*.md"
  "--exclude=*.zip"
  "--exclude=.git"
)

if [[ "${EUID}" -ne 0 ]]; then
  echo "Execute como root pelo terminal do aaPanel."
  exit 1
fi

if [[ ! -d "${SOURCE}" ]]; then
  echo "Origem não encontrada: ${SOURCE}"
  exit 1
fi

mkdir -p "${BACKUP_ROOT}/${DATE_TAG}"

echo "Origem: ${SOURCE}"
echo "Backup: ${BACKUP_ROOT}/${DATE_TAG}"
echo ""

for TARGET in "${TARGETS[@]}"; do
  if [[ ! -d "${TARGET}" ]]; then
    echo "Pulando destino inexistente: ${TARGET}"
    continue
  fi

  NAME="$(basename "${TARGET}")"
  BACKUP_DIR="${BACKUP_ROOT}/${DATE_TAG}/${NAME}"
  mkdir -p "${BACKUP_DIR}"

  echo "========================================"
  echo "Atualizando: ${TARGET}"
  echo "Backup sensível: ${BACKUP_DIR}"
  echo "========================================"

  for ITEM in "${PRESERVE[@]}"; do
    if [[ -e "${TARGET}/${ITEM}" ]]; then
      mkdir -p "${BACKUP_DIR}/$(dirname "${ITEM}")"
      cp -a "${TARGET}/${ITEM}" "${BACKUP_DIR}/${ITEM}"
    fi
  done

  rsync -avz --no-times --omit-dir-times --delete "${RSYNC_EXCLUDES[@]}" "${SOURCE}/" "${TARGET}/"

  echo "OK: ${TARGET}"
  echo ""
done

echo "Atualização concluída. Reinicie os PM2 correspondentes se necessário:"
echo "pm2 list"
echo "pm2 restart <nome_do_processo>"
