# Kit Manual de Migração — Frank Agência

Origem atualizada:

```text
/www/wwwroot/app_zapmatic_app
```

Destino:

```text
ubuntu@144.22.167.45:/www/wwwroot/app_frank_agencia
```

## Objetivo

Substituir praticamente todo o código do destino pelo código atualizado da origem, preservando:

```text
.env
app_zapmatic_api/config.js
app_zapmatic_api/sessions/
writable/
app_zapmatic_api/files/
app_zapmatic_api/store/
```

E preservar o banco de dados do destino, aplicando somente o que faltar em estrutura/tabelas/colunas.

## Arquivos gerados neste kit

```text
MIGRATION_FRANK_AGENCIA_AUDIT.md
KIT_MANUAL_MIGRACAO_FRANK_AGENCIA.md
frank_schema_incremental_REVIEW.sql
/tmp/frank_rsync_dry_run.txt
```

## Backup já criado no destino

Backup dos itens preservados criado em:

```text
/www/backup_zapmatic_migration/frank_20260617_094644
```

Tamanho:

```text
286M
```

Itens copiados para backup:

```text
.env
app_zapmatic_api/config.js
app_zapmatic_api/sessions/
writable/
app_zapmatic_api/files/
app_zapmatic_api/store/
```

## Banco do destino

Banco detectado no destino:

```text
sql_frank_db
```

Usuário:

```text
sql_frank_db
```

A senha fica preservada no `.env` do destino e não deve ser sobrescrita.

## SQL incremental

Arquivo gerado para revisão:

```text
/www/wwwroot/app_zapmatic_app/frank_schema_incremental_REVIEW.sql
```

Resumo:

```text
19 tabelas ausentes
2 tabelas com colunas ausentes
```

Importante:

- Esse SQL é para revisão antes de executar.
- Ele não contém DROP.
- Ele tenta apenas criar tabelas faltantes e adicionar colunas faltantes.
- Não importa dados da origem.
- Não apaga dados do destino.

## Atenção sobre tabelas de licença

O SQL incremental inclui tabelas `sp_license_*` porque existem na origem e faltam no destino.

Antes de aplicar, revisar se o destino deve receber essas tabelas. Se o sistema Frank Agência não usa esse módulo de licença, pode ser opcional.

## Estratégia manual recomendada

### 1. Confirmar backup

No destino:

```bash
sudo du -sh /www/backup_zapmatic_migration/frank_20260617_094644
sudo ls -la /www/backup_zapmatic_migration/frank_20260617_094644
```

### 2. Parar processo Node do destino antes da troca

No destino:

```bash
sudo PM2_HOME=/root/.pm2 pm2 stop frank
```

Não parar os outros apps:

```text
abner
arthur
pedidu
```

### 3. Sincronizar arquivos preservando dados

Comando recomendado a partir da origem:

```bash
rsync -az --delete \
  -e "ssh -i /tmp/zapmatic_frank_migration -o UserKnownHostsFile=/tmp/zapmatic_known_hosts" \
  --exclude='.env' \
  --exclude='writable/' \
  --exclude='app_zapmatic_api/config.js' \
  --exclude='app_zapmatic_api/sessions/' \
  --exclude='app_zapmatic_api/files/' \
  --exclude='app_zapmatic_api/store/' \
  --exclude='vendor/' \
  --exclude='app_zapmatic_api/node_modules/' \
  --exclude='.user.ini' \
  --exclude='.well-known/' \
  /www/wwwroot/app_zapmatic_app/ \
  ubuntu@144.22.167.45:/www/wwwroot/app_frank_agencia/
```

### 4. Observação sobre arquivos de backup/docs da origem

O dry-run mostrou que arquivos como backups `.zip`, docs e logs também seriam enviados. Se quiser uma migração mais limpa, adicionar exclusões extras:

```bash
--exclude='*.zip'
--exclude='*.log'
--exclude='debug-*.md'
--exclude='MIGRATION_*.md'
--exclude='KIT_MANUAL_*.md'
--exclude='FLOW_BUILDER_TYPEBOT_ROADMAP.md'
--exclude='.agent/'
--exclude='.vscode/'
```

Minha recomendação: usar essas exclusões extras para o destino de produção/cliente.

