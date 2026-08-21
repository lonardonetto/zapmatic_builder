# Sincronizacao MetaSenderPro — Especificacao, Execucao e Template

> **Status:** concluido  
> **Data:** 2026-08-17 (primeira) / 2026-08-20 (atualizacao)  
> **Executado por:** Kilo (automatizado via SSH)  
> **Versao resultante:** v8.5.18 (identico ao main)  
> **Commit main:** `38a6049c`  
> **Principio:** main Zapmatic (`zapmatic.tec.br`) e o laboratorio de desenvolvimento — todos os demais servidores devem ser identicos ao main em codigo, estrutura e comportamento, mas com credenciais proprias e autonomas.

---

## 1. Visao Geral

Cada servidor cliente (tenant) recebe uma copia 100% identica ao main Zapmatic em termos de:
- Codigo fonte (inc/core, app, Go API, scraper, assets, migrations)
- Estrutura de banco de dados (76 tabelas, colunas, indices)
- Processos (systemd Go gateway + 3 PM2 workers)
- Especificacoes (.spec) e documentacao (docs)

**O que NUNCA e substituido:**
- `.env` — credenciais e configuracoes propias do tenant
- `writable/` — dados locais (logs, audios, cache, sessions)
- `.git/` — repositorio proprio (se houver)

---

## 2. Template de Execucao

> Este processo pode ser replicado para qualquer servidor.  
> Basta preencher as variaveis na secao 3 e seguir os 8 passos.

### Passo 1 — Analise de Diferencas de Banco

**Objetivo:** Identificar tabelas/colunas que faltam no destino e tabelas/colunas legado que nao existem no main.

```bash
# 1. Dump schema do destino via SSH
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} \
  "mysqldump -u {DB_USER} -p{DB_PASS} --no-data --single-transaction {DB_NAME}" > /tmp/{TENANT}_schema.sql

# 2. Comparar tabelas
grep "CREATE TABLE" migration_db_zapmatic_full.sql | sed 's/CREATE TABLE.*`\(`.*`\)`.*/\1/' | sort > /tmp/main_tables.txt
grep "CREATE TABLE" /tmp/{TENANT}_schema.sql | sed 's/CREATE TABLE.*`\(`.*`\)`.*/\1/' | sort > /tmp/tenant_tables.txt

# Tabelas que faltam no destino
comm -23 /tmp/main_tables.txt /tmp/tenant_tables.txt

# Tabelas legado no destino (NUNCA existem — main sempre e referencia)
comm -13 /tmp/main_tables.txt /tmp/tenant_tables.txt

# 3. Comparar colunas (Python script na secao 5)
```

**Regras:**
- FALTAM no destino → ADD (CREATE TABLE ou ALTER TABLE ADD COLUMN)
- Existe no destino mas NAO no main → DROP (tabela ou coluna legado)
- NUNCA dropar dados — apenas estrutura

### Passo 2 — Aplicar Migration no Destino

```bash
# Gerar SQL diff com CREATE TABLE para tabelas novas + ALTER TABLE para colunas novas
# Enviar e executar no destino
sshpass -p '{SSH_PASS}' scp /tmp/{TENANT}_migration.sql {SSH_USER}@{SSH_IP}:/tmp/migration.sql
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} \
  "mysql -u {DB_USER} -p{DB_PASS} {DB_NAME} < /tmp/migration.sql"

# Verificar contagem
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} \
  "mysql -u {DB_USER} -p{DB_PASS} {DB_NAME} -e \"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='{DB_NAME}'\""
# Deve retornar: 76
```

### Passo 3 — Substituir Codigo

```bash
# Backup do .env e ecosystem do destino
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} \
  "cp {PATH}/.env /tmp/{TENANT}_env_backup; cp {PATH}/ecosystem.config.js /tmp/{TENANT}_ecosystem_backup"

# Rsync de cada pasta (excluindo .env, writable, .git, sessions, binary)
export SSHPASS='{SSH_PASS}'
SSH="ssh -o StrictHostKeyChecking=no"
EXCLUDE="--exclude=.env --exclude=writable/ --exclude=.git/ --exclude=node_modules/ --exclude=vendor/ --exclude=storage/sessions/ --exclude=app_zapmatic_whatsmeow_api/zapmatic-whatsmeow --exclude=app_zapmatic_whatsmeow_api/storage/ --exclude=app_zapmatic_whatsmeow_api/logs/ --exclude='*.db' --exclude='*.db-shm' --exclude='*.db-wal'"

for dir in inc app app_zapmatic_scraper app_zapmatic_whatsmeow_api assets migrations sql .spec docs _bmad; do
    sshpass -p '{SSH_PASS}' rsync -az --delete -e "$SSH" $EXCLUDE \
      /www/wwwroot/app_zapmatic_app/$dir/ \
      {SSH_USER}@{SSH_IP}:{PATH}/$dir/
done

# Root files
for f in call_audio_stream.php composer.json ecosystem.config.js index.html index.php \
         spark version.json release.sh CHANGELOG.md README.md migration_db_zapmatic_full.sql \
         deploy_all.sh deploy_folder.sh deploy_go.sh deploy_updater_remote.sh 404.html 502.html; do
    sshpass -p '{SSH_PASS}' scp $SSH /www/wwwroot/app_zapmatic_app/$f {SSH_USER}@{SSH_IP}:{PATH}/$f
done

# Instalar dependencias
# ⚠️ ATENCAO: NUNCA usar `--no-dev` no composer deste projeto.
# O main tem o vendor/ instalado COM dependencias de dev (phpunit, faker, vfsstream),
# e o autoload referencia `phpunit/phpunit/src/Framework/Assert/Functions.php` no
# bootstrap de TODA requisicao. `--no-dev` remove o phpunit e quebra o site inteiro
# com `Fatal error: require(phpunit/.../Functions.php): Failed to open stream`.
# Ver secao 4.10.8 (correcao do Astros).
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} \
  "cd {PATH} && sudo -u www composer install --optimize-autoloader"
# Sempre reiniciar o PHP-FPM apos o composer para limpar o OPcache (autoload antigo).
# Exemplo (PHP 8.1 BT Panel): sudo /etc/init.d/php-fpm-81 restart
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} \
  "cd {PATH}/app_zapmatic_scraper && sudo -u www npm install --production"
```

### Passo 4 — Compilar Go Binary

```bash
# Instalar Go (se nao tiver)
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} \
  "wget -q https://go.dev/dl/go1.22.6.linux-amd64.tar.gz -O /tmp/go.tar.gz && \
   sudo tar -C /usr/local -xzf /tmp/go.tar.gz && rm /tmp/go.tar.gz"

# Compilar NO servidor destino (CGO_ENABLED=1 obrigatorio para sqlite3/whatsmeow)
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} \
  "export PATH=/usr/local/go/bin:\$PATH && export CGO_ENABLED=1 && \
   cd {PATH}/app_zapmatic_whatsmeow_api && \
   go build -o zapmatic-whatsmeow ./cmd/server/ && \
   chmod +x zapmatic-whatsmeow"
```

**NOTA:** NAO usar cross-compile (`CGO_ENABLED=0`) — o binary precisa de CGO para sqlite3 (whatsmeow sessions).

### Passo 5 — Ajustar Credenciais

**5.1 — config.json do Go gateway:**
```json
{
  "port": "{GO_PORT}",
  "log_level": "info",
  "log_dir": "logs",
  "store_dir": "storage/sessions",
  "webhook_url": "https://{DOMAIN}/index.php/bot-builder/webhook",
  "api_key": "",
  "database": {
    "host": "localhost",
    "port": 3306,
    "user": "{DB_USER}",
    "password": "{DB_PASS}",
    "name": "{DB_NAME}"
  }
}
```

**5.2 — ecosystem.config.js:**
```javascript
module.exports = {
  apps: [
    {
      name: "{TENANT}-bot-worker-all",
      script: "spark", args: "bot:all", interpreter: "php",
      cwd: "{PATH}", autorestart: true, max_memory_restart: "256M",
      error_file: "writable/logs/pm2-all-error.log",
      out_file: "writable/logs/pm2-all-out.log",
    },
    {
      name: "{TENANT}-call-worker",
      script: "spark", args: "call:campaigns", interpreter: "php",
      cwd: "{PATH}", autorestart: true, max_memory_restart: "128M",
      error_file: "writable/logs/pm2-call-error.log",
      out_file: "writable/logs/pm2-call-out.log",
    },
    {
      name: "{TENANT}-gmscraper",
      script: "index.js", interpreter: "node",
      cwd: "{PATH}/app_zapmatic_scraper", autorestart: true, max_memory_restart: "256M",
    },
  ]
};
```

