# Infraestrutura de Processos e Crons — Especificacao

> **Status:** rascunho  
> **Ultima atualizacao:** 2026-08-16  
> **Escopo:** todos os servidores locais (5) + remotos (7)  
> **Objetivo:** garantir que cada instalacao seja 100% autonomo com todos os processos e crons funcionando

---

## 1. Arquitetura Geral

Cada servidor/aplicacao (aqui chamado de "tenant") precisa de **3 categorias** de processos:

| Categoria | Tecnologia | Gerenciador | Funcao |
|---|---|---|---|
| **Go Gateway** | Go binary (`zapmatic-whatsmeow`) | systemd | WhatsApp Web API (ligacoes, mensagens, sessions) |
| **PHP Workers** | CodeIgniter Spark commands | PM2 | Bot builder, campanhas, call campaigns |
| **Node Workers** | Playwright scraper | PM2 | Google Maps scraping |

Alem disso, **crons do BT Panel** rodam tarefas agendadas (limpeza, SSL, etc).

---

## 2. Servidores Locais (nesta maquina)

### 2.1 Mapa de Portas e Servicos

| Tenant | Diretorio | Go Port | Go systemd service | PM2 prefixo |
|---|---|---|---|---|
| **zapmatic** (main) | `/www/wwwroot/app_zapmatic_app` | 8090 | `zapmatic-whatsmeow` | `zapmatic-` |
| **paulo** | `/www/wwwroot/app_paulo_app` | 8091 | `zapmatic-whatsmeow-paulo` | `paulo-` |
| **elias** | `/www/wwwroot/app_elias_app` | 8092 | `zapmatic-whatsmeow-elias` | `elias-` |
| **renovo** | `/www/wwwroot/renovo_app` | 8093 | `zapmatic-whatsmeow-renovo` | `renovo-` |
| **astros** | `/www/wwwroot/app.astroscomunicacaodigital.com` | 8094 | `zapmatic-whatsmeow-astros` | `astros-` |

### 2.2 Processos PM2 (por tenant)

Cada tenant DEVE ter 3 processos no PM2 (root):

| Processo | Comando | Funcao | Memoria max |
|---|---|---|---|
| `{prefixo}-bot-worker-all` | `php spark bot:all` | Bot builder consolidado (debounce + queue + sessions + campaigns) | 256M |
| `{prefixo}-call-worker` | `php spark call:campaigns` | Campanhas de ligacao WhatsApp com audio + auto-hangup | 128M |
| `{prefixo}-gmscraper` | `node index.js` (em `app_zapmatic_scraper/`) | Google Maps scraper com Playwright | 256M |

**Total local: 5 tenants x 3 processos = 15 processos PM2**

### 2.3 Servicos systemd (por tenant)

Cada tenant DEVE ter 1 servico systemd:

| Servico | Binary | Descricao |
|---|---|---|
| `zapmatic-whatsmeow[-{tenant}]` | `zapmatic-whatsmeow --port {porta}` | Go gateway — WhatsApp Web multi-sessao |

**Total local: 5 servicos systemd**

### 2.4 Crons do BT Panel

| ID | Agendamento | Comando | Descricao |
|---|---|---|---|
| `3b3e6b...` | `59 * * * *` (a cada hora) | `rm -f /www/wwwlogs/*` | Limpa logs de acesso/nginx |
| `171306...` | `59 * * * *` (a cada hora) | `/www/server/panel/script/rememory.sh` | Libera memoria (rememory) |
| `3ab48c...` | `02 8 * * *` (diario 8h02) | `acme_v2.py --renew=1` | Renovacao SSL cert v2 |
| `a50afd...` | `40 7 * * *` (diario 7h40) | `acme_v2.py --renew_v3=1` | Renovacao SSL cert v3 |
| `99d655...` | `*/30 * * * *` (a cada 30min) | `pm2 restart EUDEZIO` | **PROBLEMA:** referencia `EUDEZIO` (nome antigo/stale) |
| `54f3c7...` | `59 * * * *` (a cada hora) | `rm -f {todos}/app_zapmatic_api/files/*` | Limpa arquivos temporarios da API em todos tenants |

### 2.5 Servico Inativo

| Servico | Status | Descricao |
|---|---|---|
| `zapmatic-buffer` | **INATIVO** | Bot Builder buffer processor. Script: `/usr/local/bin/zapmatic_buffer_cron.sh`. Faz curl em loop para `process_buffer`. **Verificar se ainda e necessario** — pode ter sido substituido pelo `bot:all`. |

---

## 3. Servidores Remotos

