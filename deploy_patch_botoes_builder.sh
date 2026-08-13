#!/usr/bin/env bash
set -euo pipefail

SRC="/www/wwwroot/app_zapmatic_app"
BACKUP_ROOT="/www/wwwroot/app_zapmatic_app/backups/patch_buttons_$(date +%Y%m%d_%H%M%S)"

TARGETS=(
  "/www/wwwroot/app_paulo_app"
  "/www/wwwroot/app_elias_app"
  "/www/wwwroot/renovo_app"
  "/www/wwwroot/app.astroscomunicacaodigital.com"
)

FILES=(
  "inc/core/Bot_builder/Controllers/Bot_builder.php"
  "inc/core/Bot_builder/Assets/js/bot_builder.js"
  "inc/core/Whatsapp_button_template/Controllers/Whatsapp_button_template.php"
  "inc/core/Whatsapp_button_template/Views/update.php"
  "inc/core/Whatsapp_button_template/Language/Language.php"
  "app_zapmatic_api/waziper/waziper.js"
)

if [[ "${EUID}" -ne 0 ]]; then
  echo "Execute como root no terminal do aaPanel."
  exit 1
fi

mkdir -p "$BACKUP_ROOT"

echo "Origem: $SRC"
echo "Backup: $BACKUP_ROOT"
echo ""

for TARGET in "${TARGETS[@]}"; do
  if [[ ! -d "$TARGET" ]]; then
    echo "Pulando destino inexistente: $TARGET"
    continue
  fi

  NAME="$(basename "$TARGET")"
  echo "========================================"
  echo "Atualizando somente patch de botões em: $TARGET"
  echo "========================================"

  for FILE in "${FILES[@]}"; do
    if [[ ! -f "$SRC/$FILE" ]]; then
      echo "Origem não encontrada: $SRC/$FILE"
      continue
    fi

    mkdir -p "$BACKUP_ROOT/$NAME/$(dirname "$FILE")"
    mkdir -p "$TARGET/$(dirname "$FILE")"

    if [[ -f "$TARGET/$FILE" ]]; then
      cp -a "$TARGET/$FILE" "$BACKUP_ROOT/$NAME/$FILE"
    fi

    cp -a "$SRC/$FILE" "$TARGET/$FILE"
    echo "OK: $FILE"
  done

  echo ""
done

echo "Patch concluído."
echo "Backup salvo em: $BACKUP_ROOT"
echo ""
echo "Se alguma instância tiver PM2 próprio da API, reinicie apenas ela, exemplo:"
echo "pm2 list"
echo "pm2 restart <nome-da-api-da-instancia>"