**5.3 — systemd service:**
```ini
[Unit]
Description=Zapmatic Whatsmeow Gateway ({TENANT_NAME})
After=network.target

[Service]
Type=simple
User=www
ExecStart={PATH}/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow --port {GO_PORT} --log-dir {PATH}/app_zapmatic_whatsmeow_api/logs
WorkingDirectory={PATH}/app_zapmatic_whatsmeow_api
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

**5.4 — Verificar .env (NAO substituir):**
```bash
# Confirmar que .env tem credenciais do TENANT e nao do main
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} \
  "grep -c 'db_zapmatic_sql\|inTwk7z37PnhWcY5\|zapmatic\.tec\.br' {PATH}/.env {PATH}/app_zapmatic_whatsmeow_api/config.json"
# Deve retornar: 0 em todos
```

### Passo 6 — Criar Diretorios e Ajustar Permissoes

```bash
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} "
  sudo mkdir -p {PATH}/writable/logs {PATH}/writable/call_audio
  sudo mkdir -p {PATH}/app_zapmatic_whatsmeow_api/logs
  sudo mkdir -p {PATH}/app_zapmatic_whatsmeow_api/storage/sessions
  sudo chown -R www:www {PATH}/writable/
  sudo chown -R www:www {PATH}/app_zapmatic_whatsmeow_api/logs/
  sudo chown -R www:www {PATH}/app_zapmatic_whatsmeow_api/storage/
"
```

### Passo 7 — Iniciar Processos

```bash
# Go gateway
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} "
  sudo systemctl daemon-reload
  sudo systemctl enable zapmatic-whatsmeow-{TENANT}
  sudo systemctl restart zapmatic-whatsmeow-{TENANT}
  sleep 2
  systemctl is-active zapmatic-whatsmeow-{TENANT}
"

# PM2 workers
sshpass -p '{SSH_PASS}' ssh {SSH_USER}@{SSH_IP} "
  sudo pm2 stop {TENANT}-bot-worker-all {TENANT}-call-worker {TENANT}-gmscraper 2>/dev/null
  sudo pm2 delete {TENANT}-bot-worker-all {TENANT}-call-worker {TENANT}-gmscraper 2>/dev/null
  sudo pm2 delete call-campaign-worker gmscraper-main 2>/dev/null
  sudo pm2 start {PATH}/ecosystem.config.js
  sudo pm2 save
"
```

### Passo 8 — Testes de Verificacao

| Teste | Comando | Criterio de sucesso |
|---|---|---|
| Go health | `curl http://localhost:{GO_PORT}/health` | `{"status":"ok"}` |
| Web interface | `curl -s -o /dev/null -w "%{http_code}" https://{DOMAIN}/` | `200` |
| DB tables | `SELECT COUNT(*) FROM information_schema.tables` | `76` |
| Credenciais | `grep -c 'db_zapmatic_sql' .env config.json` | `0` |
| PM2 processes | `pm2 list` | 3 processos online |
| Systemd | `systemctl is-active zapmatic-whatsmeow-{TENANT}` | `active` |
| Ligacao + auto-hangup | `POST /call/start` com audio | OnFinish → hangup 2s |
| Call API | `GET /call/list` | `{"status":"success"}` |

---

## 3. Variaveis por Servidor

> Preencher para cada novo servidor. O MetaSenderPro esta preenchido como exemplo.

| Variavel | MetaSenderPro (exemplo) | Kivozap | AgenciaMCW | Chatbut | IaClicks | Elite | PlusZap |
|---|---|---|---|---|---|---|---|
| `{SSH_IP}` | 92.113.149.185 | 144.22.167.45 | 144.22.167.45 | 144.22.167.45 | 45.148.29.92 | 193.180.211.190 | 92.113.144.161 |
| `{SSH_USER}` | MetaSenderPro | ubuntu | ubuntu | ubuntu | admin | admin | admin |
| `{SSH_PASS}` | Hacker5030 | (key) | (key) | (key) | Leonetto1982 | Leonetto1982 | Leonetto1982 |
| `{PATH}` | /www/wwwroot/app_metasenderpro | /www/wwwroot/app_abner_app | /www/wwwroot/app_frank_agencia | /www/wwwroot/app_alex_pedidu_app | /www/wwwroot/app_zapmatic_app | /www/wwwroot/elitecomunicacao.zapmatic.tec.br | /www/wwwroot/app_zapmatic_app |
| `{DOMAIN}` | sender.metanivelpro.com | kivozap.com.br | chatbot.agenciamcw.com.br | chatbut.com.br | iaclicks.com | elitecomunicacao.zapmatic.tec.br | pluszap.com |
| `{DB_NAME}` | sql_metasenderpro_db | db_abner_sql | sql_frank_db | sql_alex_db | sql_iaclicks_db | sql_zapmatic_db | sql_zapmatic_db |
| `{DB_USER}` | sql_metasenderpro_db | db_abner_sql | sql_frank_db | sql_alex_db | sql_iaclicks_db | sql_zapmatic_db | sql_zapmatic_db |
| `{DB_PASS}` | ebPCdCaWz5AsdkAh | inTwk7z37PnhWcY5 | apw4iTDGjePic8cb | 6eNEfwPxHjdT757w | FxMzzfdLPr2yDS2F | fe5kwDTMy3JdxDhT | ZnCYYPwZwYxw8b6r |
| `{GO_PORT}` | 8101 | 8095 | 8096 | 8097 | 8098 | 8099 | 8100 |
| `{TENANT}` | metasenderpro | kivozap | agenciamcw | chatbut | iaclicks | elite | pluszap |
| `{TENANT_NAME}` | MetaSenderPro | Kivozap | AgenciaMCW | Chatbut | IaClicks | Elite | PlusZap |
| **Status** | **CONCLUIDO (v8.5.18)** | pendente | pendente | **CONCLUIDO (v8.5.17)** | pendente | pendente | pendente |

---

## 4. Resultado da Execucao — MetaSenderPro

### 4.1 Banco de Dados

| Item | Antes | Depois |
|---|---|---|
| Total de tabelas | 73 | 76 |
| Tabelas adicionadas | — | `sp_clone_group_queue`, `sp_export_participants_queue`, `sp_whatsapp_schedule_groups` |
| Colunas adicionadas | — | `sp_whatsapp_schedules.target_type` (varchar(16), default 'contacts') |
| Tabelas legado dropadas | 0 | 0 |
| Colunas legado dropadas | 0 | 0 |

### 4.2 Codigo

| Pasta | Acao | Status |
|---|---|---|
| `inc/core/` | rsync completo | ✅ |
| `app/` | rsync completo | ✅ |
| `app_zapmatic_whatsmeow_api/` | rsync + compilacao Go | ✅ |
| `app_zapmatic_scraper/` | rsync + npm install | ✅ |
| `assets/` | rsync completo | ✅ |
| `migrations/` | rsync completo | ✅ |
| `.spec/` | rsync completo | ✅ |
| `docs/` | rsync completo | ✅ |
| `_bmad/` | rsync completo | ✅ |
| Root files | scp individual | ✅ |
| `.env` | NAO substituido | ✅ preservado |
| `writable/` | NAO substituido | ✅ preservado |

### 4.3 Go Binary

| Item | Valor |
|---|---|
| Compilacao | `CGO_ENABLED=1` (necessario para sqlite3/whatsmeow) |
| Local | No proprio servidor Meta (nao cross-compile) |
| Tamanho | 28MB |
| Arquitetura | linux/amd64 |
| Go version | 1.22.6 (instalado no Meta) |

### 4.4 Credenciais

| Arquivo | Conteudo | Cross-ref com main |
|---|---|---|
| `.env` | `sql_metasenderpro_db` / `sender.metanivelpro.com` | ✅ ZERO |
| `config.json` | port 8101 / `sql_metasenderpro_db` / webhook `sender.metanivelpro.com` | ✅ ZERO |
| `ecosystem.config.js` | prefixo `metasenderpro-` | ✅ ZERO |
| systemd service | port 8101 / user `www` | ✅ ZERO |

### 4.5 Processos

| Processo | Tipo | PID | Status |
|---|---|---|---|
| `zapmatic-whatsmeow-metasenderpro` | systemd | 267681 | ✅ active (port 8101) |
| `metasenderpro-bot-worker-all` | PM2 | 268367 | ✅ online |
| `metasenderpro-call-worker` | PM2 | 268368 | ✅ online |
| `metasenderpro-gmscraper` | PM2 | 268369 | ✅ online |

### 4.6 Testes Executados

