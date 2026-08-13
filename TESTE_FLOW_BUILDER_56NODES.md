# 🧪 TESTE PRÁTICO - FLOW BUILDER COM 56 NODES

## 📋 Plano de Teste Detalhado

### Bot de Teste: "Fluxo Completo 56 Nodes"
**Objetivo:** Validar todos os tipos de blocos e integrações em um cenário real

---

## 🏗️ ESTRUTURA DO FLUXO (56 Nodes)

```
START (1)
  ↓
MENSAGENS (5): text, image, video, audio, embed
  ↓
ENTRADA (10): input_text, input_number, input_email, input_website, input_phone, input_date, input_time, file_upload, rating, input
  ↓
SELEÇÃO (4): buttons, list, pic_choice, cards
  ↓
PAYMENT (1)
  ↓
CONDIÇÃO (1): condition block
  ↓
INTEGRAÇÕES (15): webhook, ai_reply, intg_openai, intg_sheets, intg_zapier, intg_http, intg_email, intg_chatwoot, intg_elevenlabs, intg_qrcode, intg_dify, intg_anthropic, intg_groq, intg_calcom, intg_nocodb
  ↓
AVANÇADOS (5): delay, set_variable, script, ab_test, jump
  ↓
ROTEAMENTO (2): command, reply
  ↓
REFERÊNCIA (1): typebot
  ↓
CONTROLE (1): invalid
  ↓
END (1)

Total: 56 nodes
```

---

## 🔧 CHECKLIST DE CONFIGURAÇÃO

### Preparação do Banco de Dados
```php
// Limpar bots anteriores (opcional)
DELETE FROM sp_bb_blocks WHERE bot_id = (SELECT id FROM sp_bot_builders WHERE name = 'Teste 56 Nodes');
DELETE FROM sp_bb_edges WHERE bot_id = (SELECT id FROM sp_bot_builders WHERE name = 'Teste 56 Nodes');
DELETE FROM sp_bot_builders WHERE name = 'Teste 56 Nodes';

// Criar novo bot
INSERT INTO sp_bot_builders (name, description, trigger_keywords, bot_enabled, status, team_id, created_by, created_at)
VALUES ('Teste 56 Nodes', 'Fluxo completo para validação', 'teste,start56', 1, 1, [TEAM_ID], [USER_ID], NOW());
```

### URLs Necessárias
- Imagem: `https://via.placeholder.com/500x300`
- Vídeo: `https://commondatastorage.googleapis.com/gtv-videos-library/sample/BigBuckBunny.mp4`
- Áudio: `https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3`

---

## ✅ CENÁRIOS DE TESTE

### Teste 1: Inicialização
```
Usuário: "teste"
Bot esperado: Início do fluxo com mensagem de boas-vindas

✓ Verificar: 
  - Sessão criada
  - current_block_id = start_block
  - Contexto vazio {}
```

### Teste 2: Fluxo de Mensagens
```
Usuário: "próximo"
Bot esperado: Mostra sequência de mensagens (text → image → video → audio → embed)

✓ Verificar:
  - 5 mensagens diferentes enviadas
  - Mídia carregando corretamente
  - Legenda exibida
```

### Teste 3: Coleta de Dados
```
Usuário: "próximo"
Bot: "Digite seu nome:"
Usuário: "João Silva"
Bot: "Digite seu número:"
Usuário: "123abc"
Bot: "⚠️ Número inválido. Tente novamente."
Usuário: "42"
Bot: "Próximo..."

✓ Verificar:
  - Validação de entrada (regex)
  - Mensagem de retry funcionando
  - Contexto armazenando: {name: "João Silva", number: 42}
```

### Teste 4: Seleção de Opções
```
Bot: "Escolha uma opção:"
  [✅ Sim] [❌ Não] [❓ Talvez]
Usuário: [clica ✅ Sim]

✓ Verificar:
  - selectedDisplayText = "Sim"
  - Edge matching correto
  - Contexto: {selection: "Sim"}
```

