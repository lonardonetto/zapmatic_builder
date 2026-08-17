# Sincronizacao MetaSenderPro — Especificacao e Historia

> **Status:** em execucao  
> **Data:** 2026-08-17  
> **Servidor origem (referencia):** main Zapmatic (`localhost`, `db_zapmatic_sql`, port 8090)  
> **Servidor destino:** MetaSenderPro (`92.113.149.185`, port 8101)  
> **Principio:** main Zapmatic e o laboratorio de desenvolvimento — todos os demais servidores devem ser identicos ao main em codigo, estrutura e comportamento, mas com credenciais proprias.

---

## 1. Escopo

### 1.1 O que Sera Atualizado

| Componente | Acao | Detalhe |
|---|---|---|
| **Banco de dados** | Sincronizar | Adicionar tabelas/colunas que faltam no meta. Dropar apenas tabelas/colunas legado que NAO existem no main. NUNCA dropar dados. |
| **inc/core/** | Substituir total | Copiar do main 100% |
| **app/** | Substituir total | Copiar do main 100% |
| **app_zapmatic_whatsmeow_api/** | Substituir + recompilar | Copiar do main, recompilar Go binary para o meta |
| **app_zapmatic_scraper/** | Substituir total | Copiar do main 100% |
| **assets/** | Substituir total | Copiar do main 100% |
| **migrations/** | Substituir total | Copiar do main 100% |
| **.spec/** | Substituir total | Copiar do main 100% |
| **docs/** | Substituir total | Copiar do main 100% |
| **_bmad/** | Substituir total | Copiar do main 100% |
| **Root files** | Substituir | ecosystem.config.js, spark, composer.json, etc |

### 1.2 O que NAO Sera Tocado

| Componente | Motivo |
|---|---|

pasta writbles nao devra ser substitutida
| **.env** | Credenciais proprias do meta (DB, dominio, keys) |
| **writable/** | Dados locais (logs, audio files, cache) |
| **.git/** | Repositorio proprio do meta (se houver) |

### 1.3 Pos-Atualizacao: Credenciais

Apos TODA substituicao, verificar e ajustar:

| Arquivo | O que verificar |
|---|---|
| `.env` | database credentials, baseURL, API keys (do META) |
| `app_zapmatic_whatsmeow_api/config.json` | DB credentials, webhook_url, port (do META) |
| `app/Config/App.php` | baseURL (do META) |
| `app/Config/Database.php` | credentials (do META) |
| systemd service | porta Go (8101), diretorio correto |
| ecosystem.config.js | prefixo `metasenderpro-` |

### 1.4 Pos-Atualizacao: Processos

| Processo | Comando | Status |
|---|---|---|
| `zapmatic-whatsmeow-metasenderpro` | systemd service, port 8101 | deve estar active |
| `metasenderpro-bot-worker-all` | PM2, `php spark bot:all` | deve estar online |
| `metasenderpro-call-worker` | PM2, `php spark call:campaigns` | deve estar online |
| `metasenderpro-gmscraper` | PM2, `node index.js` | deve estar online |

### 1.5 Testes Obrigatorios

| Teste | Criterio de sucesso |
|---|---|
| Extrair leads Google Maps | Job criado, leads aparecem na lista |
| Criar contato | Contato aparece na lista de contatos |
| Ligacao WhatsApp + auto-hangup | Chamada atendida, audio toca, desliga 2s apos audio |
| Disparo em massa (Cloud API) | Mensagens enviadas via Cloud API |
| Disparo em massa (Go/Baileys) | Mensagens enviadas via Go gateway |
| Fluxo Builder | Fluxo executa corretamente |
| Autoresponder | Responde automaticamente |
| Debounce | Mensagens agrupadas corretamente |

---

## 2. Execucao — Passo a Passo

### Passo 1: Analise de Diferencas de Banco
- Dump schema do meta via SSH
- Comparar com `migration_db_zapmatic_full.sql` do main
- Gerar SQL diff: tabelas/colunas para ADD e tabelas/colunas legado para DROP

### Passo 2: Aplicar Migration no Meta
- Executar SQL diff no meta
- Verificar que todas as 76 tabelas existem com colunas corretas

### Passo 3: Substituir Codigo
- rsync pastas: inc/core, app, app_zapmatic_whatsmeow_api, app_zapmatic_scraper, assets, migrations, .spec, docs, _bmad
- rsync root files: ecosystem.config.js, spark, composer.json, etc
- EXCLUIR: .env, writable/, .git/

### Passo 4: Compilar Go para Meta
- Compilar binary `zapmatic-whatsmeow` no servidor meta (ou cross-compile)
- Configurar `config.json` com credenciais do meta

### Passo 5: Ajustar Credenciais
- Verificar .env do meta (nao substituir)
- Ajustar config.json do Go com DB e porta do meta
- Ajustar systemd service para porta 8101
- Ajustar ecosystem.config.js para prefixo `metasenderpro-`

### Passo 6: Iniciar Processos
- `systemctl daemon-reload && systemctl restart zapmatic-whatsmeow-metasenderpro`
- `pm2 start ecosystem.config.js`
- `pm2 save`

### Passo 7: Testes
- Executar cada teste da secao 1.5
- Reportar resultados

---

## 3. Credenciais MetaSenderPro

| Item | Valor |
|---|---|
| IP | 92.113.149.185 |
| SSH user | MetaSenderPro |
| SSH pass | Hacker5030 |
| Path | /www/wwwroot/app_zapmatic_app |
| Go port | 8101 |
| Go service | zapmatic-whatsmeow-metasenderpro |
| DB | (a descobrir no passo 1) |

---

## 4. Checklist de Seguranca

- [ ] NUNCA substituir .env do meta
- [ ] NUNCA substituir writable/ do meta
- [ ] NUNCA dropar tabela ou coluna que existe no main
- [ ] NUNCA dropar dados — apenas estrutura legado
- [ ] NUNCA usar credenciais do main no meta
- [ ] NUNCA ter requisicoes cruzadas entre main e meta
- [ ] Verificar que webhook_url aponta para dominio do meta (ou IP)
- [ ] Verificar que Go config.json tem DB do meta
- [ ] Verificar que systemd service usa porta 8101
- [ ] Verificar que PM2 nomes tem prefixo `metasenderpro-`