| Teste | Resultado | Detalhe |
|---|---|---|
| Go health | ✅ OK | `{"status":"ok","version":"0.1.0"}` |
| Web interface | ✅ 200 | `https://sender.metanivelpro.com/` |
| DB tables | ✅ 76 | Identico ao main |
| Credenciais | ✅ limpo | Nenhum dado do main |
| PM2 | ✅ 3 online | Todos com prefixo `metasenderpro-` |
| Systemd | ✅ active | Port 8101 listening |
| **Ligacao + auto-hangup** | **✅ OK** | Audio: 7s. OnFinish: 11:12:18. Hangup: 11:12:20 (2s depois). Reason: `hangup` |
| Call API | ✅ OK | `GET /call/list` retornou `{"status":"success"}` |

### 4.7 Timeline da Ligacao de Teste

```
11:12:02  Call placed (offer sent)
11:12:07  Peer preaccepted (tocando no destino)
11:12:10  Call answered — audio comecou a tocar
11:12:10  Safety fallback scheduled (7s + 3s = 10s)
11:12:18  Audio finished (OnFinish callback)
11:12:20  Auto-hangup executado (2s apos OnFinish)
11:12:20  Call ended (reason: hangup)
```

---

## 4.8 Resultado da Execucao — Chatbut

> Executado em 2026-08-17. Replicado a partir do main v8.5.15 (mesmo template da secao 2).

### 4.8.1 Banco de Dados

| Item | Antes | Depois |
|---|---|---|
| Total de tabelas | 75 | 76 |
| Tabelas adicionadas | — | `sp_clone_group_queue`, `sp_export_participants_queue`, `sp_whatsapp_schedule_groups` |
| Colunas adicionadas | — | `sp_bb_message_buffer.push_name`, `sp_gmscraper_jobs.{ids,target_phonebook,delay_seconds,created,changed,ddi,proxy}`, `sp_gmscraper_leads.{reviews,address,website,created}`, `sp_whatsapp_schedules.target_type` |
| Tabelas legado dropadas | `sp_whatsapp_autoresponder` (31 regs), `sp_whatsapp_chatbot` (306 regs) | 0 |
| Colunas legado dropadas | `sp_gmscraper_jobs.{created_at,updated_at,user_id}`, `sp_gmscraper_leads.scraped_at` | 0 |
| Backup dados legado | — | `/tmp/chatbut_legacy_backup.sql` (servidor e local) |

> Observacao: o MySQL do Chatbut usa socket `/tmp/mysql.sock` (painel BT/宝塔). Usar `mysql -h 127.0.0.1 -P 3306` em todas as operacoes.

### 4.8.2 Codigo

| Pasta | Acao | Status |
|---|---|---|
| `inc/core/`, `app/`, `assets/`, `migrations/`, `sql/`, `.spec/`, `docs/`, `_bmad/` | rsync completo (`--rsync-path="sudo rsync"`) | ✅ |
| `app_zapmatic_whatsmeow_api/` | rsync + compilacao Go | ✅ |
| `app_zapmatic_scraper/` | rsync + npm install (chown node_modules) | ✅ |
| Root files | scp + sudo mv | ✅ |
| `.env` | NAO substituido | ✅ preservado |
| `writable/` | NAO substituido | ✅ preservado |

### 4.8.3 Go Binary

| Item | Valor |
|---|---|
| Compilacao | `CGO_ENABLED=1` (necessario sqlite3/whatsmeow) |
| Local | No proprio servidor Chatbut (nao cross-compile) |
| Tamanho | 29MB |
| Arquitetura | linux/amd64 (ELF 64-bit) |
| Go version | 1.24.5 (ja instalado no Chatbut) |

### 4.8.4 Credenciais

| Arquivo | Conteudo | Cross-ref com main |
|---|---|---|
| `.env` | `sql_alex_db` / `chatbut.com.br` | ✅ ZERO |
| `config.json` | port 8097 / `sql_alex_db` / webhook `chatbut.com.br` | ✅ ZERO |
| `ecosystem.config.js` | prefixo `chatbut-` (3 workers: bot, call, gmscraper) | ✅ ZERO |
| systemd service | port 8097 / user `www` | ✅ ZERO |

### 4.8.5 Processos

| Processo | Tipo | Status |
|---|---|---|
| `zapmatic-whatsmeow-chatbut` | systemd | ✅ active (port 8097) |
| `chatbut-bot-worker-all` | PM2 | ✅ online |
| `chatbut-call-worker` | PM2 | ✅ online |
| `chatbut-gmscraper` | PM2 | ✅ online |

### 4.8.6 Testes Executados

| Teste | Resultado | Detalhe |
|---|---|---|
| Go health | ✅ OK | `{"connected":1,"provider":"whatsmeow","status":"ok","total_instances":1,"version":"0.1.0"}` |
| Web interface | ✅ 200 | `https://chatbut.com.br/` |
| DB tables | ✅ 76 | Identico ao main |
| Credenciais | ✅ limpo | Nenhum dado do main |
| PM2 | ✅ 3 online | Prefixo `chatbut-` |
| Systemd | ✅ active | Port 8097 listening |
| Call API | ✅ OK | `GET /call/list` retornou `{"calls":[],"status":"success","total":0}` |
| Cron export queue | ✅ OK | `GET /whatsapp_export_participants/cron` → `ok` (HTTP 200) |

### 4.8.7 Processos e Crons (verificacao adicional)

**Processos:** todos ativos (systemd `active`, 3 workers PM2 `online`).

**Crons — correcoes aplicadas:**

| Item | Estado antes | Acao | Estado depois |
|---|---|---|---|
| Cron export/clone queue | curl para `kivozap.com.br` (domínio errado) no crontab do `ubuntu` | Removido; criado `/www/server/cron/chatbut_export_queue.sh` (curl `chatbut.com.br`) e registrado no crontab do `root` | ✅ `* * * * *` apontando para `chatbut.com.br` |
| Script export queue | inexistente | Criado `chatbut_export_queue.sh` (padrao identico ao `zapmatic_export_queue.sh` do main) | ✅ `/www/server/cron/chatbut_export_queue.sh` (chmod 700 root) |
| Crons BT Panel (SSL, backup, limpeza files) | ja existiam | mantidos | ✅ OK |
| Crons `pm2 restart pedidu/arthur` | pertencem a outros sistemas do servidor | NAO alterados | mantidos |

> **Nota:** no main, o script `zapmatic_export_queue.sh` existe mas NAO esta registrado em nenhum crontab (a fila de export/clone tambem esta parada no main). No Chatbut, alem de criar o script, o cron foi DEVIDAMENTE registrado (a cada minuto) — garantindo que a fila de export/clone funcione.

---

## 4.9 Resultado da Atualizacao — MetaSenderPro (2026-08-20, v8.5.18)

> Atualizacao para incorporar o fix de auto-reconexao do socket whatsmeow (commit `38a6049c`, tag `v8.5.18`) e manter paridade total com o main.

### 4.9.1 Banco de Dados

| Item | Resultado |
|---|---|
| Total de tabelas | 76 (identico ao main) |
| Colunas diferentes | 0 (100% identicas) |
| Migration necessaria | NENHUMA |

### 4.9.2 Codigo

| Pasta | Acao | Status |
|---|---|---|
| `inc/`, `app/`, `app_zapmatic_scraper/`, `app_zapmatic_whatsmeow_api/`, `assets/`, `migrations/`, `sql/`, `.spec/`, `docs/`, `_bmad/` | rsync completo (`--rsync-path="sudo rsync"`) | ✅ |
| Root files | scp + sudo mv | ✅ |
| `.env` | NAO substituido | ✅ preservado (`sql_metasenderpro_db`) |
| `writable/` | NAO substituido | ✅ preservado |
| `storage/sessions/` (SQLite) | NAO substituido | ✅ preservado (2 instancias) |

### 4.9.3 Go Binary

| Item | Valor |
|---|---|
| Compilacao | `CGO_ENABLED=1` no proprio servidor |
| Go version | 1.22.6 |
| Includes | fix auto-reconexao socket (`internal/sender/text.go`) |

### 4.9.4 Credenciais (restauradas apos rsync)

| Arquivo | Conteudo | Cross-ref main |
|---|---|---|
| `.env` | `sql_metasenderpro_db` / `sender.metanivelpro.com` | ✅ ZERO |
| `config.json` | port 8101 / `sql_metasenderpro_db` / webhook `sender.metanivelpro.com` | ✅ ZERO |
| `ecosystem.config.js` | prefixo `metasenderpro-` (4 workers: bot, call, gmscraper, cloud-campaign) | ✅ ZERO |
| systemd service | port 8101 / user `www` | ✅ ZERO |

### 4.9.5 Processos

| Processo | Tipo | Status |
|---|---|---|
| `zapmatic-whatsmeow-metasenderpro` | systemd | ✅ active (port 8101) |
| `metasenderpro-bot-worker-all` | PM2 | ✅ online |
| `metasenderpro-call-worker` | PM2 | ✅ online |
| `metasenderpro-gmscraper` | PM2 | ✅ online |
| `metasenderpro-cloud-campaign-worker` | PM2 | ✅ online (novo worker) |