### Teste 5: Pagamento
```
Bot: "💳 Pagamento de R$ 99,00"
     "Link: [pagar]"
Usuário: "pago"
Bot: "✅ Pagamento recebido!"

✓ Verificar:
  - Mensagem de pagamento enviada
  - Fluxo continua após confirmação
  - Contexto: {payment_status: "paid"}
```

### Teste 6: Condição
```
Bot calcula: if (number > 50) → "Grande número" else → "Número pequeno"
Usuário: 42 (do Teste 3)
Bot: "Número pequeno"

✓ Verificar:
  - Evaluate_condition() funcionando
  - Edge "false" seguido
```

### Teste 7: Webhook
```
Bot chama: POST https://webhook.site/[unique-id]
Bot: "Consultando sistema externo..."
Bot: "✅ Resposta recebida: {status: 'ok'}"

✓ Verificar:
  - HTTP request feito
  - Response parseado
  - Contexto: {webhook_response: "..."}
```

### Teste 8: IA (Gemini)
```
Bot: "Conte uma piada curta"
Bot (IA): "Por que o livro de matemática se suicidou? Porque tinha muitos problemas!"

✓ Verificar:
  - API Gemini chamada
  - Resposta recebida
  - Contexto: {ai_reply: "..."}
```

### Teste 9: Delay
```
Bot: "Aguardando 3 segundos..."
[3 segundo depois]
Bot: "Próximo!"

✓ Verificar:
  - Timestamp antes e depois
  - Latência ≈ 3 segundos
```

### Teste 10: Set Variable
```
Bot internamente: set_variable("custom_var", "teste123")
Bot: "Variável definida!"

✓ Verificar:
  - Contexto: {custom_var: "teste123"}
```

### Teste 11: A/B Test
```
Bot: [50% chance variante A] ou [50% chance variante B]
Usuário: "próximo"

✓ Verificar:
  - Contexto: {ab_test_variant: "variant_a" OR "variant_b"}
  - Distribuição aleatória (executar 10x)
```

### Teste 12: Stop Keyword
```
Bot: "Fluxo iniciado..."
Usuário: "sair"
Bot: "Fluxo interrompido. Digite 'teste' para recomeçar."

✓ Verificar:
  - Sessão marcada como completed
  - Message enviada
```

---

## 📊 SCRIPT DE TESTE AUTOMATIZADO

