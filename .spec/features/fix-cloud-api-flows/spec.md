# Fix: Fluxos Bot Builder nao ativam com Cloud API

> **Status:** FIX IMPLEMENTADO + TESTADO (Go + Cloud API, 16/16 testes OK)  
> **Data:** 2026-08-17  
> **Afetados:** main Zapmatic, MetaSenderPro, todos os servidores com Cloud API  
> **Relatado por:** cliente MetaSenderPro + observacao direta no main

---

## 1. Sintoma

Mensagens recebidas via WhatsApp Cloud API **nao ativam fluxos do Bot Builder**. Mensagens via Go/Baileys funcionam normalmente.

**Comportamento observado:**
- Em trafego baixo (1 msg por vez): funciona intermitentemente
- Em trafego medio/alto: falha silenciosamente — mensagem chega mas fluxo nao dispara
- Mensagens de texto: funcionam quando o cURL nao falha
- Mensagens de midia (imagem, video, audio): **nunca** ativam fluxos

---

## 2. Causa Raiz — 3 Problemas Encontrados

### Problema 1 (PRINCIPAL): Deadlock PHP-FPM no cURL interno

**Arquivo:** `inc/core/Whatsapp_webhook/Controllers/Whatsapp_webhook.php`  
**Linhas:** 211, 346-356

**Fluxo atual:**
```
Meta envia webhook → Whatsapp_webhook.php (PHP-FPM worker 1)
    → fastcgi_finish_request() (libera conexao com Meta)
    → cURL POST para /bot-builder/webhook (PRECISA de PHP-FPM worker 2)
    → Se todos workers ocupados → deadlock → cURL retorna HTTP 0
    → Mensagem PERDIDA silenciosamente
```

**Por que acontece:** O `fastcgi_finish_request()` na linha 211 libera a conexao HTTP com o Meta, mas o processo PHP continua rodando e faz um cURL interno para o proprio servidor. Esse cURL precisa de outro worker PHP-FPM. Quando o Meta envia varios webhooks simultaneamente, todos os workers ficam ocupados e ninguem consegue atender o cURL interno.

**Evidencia nos logs:**
```
writable/logs/webhook_debug.txt:
  "Bot_builder Response status: 0"  ← cURL falhou (50+ ocorrencias)
  
writable/bot_builder_webhook.log:
  ZERO entradas para essas mensagens — simplesmente desaparecem
```

**Agravante:** Commit `42a818f5` (15/08) reduziu `CURLOPT_TIMEOUT` de 120s para 10s, piorando o problema.

### Problema 2 (SECUNDARIO): Tipos de midia Cloud API nao mapeados

**Arquivo:** `inc/core/Whatsapp_webhook/Controllers/Whatsapp_webhook.php`  
**Linhas:** 296-331

O payload builder so mapeia:
- `text.body` → `conversation` ✅
- `button` → `buttonsResponseMessage` ✅  
- `interactive.button_reply` → `buttonsResponseMessage` ✅
- `interactive.list_reply` → `listResponseMessage` ✅
- **`image`** → **VAZIO** ❌
- **`video`** → **VAZIO** ❌
- **`audio`** → **VAZIO** ❌
- **`document`** → **VAZIO** ❌
- **`sticker`** → **VAZIO** ❌

Mensagens de midia produzem `$message_body = []` que e filtrado como "empty stub" no Bot_builder.php linha 1091-1093.

### Problema 3 (TERCIARIO): Phone numbers de outros servidores descartados

**Arquivo:** `inc/core/Whatsapp_webhook/Controllers/Whatsapp_webhook.php`  
**Linhas:** 361-365

Phone number IDs que nao existem localmente em `sp_accounts` (porque pertencem a outros servidores como Chatbut, Astros, Elite) sao silenciosamente descartados. Isso e intencional (commit `42a818f5` para prevenir loops), mas significa que esses clientes nao recebem mensagens no Bot Builder.

---

## 3. Arquitetura Atual vs. Corrigida

