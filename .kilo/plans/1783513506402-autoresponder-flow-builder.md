# Plano: Autoresponder via Flow Builder (substitui legado)

## Contexto

O ZapMatic tem um **autoresponder legado** (`sp_whatsapp_autoresponder`) que será **removido**. Este plano implementa a funcionalidade equivalente dentro do Flow Builder — qualquer fluxo pode ser ativado como autoresponder (responde qualquer mensagem, com delay em segundos configurável).

### 3 APIs — Isolamento total (nenhuma mistura)

| API | login_type | Entrada de mensagem | Envio de resposta |
|-----|-----------|---------------------|-------------------|
| **Baileys** | 2/3 | WebSocket → waziper.js → POST `bot-builder/webhook` | `sessions[id].sendMessage()` |
| **Meta Cloud API** | 1 | Webhook PHP → waziper.js → POST `bot-builder/webhook` | `POST graph.facebook.com/v19.0/...` |
| **whatsmeow** (Go) | via gateway | Go `receiver.go` → POST direto `bot-builder/webhook` | `WhatsAppGatewayService::send()` |

**Convergência:** Todas as 3 APIs convergem no MESMO endpoint PHP `process_webhook()` do `Bot_builder.php`. O autoresponder hook é adicionado lá — funciona para todas as APIs automaticamente.

**Envio:** O `send_whatsapp()` do Flow Builder já roteia por `login_type`. Autoresponder usa a mesma rota — zero mudanças no envio.

### Concorrência / Fila (múltiplos números simultâneos)

- **Cada telefone ganha sua própria sessão** (`sp_bb_sessions`) — não conflitam
- **Go server (whatsmeow):** usa goroutines nativas (uma por mensagem), HTTP POST síncrono (30s timeout) para o PHP. Sem fila explícita — Go gerencia concorrência nativamente.
- **waziper.js (Baileys/Cloud):** cada mensagem é processada em async call independente (fire-and-forget)
- **PHP:** foreach sequencial no `process_webhook`, mas sessões são independentes por telefone — 10 números chamando ao mesmo tempo = 10 sessões independentes
- **Delay check** é per-phone — diferentes números podem ter sessões ativas simultaneamente sem conflito
- **Envio de respostas:** `send_whatsapp()` já roteia para o provedor correto (Baileys direto, whatsmeow via `WhatsAppGatewayService` HTTP POST para Go, Cloud via Meta API). Cada envio é independente.
- Para cargas normais, não precisa de fila explícita. Se necessário no futuro, adicionar worker pool no Go ou queue no Node.js.

---

## Alterações

### 1. Banco de Dados — 3 colunas novas

```sql
-- sp_bot_builders: configuração do autorespond por bot
ALTER TABLE sp_bot_builders
ADD COLUMN autorespond TINYINT(1) DEFAULT 0 AFTER chat_type,
ADD COLUMN autorespond_delay INT(11) DEFAULT 60 AFTER autorespond;

-- sp_bb_sessions: rastrear último autorespond por sessão (para delay)
ALTER TABLE sp_bb_sessions
ADD COLUMN autorespond_last_at DATETIME DEFAULT NULL AFTER updated_at;
```

| Campo | Tabela | Tipo | Default | Descrição |
|-------|--------|------|---------|-----------|
| `autorespond` | sp_bot_builders | TINYINT(1) | 0 | 1=responde qualquer palavra |
| `autorespond_delay` | sp_bot_builders | INT(11) | 60 | Segundos entre respostas ao mesmo contato |
| `autorespond_last_at` | sp_bb_sessions | DATETIME | NULL | Timestamp do último autorespond enviado |

### 2. Model Migration (`Bot_builderModel.php::auto_migrate` ~linha 153)

```php
$this->safe_add_column('sp_bot_builders', 'autorespond', "TINYINT(1) DEFAULT 0 AFTER `chat_type`");
$this->safe_add_column('sp_bot_builders', 'autorespond_delay', "INT(11) DEFAULT 60 AFTER `autorespond`");
$this->safe_add_column('sp_bb_sessions', 'autorespond_last_at', "DATETIME DEFAULT NULL AFTER `updated_at`");
```

### 3. Método `find_autorespond_bot()` (`Bot_builder.php`)

Novo método privado. Busca bot com `autorespond=1` vinculado à instância. Se múltiplos, retorna o primeiro por ID.