### 4.9.6 Testes Executados

| Teste | Resultado | Detalhe |
|---|---|---|
| Go health | ✅ OK | `{"connected":2,"provider":"whatsmeow","status":"ok","total_instances":2,"version":"0.1.0"}` |
| Web interface | ✅ 200 | `https://sender.metanivelpro.com/` |
| DB tables | ✅ 76 | Identico ao main |
| Credenciais | ✅ limpo | Nenhum dado do main no config.json/.env |
| PM2 | ✅ 4 online | Prefixo `metasenderpro-` |
| Systemd | ✅ active | Port 8101 |
| Call API | ✅ OK | `GET /call/list` → `{"calls":[],"status":"success","total":0}` |
| Sessoes SQLite | ✅ 2 preservadas | instancias reconectaram apos restart |

> **Nota:** foi adicionado o worker `metasenderpro-cloud-campaign-worker` (spark `cloud:campaigns`) para acompanhar o main, que passou a ter 4 workers PM2.

### 4.9.7 Divida Tecnica Futura — Consolidar Cloud API no Go

> **Status:** PENDENTE (sera executado sob demanda — o usuario solicitara quando precisar)

O worker PHP `CloudCampaignWorker` (`cloud:campaigns`) e uma **solucao temporaria** para destravar campanhas Cloud API que ficaram paradas apos a remocao do Node.js/Baileys (commit `7ffefe8d`). A solucao definitiva e consolidar o envio de campanhas Cloud API dentro do proprio gateway Go, eliminando a duplicacao de logica.

**Motivacao (por que consolidar):**
- Hoje existem **dois despachantes** de campanha em massa: Go (whatsmeow) + PHP (Cloud API), cada um com sua propria implementacao de lock otimista (`run`) e offset persistente (`sent`+`failed`).
- O Go **ja possui** o codigo de envio Cloud API pronto (`sendViaCloudAPIHTTP` em `internal/bulk/processor.go`), mas ele e **codigo morto** no caminho de campanha: o `return` da linha 164 (`Skip campaigns that don't use whatsmeow`) barra antes do roteamento por provider da linha 220.
- Manter a logica duplicada em PHP e Go e fragmental e dificulta manutencao.

**Escopo do trabalho futuro:**
1. Remover o `return` de skip nao-whatsmeow (linha ~164 do `internal/bulk/processor.go`), deixando o Go rotear `whatsmeow` / `cloud_api` / `baileys` no mesmo loop.
2. Completar `sendViaCloudAPIHTTP` no Go para suportar botao, lista e template oficial (hoje so cobre texto e imagem) — portar a logica de `buildCloudAPIPayload` do `WhatsAppGatewayService.php`.
3. Aposentar `app/Commands/CloudCampaignWorker.php` e remover o 4o worker (`*-cloud-campaign-worker`) do `ecosystem.config.js`, voltando a 3 workers PM2.
4. Re-testar o fluxo Cloud API ponta a ponta (envio, template, botao, lista, offset/lock).

**Beneficios esperados:**
- Um unico engine de campanhas (Go) e uma unica fonte de verdade de lock/offset.
- Menos um processo PM2 (volta a 3 workers).
- Alinhado ao principio do spec: main como referencia unica, comportamento identico em todos os tenants.

---

## 4.10 Resultado da Atualizacao — Astros (2026-08-20, v8.5.18)

> Sincronizacao completa do tenant **Astros** (local, mesmo servidor do main) a partir do main v8.5.18 (commit `38a6049c`). Seguiu fielmente o template da secao 2.

### 4.10.1 Dados do Tenant

| Variavel | Valor |
|---|---|
| `{PATH}` | `/www/wwwroot/app.astroscomunicacaodigital.com` |
| `{DOMAIN}` | `app.astroscomunicacaodigital.com` |
| `{DB_NAME}` / `{DB_USER}` | `sql_eudezio_db` |
| `{GO_PORT}` | 8094 |
| `{TENANT}` / `{TENANT_NAME}` | `astros` / Astros |
| `{GO_SERVICE}` | `zapmatic-whatsmeow-astros` |
| Localizacao | LOCAL (mesma maquina do main, BT Panel) |

### 4.10.2 Banco de Dados

| Item | Antes | Depois |
|---|---|---|
| Total de tabelas | 75 | 76 |
| Tabelas adicionadas | — | `sp_clone_group_queue`, `sp_export_participants_queue`, `sp_whatsapp_schedule_groups` |
| Tabelas legado dropadas | `sp_whatsapp_autoresponder` (0 regs), `sp_whatsapp_chatbot` (127 regs) | 0 |
| Backup dados legado | — | `/tmp/astros_legacy_backup.sql` (101KB, local) |
| Colunas normalizadas | — | `sp_bb_message_buffer` (recriada: 0 regs; ganhou `push_name`, `messages`/`first_message` `json`, `id int`, defaults `3`/`30`), `sp_bb_sessions` (31 regs preservadas; `timeout_at int`, `timeout_retry_msg`/`timeout_exit_msg` `text`, `timeout_instance_id`/`reply_phone` `varchar(255)`), `sp_bot_builders` (1 reg preservada; `debounce_seconds` default `0`) |

> Diffs restantes apos migration sao **apenas comentarios** (`COMMENT '...'`), cosmeticos e ignorados pelo MySQL.

### 4.10.3 Codigo

| Pasta | Acao | Status |
|---|---|---|
| `inc/`, `app/`, `app_zapmatic_scraper/`, `app_zapmatic_whatsmeow_api/`, `assets/`, `migrations/`, `sql/`, `.spec/`, `docs/`, `_bmad/` | rsync completo (`sudo rsync -az --delete`) | ✅ |
| Root files | `sudo cp -p` (index.php, spark, composer.json, version.json, etc.) | ✅ |
| `.env` | NAO substituido | ✅ preservado |
| `writable/` | NAO substituido | ✅ preservado |
| `storage/sessions/` (SQLite) | NAO substituido | ✅ preservado (whatsmeow.db 383MB) |
| `config.json` (Go) | NAO substituido | ✅ preservado (port 8094 / sql_eudezio_db) |
| Composer | `sudo composer install --no-dev --optimize-autoloader` (16 pacotes atualizados) | ✅ |
| NPM (scraper) | `sudo npm install --production` (up to date) | ✅ |

> **Nota:** transferencia LOCAL exigiu `sudo rsync` direto (o `--rsync-path="sudo rsync"` so funciona via SSH). Sem sudo, o rsync falhou com `Permission denied` pois os diretorios pertencem a `root`/`www`.

### 4.10.4 Go Binary

| Item | Valor |
|---|---|
| Compilacao | `CGO_ENABLED=1` no proprio servidor (Go 1.25.11, `/usr/local/go/bin/go`) |
| Arquitetura | linux/arm64 (ARM aarch64) |
| Tamanho | 27.7MB |
| Owner | `www:www` |

### 4.10.5 Credenciais

| Arquivo | Conteudo | Cross-ref main |
|---|---|---|
| `.env` | `sql_eudezio_db` | ✅ ZERO |
| `config.json` | port 8094 / `sql_eudezio_db` / webhook `app.astroscomunicacaodigital.com` | ✅ ZERO |
| `ecosystem.config.js` | prefixo `astros-` (4 workers: bot, call, gmscraper, cloud-campaign) | ✅ ZERO |

### 4.10.6 Processos

| Processo | Tipo | Status |
|---|---|---|
| `zapmatic-whatsmeow-astros` | systemd | ✅ active (port 8094) |
| `astros-bot-worker-all` | PM2 | ✅ online |
| `astros-call-worker` | PM2 | ✅ online |
| `astros-gmscraper` | PM2 | ✅ online |
| `astros-cloud-campaign-worker` | PM2 | ✅ online (novo worker) |

### 4.10.7 Testes Executados

| Teste | Resultado | Detalhe |
|---|---|---|
| Go health | ✅ OK | `{"connected":20,"provider":"whatsmeow","status":"ok","total_instances":22,"version":"0.1.0"}` |
| Web interface | ✅ 200 | `https://app.astroscomunicacaodigital.com/` |
| DB tables | ✅ 76 | Identico ao main |
| Credenciais | ✅ limpo | Nenhum dado do main no config.json/.env |
| PM2 | ✅ 4 online | Prefixo `astros-`, restarts=0 |
| Systemd | ✅ active | Port 8094 |
| Call API | ✅ OK | `GET /call/list` → `{"calls":[],"status":"success","total":0}` |
| Sessoes SQLite | ✅ preservadas | `whatsmeow.db` + `instance_map.json` intactos |
| CloudCampaignWorker | ✅ OK | log: `[CloudCampaignWorker] Starting (Cloud API dispatcher)...` |