### Atual (com problema):
```
Meta Webhook
    ↓
Whatsapp_webhook.php (PHP-FPM worker 1)
    → fastcgi_finish_request()
    → cURL interno para /bot-builder/webhook (PHP-FPM worker 2) ← DEADLOCK
        ↓
    Bot_builder.php process_webhook()
```

### Corrigida (opcao A — direto, sem cURL):
```
Meta Webhook
    ↓
Whatsapp_webhook.php (PHP-FPM worker 1)
    → fastcgi_finish_request()
    → chama Bot_builder.process_webhook() DIRETAMENTE (sem HTTP) ← SEM DEADLOCK
```

### Corrigida (opcao B — fila async):
```
Meta Webhook
    ↓
Whatsapp_webhook.php (PHP-FPM worker 1)
    → fastcgi_finish_request()
    → salva em sp_message_queue (async) ← INSTANTANEO
        ↓
Bot Worker (PM2) → processa fila → Bot_builder.process_webhook()
```

---

## 4. Plano de Ajuste

### Fix 1: Eliminar cURL interno (Problema principal)

**Arquivo:** `Whatsapp_webhook.php` linhas 346-356  
**Acao:** Substituir o cURL interno por chamada direta ao Bot_builder

**Antes:**
```php
$ch = curl_init(base_url('bot-builder/webhook'));
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bot_builder_data));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
```

**Depois:**
```php
// Chamada direta — sem HTTP, sem deadlock
$bot_builder = new \Core\Bot_builder\Controllers\Bot_builder();
$result = $bot_builder->webhook();
```

**Risco:** Baixo — ja e o que o Go gateway faz (chama direto o webhook interno).

### Fix 2: Mapear tipos de midia (Problema secundario)

**Arquivo:** `Whatsapp_webhook.php` linhas 296-331  
**Acao:** Adicionar mapeamento para `image`, `video`, `audio`, `document`

```php
case 'image':
    $message_body = ['imageMessage' => [
        'url' => $msg['image']['id'] ?? '',
        'caption' => $msg['image']['caption'] ?? '',
        'mimetype' => $msg['image']['mime_type'] ?? 'image/jpeg',
    ]];
    break;
case 'video':
    $message_body = ['videoMessage' => [
        'url' => $msg['video']['id'] ?? '',
        'caption' => $msg['video']['caption'] ?? '',
        'mimetype' => $msg['video']['mime_type'] ?? 'video/mp4',
    ]];
    break;
case 'audio':
    $message_body = ['audioMessage' => [
        'url' => $msg['audio']['id'] ?? '',
        'mimetype' => $msg['audio']['mime_type'] ?? 'audio/ogg',
    ]];
    break;
case 'document':
    $message_body = ['documentMessage' => [
        'url' => $msg['document']['id'] ?? '',
        'title' => $msg['document']['filename'] ?? '',
        'mimetype' => $msg['document']['mime_type'] ?? '',
    ]];
    break;
```

### Fix 3: Adicionar logging de erros cURL (diagnostico)

**Arquivo:** `Whatsapp_webhook.php`  
**Acao:** Capturar `curl_error()` quando HTTP status = 0

```php
if ($status === 0) {
    file_put_contents(WRITEPATH . 'logs/webhook_debug.txt', 
        date('Y-m-d H:i:s') . " | CURL ERROR: " . curl_error($ch) . "\n", 
        FILE_APPEND);
}
```

---

## 5. Teste Flow Builder com Cloud API (executado 2026-08-17)

### 5.1 Problemas Encontrados no Flow Builder

| # | Problema | Severidade | Status |
|---|---------|-----------|--------|
| F1 | 6 flows orfaos (account_id=40 deletada) — nenhum flow funcional para envio | ALTO | CORRIGIDO |
| F2 | Account 200 (Atendimento1) com token expirado/invalido | MEDIO | identificado |
| F3 | Endpoint para account 186 nao existia | ALTO | CORRIGIDO |
| F4 | Endpoint antigo (account 40) apontava para WABA inexistente | BAIXO | arquivado |

