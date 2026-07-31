#!/bin/bash
# ============================================================
# deploy_go.sh - Deploy de mudancas no Gateway Go (whatsmeow)
#
# Aplica mudancas no codigo Go (cmd/, internal/, vendor/) em TODOS
# os sistemas (5 locais + 7 remotos), recompila o binario e
# reinicia o servico. PRESERVA config.json, sessoes e logs.
#
# Uso: sudo bash deploy_go.sh
# ============================================================

MAIN_SRC="/www/wwwroot/app_zapmatic_app/app_zapmatic_whatsmeow_api"
GO_BIN="/usr/local/go/bin/go"
SSH_KEY="/home/ubuntu/.ssh/chave_zapmatic.key"

echo "=============================================="
echo "Deploy Go Gateway (whatsmeow) - TODOS os sistemas"
echo "Fonte: $MAIN_SRC"
echo "=============================================="

# Funcao: deploy para um sistema (local ou remoto)
deploy_system() {
    local name=$1
    local target=$2      # caminho local OU "user@ip:path"
    local svc=$3
    local port=$4
    local ssh_opts=$5    # "" para local, ou "sshpass -p ..." / "-i chave"
    local is_remote=$6   # "1" se remoto
    
    echo ""
    echo "=== $name (servico $svc, porta $port) ==="
    
    local REMOTE_CMD=""
    if [ "$is_remote" = "1" ]; then
        REMOTE_CMD="ssh -o StrictHostKeyChecking=no"
        if [ -n "$ssh_opts" ]; then
            REMOTE_CMD="sshpass -p '$ssh_opts' ssh -o StrictHostKeyChecking=no"
        else
            REMOTE_CMD="ssh -o StrictHostKeyChecking=no -i $SSH_KEY"
        fi
    fi
    
    # 1. Parar servico
    if [ "$is_remote" = "1" ]; then
        eval $REMOTE_CMD "$(echo $target | cut -d: -f1)" "sudo systemctl stop $svc 2>/dev/null" 2>/dev/null
    else
        sudo systemctl stop "$svc" 2>/dev/null
    fi
    sleep 1
    
    # 2. Copiar source Go (preservando config.json, storage, logs, binario)
    if [ "$is_remote" = "1" ]; then
        local REMOTE_USER_IP=$(echo $target | cut -d: -f1)
        local REMOTE_PATH=$(echo $target | cut -d: -f2-)
        rsync -avz -e "$REMOTE_CMD" \
            --exclude='config.json' --exclude='storage' --exclude='logs' \
            --exclude='zapmatic-whatsmeow' --exclude='*.bak*' \
            --exclude='.gitignore' --exclude='config.json.example' --exclude='start.sh' \
            "$MAIN_SRC/cmd/" "$REMOTE_USER_IP:$REMOTE_PATH/app_zapmatic_whatsmeow_api/cmd/" 2>&1 | tail -1
        rsync -avz -e "$REMOTE_CMD" \
            --exclude='config.json' --exclude='storage' --exclude='logs' \
            --exclude='zapmatic-whatsmeow' --exclude='*.bak*' \
            --exclude='.gitignore' --exclude='config.json.example' --exclude='start.sh' \
            "$MAIN_SRC/internal/" "$REMOTE_USER_IP:$REMOTE_PATH/app_zapmatic_whatsmeow_api/internal/" 2>&1 | tail -1
        rsync -avz -e "$REMOTE_CMD" "$MAIN_SRC/vendor/" "$REMOTE_USER_IP:$REMOTE_PATH/app_zapmatic_whatsmeow_api/vendor/" 2>&1 | tail -1
        eval $REMOTE_CMD "$REMOTE_USER_IP" "cp $MAIN_SRC/go.mod $REMOTE_PATH/app_zapmatic_whatsmeow_api/go.mod; cp $MAIN_SRC/go.sum $REMOTE_PATH/app_zapmatic_whatsmeow_api/go.sum; sudo chmod 777 $REMOTE_PATH/app_zapmatic_whatsmeow_api 2>/dev/null" 2>/dev/null
        # 3. Compilar no remoto
        eval $REMOTE_CMD "$REMOTE_USER_IP" "cd $REMOTE_PATH/app_zapmatic_whatsmeow_api && /usr/local/go/bin/go build -mod=vendor -o /tmp/deploy_go_bin ./cmd/server/ 2>&1 && sudo cp /tmp/deploy_go_bin zapmatic-whatsmeow && sudo chown www:www zapmatic-whatsmeow && sudo chmod +x zapmatic-whatsmeow && echo '  ✅ Binario recompilado' || echo '  ❌ COMPILACAO FALHOU'" 2>/dev/null
        # 4. Iniciar
        eval $REMOTE_CMD "$REMOTE_USER_IP" "sudo systemctl start $svc 2>/dev/null" 2>/dev/null
        sleep 4
        # 5. Verificar
        local STATUS=$(eval $REMOTE_CMD "$REMOTE_USER_IP" "sudo systemctl is-active $svc 2>/dev/null" 2>/dev/null)
        echo "  Servico: $STATUS"
    else
        rsync -avz \
            --exclude='config.json' --exclude='storage' --exclude='logs' \
            --exclude='zapmatic-whatsmeow' --exclude='*.bak*' \
            --exclude='.gitignore' --exclude='config.json.example' --exclude='start.sh' \
            "$MAIN_SRC/cmd/" "$target/app_zapmatic_whatsmeow_api/cmd/" 2>&1 | tail -1
        rsync -avz \
            --exclude='config.json' --exclude='storage' --exclude='logs' \
            --exclude='zapmatic-whatsmeow' --exclude='*.bak*' \
            --exclude='.gitignore' --exclude='config.json.example' --exclude='start.sh' \
            "$MAIN_SRC/internal/" "$target/app_zapmatic_whatsmeow_api/internal/" 2>&1 | tail -1
        rsync -avz "$MAIN_SRC/vendor/" "$target/app_zapmatic_whatsmeow_api/vendor/" 2>&1 | tail -1
        cp "$MAIN_SRC/go.mod" "$target/app_zapmatic_whatsmeow_api/go.mod"
        cp "$MAIN_SRC/go.sum" "$target/app_zapmatic_whatsmeow_api/go.sum"
        cd "$target/app_zapmatic_whatsmeow_api"
        sudo chmod 777 "$target/app_zapmatic_whatsmeow_api"
        sudo -u ubuntu "$GO_BIN" build -mod=vendor -o /tmp/deploy_go_bin ./cmd/server/ 2>&1 | tail -1
        if [ -f /tmp/deploy_go_bin ]; then
            sudo cp /tmp/deploy_go_bin "$target/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow"
            sudo chown www:www "$target/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow"
            sudo chmod +x "$target/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow"
            rm -f /tmp/deploy_go_bin
            echo "  ✅ Binario recompilado"
        else
            echo "  ❌ COMPILACAO FALHOU"
        fi
        sudo systemctl start "$svc" 2>/dev/null
        sleep 4
        echo "  Servico: $(sudo systemctl is-active $svc)"
    fi
}