> **Nota:** o `version.json` ficou em `8.5.15` (copiado do main, que esta desatualizado — o git do main ja esta em `v8.5.18`). Inconsistencia PRE-EXISTENTE no main, nao causada por esta sync.

### 4.10.8 Correcao Pos-Execucao — Composer Dev Deps (phpunit)

> **Problema:** apos o `composer install --no-dev`, o site retornou `Fatal error: require(phpunit/phpunit/src/Framework/Assert/Functions.php): Failed to open stream`.

**Causa raiz:** o `composer.json` do main tem `phpunit/phpunit` em `require-dev`, mas o `autoload` do projeto (via `autoload_files.php`/`autoload_static.php`) referencia o arquivo `phpunit/phpunit/src/Framework/Assert/Functions.php` como um arquivo a ser carregado automaticamente. O `--no-dev` removeu o phpunit, deixando o autoload com referencia quebrada.

**Correcao aplicada:**
1. Rodar `composer install --optimize-autoloader` (COM dev deps) para instalar `phpunit/phpunit`, `fakerphp/faker` e `mikey179/vfsstream` — replicando o estado exato do main.
2. Reiniciar o PHP-FPM 8.1 (`/etc/init.d/php-fpm-81 restart`) para limpar o OPcache, que servia o autoload antigo.

**Resultado:** site voltou a `HTTP 200`, sem erro de phpunit.

> **Licao:** o template da secao 2 usa `composer install --no-dev`, mas o main tem o `vendor/` instalado COM dev deps (phpunit). Para manter paridade exata de `vendor/`, deve-se usar `composer install` SEM `--no-dev` neste projeto.

---

## 4.11 Resultado da Atualizacao — Paulo (2026-08-20, v8.5.18)

> Sincronizacao completa do tenant **Paulo** (local, mesmo servidor do main) a partir do main v8.5.18 (commit `38a6049c`). Seguiu o template da secao 2 com as correcoes da secao 4.10.8 (composer COM dev deps + restart PHP-FPM).

### 4.11.1 Dados do Tenant

| Variavel | Valor |
|---|---|
| `{PATH}` | `/www/wwwroot/app_paulo_app` |
| `{DOMAIN}` | `atualizaleads.app.br` |
| `{DB_NAME}` / `{DB_USER}` | `sql_paulo_db` |
| `{GO_PORT}` | 8091 |
| `{TENANT}` / `{TENANT_NAME}` | `paulo` / Paulo |
| `{GO_SERVICE}` | `zapmatic-whatsmeow-paulo` |
| Localizacao | LOCAL (mesma maquina do main) |

### 4.11.2 Banco de Dados

| Item | Antes | Depois |
|---|---|---|
| Total de tabelas | 75 | 76 |
| Tabelas adicionadas | — | `sp_clone_group_queue`, `sp_export_participants_queue`, `sp_whatsapp_schedule_groups` |
| Tabelas legado dropadas | `sp_whatsapp_autoresponder` (13 regs), `sp_whatsapp_chatbot` (127 regs) | 0 |
| Backup dados legado | — | `/tmp/paulo_legacy_backup.sql` (105KB, local) |
| Colunas/tabelas normalizadas | — | `sp_bb_message_buffer` (recriada, 0 regs), `sp_bb_sessions` (recriada, 0 regs), `sp_bot_builders` (ALTER default `debounce_seconds`) |

### 4.11.3 Codigo

| Pasta | Acao | Status |
|---|---|---|
| `inc/`, `app/`, `app_zapmatic_scraper/`, `app_zapmatic_whatsmeow_api/`, `assets/`, `migrations/`, `sql/`, `.spec/`, `docs/`, `_bmad/` | `sudo rsync -az --delete` | ✅ |
| Root files | `sudo cp -p` (index.php, spark, composer.json, version.json, etc.) | ✅ |
| `.env` | NAO substituido | ✅ preservado (`sql_paulo_db`) |
| `writable/` | NAO substituido | ✅ preservado |
| `storage/sessions/` (SQLite) | NAO substituido | ✅ preservado (whatsmeow.db 385MB) |
| `config.json` (Go) | NAO substituido | ✅ preservado (port 8091 / sql_paulo_db) |
| Composer | `sudo composer install --optimize-autoloader` (COM dev deps) | ✅ |
| NPM (scraper) | `sudo npm install --production` | ✅ |

### 4.11.4 Go Binary

| Item | Valor |
|---|---|
| Compilacao | `CGO_ENABLED=1` no proprio servidor (Go 1.25.11) |
| Arquitetura | linux/arm64 (ARM aarch64) |
| Tamanho | 27.7MB |
| Owner | `www:www` |

### 4.11.5 Credenciais

| Arquivo | Conteudo | Cross-ref main |
|---|---|---|
| `.env` | `sql_paulo_db` | ✅ ZERO |
| `config.json` | port 8091 / `sql_paulo_db` / webhook `atualizaleads.app.br` | ✅ ZERO |
| `ecosystem.config.js` | prefixo `paulo-` (4 workers: bot, call, gmscraper, cloud-campaign) | ✅ ZERO |

### 4.11.6 Processos

| Processo | Tipo | Status |
|---|---|---|
| `zapmatic-whatsmeow-paulo` | systemd | ✅ active (port 8091) |
| `paulo-bot-worker-all` | PM2 | ✅ online |
| `paulo-call-worker` | PM2 | ✅ online |
| `paulo-gmscraper` | PM2 | ✅ online |
| `paulo-cloud-campaign-worker` | PM2 | ✅ online (novo worker) |

### 4.11.7 Testes Executados

| Teste | Resultado | Detalhe |
|---|---|---|
| Go health | ✅ OK | `{"connected":8,"provider":"whatsmeow","status":"ok","total_instances":11,"version":"0.1.0"}` |
| Web interface | ✅ 200 | `https://atualizaleads.app.br/` (sem erro phpunit) |
| DB tables | ✅ 76 | Identico ao main |
| Credenciais | ✅ limpo | Nenhum dado do main |
| PM2 | ✅ 4 online | Prefixo `paulo-`, restarts=0 |
| Systemd | ✅ active | Port 8091 |
| Call API | ✅ OK | `GET /call/list` → `{"calls":[],"status":"success","total":0}` |
| Sessoes SQLite | ✅ preservadas | `whatsmeow.db` + `instance_map.json` intactos |
| CloudCampaignWorker | ✅ OK | log: `[CloudCampaignWorker] Starting (Cloud API dispatcher)...` |

---

## 4.12 Resultado da Atualizacao — Elias (2026-08-20, v8.5.18)

> Sincronizacao completa do tenant **Elias** (local, mesma maquina) a partir do main v8.5.18.

### 4.12.1 Dados do Tenant

| Variavel | Valor |
|---|---|
| `{PATH}` | `/www/wwwroot/app_elias_app` |
| `{DOMAIN}` | `multiconnecta.com.br` |
| `{DB_NAME}` / `{DB_USER}` | `sql_elias_db` |
| `{GO_PORT}` | 8092 |
| `{TENANT}` / `{TENANT_NAME}` | `elias` / Elias |
| `{GO_SERVICE}` | `zapmatic-whatsmeow-elias` |

### 4.12.2 Banco de Dados

| Item | Antes | Depois |
|---|---|---|
| Total de tabelas | 73 | 76 |
| Tabelas adicionadas | — | `sp_clone_group_queue`, `sp_export_participants_queue`, `sp_whatsapp_schedule_groups` |
| Tabelas legado dropadas | 0 (ja tinham sido dropadas) | 0 |
| Tabelas normalizadas | — | `sp_bb_message_buffer` (recriada, 0 regs; ganhou UNIQUE KEY `idx_phone_instance`), `sp_bb_sessions` (recriada, 0 regs) |

> **Observacao:** o Elias ja NAO tinha as tabelas legado (`sp_whatsapp_autoresponder`, `sp_whatsapp_chatbot`). A normalizacao focou em `sp_bb_message_buffer` (faltava a UNIQUE KEY e tinha `id bigint`/`account_id NULL`/`last_at NULL`) e `sp_bb_sessions` (tipos `timeout_at`, `timeout_*_msg`, `timeout_instance_id`, `reply_phone`).

### 4.12.3 Codigo e Go Binary

| Item | Valor |
|---|---|
| Codigo | `sudo rsync -az --delete` + root files + `composer install --optimize-autoloader` (COM dev deps) + `npm install --production` |
| Go binary | `CGO_ENABLED=1`, arm64, 27.7MB, owner `www:www` |
| `.env` / `config.json` / sessions | preservados (cross-ref main = 0) |
| Ecosystem | 4 workers prefixo `elias-` |