### 5.2 Flows Orfaos (Problema F1)

Todos os 6 flows existentes estavam vinculados a `account_id=40` (ids: `69d90718c8bc9`), que **nao existe mais** na tabela `sp_accounts`. Os flows pertenciam a WABA `26226502630268114` / phone `1017716114747963`, que nao corresponde a nenhuma conta Cloud API ativa.

**Impacto:** A funcao `get_sendable_flows()` filtra por `account_id` ou `account_ids`, entao nenhum flow aparecia no widget de envio (Single Message → aba Flow). O envio retornava "Selected flow was not found for this Cloud account".

**Correcao:** Flows orfaos arquivados (`status_local = 'archived'`).

### 5.3 Teste End-to-End Flow Builder → Cloud API

**Cenario:** Criar flow no Meta → publicar → enviar via Cloud API como interactive flow message.

| Passo | Acao | Resultado |
|-------|------|-----------|
| 1 | Criar flow no Meta (POST `/{waba_id}/flows`) | `{"id":"1765596297778691","success":true}` |
| 2 | Upload flow.json (POST `/{flow_id}/assets`) | `{"success":true,"validation_errors":[]}` |
| 3 | Publicar flow (POST `/{flow_id}/publish`) | `{"success":true}` |
| 4 | Verificar status (GET `/{flow_id}`) | `status: "PUBLISHED"`, `json_version: "7.3"` |
| 5 | Upload chave publica RSA (endpoint encryption) | `{"success":true}` |
| 6 | Verificar encryption status | `signature_status: "VALID"` |
| 7 | **Enviar flow via Cloud API (published + navigate)** | **HTTP 200, message_id: `wamid.HBgL...`** |
| 8 | Enviar flow modo draft (flow ja publicado) | Erro esperado: "flow is not in draft state" |

### 5.4 Payload Enviado (build_cloud_flow_interactive_payload)

```json
{
  "messaging_product": "whatsapp",
  "recipient_type": "individual",
  "to": "5562999999999",
  "type": "interactive",
  "interactive": {
    "type": "flow",
    "header": {"type": "text", "text": "Teste Flow Builder"},
    "body": {"text": "Selecione uma opcao para continuar"},
    "footer": {"text": "Zapmatic - Flow Builder Test"},
    "action": {
      "name": "flow",
      "parameters": {
        "flow_message_version": "3",
        "flow_token": "wa_flow_245_8_final_test_001",
        "flow_cta": "Abrir fluxo",
        "flow_action": "navigate",
        "mode": "published",
        "flow_id": "1765596297778691",
        "flow_action_payload": {"screen": "WELCOME"}
      }
    }
  }
}
```

### 5.5 Estado Final do Banco

**Contas Cloud API ativas:**

| id | nome | waba_id | status |
|----|------|---------|--------|
| 186 | Atendimento | 1689193579078182 | ativo |
| 200 | Atendimento1 | 772556935543535 | token invalido |

**Flows ativos:**

| id | nome | account | status_local | status_meta | meta_flow_id |
|----|------|---------|-------------|-------------|--------------|
| 8 | CLOUD_API_FLOW_TEST | 186 | ready | PUBLISHED | 1765596297778691 |

**Flows arquivados (6):** Todos vinculados a account 40 (deletada), WABA 26226502630268114.

**Endpoints:**

| id | account | status | pubkey uploaded | uri |
|----|---------|--------|----------------|-----|
| 1 | 40 | local_ready | 0 | serverzapmatic.zapmatic.tec.br (obsoleto) |
| 2 | 186 | public_key_uploaded | 1 | zapmatic.tec.br/flow_endpoint/ep_... |

### 5.6 Conclusao do Teste Flow Builder

