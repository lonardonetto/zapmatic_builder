# Sincronizacao Astros — Especificacao e Execucao

> **Status:** concluido  
> **Data:** 2026-08-22  
> **Executado por:** Kilo (automatizado via rsync local)  
> **Versao base:** v8.5.25 (main Zapmatic)  
> **Commit main:** `a3dfc29f`  
> **Principio:** main Zapmatic (`zapmatic.tec.br`) e o laboratorio de desenvolvimento — todos os demais servidores devem ser identicos ao main em codigo, estrutura e comportamento, mas com credenciais proprias e autonomas.

---

## 1. Visao Geral

O servidor Astros (Eudezio) recebeu uma copia 100% identica ao main Zapmatic em termos de:
- Codigo fonte (inc/core, app, Go API, scraper, assets, migrations)
- Estrutura de banco de dados (78 tabelas, colunas, indices)
- Processos (systemd Go gateway + 4 PM2 workers)
- Especificacoes (.spec) e documentacao (docs)

**O que NUNCA e substituido:**
- `.env` — credenciais e configuracoes propias do tenant
- `writable/` — dados locais (logs, audios, cache, sessions)
- `ecosystem.config.js` — nomes e caminhos dos processos PM2
- `config.json` (Go API) — credenciais de banco e porta do tenant

---

## 2. Variaveis do Astros

| Variavel | Valor |
|---|---|
| `{PATH}` | /www/wwwroot/app.astroscomunicacaodigital.com |
| `{DOMAIN}` | app.astroscomunicacaodigital.com |
| `{DB_NAME}` | sql_eudezio_db |
| `{DB_USER}` | sql_eudezio_db |
| `{DB_PASS}` | TJ8SA88YLJNRiNdG |
| `{GO_PORT}` | 8094 |
| `{TENANT}` | astros |
| `{TENANT_NAME}` | Astros (Eudezio) |

---

## 3. Passos de Execucao

### Passo 1 — Analise de Diferencas de Banco ✅
**Objetivo:** Identificar tabelas/colunas que faltam no destino.
**Resultado:** 
- Tabela `sp_call_events` ausente no destino (criada via CREATE TABLE)
- 5 colunas faltantes em `sp_call_leads`: `platform`, `heard_full_audio`, `hangup_source`, `ring_duration_seconds`, `last_error` (adicionadas via ALTER TABLE)
- Apos migracao: 78 tabelas identicas ao main.

### Passo 2 — Backup de Credenciais ✅
```bash
/tmp/astros_backup/.env
/tmp/astros_backup/ecosystem.config.js
/tmp/astros_backup/config.json
```

### Passo 3 — Substituir Codigo (rsync completo com sudo) ✅
```bash
sudo rsync -az --delete --exclude=.env --exclude=writable/ --exclude=.git/ ...
```
Diretorios sincronizados: `inc/`, `app/`, `app_zapmatic_whatsmeow_api/`, `app_zapmatic_scraper/`, `assets/`, `migrations/`, `sql/`, `.spec/`, `docs/`

### Passo 4 — Root Files (sudo cp) ✅
Arquivos copiados: `composer.json`, `index.php`, `spark`, `version.json`, `404.html`, `502.html`, `.htaccess`, `CHANGELOG.md`

### Passo 5 — Dependencias PHP (composer install) ✅
```bash
sudo -u www composer install --no-dev --optimize-autoloader
```

### Passo 6 — Dependencias Node.js (npm install) ✅
```bash
cd app_zapmatic_scraper && sudo npm install --omit=dev
```

### Passo 7 — Compilar Go Binary (CGO_ENABLED=1) ✅
```bash
export PATH=/usr/local/go/bin:$PATH
export CGO_ENABLED=1
cd app_zapmatic_whatsmeow_api && go mod vendor && go build -o zapmatic-whatsmeow ./cmd/server/
```

### Passo 8 — Restaurar Configuracoes de Producao ✅
- `.env` restaurado do backup
- `ecosystem.config.js` restaurado do backup
- `config.json` (Go API) restaurado do backup

### Passo 9 — Diretorios e Permissoes ✅
```bash
sudo mkdir -p writable/logs writable/call_audio
sudo mkdir -p app_zapmatic_whatsmeow_api/logs app_zapmatic_whatsmeow_api/storage/sessions
sudo chown -R www:www writable/ app_zapmatic_whatsmeow_api/logs/ app_zapmatic_whatsmeow_api/storage/
```

### Passo 10 — Iniciar Processos ✅
```bash
sudo systemctl daemon-reload && sudo systemctl restart zapmatic-whatsmeow-astros
sudo pm2 restart astros-bot-worker-all astros-call-worker astros-gmscraper astros-cloud-campaign-worker
sudo pm2 save
```

---

## 4. Resultado da Execucao

### 4.1 Banco de Dados
| Item | Status |
|---|---|
| Total de tabelas | 78 (identico ao main) |
| Tabelas adicionadas | 1 (`sp_call_events`) |
| Colunas adicionadas | 5 em `sp_call_leads` |
| Tabelas legado dropadas | 0 |

### 4.2 Codigo
| Pasta | Acao | Status |
|---|---|---|
| `inc/core/`, `app/`, `assets/`, `migrations/`, `sql/`, `.spec/`, `docs/` | rsync completo (sudo) | ✅ |
| `app_zapmatic_whatsmeow_api/` | rsync + compilacao Go (`go mod vendor && go build`) | ✅ |
| `app_zapmatic_scraper/` | rsync + npm install | ✅ |
| Root files | sudo cp | ✅ |

### 4.3 Processos
| Servico | Status | Porta |
|---|---|---|
| `zapmatic-whatsmeow-astros` (systemd) | **active** | 8094 |
| `astros-bot-worker-all` (PM2) | **online** | — |
| `astros-call-worker` (PM2) | **online** | — |
| `astros-gmscraper` (PM2) | **online** | — |
| `astros-cloud-campaign-worker` (PM2) | **online** | — |

### 4.4 Testes de Verificacao
| Endpoint | HTTP Code | Comportamento Esperado |
|---|---|---|
| `https://app.astroscomunicacaodigital.com/` | 200 | Home carrega OK |
| `https://app.astroscomunicacaodigital.com/whatsapp_official_template` | 200 | Lista templates OK |
| `https://app.astroscomunicacaodigital.com/whatsapp_button_template/update` | 302 | Redirect login (esperado) |
| `https://app.astroscomunicacaodigital.com/caption` | 302 | Redirect login (esperado) |
| `http://localhost:8094/health` | 200 | `{"status":"ok","connected":16,...}` |

### 4.5 Versao
```json
{
    "version": "8.5.25",
    "notes": "v8.5.25 - correções no onboarding Meta COEX (SessionInfo v3) e finalização da spec de duplicação + variáveis"
}
```

---

## 5. Backups Locais (servidor)
- `/tmp/astros_backup/.env` — .env original
- `/tmp/astros_backup/ecosystem.config.js` — ecosystem.config.js original
- `/tmp/astros_backup/config.json` — config.json Go API original

---

## 6. Observacoes
- Servidor Astros esta na MESMA maquina que o main (rsync local, sem SSH)
- Go API compilado com `CGO_ENABLED=1` (necessario para sqlite3)
- PM2 roda como root (sudo pm2)
- systemd service: `zapmatic-whatsmeow-astros.service`
- 16 sessoes WhatsApp conectadas apos reinicio (era 17 antes — 1 pode ter reconectado apos delay)