### 4.12.4 Testes

| Teste | Resultado |
|---|---|
| Go health | ✅ `connected:12` de 15 instancias |
| Web | ✅ 200 (`multiconnecta.com.br`) |
| DB tables | ✅ 76 |
| PM2 | ✅ 4 online (restarts=0) |
| Systemd | ✅ active (8092) |
| Call API | ✅ `{"calls":[],"status":"success","total":0}` |
| CloudCampaignWorker | ✅ `[CloudCampaignWorker] Starting...` |

---

## 4.13 Resultado da Atualizacao — Renovo (2026-08-20, v8.5.18)

> Sincronizacao completa do tenant **Renovo** (local, mesma maquina) a partir do main v8.5.18.

### 4.13.1 Dados do Tenant

| Variavel | Valor |
|---|---|
| `{PATH}` | `/www/wwwroot/renovo_app` |
| `{DOMAIN}` | `renovo.app` |
| `{DB_NAME}` / `{DB_USER}` | `db_renovo_sql` |
| `{GO_PORT}` | 8093 |
| `{TENANT}` / `{TENANT_NAME}` | `renovo` / Renovo |
| `{GO_SERVICE}` | `zapmatic-whatsmeow-renovo` |

### 4.13.2 Banco de Dados

| Item | Antes | Depois |
|---|---|---|
| Total de tabelas | 75 | 76 |
| Tabelas adicionadas | — | `sp_clone_group_queue`, `sp_export_participants_queue`, `sp_whatsapp_schedule_groups` |
| Tabelas legado dropadas | `sp_whatsapp_autoresponder` (2 regs), `sp_whatsapp_chatbot` (0 regs) | 0 |
| Backup dados legado | — | `/tmp/renovo_legacy_backup.sql` (4KB, local) |
| Colunas normalizadas | — | `sp_bb_message_buffer` (recriada, 0 regs), `sp_whatsapp_phone_numbers.phone` (ALTER `varchar(100) NULL` → `varchar(128) NOT NULL`; 21232 regs, 0 NULL, max 13 chars) |

> **Observacao:** `sp_bb_sessions` (69 regs, 68 completed) ja estava com estrutura identica ao main — NAO foi recriada, dados preservados.

### 4.13.3 Codigo e Go Binary

| Item | Valor |
|---|---|
| Codigo | `sudo rsync -az --delete` + root files + `composer install --optimize-autoloader` (COM dev deps) + `npm install --production` |
| Go binary | `CGO_ENABLED=1`, arm64, 27.7MB, owner `www:www` |
| Nota Go build | precisou de `-buildvcs=false` (diretorio sem .git gerava "error obtaining VCS status") |
| `.env` / `config.json` / sessions | preservados (cross-ref main = 0) |
| Ecosystem | 4 workers prefixo `renovo-` |

### 4.13.4 Testes

| Teste | Resultado |
|---|---|
| Go health | ✅ `connected:12` de 12 instancias |
| Web | ✅ 302 → `/login` (comportamento normal de auth), final 200 |
| DB tables | ✅ 76 |
| PM2 | ✅ 4 online (restarts=0) |
| Systemd | ✅ active (8093) |
| Call API | ✅ `{"calls":[],"status":"success","total":0}` |
| Sessoes SQLite | ✅ preservadas (whatsmeow.db + instance_map.json) |
| CloudCampaignWorker | ✅ `[CloudCampaignWorker] Starting...` |

---

## 4.14 Resultado da Atualizacao — Frank/AgenciaMCW (2026-08-20, v8.5.18)

> Sincronizacao completa do tenant **Frank (AgenciaMCW)** — servidor remoto `144.22.167.45` (key auth), a partir do main v8.5.18.

### 4.14.1 Dados do Tenant

| Variavel | Valor |
|---|---|
| `{PATH}` | `/www/wwwroot/app_frank_agencia` |
| `{DOMAIN}` | `chatbot.agenciamcw.com.br` |
| `{DB_NAME}` / `{DB_USER}` | `sql_frank_db` |
| `{GO_PORT}` | 8096 |
| `{TENANT}` | `frank` (PM2 prefixo `frank-`; systemd `zapmatic-whatsmeow-agenciamcw`) |
| Acesso SSH | `ubuntu@144.22.167.45` (key auth) |
| MySQL | socket `/tmp/mysql.sock` (usar `-h 127.0.0.1 -P 3306`) |
| PHP-FPM | 8.2 (`/www/server/php/82/`) |
| Arquitetura | x86-64 |

### 4.14.2 Banco de Dados

| Item | Antes | Depois |
|---|---|---|
| Total de tabelas | 78 | 76 |
| Tabelas adicionadas | 0 (ja tinha as 76 do main) | — |
| Tabelas legado dropadas | `sp_whatsapp_autoresponder` (1 reg), `sp_whatsapp_chatbot` (143 regs) | 0 |
| Backup dados legado | — | `/tmp/frank_legacy_backup.sql` (110KB, local) |
| Colunas/tabelas normalizadas | — | `sp_bb_message_buffer` (recriada, 0 regs; ganhou `push_name` + tipos `json`), `sp_bb_sessions` (ALTER, 65 regs preservadas), `sp_bot_builders.debounce_seconds` (ALTER default), `sp_call_campaigns.status` (ALTER `varchar(20)` → `enum`) |

> **Nota collation:** as tabelas do Frank usam `utf8mb4_general_ci` enquanto o main usa `utf8mb4_unicode_ci`. Diferenca de ordenacao/comparacao, ambos utf8mb4 — NAO alterada (baixo risco; rebuild pesado). Documentada como divergencia aceitavel.

### 4.14.3 Codigo e Go Binary

| Item | Valor |
|---|---|
| Codigo | `rsync -az --delete --rsync-path="sudo rsync"` (10 pastas) + root files via scp |
| Composer | `sudo composer install --optimize-autoloader` (COM dev deps) |
| NPM | `sudo npm install --production` |
| Go binary | `CGO_ENABLED=1`, `-buildvcs=false`, x86-64, 29.2MB |
| `.env` / `config.json` | preservados (MD5 identico, cross-ref main = 0) |
| Sessoes SQLite | preservadas (whatsmeow.db 54MB + instance_map.json) |
| Ecosystem | 4 workers prefixo `frank-` |

### 4.14.4 Testes

| Teste | Resultado |
|---|---|
| Go health | ✅ `connected:6` de 6 instancias |
| Web | ✅ 200 (`chatbot.agenciamcw.com.br`, sem erro phpunit) |
| DB tables | ✅ 76 |
| PM2 | ✅ 4 online (restarts=0) |
| Systemd | ✅ active (8096) |
| Call API | ✅ `{"calls":[],"status":"success","total":0}` |
| CloudCampaignWorker | ✅ `[CloudCampaignWorker] Starting...` |

---

## 4.15 Resultado da Atualizacao — Kivozap (2026-08-20, v8.5.18)

> Sincronizacao completa do tenant **Kivozap** — servidor remoto `144.22.167.45` (key auth), a partir do main v8.5.18.

### 4.15.1 Dados do Tenant

| Variavel | Valor |
|---|---|
| `{PATH}` | `/www/wwwroot/app_abner_app` |
| `{DOMAIN}` | `kivozap.com.br` |
| `{DB_NAME}` / `{DB_USER}` | `db_abner_sql` |
| `{GO_PORT}` | 8090 (⚠️ divergencia da spec, ver nota abaixo) |
| `{TENANT}` | `kivozap` (PM2 prefixo `kivozap-`; systemd `zapmatic-whatsmeow-kivozap`) |
| Acesso SSH | `ubuntu@144.22.167.45` (key auth) |
| MySQL | socket `/tmp/mysql.sock` (usar `-h 127.0.0.1 -P 3306`) |
| PHP-FPM | 8.2 (`/www/server/php/82/`) |
| Arquitetura | x86-64 |

> **⚠️ Nota porta:** o `deploy_servers.json` registra Kivozap na porta 8095, mas o `config.json` real usa porta **8090** e o sistema esta funcionando nela. NAO alterada (preservado o que funciona). Divergencia de documentacao registrada.

### 4.15.2 Banco de Dados

| Item | Antes | Depois |
|---|---|---|
| Total de tabelas | 76 | 76 (ja em paridade) |
| Tabelas legado | 0 (ja estavam limpas) | 0 |
| Colunas/tabelas normalizadas | — | `sp_bb_message_buffer` (recriada, 0 regs; ganhou `push_name` + tipos `json`), `sp_bot_builders.debounce_seconds` (ALTER default, 32 regs preservadas) |