| Tenant | IP | Diretorio | Go Port | Go Service | PM2 | Acesso SSH |
|---|---|---|---|---|---|---|
| **kivozap** | 144.22.167.45 | `/www/wwwroot/app_abner_app` | 8095 | `zapmatic-whatsmeow-kivozap` | Verificar | `abner@144.22.167.45` |
| **agenciamcw** | 144.22.167.45 | `/www/wwwroot/app_frank_agencia` | 8096 | `zapmatic-whatsmeow-agenciamcw` | Verificar | `frank@144.22.167.45` |
| **chatbut** | 144.22.167.45 | `/www/wwwroot/app_alex_pedidu_app` | 8097 | `zapmatic-whatsmeow-chatbut` | Verificar | `alex@144.22.167.45` |
| **iaclicks** | 45.148.29.92 | `/www/wwwroot/app_zapmatic_app` | 8098 | `zapmatic-whatsmeow-iaclicks` | Verificar | `admin@45.148.29.92` |
| **elite** | 193.180.211.190 | `/www/wwwroot/elitecomunicacao.zapmatic.tec.br` | 8099 | `zapmatic-whatsmeow-elite` | Verificar | Verificar |
| **pluszap** | 92.113.144.161 | `/www/wwwroot/app_zapmatic_app` | 8100 | `zapmatic-whatsmeow-pluszap` | Verificar | Verificar |
| **metasenderpro** | 92.113.149.185 | `/www/wwwroot/app_zapmatic_app` | 8101 | `zapmatic-whatsmeow-metasenderpro` | Verificar | Verificar |

---

## 4. Diagrama de Dependencias

```
┌─────────────────────────────────────────────────────┐
│                    SERVIDOR (BT Panel)                │
│                                                       │
│  ┌──────────────┐   ┌──────────────┐   ┌──────────┐ │
│  │ systemd      │   │ PM2 (root)   │   │ crons    │ │
│  │              │   │              │   │          │ │
│  │ Go Gateway   │   │ bot-worker   │   │ SSL      │ │
│  │ (whatsmeow)  │   │ call-worker  │   │ cleanup  │ │
│  │ port 809x    │   │ gmscraper    │   │ rememory │ │
│  └──────┬───────┘   └──────┬───────┘   └──────────┘ │
│         │                  │                          │
│         │    HTTP REST     │                          │
│         ├──────────────────┤                          │
│         │  /call/start     │                          │
│         │  /call/status    │                          │
│         │  /send/*         │                          │
│         │                  │                          │
│  ┌──────┴───────┐   ┌──────┴───────┐                 │
│  │ WhatsApp Web │   │ MySQL        │                 │
│  │ Sessions     │   │ (db_zapmatic)│                 │
│  │ (whatsmeow.db│   │              │                 │
│  └──────────────┘   └──────────────┘                 │
└─────────────────────────────────────────────────────┘
```

**Fluxo de uma campanha de ligacao:**
1. Usuario cria campanha na UI (PHP) → salva em `sp_call_campaigns`
2. `call-worker` (PM2) faz poll a cada 3s → encontra campanha `running`
3. Worker busca audio em `sp_call_audios` → monta payload com `audio_path` + `audio_duration`
4. Worker envia POST para Go gateway `/call/start`
5. Go gateway inicia chamada WhatsApp → registra `OnReady` + `OnFinish` hooks
6. Quando atendida → toca audio → `OnFinish` → auto-hangup apos 2s
7. Worker faz poll `/call/status` → atualiza `sp_call_leads` com resultado

---

## 5. Checklist de Verificacao por Tenant

Para cada servidor, verificar:

### 5.1 systemd
- [ ] Servico `zapmatic-whatsmeow[-{tenant}]` existe em `/etc/systemd/system/`
- [ ] Status: `active (running)`
- [ ] Porta correta configurada (`--port {809x}`)
- [ ] `Restart=always` no service file
- [ ] `After=network.target` no service file
- [ ] Binary compilado com codigo mais recente (verificar data de modificacao)

### 5.2 PM2
- [ ] PM2 rodando como **root** (unico daemon)
- [ ] `{prefixo}-bot-worker-all` online
- [ ] `{prefixo}-call-worker` online
- [ ] `{prefixo}-gmscraper` online
- [ ] `ecosystem.config.js` com nomes corretos (prefixo do tenant)
- [ ] `pm2 save` executado (dump.pm2 atualizado)
- [ ] Nenhum PM2 daemon extra rodando (www, ubuntu, etc)

### 5.3 Crons
- [ ] SSL renewal configurado
- [ ] Limpeza de logs configurada
- [ ] Limpeza de arquivos temporarios configurada
- [ ] Sem referencias stale (como `EUDEZIO`)

### 5.4 Banco de Dados
- [ ] Tabelas `sp_call_campaigns`, `sp_call_leads`, `sp_call_audios` existem
- [ ] Tabelas do bot builder existem
- [ ] `.env` com credenciais corretas do tenant

### 5.5 Arquivos
- [ ] `app_zapmatic_scraper/` com `node_modules/` instalado
- [ ] `writable/call_audio/` com permissao de escrita
- [ ] `writable/logs/` existe
- [ ] Go binary em `app_zapmatic_whatsmeow_api/zapmatic-whatsmeow`