```php
private function find_autorespond_bot($instance_id)
{
    return $this->model->db->table('sp_bot_builders as b')
        ->select('b.*')
        ->join('sp_bb_integrations as i', 'i.bot_id = b.id')
        ->where('i.instance_id', $instance_id)
        ->where('i.status', 1)
        ->where('b.status', 1)
        ->where('b.bot_enabled', 1)
        ->where('b.autorespond', 1)
        ->orderBy('b.id', 'ASC')
        ->get()->getRow() ?: null;
}
```

### 4. Método `check_autorespond_delay()` (`Bot_builder.php`)

Verifica se já passou o delay configurado desde a última resposta ao mesmo contato.

```php
private function check_autorespond_delay($bot_id, $phone, $delay_seconds)
{
    $session = $this->model->db->table('sp_bb_sessions')
        ->where('bot_id', $bot_id)
        ->where('phone', $phone)
        ->where('autorespond_last_at IS NOT NULL')
        ->orderBy('autorespond_last_at', 'DESC')
        ->get()->getRow();

    if (!$session) return true;
    return (time() - strtotime($session->autorespond_last_at)) >= $delay_seconds;
}
```

### 5. Hook no `process_webhook()` (Bot_builder.php ~linha 995)

Após Reply trigger falhar, antes do `}` que fecha o `else`:

```php
// 5. Autorespond — responder qualquer palavra
if ($handled_count === 0) {
    $auto_bot = $this->find_autorespond_bot($instance_id_for_lookup);
    if ($auto_bot) {
        $delay = max(1, intval($auto_bot->autorespond_delay ?? 60));
        if ($this->check_autorespond_delay($auto_bot->id, $phone, $delay)) {
            $session_id = $this->model->create_session($auto_bot->id, $phone, $instance_id_for_lookup);
            $this->model->db->table('sp_bb_sessions')
                ->where('id', $session_id)
                ->update(['autorespond_last_at' => date('Y-m-d H:i:s')]);
            $session = (object)[
                'id' => $session_id,
                'bot_id' => $auto_bot->id,
                'phone' => $reply_phone,
                'reply_phone' => $reply_phone,
                'canonical_phone' => $phone,
                'context' => '{}',
                'current_block_id' => $auto_bot->start_block_id
            ];
            $this->run_flow($session, $text, $type, $instance_id_for_send, true);
            $handled_count++;
        }
    }
}
```

### 6. `save_bot_settings()` (Bot_builder.php ~linha 573)

```php
if (post('autorespond') !== null) {
    $update['autorespond'] = post('autorespond') == '1' ? 1 : 0;
}
if (post('autorespond_delay') !== null) {
    $update['autorespond_delay'] = max(1, intval(post('autorespond_delay')));
}
```

### 7. `get_bot_settings()` (Bot_builder.php ~linha 601)

```php
'autorespond' => $bot->autorespond ?? 0,
'autorespond_delay' => $bot->autorespond_delay ?? 60,
```

### 8. waziper.js — Gate check `bot_builder_flow()` (~linha 3535)

Query SQL — adicionar colunas:
```javascript
// No SELECT, adicionar: b.autorespond, b.autorespond_delay
"SELECT i.bot_id, b.trigger_keywords, b.enable_keyword, b.stop_keyword, b.bot_enabled, " +
"b.keyword_match_type, b.chat_type, b.status, b.autorespond, b.autorespond_delay " +
```

Após keyword matching falhar (~linha 3600), adicionar:
```javascript
// Autorespond check
if (!shouldProcess) {
    for (const bot of integrations) {
        if (bot.bot_enabled == 0) continue;
        const chatType = bot.chat_type || 'all';
        if (chatType === 'individual' && isGroup) continue;
        if (chatType === 'groups' && !isGroup) continue;
        if (bot.autorespond == 1) {
            shouldProcess = true;
            break;
        }
    }
}
```

### 9. Frontend — Toggle no Publish Modal (`editor.php` ~linha 475)