> **Observacao:** `sp_bb_sessions` (5964 regs) ja estava com estrutura identica ao main — NAO foi tocada, dados preservados.

### 4.15.3 Codigo e Go Binary

| Item | Valor |
|---|---|
| Codigo | `rsync -az --delete --rsync-path="sudo rsync"` (10 pastas) + root files via scp |
| Composer | `sudo composer install --optimize-autoloader` (COM dev deps) |
| NPM | `sudo npm install --production` |
| Go binary | `CGO_ENABLED=1`, `-buildvcs=false`, x86-64, 29.2MB |
| `.env` / `config.json` | preservados (MD5 identico, cross-ref main = 0) |
| Sessoes SQLite | preservadas (whatsmeow.db 740MB + instance_map.json) |
| Ecosystem | 4 workers prefixo `kivozap-` |

### 4.15.4 Testes

| Teste | Resultado |
|---|---|
| Go health | ✅ `connected:37` de 37 instancias (maior de todos os tenants) |
| Web | ✅ 200 (`kivozap.com.br`, sem erro phpunit) |
| DB tables | ✅ 76 |
| PM2 | ✅ 4 online (restarts=0) |
| Systemd | ✅ active (8090) |
| Call API | ✅ `{"calls":[],"status":"success","total":0}` |
| CloudCampaignWorker | ✅ `[CloudCampaignWorker] Starting...` |

---

## 4.16 Resultado da Atualizacao — Chatbut (2026-08-20, v8.5.18)

> Finalizacao do tenant **Chatbut** — servidor remoto `144.22.167.45` (key auth), a partir do main v8.5.18. O Chatbut ja estava PARCIAL (codigo v8.5.15, CCW presente, DB 76 tab) mas faltava: 4o worker no PM2, dev deps (phpunit) e recompilacao do binario Go.

### 4.16.1 Dados do Tenant

| Variavel | Valor |
|---|---|
| `{PATH}` | `/www/wwwroot/app_alex_pedidu_app` |
| `{DOMAIN}` | `chatbut.com.br` |
| `{DB_NAME}` / `{DB_USER}` | `sql_alex_db` |
| `{GO_PORT}` | 8097 |
| `{TENANT}` | `chatbut` (PM2 prefixo `chatbut-`; systemd `zapmatic-whatsmeow-chatbut` com `User=www`) |
| Acesso SSH | `ubuntu@144.22.167.45` (key auth) |
| MySQL | socket `/tmp/mysql.sock` (usar `-h 127.0.0.1 -P 3306`) |
| PHP-FPM | 8.2 (`/www/server/php/82/`) |
| Arquitetura | x86-64 |

### 4.16.2 Banco de Dados

| Item | Antes | Depois |
|---|---|---|
| Total de tabelas | 76 | 76 (ja em paridade) |
| Tabelas legado | 0 | 0 |
| Tabelas normalizadas | — | `sp_bb_message_buffer` (recriada, 0 regs), `sp_bb_sessions` (ALTER, 332 regs preservadas), `sp_bot_builders.debounce_seconds` (ALTER default, 18 regs), `sp_gmscraper_jobs` (recriada, 0 regs), `sp_gmscraper_leads` (recriada, 0 regs) |

### 4.16.3 Codigo e Go Binary

| Item | Valor |
|---|---|
| Codigo | JA estava identico ao main (MD5 de CloudCampaignWorker, WhatsAppGatewayService e processor.go iguais) — NAO precisou rsync |
| Composer | `sudo composer install --optimize-autoloader` (COM dev deps — phpunit estava AUSENTE e foi instalado) |
| Go binary | Recompilado (`CGO_ENABLED=1`, `-buildvcs=false`, x86-64, 29.2MB, owner `www`) — estava desatualizado (Aug 19) |
| `.env` / `config.json` | preservados (MD5 identico, cross-ref main = 0) |
| Sessoes SQLite | preservadas (whatsmeow.db 20MB + instance_map.json) |
| Ecosystem | 4 workers prefixo `chatbut-` |

### 4.16.4 Testes

| Teste | Resultado |
|---|---|
| Go health | ⚠️ `connected:0` de 0 instancias (ver pendencia abaixo) |
| Web | ✅ 200 (`chatbut.com.br`, sem erro phpunit) |
| DB tables | ✅ 76 |
| PM2 | ✅ 4 online (restarts=0) |
| Systemd | ✅ active (8097) |
| Call API | ✅ `{"calls":[],"status":"success","total":0}` |
| CloudCampaignWorker | ✅ `[CloudCampaignWorker] Starting...` |

### 4.16.5 Pendencia Operacional — Reconectar Instancias WhatsApp

> **Situacao:** o `instance_map.json` do Chatbut esta `null` (4 bytes, literal "null") desde **18/08 10:21** — ANTES desta sync. O log do gateway confirma `"devices":0,"restored":0`, ou seja, o gateway inicia mas NAO restaura nenhuma sessao.

**Causa:** o mapeamento `instance_id → JID` foi zerado em 18/08 (provavelmente na migracao Baileys → whatsmeow). Ha 18 contas com `status=1` em `sp_accounts`, mas sem o `instance_map.json` o gateway nao consegue reconecta-las automaticamente.

**Resolucao (manual, via painel):** reconectar cada numero WhatsApp via QR code no painel do Chatbut. Ao escanear, o gateway regenera o `instance_map.json` e a sessao volta a conectar.

> **Nota:** NAO foi causado por esta sync — o `storage/sessions/` foi preservado no rsync, e o `instance_map.json` ja estava `null` (data 18/08) antes da intervencao.

---

## 4.17 Fix — Chamadas Recusadas Presas em `ringing` (fallback de call-id) (2026-08-20)

> **Escopo:** correcao no gateway Go (meowcaller) + replicacao no MetaSenderPro. Aplicado primeiro no main (comprovado por `/health` e `/call/list` limpos), depois propagado ao MetaSenderPro sob demanda.

### 4.17.1 Causa Raiz

Quando o servidor do WhatsApp **recusa** uma ligacao (erro `463` "misdial or blocked" ou `403` "forbidden"), ele responde com um `<ack class="call" error="...">` cujo call-id **nao** vem na tag filha `<error call-id="...">` — vem em outro atributo (ou em nenhum lugar previsivel).

O codigo original de `onCallAck` (em `vendor/github.com/purpshell/meowcaller/engine.go`) so procurava o call-id em **um unico lugar**:

```go
callID := ""
if en := findChild(ack, "error"); en != nil {
    callID = en.AttrGetter().String("call-id")
}
```

Como o nó de erro nao trazia `call-id` naquela posicao, `callID` ficava `""`. A chamada `finishCall("", ...)` e ignorada (guarda `if callID == "" { return }`), entao:

- O callback `OnEnd` **nunca dispara**.
- A entrada continua em `callStore` com `status: "ringing"` **para sempre**.
- O `GET /call/list` fica poluido e o worker de disparo de chamadas fica travado (a fila nao avanca porque ha chamadas "ativas").

### 4.17.2 O Ajuste (correlacao por stanza id — correcao definitiva)

> **Importante:** houve DUAS iteracoes. A primeira (fallback `for id := range e.calls`) era **incorreta** e foi substituida.

**Primeira tentativa (descarte):** adicionar `if callID == "" { ack.AttrGetter().String("id") }` e depois um fallback `for id := range e.calls { break }`. Esse ultimo pega uma chamada **aleatoria** do map (iteracao de map em Go nao e deterministica). Com mais de uma chamada ativa, ele podia encerrar a chamada **errada** e deixar a realmente rejeitada presa — exatamente o sintoma "ligava pro primeiro e falhava na segunda".

**Correcao definitiva:** o `<ack class="call" error="463|403">` **ecoa o stanza `id`** do `<call>` original como seu proprio atributo `id` (o call-id vive dentro do child `<offer>`, e nao e repetido pelo ack). O `engineCall` agora **guarda o stanza id** no `placeCall` e a correlacao e feita de forma deterministica:

1. `engineCall.stanzaID` (novo campo) e preenchido no `placeCall` com `cli.GenerateMessageID()` (o mesmo valor colocado em `offer.Attrs["id"]`).
2. No `onCallAck`, a resolucao do call-id segue esta ordem:
   ```go
   callID := ""
   if en := findChild(ack, "error"); en != nil {
       callID = en.AttrGetter().String("call-id")      // 1. <error call-id=...>
   }
   if callID == "" {
       callID = ack.AttrGetter().String("call-id")     // 2. atributo direto do <ack>
   }
   if callID == "" {
       callID = e.callIDByStanza(ack.AttrGetter().String("id"))  // 3. stanza id → call-id
   }
   if callID == "" {
       callID = e.onlyActiveCallID()                   // 4. unica chamada ativa (inequivoco)
   }
   ```