---

## 6. Problemas Conhecidos e Resolucoes

### 6.1 PM2 com nomes errados
- **Problema:** main tinha `pluszap-bot-worker-all` e `call-campaign-worker` (sem prefixo)
- **Resolucao:** Renomear para `zapmatic-bot-worker-all` e `zapmatic-call-worker`
- **Status:** CORRIGIDO no main (2026-08-16)

### 6.2 Call workers nao iniciados
- **Problema:** 4 servidores (paulo, elias, renovo, astros) tinham call-worker no ecosystem mas nao estavam rodando
- **Resolucao:** `pm2 start ecosystem.config.js --only {prefixo}-call-worker`
- **Status:** CORRIGIDO (2026-08-16)

### 6.3 gmscraper so no main
- **Problema:** gmscraper era iniciado manualmente apenas no main, outros 4 servidores nao tinham
- **Resolucao:** Adicionar gmscraper em todos os ecosystem.config.js e iniciar
- **Status:** CORRIGIDO (2026-08-16)

### 6.4 PM2 daemon do www rodando
- **Problema:** PM2 daemon do usuario `www` rodando em paralelo ao root (consumia memoria sem necessidade)
- **Resolucao:** `pm2 kill` no daemon do www
- **Status:** CORRIGIDO (2026-08-16)

### 6.5 Cron stale `EUDEZIO`
- **Problema:** Cron roda `pm2 restart EUDEZIO` a cada 30min — EUDEZIO nao existe no PM2
- **Resolucao:** Remover ou atualizar o cron para o nome correto
- **Status:** PENDENTE

### 6.6 zapmatic-buffer inativo
- **Problema:** Servico systemd `zapmatic-buffer` esta inativo
- **Resolucao:** Verificar se `bot:all` ja substituiu essa funcionalidade. Se sim, remover o servico. Se nao, reativar.
- **Status:** PENDENTE (precisa investigacao)

---

## 7. Template: ecosystem.config.js por Tenant

```javascript
module.exports = {
  apps: [
    {
      name: "{PREFIXO}-bot-worker-all",
      script: "spark",
      args: "bot:all",
      interpreter: "php",
      cwd: "/www/wwwroot/{DIRETORIO}",
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      watch: false,
      max_memory_restart: "256M",
      error_file: "writable/logs/pm2-all-error.log",
      out_file: "writable/logs/pm2-all-out.log",
    },
    {
      name: "{PREFIXO}-call-worker",
      script: "spark",
      args: "call:campaigns",
      interpreter: "php",
      cwd: "/www/wwwroot/{DIRETORIO}",
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      watch: false,
      max_memory_restart: "128M",
      error_file: "writable/logs/pm2-call-error.log",
      out_file: "writable/logs/pm2-call-out.log",
    },
    {
      name: "{PREFIXO}-gmscraper",
      script: "index.js",
      interpreter: "node",
      cwd: "/www/wwwroot/{DIRETORIO}/app_zapmatic_scraper",
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      watch: false,
      max_memory_restart: "256M",
    },
  ]
};
```

---

## 8. Template: systemd service por Tenant

```ini
[Unit]
Description=Zapmatic Whatsmeow Gateway ({TENANT})
After=network.target

[Service]
Type=simple
User=ubuntu
ExecStart=/www/wwwroot/{DIRETORIO}/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow --port {PORTA} --log-dir /www/wwwroot/{DIRETORIO}/app_zapmatic_whatsmeow_api/logs
WorkingDirectory=/www/wwwroot/{DIRETORIO}/app_zapmatic_whatsmeow_api
Restart=always
RestartSec=5
Environment=CGO_ENABLED=1

[Install]
WantedBy=multi-user.target
```

---

## 9. Crons Recomendados por Tenant

| Agendamento | Comando | Descricao |
|---|---|---|
| `*/30 * * * *` | `pm2 restart {PREFIXO}-gmscraper` | Restart periodico do scraper (libera memoria Playwright) |
| `0 3 * * 0` | `cd /www/wwwroot/{DIR} && php spark system:update` | Atualizacao semanal (dom 3h) |

**Crons compartilhados (apenas 1 vez no servidor):**

| Agendamento | Comando | Descricao |
|---|---|---|
| `59 * * * *` | `rm -f /www/wwwlogs/*` | Limpa logs nginx |
| `59 * * * *` | `rm -f /www/wwwroot/*/app_zapmatic_api/files/*` | Limpa tmp files |
| `*/30 * * * *` | `/www/server/panel/script/rememory.sh` | Libera memoria |
| `02 8 * * *` | ACME SSL renewal | Renova certificados SSL |
| `40 7 * * *` | ACME SSL renewal v3 | Renova certificados SSL v3 |