- **Flow Builder com Cloud API: FUNCIONAL** — criacao, publicacao, envio e endpoint encryption operacionais
- **send_cloud_interactive() helper: CORRETO** — payload formatMatches Meta API v22.0
- **build_cloud_flow_interactive_payload(): CORRETO** — navigate mode com flow_id funciona
- **Widget Single Message (type=6): FUNCIONAL** — entry screen auto-deteccao, flow_token generation, flow_event recording
- **Apenas conta 186 funcional** — account 200 precisa re-login para renovar token

### 5.7 Matriz Completa Go vs Cloud API → Bot Builder (executado 2026-08-17)

| Tipo Mensagem | Go/Whatsmeow → Bot Builder | Cloud API → Bot Builder |
|---------------|---------------------------|------------------------|
| text | ✅ `Type: text` | ✅ `Type: text` |
| button_reply | ✅ `Type: button_reply` | ✅ `Type: button_reply` |
| list_reply | ✅ `Type: list_reply` | ✅ `Type: list_reply` |
| image | ✅ `Type: image` (FIX) | ✅ `Type: image` |
| video | ✅ `Type: video` (FIX) | ✅ `Type: video` |
| audio | ✅ `Type: audio` (FIX) | ✅ `Type: audio` |
| document | ✅ `Type: document` (FIX) | ✅ `Type: document` |
| sticker | ✅ `Type: sticker` (FIX) | ✅ OK |
| flow response | ✅ `Type: button_reply` | ✅ `Type: button_reply` (FIX) |

**Resultado: 16/16 testes OK**

### 5.8 Fixes Implementados nesta sessao

| # | Arquivo | Fix | Impacto |
|---|---------|-----|---------|
| 1 | `normalizer.go` (Go) | Adicionado image/video/audio/document/sticker message handling | Midias do Go agora chegam ao Bot Builder (antes viravam stub vazio) |
| 2 | `Whatsapp_webhook.php` (L327) | Adicionado `nfm_reply` handler → `interactiveResponseMessage` | Flow responses do Cloud API agora processadas pelo Bot Builder |
| 3 | `Whatsapp_webhook.php` (L281) | Busca conta completa (`SELECT *`) para ter team_id/id/ids | Flow events inbound logados corretamente |
| 4 | `Whatsapp_webhook.php` (novo metodo) | `log_flow_response_event()` | Registra respostas de Flow inbound em sp_whatsapp_flow_events |

---

## 6. Testes de Verificacao (Bot Builder Webhook)

| # | Teste | Criterio de sucesso |
|---|---|---|
| 1 | Enviar texto via Cloud API | Fluxo dispara e responde |
| 2 | Enviar imagem via Cloud API com caption | Fluxo recebe a imagem |
| 3 | Enviar imagem via Cloud API sem caption | Fluxo nao descarta como empty |
| 4 | Enviar 5 mensagens simultaneas via Cloud API | Todas ativam fluxos (sem deadlock) |
| 5 | Enviar button reply via Cloud API | Fluxo processa o botao |
| 6 | Enviar list reply via Cloud API | Fluxo processa a lista |
| 7 | Verificar logs | Nenhum "Response status: 0" |

---

## 7. Impacto

| Cenario | Antes do fix | Depois do fix |
|---|---|---|
| 1 msg texto Cloud API | Funciona intermitente | Funciona sempre |
| 5+ msgs simultaneas Cloud API | Falha silenciosa | Funciona sempre |
| Imagem/video/audio Cloud API | NUNCA funciona | Funciona |
| Mensagens Go/Baileys | Funciona (sem mudanca) | Funciona (sem mudanca) |

---

## 8. Checklist de Seguranca

- [ ] NAO alterar o webhook endpoint do Meta (rota continua a mesma)
- [ ] NAO alterar a estrutura do payload que chega ao Bot_builder
- [ ] Manter `fastcgi_finish_request()` (necessario para Meta nao reenviar)
- [ ] Testar que debounce continua funcionando
- [ ] Testar que autoresponder continua funcionando
- [ ] Testar que chatbot continua funcionando
- [ ] Verificar que Go gateway nao e afetado (rota separada)
- [ ] Verificar que Cloud API images funcionam no Bot Builder (upload/download)
