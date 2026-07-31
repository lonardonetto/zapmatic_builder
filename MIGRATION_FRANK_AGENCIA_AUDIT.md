# Auditoria de Migração — app_frank_agencia

Status: auditoria inicial concluída
Origem: `/www/wwwroot/app_zapmatic_app`
Destino: `ubuntu@144.22.167.45:/www/wwwroot/app_frank_agencia`

## Objetivo

Atualizar o sistema do destino com o código atual da origem, preservando os dados do banco e dados persistentes do destino.

## Regras obrigatórias

1. Não sobrescrever banco de dados do destino.
2. Não sobrescrever `.env` do destino.
3. Não sobrescrever sessões WhatsApp do destino.
4. Não sobrescrever uploads/writable do destino.
5. Não criar processo PM2 duplicado.
6. Fazer backup antes de qualquer alteração.
7. Sincronizar código com exclusões explícitas.
8. Aplicar ajustes de banco apenas por migração incremental/schema diff, se necessário.

## Acesso SSH

Conexão validada com chave temporária:

```text
Host: 144.22.167.45
User: ubuntu
Projeto: /www/wwwroot/app_frank_agencia
```

## Auditoria destino

Projeto existe:

```text
/www/wwwroot/app_frank_agencia
```

Banco configurado no destino:

```text
Database: sql_frank_db
User: sql_frank_db
Host: localhost
```

Observação: senha não documentada neste arquivo por segurança.

Arquivos importantes:

```text
.env EXISTS
app_zapmatic_api/.env MISSING
```

Dados persistentes encontrados:

```text
writable: 249M
writable/uploads: 98M
writable/session: 82M
writable/debugbar: 57M
app_zapmatic_api/sessions: 37M
sessions count: 114
```

Runtime destino:

```text
PHP 8.2.28
Node v22.11.0
```

PM2 root destino:

```text
frank: online
script: /www/wwwroot/app_frank_agencia/app_zapmatic_api/app.js
user: root
```

Outros processos root existem no mesmo servidor:

```text
abner
arthur
pedidu
zapmatic_app errored
```

## Itens que devem ser preservados no destino

Não sincronizar/substituir:

```text
.env
app_zapmatic_api/.env
writable/
app_zapmatic_api/sessions/
app_zapmatic_api/files/
app_zapmatic_api/store/
vendor/
app_zapmatic_api/node_modules/
.user.ini
.well-known/
```

Avaliar antes de copiar:

```text
assets/uploads
public/uploads
qualquer pasta de mídia local
```

## Estratégia recomendada

### Etapa 1 — Backup no destino

Criar pasta:

```text
/www/backup_zapmatic_migration/frank_YYYYMMDD_HHMMSS
```

Backups mínimos:

```text
.env
app_zapmatic_api/config.js
inc/
app/
assets/
app_zapmatic_api/app.js
app_zapmatic_api/waziper/
composer.json
package.json
```

Backup de banco:

```text
mysqldump sql_frank_db > sql_frank_db_before_migration.sql
```

### Etapa 2 — Dry-run de rsync

Executar rsync com `--dry-run` primeiro.

Exclusões obrigatórias:

```text
--exclude='.env'
--exclude='writable/'
--exclude='vendor/'
--exclude='app_zapmatic_api/node_modules/'
--exclude='app_zapmatic_api/sessions/'
--exclude='app_zapmatic_api/files/'
--exclude='app_zapmatic_api/store/'
--exclude='app_zapmatic_api/.env'
--exclude='.user.ini'
--exclude='.well-known/'
```

### Etapa 3 — Sincronizar código

Somente após revisar dry-run.

### Etapa 4 — Preservar configurações locais

Após sync, conferir:

```text
.env continua do destino
config.js continua compatível com destino
PM2 frank continua apontando para /www/wwwroot/app_frank_agencia/app_zapmatic_api/app.js
```

### Etapa 5 — Banco

Não importar banco da origem.

Fazer apenas comparação de schema:

```text
origem schema vs destino schema
```

Gerar SQL incremental se houver tabelas/colunas necessárias.

### Etapa 6 — Validações

Validar no destino:

```text
php -l arquivos críticos
node -c app_zapmatic_api/waziper/waziper.js
node -c app_zapmatic_api/app.js
```

### Etapa 7 — Restart controlado

Reiniciar somente PM2 root app `frank`:

```text
PM2_HOME=/root/.pm2 pm2 restart frank
```

Não criar processo novo.

## Próximo passo

Aguardando autorização explícita para:

1. Criar backup no destino.
2. Rodar rsync dry-run.
3. Revisar lista de arquivos que mudariam.
4. Só então executar sync real.