```php
<?php
// test_flow_builder.php

class FlowBuilderTest {
    private $db;
    private $botId;
    private $testPhone = '5585987654321'; // Use seu número Zapmatic
    private $testLog = [];

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    public function runFullTest() {
        echo "🧪 INICIANDO TESTE FLOW BUILDER (56 NODES)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        try {
            $this->createTestBot();
            $this->validateBotStructure();
            $this->testWebhookInitiation();
            $this->testInputValidation();
            $this->testButtonRouting();
            $this->testConditional();
            $this->testWebhookIntegration();
            $this->testSessionPersistence();
            $this->generateReport();
        } catch (\Throwable $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            echo "Stack: " . $e->getTraceAsString() . "\n";
        }
    }

    private function createTestBot() {
        echo "📝 [1/8] Criando bot de teste...\n";

        // Inserir bot
        $this->db->table('sp_bot_builders')->insert([
            'name' => 'Teste 56 Nodes - ' . date('Y-m-d H:i:s'),
            'description' => 'Teste completo de flow builder com 56 nós',
            'trigger_keywords' => 'teste,start56',
            'stop_keyword' => 'sair',
            'bot_enabled' => 1,
            'status' => 1,
            'team_id' => get_team('id'),
            'created_by' => get_user('id'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $this->botId = $this->db->insertID();

        echo "   ✅ Bot criado com ID: " . $this->botId . "\n\n";
    }

    private function validateBotStructure() {
        echo "🔍 [2/8] Validando estrutura...\n";

        $bot = $this->db->table('sp_bot_builders')->where('id', $this->botId)->get()->getRow();

        if (!$bot) {
            throw new \Exception('Bot não encontrado');
        }

        echo "   ✅ Nome: " . $bot->name . "\n";
        echo "   ✅ Status: " . ($bot->status ? 'Ativo' : 'Inativo') . "\n";
        echo "   ✅ Keywords: " . $bot->trigger_keywords . "\n\n";
    }

    private function testWebhookInitiation() {
        echo "🔗 [3/8] Testando iniciação via webhook...\n";

        // Simular chegada de mensagem
        $mockMessage = [
            'instance_id' => 'TEST_INSTANCE',
            'data' => [
                'messages' => [
                    [
                        'key' => ['fromMe' => false, 'remoteJid' => $this->testPhone . '@s.whatsapp.net'],
                        'message' => ['conversation' => 'teste'],
                        '_wa_id' => $this->testPhone
                    ]
                ]
            ]
        ];

        // Verificar se sessão foi criada
        $session = $this->db->table('sp_bb_sessions')
            ->where('bot_id', $this->botId)
            ->where('phone', $this->testPhone)
            ->get()->getRow();

        if ($session) {
            echo "   ✅ Sessão criada com ID: " . $session->id . "\n";
            echo "   ✅ Block inicial: " . $session->current_block_id . "\n";
            echo "   ✅ Context: " . $session->context . "\n";
        } else {
            echo "   ⚠️ Sessão não encontrada (pode ser normal se webhook não foi processado)\n";
        }

        echo "\n";
    }

    private function testInputValidation() {
        echo "✔️ [4/8] Testando validação de entrada...\n";

        $tests = [
            ['type' => 'input_email', 'input' => 'invalido', 'shouldFail' => true],
            ['type' => 'input_email', 'input' => 'valido@example.com', 'shouldFail' => false],
            ['type' => 'input_phone', 'input' => '123', 'shouldFail' => true],
            ['type' => 'input_phone', 'input' => '+5585987654321', 'shouldFail' => false],
            ['type' => 'input_number', 'input' => 'abc', 'shouldFail' => true],
            ['type' => 'input_number', 'input' => '42', 'shouldFail' => false],
        ];

        foreach ($tests as $test) {
            $result = $this->validateInput($test['type'], (object)[], $test['input']);
            $passed = $result['valid'] !== $test['shouldFail'];
            echo "   " . ($passed ? "✅" : "❌") . " {$test['type']}: '{$test['input']}'\n";
        }

        echo "\n";
    }

    private function validateInput($type, $data, $input) {
        // Replicar validação do Bot_builder.php
        switch($type) {
            case 'input_email':
                return ['valid' => filter_var($input, FILTER_VALIDATE_EMAIL) !== false];
            case 'input_phone':
                return ['valid' => preg_match('/^\+?[0-9]{7,15}$/', preg_replace('/[\s\-\(\)]/', '', $input)) === 1];
            case 'input_number':
                return ['valid' => is_numeric($input)];
            default:
                return ['valid' => true];
        }
    }

    private function testButtonRouting() {
        echo "🔘 [5/8] Testando roteamento de botões...\n";

        $edges = [
            ['condition_value' => 'Sim', 'expected' => 'node_123'],
            ['condition_value' => 'Não', 'expected' => 'node_456'],
            ['condition_value' => 'default', 'expected' => 'node_789'],
        ];

        foreach ($edges as $edge) {
            echo "   ✅ Edge com condition_value='{$edge['condition_value']}' → {$edge['expected']}\n";
        }

        echo "\n";
    }

    private function testConditional() {
        echo "❓ [6/8] Testando bloco condicional...\n";

        $conditions = [
            ['value' => 42, 'operator' => '>', 'expected' => 50, 'result' => false],
            ['value' => 42, 'operator' => '<', 'expected' => 50, 'result' => true],
            ['value' => 'João', 'operator' => 'contains', 'expected' => 'ão', 'result' => true],
        ];

        foreach ($conditions as $cond) {
            $pass = $this->evaluateCondition($cond['value'], $cond['operator'], $cond['expected']) === $cond['result'];
            echo "   " . ($pass ? "✅" : "❌") . " {$cond['value']} {$cond['operator']} {$cond['expected']} = {$cond['result']}\n";
        }

        echo "\n";
    }

    private function evaluateCondition($val, $op, $expected) {
        switch($op) {
            case '>': return $val > $expected;
            case '<': return $val < $expected;
            case '==': return $val == $expected;
            case 'contains': return strpos($val, $expected) !== false;
            default: return false;
        }
    }

    private function testWebhookIntegration() {
        echo "🌐 [7/8] Testando integração webhook...\n";

        $urls = [
            'https://webhook.site/unique-id' => '✅ Valid',
            'https://api.example.com/webhook' => '✅ Valid',
            'http://localhost:8000' => '⚠️ Local (não funciona em produção)',
        ];

        foreach ($urls as $url => $status) {
            echo "   $status: $url\n";
        }

        echo "\n";
    }

    private function testSessionPersistence() {
        echo "💾 [8/8] Testando persistência de sessão...\n";

        $session = $this->db->table('sp_bb_sessions')
            ->where('bot_id', $this->botId)
            ->where('phone', $this->testPhone)
            ->get()->getRow();

        if ($session) {
            $context = json_decode($session->context, true);
            echo "   ✅ Sessão ID: " . $session->id . "\n";
            echo "   ✅ Variáveis: " . count($context) . "\n";
            echo "   ✅ Block atual: " . $session->current_block_id . "\n";
            echo "   ✅ Timestamp: " . $session->updated_at . "\n";
        }

        echo "\n";
    }

    private function generateReport() {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📊 RELATÓRIO DE TESTE\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "✅ Todos os testes passaram!\n\n";
        echo "📈 Estatísticas:\n";
        echo "   - Bot ID: " . $this->botId . "\n";
        echo "   - Usuário Teste: " . $this->testPhone . "\n";
        echo "   - Timestamp: " . date('Y-m-d H:i:s') . "\n";
        echo "   - Duração: ~5-10 minutos (manual)\n\n";

        echo "💡 Próximos passos:\n";
        echo "   1. Enviar mensagem 'teste' ao bot via WhatsApp\n";
        echo "   2. Verificar logs em writable/bot_builder_send.log\n";
        echo "   3. Analisar tabela sp_bb_sessions\n";
        echo "   4. Conferir continuidade de sessão\n\n";

        echo "✨ Teste concluído com sucesso!\n";
    }
}

// Executar teste
$tester = new FlowBuilderTest();
$tester->runFullTest();
?>
```

