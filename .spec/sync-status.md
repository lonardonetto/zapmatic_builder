# Acompanhamento de Sincronizacao — Todos os Sistemas

> **Referencia:** main Zapmatic (`zapmatic.tec.br`) — o laboratorio de desenvolvimento  
> **Versao atual do main:** v8.5.26 (tag `v8.5.26`, 2026-08-27)  
> **Tabelas no main:** 77  
> **Processos por tenant:** 4 PM2 (bot, call, gmscraper, cloud-campaign) + 1 systemd  
> **Spec template:** `.spec/features/sync-metasenderpro/spec.md`

> **⚠️ Nota sobre version.json:** o `version.json` do main esta em v8.5.26. A versao de referencia e `v8.5.26` (tag `v8.5.26`).

> **🔧 Fix call-id meowcaller (2026-08-20):** correcao do fallback de call-id em chamadas recusadas (erro 463/403) — ver `.spec/features/sync-metasenderpro/spec.md` secao 4.17. **Solucao definitiva aplicada:** fork `lonardonetto/meowcaller` + `replace` no `go.mod` (tag `v0.0.1-callid`), com correlacao por **stanza id** (corrige o caso de multiplas ligacoes simultaneas — a versao anterior `v0.0.0-...-callid` usava fallback aleatorio e foi removida). O fix agora e versionado — `go mod vendor`/`go mod tidy` puxa o patch automaticamente.

---

## 1. Status Geral

| # | Servidor | IP | Dominio | Versao | DB | Codigo (CCW) | Go | PM2 | Status |
|---|---|---|---|---|---|---|---|---|---|
| 1 | **MetaSenderPro** | 92.113.149.185 | sender.metanivelpro.com | v8.5.15 | ✅ 76 | ✅ SIM | ✅ 8101 | ✅ 4 proc | **CONCLUIDO** (+fix call-id) |
| 2 | **Astros** (local) | local | app.astroscomunicacaodigital.com | v8.5.15 | ✅ 76 | ✅ SIM | ✅ 8094 | ✅ 4 proc | **CONCLUIDO** |
| 3 | **Chatbut** | 144.22.167.45 | chatbut.com.br | v8.5.15 | ✅ 76 | ✅ SIM | ✅ 8097 | ✅ 4 proc | **CONCLUIDO** (pendencia: reconectar instancias via QR) |
| 4 | **AgenciaMCW** | 144.22.167.45 | chatbot.agenciamcw.com.br | v8.5.15 | ✅ 76 | ✅ SIM | ✅ 8096 | ✅ 4 proc | **CONCLUIDO** |
| 5 | **Kivozap** | 144.22.167.45 | kivozap.com.br | v8.5.15 | ✅ 76 | ✅ SIM | ✅ 8090 | ✅ 4 proc | **CONCLUIDO** |
| 6 | **PlusZap** | 92.113.144.161 | pluszap.com | v8.3.24 | ❌ | ❌ NAO | ❌ | ❌ 2 proc | **PENDENTE** |
| 7 | **IaClicks** | 45.148.29.92 | iaclicks.com | v8.5.27 | ✅ 77 | ✅ SIM | ✅ 8098 | ✅ 4 proc | **CONCLUIDO** (2026-08-28) |
| 8 | **Elite** | 193.180.211.190 | elitecomunicacao.zapmatic.tec.br | v8.3.28 | ❌ | ❌ NAO | ❌ | ❌ 2 proc | **PENDENTE** |
| 9 | **Paulo** (local) | local | atualizaleads.app.br | v8.5.15 | ✅ 76 | ✅ SIM | ✅ 8091 | ✅ 4 proc | **CONCLUIDO** |
| 10 | **Elias** (local) | local | multiconnecta.com.br | v8.5.15 | ✅ 76 | ✅ SIM | ✅ 8092 | ✅ 4 proc | **CONCLUIDO** |
| 11 | **Renovo** (local) | local | renovo.app | v8.5.26 | ✅ 77 | ✅ SIM | ✅ 8093 | ✅ 4 proc | **CONCLUIDO** |

**Legenda:**
- ✅ concluido e verificado
- ❌ pendente (nao iniciado)
- ⚠️ parcial (em andamento, com pendencia ou divergente)

---

## 2. Resumo — Quem JA FOI e Quem FALTA

### 2.1 Concluidos (paridade total com main)

