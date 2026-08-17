# Acompanhamento de Sincronizacao — Todos os Sistemas

> **Referencia:** main Zapmatic (`zapmatic.tec.br`) — o laboratorio de desenvolvimento  
> **Versao atual do main:** v8.5.14 (commit `79f85a1e`, 2026-08-17)  
> **Tabelas no main:** 76  
> **Processos por tenant:** 3 PM2 + 1 systemd  
> **Spec template:** `.spec/features/sync-metasenderpro/spec.md`

---

## 1. Status Geral

| # | Servidor | IP | Dominio | Versao | DB | Codigo | Go | PM2 | Testes | Status |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | **MetaSenderPro** | 92.113.149.185 | sender.metanivelpro.com | v8.5.14 | ✅ 76 tab | ✅ | ✅ 8101 | ✅ 3 proc | ✅ ligacao OK | **CONCLUIDO** |
| 2 | **PlusZap** | 92.113.144.161 | pluszap.com | — | ❌ | ❌ | ❌ | ❌ | ❌ | pendente |
| 3 | **IaClicks** | 45.148.29.92 | iaclicks.com | — | ❌ | ❌ | ❌ | ❌ | ❌ | pendente |
| 4 | **Elite** | 193.180.211.190 | elitecomunicacao.zapmatic.tec.br | — | ❌ | ❌ | ❌ | ❌ | ❌ | pendente |
| 5 | **Kivozap** | 144.22.167.45 | kivozap.com.br | — | ❌ | ❌ | ❌ | ❌ | ❌ | pendente |
| 6 | **AgenciaMCW** | 144.22.167.45 | chatbot.agenciamcw.com.br | — | ❌ | ❌ | ❌ | ❌ | ❌ | pendente |
| 7 | **Chatbut** | 144.22.167.45 | chatbut.com.br | — | ❌ | ❌ | ❌ | ❌ | ❌ | pendente |

**Legenda:**
- ✅ concluido e verificado
- ❌ pendente (nao iniciado)
- ⚠️ em andamento ou com pendencia

---

## 2. Detalhe por Servidor

### 2.1 MetaSenderPro — CONCLUIDO

| Item | Valor |
|---|---|
| Data da sincronizacao | 2026-08-17 |
| Spec | `.spec/features/sync-metasenderpro/spec.md` |
| Commit main | `79f85a1e` (v8.5.14) |
| DB antes | 73 tabelas |
| DB depois | 76 tabelas (+3 tabelas, +1 coluna) |
| Go binary | Compilado no servidor, CGO_ENABLED=1, 28MB |
| Credenciais | 100% propias, zero cross-ref com main |
| Teste ligacao | ✅ auto-hangup 2s apos audio |
| Pendencias | Reconectar instancias WhatsApp (sessoes existem no banco mas precisam de pareamento) |

### 2.2 PlusZap — PENDENTE

| Item | Valor |
|---|---|
| IP | 92.113.144.161 |
| SSH | admin / Leonetto1982 |
| Path | /www/wwwroot/app_zapmatic_app |
| DB | sql_zapmatic_db / ZnCYYPwZwYxw8b6r |
| Go port | 8100 |
| Dominio | pluszap.com |
| Spec | (a criar) |

### 2.3 IaClicks — PENDENTE

| Item | Valor |
|---|---|
| IP | 45.148.29.92 |
| SSH | admin / Leonetto1982 |
| Path | /www/wwwroot/app_zapmatic_app |
| DB | sql_iaclicks_db / FxMzzfdLPr2yDS2F |
| Go port | 8098 |
| Dominio | iaclicks.com |
| Spec | (a criar) |

### 2.4 Elite — PENDENTE

| Item | Valor |
|---|---|
| IP | 193.180.211.190 |
| SSH | admin / Leonetto1982 |
| Path | /www/wwwroot/elitecomunicacao.zapmatic.tec.br |
| DB | sql_zapmatic_db / fe5kwDTMy3JdxDhT |
| Go port | 8099 |
| Dominio | elitecomunicacao.zapmatic.tec.br |
| Spec | (a criar) |

### 2.5 Kivozap — PENDENTE

| Item | Valor |
|---|---|
| IP | 144.22.167.45 |
| SSH | ubuntu (key auth) |
| Path | /www/wwwroot/app_abner_app |
| DB | db_abner_sql / inTwk7z37PnhWcY5 |
| Go port | 8095 |
| Dominio | kivozap.com.br |
| Spec | (a criar) |

### 2.6 AgenciaMCW — PENDENTE

| Item | Valor |
|---|---|
| IP | 144.22.167.45 |
| SSH | ubuntu (key auth) |
| Path | /www/wwwroot/app_frank_agencia |
| DB | sql_frank_db / apw4iTDGjePic8cb |
| Go port | 8096 |
| Dominio | chatbot.agenciamcw.com.br |
| Spec | (a criar) |

### 2.7 Chatbut — PENDENTE

| Item | Valor |
|---|---|
| IP | 144.22.167.45 |
| SSH | ubuntu (key auth) |
| Path | /www/wwwroot/app_alex_pedidu_app |
| DB | sql_alex_db / 6eNEfwPxHjdT757w |
| Go port | 8097 |
| Dominio | chatbut.com.br |
| Spec | (a criar) |

---

## 3. Processo de Atualizacao

Para cada servidor pendente:

1. Criar `.spec/features/sync-{tenant}/spec.md` a partir do template
2. Executar os 8 passos (DB → codigo → Go → credenciais → processos → testes)
3. Atualizar esta tabela de acompanhamento
4. Commitar no main

**Template de spec:** `.spec/features/sync-metasenderpro/spec.md` (secao 2)

---

## 4. Locais (mesma maquina)

Os 5 tenants locais (paulo, elias, renovo, astros + zapmatic main) compartilham o mesmo servidor mas tem bancos, portas e processos independentes. Ja foram atualizados no commit v8.5.14 (PM2 padronizado, ecosystem configs, call workers, gmscraper).

| Tenant local | Porta Go | PM2 prefixo | Status |
|---|---|---|---|
| zapmatic (main) | 8090 | zapmatic- | referencia |
| paulo | 8091 | paulo- | PM2 atualizado |
| elias | 8092 | elias- | PM2 atualizado |
| renovo | 8093 | renovo- | PM2 atualizado |
| astros | 8094 | astros- | PM2 atualizado |

---

## 5. Metricas de Progresso

| Metrica | Valor |
|---|---|
| Servidores remotos total | 7 |
| Servidores remotos concluidos | 1 |
| Servidores remotos pendentes | 6 |
| Progresso | 14% |
| Servidores locais atualizados | 5/5 (100%) |
| Versao main atual | v8.5.14 |