# ==============================================
# SISTEMAS LOCAIS
# ==============================================
deploy_system "Main (8090)"    "/www/wwwroot/app_zapmatic_app" "zapmatic-whatsmeow" "8090" "" "0"
deploy_system "Renovo (8093)"  "/www/wwwroot/renovo_app" "zapmatic-whatsmeow-renovo" "8093" "" "0"
deploy_system "Paulo (8091)"   "/www/wwwroot/app_paulo_app" "zapmatic-whatsmeow-paulo" "8091" "" "0"
deploy_system "Elias (8092)"   "/www/wwwroot/app_elias_app" "zapmatic-whatsmeow-elias" "8092" "" "0"
deploy_system "Astros (8094)"  "/www/wwwroot/app.astroscomunicacaodigital.com" "zapmatic-whatsmeow-astros" "8094" "" "0"

# ==============================================
# SISTEMAS REMOTOS
# ==============================================
deploy_system "Kivozap (8095)"    "ubuntu@144.22.167.45:/www/wwwroot/app_abner_app"       "zapmatic-whatsmeow-kivozap"    "8095" "" "1"
deploy_system "AgenciaMCW (8096)" "ubuntu@144.22.167.45:/www/wwwroot/app_frank_agencia"   "zapmatic-whatsmeow-agenciamcw" "8096" "" "1"
deploy_system "Chatbut (8097)"    "ubuntu@144.22.167.45:/www/wwwroot/app_alex_pedidu_app" "zapmatic-whatsmeow-chatbut"    "8097" "" "1"
deploy_system "IaClicks (8098)"   "admin@45.148.29.92:/www/wwwroot/app_zapmatic_app"      "zapmatic-whatsmeow-iaclicks"   "8098" "Leonetto1982" "1"
deploy_system "Elite (8099)"      "admin@193.180.211.190:/www/wwwroot/elitecomunicacao.zapmatic.tec.br" "zapmatic-whatsmeow-elite" "8099" "Leonetto1982" "1"
deploy_system "PlusZap (8100)"    "admin@92.113.144.161:/www/wwwroot/app_zapmatic_app"    "zapmatic-whatsmeow-pluszap"    "8100" "Leonetto1982" "1"

echo ""
echo "=============================================="
echo " DEPLOY GO CONCLUIDO - TODOS OS SISTEMAS!"
echo "=============================================="
