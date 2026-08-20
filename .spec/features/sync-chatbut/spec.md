# Sincronizacao Chatbut — Especificacao, Execucao e Template

> **Status:** concluido  
> **Data:** 2026-08-19  
> **Executado por:** Kilo (automatizado via SSH)  
> **Versao base:** v8.5.17 (main Zapmatic)  
> **Commit main:** `a3282eb6`  
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

## 2. Variaveis do Chatbut

| Variavel | Valor |
|---|---|
| `{SSH_IP}` | 144.22.167.45 |
| `{SSH_USER}` | ubuntu |
| `{SSH_AUTH}` | key (ssh -i ~/.ssh/chave_zapmatic.key) |
| `{PATH}` | /www/wwwroot/app_alex_pedidu_app |
| `{DOMAIN}` | chatbut.com.br |
| `{DB_NAME}` | sql_alex_db |
| `{DB_USER}` | sql_alex_db |
| `{DB_PASS}` | 6eNEfwPxHjdT757w |
| `{GO_PORT}` | 8097 |
| `{TENANT}` | chatbut |
| `{TENANT_NAME}` | Chatbut |

---

## 3. Passos de Execucao

### Passo 1 — Analise de Diferencas de Banco ✅
**Objetivo:** Identificar tabelas/colunas que faltam no destino.
**Resultado:** Banco ja identico (76 tabelas, colunas identicas). Nenhuma migracao necessaria.

### Passo 2 — Backup de Credenciais ✅
```bash
# Backups feitos antes do rsync
/tmp/chatbut_env_backup (copia do .env original)
/tmp/chatbut_ecosystem_backup (copia do ecosystem.config.js original)
```

### Passo 3 — Substituir Codigo (rsync completo com sudo) ✅
```bash
rsync -az --delete --rsync-path="sudo rsync" --exclude=.env --exclude=writable/ --exclude=.git/ ...
```
Diretorios sincronizados: `inc/`, `app/`, `app_zapmatic_scraper/`, `app_zapmatic_whatsmeow_api/`, `assets/`, `migrations/`, `sql/`, `.spec/`, `docs/`, `_bmad/`

### Passo 4 — Root Files (scp + sudo mv) ✅
Arquivos copiados: `composer.json`, `ecosystem.config.js`, `index.php`, `spark`, `version.json`, `migration_db_zapmatic_full.sql`, `release.sh`, `deploy_*.sh`, `404.html`, `502.html`, `call_audio_stream.php`

### Passo 5 — Dependencias PHP (composer install) ✅
```bash
sudo -u www composer install --no-dev --optimize-autoloader
```

### Passo 6 — Dependencias Node.js (npm install) ✅
```bash
cd app_zapmatic_scraper && sudo -u www npm install --omit=dev
```

### Passo 7 — Compilar Go Binary (CGO_ENABLED=1) ✅
```bash
export PATH=/usr/local/go/bin:$PATH
export CGO_ENABLED=1
cd app_zapmatic_whatsmeow_api && go mod vendor && go build -o zapmatic-whatsmeow ./cmd/server/
```

### Passo 8 — Configuracoes de Producao ✅

**config.json (Go API):**
```json
{
  "port": 8097,
  "log_level": "info",
  "log_dir": "logs",
  "store_dir": "storage/sessions",
  "webhook_url": "https://chatbut.com.br/index.php/bot-builder/webhook",
  "database": {
    "host": "localhost",
    "port": 3306,
    "user": "sql_alex_db",
    "password": "6eNEfwPxHjdT757w",
    "name": "sql_alex_db"
  }
}
```

**ecosystem.config.js (PM2):**
- `chatbut-bot-worker-all` (spark bot:all)
- `chatbut-call-worker` (spark call:campaigns)
- `chatbut-gmscraper` (index.js)

**systemd service:** `zapmatic-whatsmeow-chatbut.service` (porta 8097)

### Passo 9 — Diretorios e Permissoes ✅
```bash
sudo mkdir -p writable/logs writable/call_audio
sudo mkdir -p app_zapmatic_whatsmeow_api/logs app_zapmatic_whatsmeow_api/storage/sessions
sudo chown -R www:www writable/ app_zapmatic_whatsmeow_api/logs/ app_zapmatic_whatsmeow_api/storage/
```

### Passo 10 — Iniciar Processos ✅
```bash
sudo systemctl daemon-reload && sudo systemctl enable zapmatic-whatsmeow-chatbut && sudo systemctl restart zapmatic-whatsmeow-chatbut
sudo pm2 start ecosystem.config.js && sudo pm2 save
```

---

## 4. Resultado da Execucao

### 4.1 Banco de Dados
| Item | Status |
|---|---|
| Total de tabelas | 76 (identico ao main) |
| Tabelas adicionadas | 0 |
| Colunas adicionadas | 0 |
| Tabelas legado dropadas | 0 |

### 4.2 Codigo
| Pasta | Acao | Status |
|---|---|---|
| `inc/core/`, `app/`, `assets/`, `migrations/`, `sql/`, `.spec/`, `docs/`, `_bmad/` | rsync completo (`--rsync-path="sudo rsync"`) | ✅ |
| `app_zapmatic_whatsmeow_api/` | rsync + compilacao Go (`go mod vendor && go build`) | ✅ |
| `app_zapmatic_scraper/` | rsync + npm install | ✅ |
| Root files | scp + sudo mv | ✅ |

### 4.3 Processos
| Servico | Status | Porta |
|---|---|---|
| `zapmatic-whatsmeow-chatbut` (systemd) | **active** | 8097 |
| `chatbut-bot-worker-all` (PM2) | **online** | — |
| `chatbut-call-worker` (PM2) | **online** | — |
| `chatbut-gmscraper` (PM2) | **online** | — |

### 4.4 Testes de Verificacao
| Endpoint | HTTP Code | Comportamento Esperado |
|---|---|---|
| `https://chatbut.com.br/` | 200 | Home carrega OK |
| `https://chatbut.com.br/whatsapp_button_template/update` | 302 | Redirect login (antes era 500: is_admin undefined) |
| `https://chatbut.com.br/caption` | 302 | Redirect login (antes era 500: is_admin undefined) |
| `https://chatbut.com.br/whatsapp_official_template` | 200 | Lista templates OK |
| `https://chatbut.com.br/whatsapp_call_campaign` | 302 | Redirect login (antes era 500: is_admin undefined) |
| `http://localhost:8097/health` | 200 | `{"status":"ok","connected":0,...}` |

---

## 5. Backups Locais (servidor)
- `/tmp/chatbut_env_backup` — .env original
- `/tmp/chatbut_ecosystem_backup` — ecosystem.config.js original

---

## 6. Observacoes
- MySQL usa socket `/run/mysqld/mysqld.sock` — usar `-h 127.0.0.1 -P 3306` nas operacoes
- Permissoes de arquivos requerem `sudo rsync` / `sudo mv` para escrita
- Go API compilado com `CGO_ENABLED=1` (necessario para sqlite3)
- O fix do `is_admin()` (v8.5.17) esta incluso no deploy
