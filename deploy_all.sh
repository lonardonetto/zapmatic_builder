#!/bin/bash
# =============================================================
# Script de Deploy Automatizado - Zapmatic
# Atualiza app, inc, Go API e Baileys API em todos os servidores
# Uso: ./deploy_all.sh
# =============================================================

MAIN="/www/wwwroot/app_zapmatic_app"
SSH_KEY="/home/ubuntu/.ssh/chave_zapmatic.key"

echo "=========================================="
echo " ZAPMATIC DEPLOY - $(date)"
echo "=========================================="

# Funcao para enviar para servidor local
deploy_local() {
    local name=$1
    local path=$2
    echo ""
    echo "--- $name ($path) ---"
    rsync -avz --delete --force \
      --exclude='google-service-account.json' \
      "$MAIN/app/" "$path/app/" 2>&1 | tail -1
    rsync -avz --delete --force \
      "$MAIN/inc/" "$path/inc/" 2>&1 | tail -1
    echo "  -> OK"
}

# Funcao para enviar para servidor remoto via SSH
deploy_remote() {
    local name=$1
    local ip=$2
    local user=$3
    local pass=$4
    local path=$5
    local porta_go=$6
    local mysql_socket=$7
    local go_service=$8

    echo ""
    echo "--- $name ($ip -> $path) ---"

    # Verificar/instalar rsync
    sshpass -p "$pass" ssh -o StrictHostKeyChecking=no "$user@$ip" \
      "which rsync >/dev/null 2>&1 || sudo apt-get install -y rsync >/dev/null 2>&1" 2>/dev/null

    # Fix permissoes
    sshpass -p "$pass" ssh -o StrictHostKeyChecking=no "$user@$ip" \
      "sudo chown -R www:www $path/app $path/inc $path/app_zapmatic_api $path/app_zapmatic_whatsmeow_api 2>/dev/null; \
       sudo chmod -R 777 $path/app $path/inc $path/app_zapmatic_api $path/app_zapmatic_whatsmeow_api 2>/dev/null" 2>/dev/null

    # Enviar app + inc
    rsync -avz --delete --force \
      --exclude='google-service-account.json' \
      -e "sshpass -p '$pass' ssh -o StrictHostKeyChecking=no" \
      "$MAIN/app/" "$user@$ip:$path/app/" 2>&1 | tail -1
    rsync -avz --delete --force \
      -e "sshpass -p '$pass' ssh -o StrictHostKeyChecking=no" \
      "$MAIN/inc/" "$user@$ip:$path/inc/" 2>&1 | tail -1

    # Enviar Go API (exceto config.json, sessions, logs)
    rsync -avz --delete --force \
      --exclude='config.json' --exclude='storage/sessions/' --exclude='logs/' \
      -e "sshpass -p '$pass' ssh -o StrictHostKeyChecking=no" \
      "$MAIN/app_zapmatic_whatsmeow_api/" "$user@$ip:$path/app_zapmatic_whatsmeow_api/" 2>&1 | tail -1

    # Enviar Baileys API (exceto config.js, sessions, logs)
    rsync -avz --delete --force \
      --exclude='config.js' --exclude='sessions/' --exclude='logs/' \
      -e "sshpass -p '$pass' ssh -o StrictHostKeyChecking=no" \
      "$MAIN/app_zapmatic_api/" "$user@$ip:$path/app_zapmatic_api/" 2>&1 | tail -1

    # Compilar Go x86_64 e reiniciar servico
    sshpass -p "$pass" ssh -o StrictHostKeyChecking=no "$user@$ip" "
      cd $path/app_zapmatic_whatsmeow_api
      chmod +x zapmatic-whatsmeow start.sh 2>/dev/null

      # Compilar se Go instalado
      if [ -f /usr/local/go/bin/go ]; then
        CGO_ENABLED=1 /usr/local/go/bin/go build -o zapmatic-whatsmeow-amd64 ./cmd/server/ 2>/dev/null
        if [ -f zapmatic-whatsmeow-amd64 ]; then
          cp zapmatic-whatsmeow-amd64 zapmatic-whatsmeow
          chmod +x zapmatic-whatsmeow
        fi
      fi

      # Reiniciar servico
      sudo systemctl restart $go_service 2>/dev/null
      sleep 2
      status=\$(sudo systemctl is-active $go_service 2>/dev/null)
      echo \"  Status: \$status\"
    " 2>&1

    echo "  -> OK"
}

# ========================================
# 1. SERVIDOR LOCAL (principal + clientes)
# ========================================
echo ""
echo "=== SERVIDOR LOCAL ==="
deploy_local "Paulo" "/www/wwwroot/app_paulo_app"
deploy_local "Elias" "/www/wwwroot/app_elias_app"
deploy_local "Renovo" "/www/wwwroot/renovo_app"
deploy_local "Astros" "/www/wwwroot/app.astroscomunicacaodigital.com"

# ========================================
# 2. SERVIDOR 144 (Kivozap, AgenciaMCW, Chatbut)
# ========================================
echo ""
echo "=== SERVIDOR 144.22.167.45 ==="
deploy_remote "Kivozap"    "144.22.167.45" "ubuntu" "" "/www/wwwroot/app_abner_app"      8095 "/tmp/mysql.sock" "zapmatic-whatsmeow-kivozap"
deploy_remote "AgenciaMCW" "144.22.167.45" "ubuntu" "" "/www/wwwroot/app_frank_agencia"   8096 "/tmp/mysql.sock" "zapmatic-whatsmeow-agenciamcw"
deploy_remote "Chatbut"    "144.22.167.45" "ubuntu" "" "/www/wwwroot/app_alex_pedidu_app" 8097 "/tmp/mysql.sock" "zapmatic-whatsmeow-chatbut"

# ========================================
# 3. SERVIDOR 45 (IaClicks)
# ========================================
echo ""
echo "=== SERVIDOR 45.148.29.92 ==="
deploy_remote "IaClicks" "45.148.29.92" "admin" "Leonetto1982" "/www/wwwroot/app_zapmatic_app" 8098 "/tmp/mysql.sock" "zapmatic-whatsmeow-iaclicks"

# ========================================
# 4. SERVIDOR 193 (Elite)
# ========================================
echo ""
echo "=== SERVIDOR 193.180.211.190 ==="
deploy_remote "Elite" "193.180.211.190" "admin" "Leonetto1982" "/www/wwwroot/elitecomunicacao.zapmatic.tec.br" 8099 "/tmp/mysql.sock" "zapmatic-whatsmeow-elite"

# ========================================
# 5. SERVIDOR 92 (PlusZap)
# ========================================
echo ""
echo "=== SERVIDOR 92.113.144.161 ==="
deploy_remote "PlusZap" "92.113.144.161" "admin" "Leonetto1982" "/www/wwwroot/app_zapmatic_app" 8100 "/tmp/mysql.sock" "zapmatic-whatsmeow-pluszap"
deploy_remote "MetaSenderPro" "92.113.149.185" "MetaSenderPro" "Hacker5030" "/www/wwwroot/app_zapmatic_app" 8101 "/tmp/mysql.sock" "zapmatic-whatsmeow-metasenderpro"

echo ""
echo "=========================================="
echo " DEPLOY COMPLETO - $(date)"
echo "=========================================="