```html
<div class="pm-setting-row">
    <div class="pm-setting-info">
        <div class="pm-setting-label">⚡ Responder qualquer palavra</div>
        <div class="pm-setting-desc">Modo autoresponder — fluxo responde qualquer mensagem recebida</div>
    </div>
    <label class="pm-toggle-switch">
        <input type="checkbox" id="pm-autorespond">
        <span class="pm-toggle-slider"></span>
    </label>
</div>
<div class="pm-input-row" id="pm-autorespond-delay-row" style="display:none;">
    <label style="font-size:12px;color:#6b7280;margin-bottom:4px;display:block;">
        Delay entre respostas ao mesmo contato (segundos)
    </label>
    <input type="number" class="pm-input" id="pm-autorespond-delay"
           value="60" min="1" max="86400" placeholder="60">
</div>
```

### 10. JS — `publish-modal.js` (~linha 70)

```javascript
const autorespond = document.getElementById('pm-autorespond');
const autorespondDelay = document.getElementById('pm-autorespond-delay');
if (autorespond) fd.append('autorespond', autorespond.checked ? '1' : '0');
if (autorespondDelay) fd.append('autorespond_delay', autorespondDelay.value || '60');
```

Toggle show/hide:
```javascript
const arToggle = document.getElementById('pm-autorespond');
const arDelayRow = document.getElementById('pm-autorespond-delay-row');
if (arToggle && arDelayRow) {
    arToggle.addEventListener('change', () => {
        arDelayRow.style.display = arToggle.checked ? 'block' : 'none';
    });
}
```

### 11. `window.initialBotSettings` (`editor.php` ~linha 1173)

```javascript
autorespond: 0,
autorespond_delay: 60
```

Carregar estado do toggle:
```javascript
if (window.initialBotSettings.autorespond == 1) {
    document.getElementById('pm-autorespond').checked = true;
    document.getElementById('pm-autorespond-delay-row').style.display = 'block';
}
document.getElementById('pm-autorespond-delay').value =
    window.initialBotSettings.autorespond_delay || 60;
```

---

## Fluxo de Execução Final

```
Mensagem recebida (Baileys / Cloud / whatsmeow)
    │
    ▼
waziper.js: bot_builder_flow()
    ├── Sessão ativa? → forward para PHP
    ├── Keyword match? → forward para PHP
    ├── Autorespond bot? → forward para PHP ← NOVO
    └── Nenhum? → return false → chatbot() → legado()

PHP: process_webhook()
    ├── 1. Sessão ativa? → run_flow()
    ├── 2. Keyword match? → create_session → run_flow()
    ├── 3. Command match? → create_session → run_flow()
    ├── 4. Reply match? → create_session → run_flow()
    ├── 5. Autorespond? → check_delay → create_session → run_flow() ← NOVO
    └── 6. Nenhum? → return 0
```

---

## Arquivos a Modificar

| # | Arquivo | Alteração | ~Linhas |
|---|---------|-----------|---------|
| 1 | `Bot_builderModel.php` | `auto_migrate()` — 3 colunas | 3 |
| 2 | `Bot_builder.php` | +`find_autorespond_bot()` | 18 |
| 3 | `Bot_builder.php` | +`check_autorespond_delay()` | 13 |
| 4 | `Bot_builder.php` | Hook no `process_webhook` (step 5) | 20 |
| 5 | `Bot_builder.php` | `save_bot_settings()` | 6 |
| 6 | `Bot_builder.php` | `get_bot_settings()` | 2 |
| 7 | `waziper.js` | Query SQL + gate check autorespond | 13 |
| 8 | `editor.php` | Toggle + delay + initialBotSettings + JS | 30 |
| 9 | `publish-modal.js` | `appendBotSettings()` + toggle handler | 14 |

**Total: ~119 linhas. NENHUM arquivo novo.**

---

## Ordem de Implementação

1. SQL migration (auto_migrate — 3 colunas)
2. `find_autorespond_bot()` + `check_autorespond_delay()` no Controller
3. Hook no `process_webhook` (step 5)
4. `save_bot_settings()` + `get_bot_settings()`
5. waziper.js — query SQL + gate check
6. Frontend: toggle + delay no Publish Modal
7. Teste end-to-end nas 3 APIs

---

## Validação

1. Criar fluxo simples (start → text "Oi, sou o autoresponder!" → end)
2. Ativar toggle "⚡ Responder qualquer palavra", delay 60s
3. Publicar e vincular a instância WhatsApp
4. Enviar "teste" → deve responder "Oi, sou o autoresponder!"
5. Enviar "teste2" dentro de 60s → NÃO responde (delay)
6. Enviar "teste3" após 60s → responde novamente
7. Repetir nas 3 APIs (Baileys, Cloud, whatsmeow)