| Tenant | Data | Nota |
|---|---|---|
| **MetaSenderPro** | 2026-08-17 (v8.5.18 update 08-20) | 4 workers, 76 tab, cloud-campaign OK |
| **Astros** | 2026-08-20 | 4 workers, 76 tab, cloud-campaign OK |
| **Paulo** | 2026-08-20 | 4 workers, 76 tab, cloud-campaign OK |
| **Elias** | 2026-08-20 | 4 workers, 76 tab, cloud-campaign OK |
| **Renovo** | 2026-08-27 | 4 workers, 77 tab, cloud-campaign OK, v8.5.26 |
| **IaClicks** | 2026-08-28 | 4 workers, 77 tab, cloud-campaign OK, v8.5.27 |
| **AgenciaMCW (Frank)** | 2026-08-20 | 4 workers, 76 tab, cloud-campaign OK |
| **Kivozap** | 2026-08-20 | 4 workers, 76 tab, cloud-campaign OK |
| **Chatbut** | 2026-08-20 | 4 workers, 76 tab, cloud-campaign OK (pendencia: reconectar QR) |

### 2.2 Parcial (quase la, precisa ajuste)

_(nenhum — todos os que estavam parciais foram concluidos)_

### 2.3 Pendentes (nao iniciados ou defasados)

| Tenant | Versao atual | DB | Principais divergencias |
|---|---|---|---|
| **PlusZap** | v8.3.24 | ❌ | muito defasado (3 versoes); 2 workers |
| **IaClicks** | v8.5.1 | ❌ | defasado; 2 workers |
| **Elite** | v8.3.28 | ❌ | muito defasado (3 versoes); 2 workers |

---

## 3. Detalhe por Servidor

### 3.1 MetaSenderPro — CONCLUIDO

| Item | Valor |
|---|---|
| Data da sincronizacao | 2026-08-17 (update 2026-08-20) |
| Spec | `.spec/features/sync-metasenderpro/spec.md` |
| Commit main | `38a6049c` (v8.5.18) |
| DB | 76 tabelas |
| Go binary | Compilado no servidor, CGO_ENABLED=1 |
| PM2 | 4 workers (bot, call, gmscraper, cloud-campaign) |
| Credenciais | 100% propias, zero cross-ref com main |
| Fix call-id (meowcaller) | ✅ aplicado 2026-08-20 (ver secao 4.17 da spec) |

### 3.2 Astros — CONCLUIDO (local)

| Item | Valor |
|---|---|
| Data da sincronizacao | 2026-08-20 |
| Spec | `.spec/features/sync-metasenderpro/spec.md` (secao 4.10) |
| Commit main | `38a6049c` (v8.5.18) |
| DB | 76 tabelas (migration aplicada, legado dropado) |
| Go binary | Compilado no servidor, CGO_ENABLED=1, arm64 |
| PM2 | 4 workers (bot, call, gmscraper, cloud-campaign) |
| Credenciais | 100% propias (sql_eudezio_db), zero cross-ref |

### 3.3 Chatbut — PARCIAL

| Item | Valor |
|---|---|
| IP | 144.22.167.45 |
| SSH | ubuntu (key auth) |
| Path | /www/wwwroot/app_alex_pedidu_app |
| DB | sql_alex_db / 6eNEfwPxHjdT757w |
| Go port | 8097 |
| Versao | v8.5.15 |
| DB tables | 76 ✅ |
| CloudCampaignWorker (arquivo) | ✅ SIM |
| PM2 | ⚠️ 2 workers (bot, call) — falta cloud-campaign + gmscraper |
| Pendencias | iniciar `cloud-campaign-worker` e `gmscraper` no PM2 |

### 3.4 AgenciaMCW — PENDENTE

| Item | Valor |
|---|---|
| IP | 144.22.167.45 |
| SSH | ubuntu (key auth) |
| Path | /www/wwwroot/app_frank_agencia |
| DB | sql_frank_db / apw4iTDGjePic8cb |
| Go port | 8096 |
| Versao | v8.5.15 |
| DB tables | 78 (76 do main + 2 legado: `sp_whatsapp_autoresponder`, `sp_whatsapp_chatbot`) |
| CloudCampaignWorker | ❌ NAO |
| PM2 | 2 workers (frank-bot, frank-call) |
| Pendencias | dropar 2 tabelas legado, adicionar CCW, adicionar cloud-campaign worker |

### 3.5 Kivozap — PENDENTE

| Item | Valor |
|---|---|
| IP | 144.22.167.45 |
| SSH | ubuntu (key auth) |
| Path | /www/wwwroot/app_abner_app |
| DB | db_abner_sql / inTwk7z37PnhWcY5 |
| Go port | 8095 |
| Versao | v8.4.0 |
| DB tables | 76 |
| CloudCampaignWorker | ❌ NAO |
| PM2 | 2 workers |
| Pendencias | atualizar para v8.5.18, adicionar CCW + cloud-campaign worker |

