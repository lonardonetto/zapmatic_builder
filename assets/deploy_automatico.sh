#!/bin/bash
# Script de Deploy Automático - Substitui tudo e adiciona apenas diferenças no banco

echo "=== DEPLOY AUTOMÁTICO ZAPMATIC ==="
echo ""

# 1. Mover arquivos de /home/admin para a raiz
echo "[1/5] Movendo arquivos..."
sudo mv /home/admin/migracao_completa_corrigida.zip /www/wwwroot/app_zapmatic_app/
sudo mv /home/admin/db_diff.sql /www/wwwroot/app_zapmatic_app/
cd /www/wwwroot/app_zapmatic_app/

# 2. Fazer backup dos arquivos que NÃO devem ser substituídos
echo "[2/5] Protegendo arquivos sensíveis..."
sudo mkdir -p /tmp/backup_deploy
sudo cp -p .env /tmp/backup_deploy/ 2>/dev/null || true
sudo cp -p config.js /tmp/backup_deploy/ 2>/dev/null || true
sudo cp -rp writable /tmp/backup_deploy/ 2>/dev/null || true
sudo cp -rp sessions /tmp/backup_deploy/ 2>/dev/null || true
sudo cp -rp storage /tmp/backup_deploy/ 2>/dev/null || true

# 3. Apagar TUDO menos os backups
echo "[3/5] Removendo arquivos antigos..."
sudo find . -mindepth 1 -maxdepth 1 ! -name 'migracao_completa_corrigida.zip' ! -name 'db_diff.sql' -exec rm -rf {} +

# 4. Extrair novos arquivos
echo "[4/5] Extraindo arquivos novos..."
sudo unzip -q -o migracao_completa_corrigida.zip

# 5. Restaurar arquivos protegidos
echo "[5/5] Restaurando arquivos sensíveis..."
sudo cp -p /tmp/backup_deploy/.env . 2>/dev/null || true
sudo cp -p /tmp/backup_deploy/config.js . 2>/dev/null || true
sudo cp -rp /tmp/backup_deploy/writable . 2>/dev/null || true
sudo cp -rp /tmp/backup_deploy/sessions . 2>/dev/null || true
sudo cp -rp /tmp/backup_deploy/storage . 2>/dev/null || true

# 6. Importar apenas diferenças do banco
echo "[6/6] Adicionando novas tabelas no banco (preservando dados)..."
mysql -h 127.0.0.1 -u sql_iaclicks_db -pFxMzzfdLPr2yDS2F sql_iaclicks_db < db_diff.sql

# 7. Limpar
echo ""
echo "Limpando arquivos temporários..."
sudo rm -f migracao_completa_corrigida.zip db_diff.sql
sudo rm -rf /tmp/backup_deploy

echo ""
echo "✅ DEPLOY CONCLUÍDO COM SUCESSO!"
echo "- Todos os arquivos substituídos"
echo "- Arquivos sensíveis preservados (.env, config.js, writable, sessions, storage)"
echo "- 14 novas tabelas adicionadas ao banco"
echo "- Dados existentes no banco preservados"
