# 🎯 GUIA PRÁTICO - TESTE COMPLETO DO FLOW BUILDER

**Data:** 12/06/2026  
**Versão:** 1.0  
**Objetivo:** Testar o módulo Flow Builder conectado ao Zapmatic Cloud

---

## 📋 SUMÁRIO

1. [Preparação do Ambiente](#preparacao)
2. [Análise do Fluxo Webhook](#webhook)
3. [Como Testar Manualmente](#teste-manual)
4. [Monitoramento e Logs](#logs)
5. [Checklist de Validação](#checklist)
6. [Problemas Comuns e Soluções](#troubleshooting)

---

## 🔧 1. PREPARAÇÃO DO AMBIENTE {#preparacao}

### 1.1 Verificar Configuração do Banco

```sql
-- Verificar se as tabelas existem
SHOW TABLES LIKE 'sp_bb_%';

-- Resultado esperado:
-- sp_bb_blocks
-- sp_bb_edges
-- sp_bb_integrations
-- sp_bb_sessions
-- sp_bb_templates
-- sp_bb_versions

-- Verificar bots existentes
SELECT id, name, trigger_keywords, bot_enabled, status 
FROM sp_bot_builders 
WHERE status = 1 
ORDER BY created_at DESC 
LIMIT 10;

-- Contar blocos por bot
SELECT bot_id, COUNT(*) as total_blocks 
FROM sp_bb_blocks 
GROUP BY bot_id;
```

### 1.2 Identificar Sua Instância Zapmatic

```sql
-- Obter token da sua instância WhatsApp
SELECT id, token, phone, status, team_id 
FROM sp_accounts 
WHERE status = 1 
AND token IS NOT NULL
ORDER BY created_at DESC;

-- Anote:
-- Token: ________________
-- Phone: ________________
-- Team ID: ______________
```

### 1.3 Verificar Integração Bot ↔ Instância

```sql
-- Ver quais bots estão conectados a instâncias
SELECT 
    bb.id as bot_id,
    bb.name as bot_name,
    bb.trigger_keywords,
    bbi.instance_id,
    acc.phone as instance_phone
FROM sp_bot_builders bb
LEFT JOIN sp_bb_integrations bbi ON bb.id = bbi.bot_id
LEFT JOIN sp_accounts acc ON bbi.instance_id = acc.id
WHERE bb.status = 1;
```

---

## 🔗 2. ANÁLISE DO FLUXO WEBHOOK {#webhook}

### 2.1 Arquitetura do Webhook

```
WhatsApp Cloud API
    ↓
Zapmatic Server (Webhook receiver)
    ↓
POST /bot-builder/webhook
    ↓
Bot_builder::webhook()
    ↓
Bot_builder::process_webhook()
    ↓
Bot_builder::run_flow()
    ↓
Execução dos blocos sequencialmente
    ↓
Resposta ao usuário via WhatsApp
```

### 2.2 Fluxo de Execução Detalhado

**Passo 1: Recebimento da Mensagem**
```php
// Arquivo: Bot_builder.php linha 650
public function webhook()
{
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Payload esperado:
    // {
    //   "instance_id": "token_da_instancia",
    //   "data": {
    //     "messages": [
    //       {
    //         "key": {"fromMe": false, "remoteJid": "5585...@s.whatsapp.net"},
    //         "message": {"conversation": "texto da mensagem"},
    //         "_wa_id": "5585..."
    //       }
    //     ]
    //   }
    // }
}
```

**Passo 2: Identificação da Sessão**
```php
// Linha 742
$session = $this->model->get_session($phone, $instance_id);

// Se existe sessão ativa → continua o fluxo
// Se não existe → tenta match com trigger keyword
```

**Passo 3: Match de Trigger Keyword**
```php
// Linha 782
$bot = $this->model->find_bot_by_trigger($text, $instance_id, $phone);

// Verifica se a mensagem contém alguma palavra-chave configurada
// em sp_bot_builders.trigger_keywords
```

**Passo 4: Criação de Sessão**
```php
// Linha 785
$session_id = $this->model->create_session($bot->id, $phone, $instance_id);

// Cria registro em sp_bb_sessions com:
// - bot_id
// - phone (número do usuário)
// - current_block_id (bloco inicial)
// - context: {} (vazio no início)
```

**Passo 5: Execução do Fluxo**
```php
// Linha 876
private function run_flow($session, $input, $input_type, $instance_id, $is_start)
{
    $blocks = $this->model->get_blocks($bot_id);
    $edges = $this->model->get_edges($bot_id);
    
    // Loop máximo: 50 passos (proteção contra loop infinito)
    $step_count = 0;
    $current_block_id = $session->current_block_id;
    
    while ($current_block_id && $step_count < 50) {
        $block = find_block($blocks, $current_block_id);
        
        // Executa o bloco baseado no tipo
        switch($block->type) {
            case 'text': /* envia mensagem */ break;
            case 'input_text': /* aguarda resposta */ break;
            case 'buttons': /* envia botões */ break;
            case 'condition': /* avalia condição */ break;
            case 'webhook': /* chama API externa */ break;
            // ... 68 tipos no total
        }
        
        // Move para próximo bloco
        $next_edge = find_edge($edges, $current_block_id);
        $current_block_id = $next_edge->to_block_id;
        $step_count++;
    }
}
```

### 2.3 Tipos de Blocos e Comportamento

**Blocos de Saída (enviam mensagem)**
- `text`, `image`, `video`, `audio`, `embed`
- Executam imediatamente, avançam para próximo

**Blocos de Entrada (aguardam resposta)**
- `input_text`, `input_email`, `buttons`, `list`
- Param o fluxo, salvam `current_block_id`
- Aguardam próxima mensagem do usuário

**Blocos de Controle**
- `condition`: Avalia expressão, escolhe edge baseado em true/false
- `delay`: Aguarda X segundos
- `jump`: Pula para bloco específico
- `end`: Finaliza sessão

**Blocos de Integração**
- `webhook`: Faz chamada HTTP externa
- `ai_reply`: Chama API de IA (OpenAI/Gemini)
- `set_variable`: Define variável no contexto

### 2.4 Sistema de Contexto (Variáveis)

```php
// Contexto é um JSON armazenado em sp_bb_sessions.context
// Exemplo:
{
    "name": "João Silva",
    "email": "joao@example.com",
    "age": 30,
    "selected_option": "Sim",
    "custom_var": "valor qualquer"
}

// Variáveis podem ser usadas em mensagens com {{variable}}
// Exemplo: "Olá {{name}}! Seu e-mail é {{email}}"
```

---

## 🧪 3. COMO TESTAR MANUALMENTE {#teste-manual}

### 3.1 Criar Bot de Teste

**Via Interface Web:**
1. Acesse: `https://seu-dominio.com/bot-builder`
2. Clique em "Criar Bot"
3. Preencha:
   - Nome: "Bot Teste Completo"
   - Palavras-chave: `teste, start, iniciar`
   - Palavra de parada: `sair, parar`
4. Salve o bot

**Via SQL (alternativo):**
```sql
INSERT INTO sp_bot_builders (
    name, 
    description, 
    trigger_keywords, 
    stop_keyword,
    bot_enabled, 
    status, 
    team_id, 
    created_by, 
    created_at
) VALUES (
    'Bot Teste Completo',
    'Bot para validação do flow builder',
    'teste,start,iniciar',
    'sair,parar,stop',
    1, -- habilitado
    1, -- ativo
    1, -- seu team_id
    1, -- seu user_id
    NOW()
);
```

### 3.2 Construir Fluxo Simples (Editor Visual)

**Fluxo Recomendado para Teste:**

```
[START]
   ↓
[TEXT] "Olá! Bem-vindo ao teste do Flow Builder"
   ↓
[INPUT_TEXT] "Qual é seu nome?"
   → Salva em variável: name
   ↓
[INPUT_EMAIL] "Digite seu e-mail:"
   → Salva em variável: email
   ↓
[BUTTONS] "Você gostou do teste?"
   → Opções: "Sim", "Não", "Mais ou Menos"
   ↓
[CONDITION] if (button_value == "Sim")
   ├─ TRUE → [TEXT] "Que ótimo, {{name}}!"
   └─ FALSE → [TEXT] "Obrigado pelo feedback!"
   ↓
[TEXT] "Resumo: Nome={{name}}, Email={{email}}"
   ↓
[END]
```

**Total:** ~10 blocos (bom para teste inicial)

### 3.3 Conectar Bot à Instância WhatsApp

**Via Interface:**
1. No editor do bot, clique em "Configurações"
2. Aba "Integrações"
3. Selecione sua instância WhatsApp
4. Clique em "Conectar"

**Via SQL:**
```sql
-- Verificar o ID da sua instância
SELECT id, phone FROM sp_accounts WHERE status = 1;

-- Conectar bot (substitua os IDs)
INSERT INTO sp_bb_integrations (bot_id, instance_id, created_at)
VALUES (
    123, -- ID do bot criado
    456, -- ID da instância (sp_accounts.id)
    NOW()
);
```

### 3.4 Testar via WhatsApp

**Preparação:**
1. Certifique-se de que sua instância está conectada
2. Verifique se o bot está habilitado (bot_enabled = 1)
3. Anote o número da instância

**Teste 1: Iniciação do Fluxo**
```
Você: teste
Bot: Olá! Bem-vindo ao teste do Flow Builder
Bot: Qual é seu nome?
```

**Teste 2: Entrada de Texto**
```
Você: João Silva
Bot: Digite seu e-mail:
```

**Teste 3: Validação de Email**
```
Você: email-invalido
Bot: ⚠️ E-mail inválido. Tente novamente.
Você: joao@example.com
Bot: Você gostou do teste?
     [Sim] [Não] [Mais ou Menos]
```

**Teste 4: Botões**
```
Você: [clica em Sim]
Bot: Que ótimo, João Silva!
Bot: Resumo: Nome=João Silva, Email=joao@example.com
Bot: ✅ Fluxo concluído!
```

**Teste 5: Palavra de Parada**
```
(Durante qualquer etapa)
Você: sair
Bot: Bot interrompido. Envie "teste" para iniciar novamente.
```

---

## 📊 4. MONITORAMENTO E LOGS {#logs}

### 4.1 Arquivos de Log

```bash
# Log principal do webhook
tail -f /www/wwwroot/app_zapmatic_app/writable/bot_builder_webhook.log

# Log de envio de mensagens
tail -f /www/wwwroot/app_zapmatic_app/writable/bot_builder_send.log

# Log geral do sistema
tail -f /www/wwwroot/app_zapmatic_app/writable/logs/log-$(date +%Y-%m-%d).log
```

### 4.2 Exemplo de Log Webhook (Sucesso)

```
2026-06-12 15:30:00 | Webhook received | Raw: {"instance_id":"abc123"...
2026-06-12 15:30:00 | Processing instance: abc123 | Messages: 1
2026-06-12 15:30:00 | Token: abc123 → Account ID: 456
2026-06-12 15:30:00 | Phone: 5585987654321 | Reply: 5585987654321 | Text: teste | Type: text
2026-06-12 15:30:00 | ✅ Keyword matched! Bot #123 (Bot Teste) | Start: block_start_xyz
2026-06-12 15:30:01 | 📤 Sending text to 5585987654321
2026-06-12 15:30:01 | ✅ Flow completed successfully
```

### 4.3 Monitorar Sessões Ativas

```sql
-- Ver sessões ativas em tempo real
SELECT 
    s.id,
    s.bot_id,
    b.name as bot_name,
    s.phone,
    bl.type as current_block_type,
    bl.label as current_block_label,
    s.updated_at,
    TIMESTAMPDIFF(MINUTE, s.updated_at, NOW()) as minutes_idle
FROM sp_bb_sessions s
JOIN sp_bot_builders b ON s.bot_id = b.id
LEFT JOIN sp_bb_blocks bl ON s.current_block_id = bl.id
WHERE s.completed_at IS NULL
ORDER BY s.updated_at DESC;
```

### 4.4 Verificar Contexto de uma Sessão

```sql
-- Ver contexto (variáveis) de uma sessão específica
SELECT 
    id,
    phone,
    JSON_PRETTY(context) as variables,
    current_block_id,
    updated_at
FROM sp_bb_sessions
WHERE id = 123; -- ID da sessão
```