### 3.6 PlusZap — PENDENTE

| Item | Valor |
|---|---|
| IP | 92.113.144.161 |
| SSH | admin / Leonetto1982 |
| Path | /www/wwwroot/app_zapmatic_app |
| DB | sql_zapmatic_db / ZnCYYPwZwYxw8b6r |
| Go port | 8100 |
| Versao | v8.3.24 |
| CloudCampaignWorker | ❌ NAO |
| PM2 | 2 workers |

### 3.7 IaClicks — CONCLUIDO (2026-08-28)

| Item | Valor |
|---|---|
| IP | 45.148.29.92 |
| SSH | admin / Leonetto1982 |
| Path | /www/wwwroot/app_zapmatic_app |
| DB | sql_iaclicks_db / FxMzzfdLPr2yDS2F |
| Go port | 8098 |
| Versao | v8.5.27 (atualizado 2026-08-28) |
| CloudCampaignWorker | ✅ SIM |
| PM2 | 4 workers |
| Commit | 3744e9bd |
| Backup | /tmp/backup_iaclicks_20260828_162454 |

### 3.8 Elite — PENDENTE

| Item | Valor |
|---|---|
| IP | 193.180.211.190 |
| SSH | admin / Leonetto1982 |
| Path | /www/wwwroot/elitecomunicacao.zapmatic.tec.br |
| DB | sql_zapmatic_db / fe5kwDTMy3JdxDhT |
| Go port | 8099 |
| Versao | v8.3.28 |
| CloudCampaignWorker | ❌ NAO |
| PM2 | 2 workers |

### 3.9 Paulo — PENDENTE (local)

| Item | Valor |
|---|---|
| Path | /www/wwwroot/app_paulo_app |
| DB | sql_paulo_db / H4R5PD5fW5GMDJSt |
| Go port | 8091 |
| Versao | v8.3.28 |
| DB tables | 75 (falta 1) |
| CloudCampaignWorker | ❌ NAO |
| PM2 | 3 workers (sem cloud-campaign) |

### 3.10 Elias — PENDENTE (local)

| Item | Valor |
|---|---|
| Path | /www/wwwroot/app_elias_app |
| DB | sql_elias_db / H57YaZSNnCEWy6zG |
| Go port | 8092 |
| Versao | v8.5.1 |
| DB tables | 73 (falta 3) |
| CloudCampaignWorker | ❌ NAO |
| PM2 | 3 workers (sem cloud-campaign) |

### 3.11 Renovo — CONCLUIDO (local)

| Item | Valor |
|---|---|
| Path | /www/wwwroot/renovo_app |
| DB | db_renovo_sql / inTwk7z37PnhWcY5 |
| Go port | 8093 |
| Versao | v8.5.26 (atualizado 2026-08-27) |
| DB tables | 77 ✅ (adicionada sp_call_events) |
| CloudCampaignWorker | ✅ SIM |
| PM2 | 4 workers (bot, call, gmscraper, cloud-campaign) ✅ |

---

## 4. Processo de Atualizacao

Para cada servidor pendente:

1. Criar `.spec/features/sync-{tenant}/spec.md` a partir do template
2. Executar os 8 passos (DB → codigo → Go → credenciais → processos → testes)
3. Atualizar esta tabela de acompanhamento
4. Commitar no main

**Template de spec:** `.spec/features/sync-metasenderpro/spec.md` (secao 2)

**⚠️ Cuidados criticos (ver secao 4.10.8 da spec):**
- NUNCA usar `composer install --no-dev` (quebra o autoload do phpunit)
- Sempre reiniciar o PHP-FPM apos `composer install`
- Transferencia local exige `sudo rsync` direto (nao `--rsync-path` via local)
- O main tem `vendor/` instalado COM dev deps — replicar exatamente

---

## 5. Metricas de Progresso

| Metrica | Valor |
|---|---|
| Total de tenants (remotos + locais) | 10 (excluindo main) |
| Concluidos | 8 (MetaSenderPro, Astros, Paulo, Elias, Renovo, AgenciaMCW, Kivozap, Chatbut) |
| Parciais | 0 |
| Pendentes | 3 (PlusZap, IaClicks, Elite) |
| Progresso | 80% concluido |
| Versao main referencia | v8.5.26 (tag `v8.5.26`) |