### 5. Restaurar ownership/permissões no destino

Depois do rsync:

```bash
sudo chown -R www:www /www/wwwroot/app_frank_agencia
sudo chown -R www:www /www/wwwroot/app_frank_agencia/writable
sudo chown -R www:www /www/wwwroot/app_frank_agencia/app_zapmatic_api/sessions
```

Se o aaPanel usa outro usuário, ajustar conforme o painel.

### 6. Dependências PHP/Node

Como `vendor/` e `node_modules/` estão preservados/excluídos, eles não serão trocados.

Se o código novo exigir dependências novas:

```bash
cd /www/wwwroot/app_frank_agencia
composer install --no-dev --optimize-autoloader
cd app_zapmatic_api
npm install --omit=dev
```

Executar apenas se necessário.

### 7. Aplicar SQL incremental

Copiar o arquivo para o destino:

```bash
scp -i /tmp/zapmatic_frank_migration \
  -o UserKnownHostsFile=/tmp/zapmatic_known_hosts \
  /www/wwwroot/app_zapmatic_app/frank_schema_incremental_REVIEW.sql \
  ubuntu@144.22.167.45:/tmp/frank_schema_incremental_REVIEW.sql
```

No destino, revisar:

```bash
sed -n '1,200p' /tmp/frank_schema_incremental_REVIEW.sql
```

Aplicar somente se revisado:

```bash
cd /www/wwwroot/app_frank_agencia
php -r '$env=parse_ini_file(".env"); echo "mysql -h 127.0.0.1 -u".$env["database.default.username"]." -p ".$env["database.default.database"]." < /tmp/frank_schema_incremental_REVIEW.sql\n";'
```

Depois executar manualmente com a senha do banco.

Alternativa sem expor senha no histórico: criar arquivo temporário `/tmp/frank_mysql.cnf` e usar:

```bash
mysql --defaults-extra-file=/tmp/frank_mysql.cnf sql_frank_db < /tmp/frank_schema_incremental_REVIEW.sql
```

### 8. Validar sintaxe

No destino:

```bash
cd /www/wwwroot/app_frank_agencia
php -l inc/core/Bot_builder/Controllers/Bot_builder.php
node -c app_zapmatic_api/app.js
node -c app_zapmatic_api/waziper/waziper.js
```

### 9. Reiniciar somente processo frank

```bash
sudo PM2_HOME=/root/.pm2 pm2 restart frank
sudo PM2_HOME=/root/.pm2 pm2 status
```

### 10. Checklist de testes

Testar no destino:

- Login/painel
- Menu WhatsApp
- Envio Single Message
- Flow Builder abre
- Templates nativos listam
- Criar/editar template retorna ao Flow
- Carrossel pelo Flow chega no mobile
- Áudio pelo Flow envia
- Instâncias WhatsApp continuam conectadas
- Uploads antigos aparecem
- Sessões não foram perdidas

## Rollback manual

Se algo quebrar, restaurar arquivos preservados:

```bash
sudo rsync -a /www/backup_zapmatic_migration/frank_20260617_094644/.env /www/wwwroot/app_frank_agencia/.env
sudo rsync -a /www/backup_zapmatic_migration/frank_20260617_094644/app_zapmatic_api/config.js /www/wwwroot/app_frank_agencia/app_zapmatic_api/config.js
sudo rsync -a /www/backup_zapmatic_migration/frank_20260617_094644/writable/ /www/wwwroot/app_frank_agencia/writable/
sudo rsync -a /www/backup_zapmatic_migration/frank_20260617_094644/app_zapmatic_api/sessions/ /www/wwwroot/app_frank_agencia/app_zapmatic_api/sessions/
sudo PM2_HOME=/root/.pm2 pm2 restart frank
```

Para rollback completo do código, é necessário ter backup do código anterior completo ou usar backup do aaPanel.

## Recomendação final

Para sua intenção atual — trocar tudo menos `sessions`, `config.js`, `.env`, `writable` e preservar banco — o caminho é correto, mas use rsync com exclusões e aplique o SQL incremental revisado.

Não copie banco da origem por cima do destino.
Não rode `git clean`, `rm -rf` ou sync sem exclusões.
Não crie PM2 novo; use apenas `frank`.
