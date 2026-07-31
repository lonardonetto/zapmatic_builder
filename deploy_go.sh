#!/bin/bash
# ============================================================
# deploy_go.sh - Deploy de mudancas no Gateway Go (whatsmeow)
# 
# O updater PHP NAO toca na pasta app_zapmatic_whatsmeow_api
# (protegida). Para atualizar o codigo Go dos sistemas, use
# este script: ele copia o source, recompila o binario e
# reinicia o servico em cada sistema.
#
# Uso: sudo bash deploy_go.sh
# ============================================================

MAIN_SRC="/www/wwwroot/app_zapmatic_app/app_zapmatic_whatsmeow_api"
GO_BIN="/usr/local/go/bin/go"

# Sistemas: [diretorio] [servico] [porta]
SYSTEMS=(
    "/www/wwwroot/app_zapmatic_app|zapmatic-whatsmeow|8090"
    "/www/wwwroot/renovo_app|zapmatic-whatsmeow-renovo|8093"
    "/www/wwwroot/app_paulo_app|zapmatic-whatsmeow-paulo|8091"
    "/www/wwwroot/app_elias_app|zapmatic-whatsmeow-elias|8092"
    "/www/wwwroot/app.astroscomunicacaodigital.com|zapmatic-whatsmeow-astros|8094"
)

echo "=============================================="
echo "Deploy Go Gateway (whatsmeow) para todos"
echo "Fonte: $MAIN_SRC"
echo "=============================================="

for entry in "${SYSTEMS[@]}"; do
    IFS='|' read -r DIR SVC PORT <<< "$entry"
    NAME=$(basename "$DIR")
    
    echo ""
    echo "=== $NAME ($SVC, porta $PORT) ==="
    
    # 1. Parar o servico
    sudo systemctl stop "$SVC" 2>/dev/null
    sleep 1
    
    # 2. Copiar SOURCE (cmd, internal, vendor, go.mod, go.sum)
    #    PRESERVANDO: config.json, storage (sessoes), logs, binario
    sudo rsync -avz \
        --exclude='config.json' \
        --exclude='storage' \
        --exclude='logs' \
        --exclude='zapmatic-whatsmeow' \
        --exclude='*.bak*' \
        --exclude='.gitignore' \
        --exclude='config.json.example' \
        --exclude='start.sh' \
        "$MAIN_SRC/cmd/" "$DIR/app_zapmatic_whatsmeow_api/cmd/" 2>/dev/null
    sudo rsync -avz \
        --exclude='config.json' \
        --exclude='storage' \
        --exclude='logs' \
        --exclude='zapmatic-whatsmeow' \
        --exclude='*.bak*' \
        --exclude='.gitignore' \
        --exclude='config.json.example' \
        --exclude='start.sh' \
        "$MAIN_SRC/internal/" "$DIR/app_zapmatic_whatsmeow_api/internal/" 2>/dev/null
    sudo rsync -avz "$MAIN_SRC/vendor/" "$DIR/app_zapmatic_whatsmeow_api/vendor/" 2>/dev/null
    sudo cp "$MAIN_SRC/go.mod" "$DIR/app_zapmatic_whatsmeow_api/go.mod"
    sudo cp "$MAIN_SRC/go.sum" "$DIR/app_zapmatic_whatsmeow_api/go.sum"
    echo "  ✅ Source copiado (config.json/sessões preservados)"
    
    # 3. Compilar binario novo (com fix LID via -mod=vendor)
    cd "$DIR/app_zapmatic_whatsmeow_api"
    sudo chmod 777 "$DIR/app_zapmatic_whatsmeow_api"
    sudo -u ubuntu "$GO_BIN" build -mod=vendor -o /tmp/deploy_go_bin ./cmd/server/ 2>&1 | tail -2
    if [ -f /tmp/deploy_go_bin ]; then
        sudo cp /tmp/deploy_go_bin "$DIR/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow"
        sudo chown www:www "$DIR/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow"
        sudo chmod +x "$DIR/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow"
        rm -f /tmp/deploy_go_bin
        echo "  ✅ Binario recompilado ($(ls -la $DIR/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow | awk '{print $5}') bytes)"
    else
        echo "  ❌ COMPILACAO FALHOU - binario antigo mantido"
    fi
    
    # 4. Iniciar servico
    sudo systemctl start "$SVC" 2>/dev/null
    sleep 4
    
    # 5. Verificar
    STATUS=$(sudo systemctl is-active "$SVC")
    CONN=$(curl -s --connect-timeout 3 "http://127.0.0.1:$PORT/status" 2>/dev/null | python3 -c "
import sys, json
try:
    d = json.load(sys.stdin)
    instances = d.get('instances', d) if isinstance(d, dict) else d
    print(len(instances))
except: print('?')
" 2>/dev/null)
    echo "  Servico: $STATUS | Sessoes: $CONN"
done

echo ""
echo "=============================================="
echo "Deploy Go concluido!"
echo "=============================================="
