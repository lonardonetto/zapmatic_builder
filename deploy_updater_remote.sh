#!/bin/bash
# ============================================================
# deploy_updater_remote.sh
# Aplica o SISTEMA DE UPDATE nos servidores remotos
# Uso: sudo bash deploy_updater_remote.sh
# ============================================================

MAIN="/www/wwwroot/app_zapmatic_app"
SSH_KEY="/home/ubuntu/.ssh/chave_zapmatic.key"

deploy_remote() {
    local name=$1 ip=$2 user=$3 pass=$4 rpath=$5 worker=$6
    
    echo ""
    echo "=============================================="
    echo "=== $name ($ip -> $rpath)"
    echo "=============================================="
    
    local SSH_CMD
    if [ -z "$pass" ]; then
        SSH_CMD="ssh -o StrictHostKeyChecking=no -i $SSH_KEY"
    else
        SSH_CMD="sshpass -p '$pass' ssh -o StrictHostKeyChecking=no"
    fi
    
    # 0. Criar pastas necessarias
    eval $SSH_CMD "$user@$ip" "sudo mkdir -p $rpath/app/Commands $rpath/migrations $rpath/inc/core/Plugins/Controllers $rpath/inc/core/Plugins/Views 2>/dev/null; sudo chown -R www:www $rpath/app/Commands $rpath/migrations 2>/dev/null" 2>/dev/null
    
    # 1. Updater (Plugins module)
    rsync -avz -e "$SSH_CMD" \
        "$MAIN/inc/core/Plugins/Controllers/System_updater.php" \
        "$MAIN/inc/core/Plugins/Controllers/Plugins.php" \
        "$user@$ip:$rpath/inc/core/Plugins/Controllers/" 2>&1 | tail -1
    rsync -avz -e "$SSH_CMD" \
        "$MAIN/inc/core/Plugins/Views/system_update.php" \
        "$MAIN/inc/core/Plugins/Views/index.php" \
        "$user@$ip:$rpath/inc/core/Plugins/Views/" 2>&1 | tail -1
    
    # 2. Worker
    rsync -avz -e "$SSH_CMD" \
        "$MAIN/app/Commands/BotWorkerAll.php" \
        "$user@$ip:$rpath/app/Commands/BotWorkerAll.php" 2>&1 | tail -1
    
    # 3. Migrations
    rsync -avz -e "$SSH_CMD" \
        "$MAIN/migrations/" "$user@$ip:$rpath/migrations/" 2>&1 | tail -1
    
    # 4. Fix FCPATH spark
    eval $SSH_CMD "$user@$ip" "sudo sed -i \"s|define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);|define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);|g\" $rpath/spark 2>/dev/null || true" 2>/dev/null
    
    # 5. version.json
    eval $SSH_CMD "$user@$ip" "cat > $rpath/version.json << 'VEOF'
{
    \"version\": \"8.0.0\",
    \"channel\": \"stable\",
    \"build_date\": \"$(date '+%Y-%m-%dT%H:%M:%S')-03:00\",
    \"git_commit\": \"\",
    \"min_php\": \"8.0\",
    \"notes\": \"Sistema de update preparado\"
}
VEOF
sudo chown www:www $rpath/version.json 2>/dev/null; sudo chmod 666 $rpath/version.json 2>/dev/null" 2>/dev/null
    echo "  ✅ version.json"
    
    # 6. ecosystem.config.js
    eval $SSH_CMD "$user@$ip" "cat > $rpath/ecosystem.config.js << 'EEOF'
module.exports = {
  apps: [
    {
      name: \"$worker\",
      script: \"spark\",
      args: \"bot:all\",
      interpreter: \"php\",
      cwd: \"$rpath\",
      instances: 1,
      exec_mode: \"fork\",
      autorestart: true,
      watch: false,
      max_memory_restart: \"256M\",
      error_file: \"writable/logs/pm2-all-error.log\",
      out_file: \"writable/logs/pm2-all-out.log\",
    },
  ]
};
EEOF" 2>/dev/null
    echo "  ✅ ecosystem.config.js"
    
    # 7. Tabelas do sistema de update
    eval $SSH_CMD "$user@$ip" "
      cd $rpath
      DB=\$(grep 'database.default.database' .env 2>/dev/null | head -1 | cut -d= -f2 | tr -d ' ')
      DBPASS=\$(grep 'database.default.password' .env 2>/dev/null | head -1 | cut -d= -f2 | tr -d ' ')
      if [ -n \"\$DB\" ]; then
        mysql -u \$DB -p\$DBPASS \$DB -e \"
          CREATE TABLE IF NOT EXISTS sp_system_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            version VARCHAR(20) DEFAULT NULL,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_filename (filename)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
          CREATE TABLE IF NOT EXISTS sp_system_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_version VARCHAR(20) DEFAULT NULL,
            to_version VARCHAR(20) DEFAULT NULL,
            channel VARCHAR(20) DEFAULT 'stable',
            status ENUM('pending','processing','applied','failed','rolled_back') DEFAULT 'pending',
            backup_file VARCHAR(255) DEFAULT NULL,
            git_commit VARCHAR(40) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            applied_at DATETIME DEFAULT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        \" 2>/dev/null && echo '  ✅ Tabelas criadas'
      fi
    " 2>/dev/null
    
    # 8. Iniciar worker PM2
    eval $SSH_CMD "$user@$ip" "
      cd $rpath
      sudo pm2 delete $worker 2>/dev/null
      sudo pm2 start ecosystem.config.js 2>/dev/null
      sudo pm2 save 2>/dev/null
    " 2>/dev/null
    echo "  ✅ Worker PM2"
    
    echo "  ✅ $name PRONTO!"
}

# ==============================================
# SERVIDOR 144 (chave SSH)
# ==============================================
deploy_remote "Kivozap"    "144.22.167.45" "ubuntu" "" "/www/wwwroot/app_abner_app"       "kivozap-bot-worker-all"
deploy_remote "AgenciaMCW" "144.22.167.45" "ubuntu" "" "/www/wwwroot/app_frank_agencia"   "agenciamcw-bot-worker-all"
deploy_remote "Chatbut"    "144.22.167.45" "ubuntu" "" "/www/wwwroot/app_alex_pedidu_app" "chatbut-bot-worker-all"

# ==============================================
# SERVIDOR 45 (IaClicks)
# ==============================================
deploy_remote "IaClicks" "45.148.29.92" "admin" "Leonetto1982" "/www/wwwroot/app_zapmatic_app" "iaclicks-bot-worker-all"

# ==============================================
# SERVIDOR 193 (Elite)
# ==============================================
deploy_remote "Elite" "193.180.211.190" "admin" "Leonetto1982" "/www/wwwroot/elitecomunicacao.zapmatic.tec.br" "elite-bot-worker-all"

# ==============================================
# SERVIDOR 92 (PlusZap)
# ==============================================
deploy_remote "PlusZap" "92.113.144.161" "admin" "Leonetto1982" "/www/wwwroot/app_zapmatic_app" "pluszap-bot-worker-all"

echo ""
echo "=============================================="
echo " DEPLOY DO UPDATER REMOTO CONCLUIDO!"
echo "=============================================="