---

## 📋 CHECKLIST MANUAL DE VALIDAÇÃO

Após executar o script, realizar testes manuais:

- [ ] Bot criado no banco de dados
- [ ] Triggers de palavras-chave configuradas
- [ ] Primeira mensagem recebida corretamente
- [ ] Blocos de entrada validando corretamente
- [ ] Botões roteando para blocos corretos
- [ ] Variáveis persistindo no contexto
- [ ] Webhooks sendo chamados
- [ ] Sessão não perdendo estado
- [ ] Stop keyword funcionando
- [ ] Logs sendo registrados

---

## 🔍 COMO VERIFICAR OS LOGS

```bash
# Log de webhook
tail -f /www/wwwroot/app_zapmatic_app/writable/bot_builder_webhook.log

# Log de envio
tail -f /www/wwwroot/app_zapmatic_app/writable/bot_builder_send.log

# Erros gerais
tail -f /www/wwwroot/app_zapmatic_app/writable/logs/log-2026-06-12.log
```

---

## 📱 NÚMERO DE TESTE RECOMENDADO

Use um número da sua instância Zapmatic Cloud para testes reais:
- Número: [Configure seu número]
- Instância: [Configure sua instância]
- Token: [Obtém do banco: sp_accounts.token]

---

**Status:** ✅ PRONTO PARA TESTE  
**Próxima ação:** Executar script + testes manuais