3. `callIDByStanza(stanzaID)` varre `e.calls` procurando o `engineCall` cujo `stanzaID` bate — correlacao exata, nunca aleatoria.
4. `onlyActiveCallID()` so retorna o call-id quando ha **exatamente uma** chamada ativa; com zero ou multiplas, retorna `""` (nunca encerra uma chamada errada).

Assim, a chamada recusada e encerrada imediatamente via `finishCall(callID, "server:"+errCode)`, o `OnEnd` dispara, o `callStore` registra `ended`/`failed` e o worker e liberado — **sem risco de encerrar a chamada errada em cenario de multiplas ligacoes simultaneas**.

> **Nota importante:** o arquivo `engine.go` esta em `vendor/` (ignorado pelo git — `.gitignore` linha `vendor/`). A correcao **nao e versionada** no repositorio main. Ela vive apenas no binario compilado + no arquivo `vendor/.../engine.go` do filesystem de cada servidor. Por isso a propagacao e **por copia de binario/source**, nao por `git pull`. Ver secao 4.17.5 (solucao definitiva via fork + replace).

### 4.17.3 Replicacao no MetaSenderPro

| Passo | Acao | Resultado |
|---|---|---|
| 1 | Backup remoto de `engine.go` | `engine.go.bak_pre_callid_fix` (53598 bytes) |
| 2 | scp do `engine.go` corrigido do main | md5 `8da64b1a...` (identico ao main) |
| 3 | Compilacao | `CGO_ENABLED=1 go build -mod=vendor -buildvcs=false` (Go 1.25.0 auto via toolchain cache) |
| 4 | Substituicao do binario | stop do service → `cp` (sem "Text file busy") → chown `www:www` |
| 5 | Restart | `zapmatic-whatsmeow-metasenderpro` → `active` |

### 4.17.4 Testes Apos o Fix (MetaSenderPro)

| Teste | Resultado |
|---|---|
| Go health | ✅ `{"connected":2,"provider":"whatsmeow","status":"ok","total_instances":2,"version":"0.1.0"}` |
| Call API | ✅ `{"calls":[],"status":"success","total":0}` (antes: 8 chamadas presas em `ringing`) |
| Systemd | ✅ `active` |

> **Antes do fix**, o `GET /call/list` do MetaSenderPro retornava `total: 8` com todas em `status: "ringing"` (chamadas presas desde 21:40). **Apos o restart com o binario corrigido**, `total: 0` — o fallback encerrou/limpou as chamadas recusadas e o worker voltou a avancar.

### 4.17.5 Solucao Definitiva — Fork do meowcaller + replace no go.mod

> **Status:** CONCLUIDO (2026-08-20)

A divida tecnica anterior (patch vivendo apenas no `vendor/` nao-versionado) foi resolvida de forma duradoura:

1. **Fork** do `purpshell/meowcaller` para `lonardonetto/meowcaller` (via API GitHub).
2. **Patch** aplicado no fork sobre o commit pinado `6d9b7b2c1807`:
   - `0280b2b` — primeira versao (fallback em cascata simples).
   - `d9c29a2` — **versao definitiva** (correlacao por stanza id; corrige o problema da segunda ligacao em cenario de multiplas chamadas).
   - Tag: `v0.0.1-callid` (a tag anterior `v0.0.0-...-callid` foi removida por apontar para a versao com fallback aleatorio).
3. **`replace`** no `go.mod` do main:
   ```
   replace github.com/purpshell/meowcaller => github.com/lonardonetto/meowcaller v0.0.1-callid
   ```
4. `go mod tidy` + `go mod vendor` + `go build` — o `vendor/` regenerado agora inclui a correlacao por stanza id automaticamente (verificado via `grep "stanzaID\|callIDByStanza\|onlyActiveCallID"`).

**Resultado:** o fix agora e **versionado** (go.mod + go.sum trackeados no repo). Qualquer novo `go mod vendor`/`go mod tidy` puxa o fork com o patch — nao ha mais risco de perder a correcao em builds futuros.

**Refs:**
- Fork: `https://github.com/lonardonetto/meowcaller`
- Tag: `v0.0.1-callid`
- Commit patch definitivo: `d9c29a2cf9ae9355cd15a096280567c1b5b70147`

---

## 4.18 Fix — Central de Conexao: `Undefined variable $number_accounts` (view oauth.php)

> **Sintoma:** ao acessar a conta de um cliente pelo admin e clicar em "adicionar conta" (Central de Conexão → aba Whatsmeow), aparecia `ErrorException: Undefined variable $number_accounts` em `inc/core/Whatsapp_profiles/Views/oauth.php` na linha 229.

### 4.18.1 Causa Raiz

A view `oauth.php` tem **dois** blocos que exibem o alerta de "Limit number of accounts" quando `check_number_account(...)` retorna `false`:

1. **Bloco Baileys** (linha ~156): define `$number_accounts = (int)permission("number_accounts")` **antes** de usar.
2. **Bloco Whatsmeow** (linha ~229): usava `$number_accounts` **sem definir** — a única definição anterior (linha 156) fica dentro do `else` do bloco Baileys, e a próxima (linha ~346) vem depois. Quando o limite é atingido no fluxo Whatsmeow e o bloco Baileys não executou, a variável não existe.

> **Importante:** o bug **existe no main também** (os arquivos são idênticos — md5 igual `dd9250af...`). Não é uma divergência Meta vs main; é um bug latente que só aparece quando `check_number_account` retorna `false` no fluxo Whatsmeow (limite de contas atingido).

### 4.18.2 Correcao

Adicionada a definicao `<?php $number_accounts = (int)permission("number_accounts"); ?>` no bloco Whatsmeow, antes do `sprintf`, espelhando o que o bloco Baileys já faz.

### 4.18.3 Aplicacao

| Servidor | Acao | Resultado |
|---|---|---|
| Main | edit na view + `php -l` (sem erro de sintaxe) + restart php-fpm-81 | ✅ site 200 |
| MetaSenderPro | backup `oauth.php.bak_pre_number_accounts` + scp da view corrigida + restart php-fpm-81 | ✅ site 200 |

> **Nota:** existe um caminho duplicado `inc/core/Whatsapp_profiles/Whatsapp_profiles/Views/oauth.php` (dead code, nao referenciado por nenhum controller — o controller usa `Core\Whatsapp_profiles\Views\oauth`). Nao foi tocado.

---

## 5. Scripts Auxiliares

### 5.1 Comparacao de Colunas (Python)

```python
import re

def extract_columns(schema_file):
    """Extrai tabela -> colunas de um dump mysqldump"""
    tables = {}
    current_table = None
    with open(schema_file) as f:
        for line in f:
            m = re.match(r'CREATE TABLE `(\w+)`', line)
            if m:
                current_table = m.group(1)
                tables[current_table] = []
            elif current_table and line.strip().startswith('`'):
                cm = re.match(r'`(\w+)`', line.strip())
                if cm:
                    tables[current_table].append(cm.group(1))
            elif current_table and line.strip().startswith(')'):
                current_table = None
    return tables

main = extract_columns('migration_db_zapmatic_full.sql')
tenant = extract_columns('/tmp/tenant_schema.sql')

for table in sorted(set(main.keys()) & set(tenant.keys())):
    missing = set(main[table]) - set(tenant[table])
    extra = set(tenant[table]) - set(main[table])
    if missing or extra:
        print(f"--- {table} ---")
        if missing: print(f"  FALTAM: {sorted(missing)}")
        if extra: print(f"  LEGADO: {sorted(extra)}")
```

---

## 6. Checklist de Seguranca

- [ ] NUNCA substituir `.env` do destino
- [ ] NUNCA substituir `writable/` do destino
- [ ] NUNCA dropar tabela ou coluna que existe no main
- [ ] NUNCA dropar dados — apenas estrutura legado
- [ ] NUNCA usar credenciais do main no destino
- [ ] NUNCA ter requisicoes cruzadas entre main e destino
- [ ] Compilar Go com `CGO_ENABLED=1` (nao cross-compile)
- [ ] NUNCA usar `composer install --no-dev` — usar `composer install` COM dev deps (ver secao 4.10.8)
- [ ] Sempre reiniciar o PHP-FPM do destino apos `composer install` (limpar OPcache do autoload antigo)
- [ ] Verificar que `webhook_url` aponta para dominio do destino
- [ ] Verificar que `config.json` tem DB do destino
- [ ] Verificar que systemd service usa porta propria do destino
- [ ] Verificar que PM2 nomes tem prefixo do destino
- [ ] Verificar que `.env` nao contem `db_zapmatic_sql` nem `zapmatic.tec.br`
- [ ] Executar todos os testes da secao 1.8 antes de marcar como concluido
