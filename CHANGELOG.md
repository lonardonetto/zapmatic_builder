# Changelog Zapmatic

Todas as versões do sistema. Atualizado automaticamente com cada release.

---

## v8.3.27 — 01/08/2026

**API completa migrada para Go (zero Baileys)**

- `Whatsapp_api.php` refatorado: helper `go_api()` chama Go API direto
- 6 chamadas `wa_get_curl` substituídas por `go_api`
- `login_type` filtro removido Baileys (`[1,2,3]` → `[1,3]`)
- `logout()` era stub vazio → agora funciona via Go API POST
- `get_groups` usa `/groups/list` do Go API
- Todas verificações `$session` legacy removidas
- Zero referências Baileys no controller

---

## v8.3.26 — 01/08/2026

**Exclusão de links + updater fix**

- Botão excluir (🗑) em links conectados e expirados na tab "Links de Conexão"
- Endpoint `delete_connection_link` adicionado
- Updater: migrations rodam ANTES do rsync (evita tela branca)
- `Connect.php` protegido com try-catch contra tabela ausente

---

## v8.3.25 — 01/08/2026

**Link público de conexão WhatsApp**

- Admin gera link seguro e envia ao cliente
- Cliente abre no navegador, vê QR Code ou PIN
- Conecta WhatsApp sem ter conta no sistema
- Perfil aparece automaticamente na Central de Conexões
- Tabs profissionais: "Contas Conectadas" + "Links de Conexão"
- Auto-refresh QR quando expira
- `pair_status.php` detecta porta Go automaticamente
- SQL escaping com query builder (não `$db->escape` raw)
- `Filters.php` exclui `/connect/*` do auth filter
- Migration SQL `sp_connection_links`

---

## v8.3.24 — 31/07/2026

**Phone Pairing Code (PIN) completo**

- Go API: endpoint `/paircode` usando `PairPhone` do whatsmeow
- `PairSuccess` agora seta `StateConnected` (antes não fazia nada)
- Goroutine QR protegida: não sobrescreve `pair_code_ready` com `qr_ready`
- Timeout/erro QR não reseta estado em modo pair code
- `pair_status.php` endpoint standalone para auto-detect de conexão
- Avatar auto-salvo ao conectar via PIN
- `config.json` aceita `port` como int ou string

---

## v8.3.23 — 31/07/2026

**Phone Pairing Code — implementação inicial**

- Go API: endpoint `/paircode` e `/get_paircode`
- Chama `cli.PairPhone()` do whatsmeow e retorna código 8 dígitos
- PHP: rota `whatsapp_profiles/get_paircode` para AJAX
- JS: polling de status + botão "Já conectei"

---

## v8.3.22 — 31/07/2026

**Fix autoresponder session ping-pong**

- `use_existing` com `current_block_id` vazio: apenas estende timeout via `NOW()`
- Não reabre sessão completada (evita ping-pong `is_completed`)
- Removeu debug temporário de logs
- `session_timeout` respeitado sem reenvio de fluxo

---

## v8.3.21 — 31/07/2026

**Deploy All script**

- Script `deploy_all.sh` para sincronizar todos os clientes
- Suporte a 5 instâncias locais + 6 remotas
- SSH key para 144.22, password para demais

---

## v8.3.18 → v8.3.20

**Infraestrutura e estabilidade**

- Migração de Baileys para WhatsMeow (Go)
- Multi-tenant com gateways separados por cliente
- Bot workers por cliente (PM2)
- Sistema de atualização automático via GitHub tags
- Cloud API (Meta) como segundo provider
- Bot Builder com debounce, autoresponder, campanhas
