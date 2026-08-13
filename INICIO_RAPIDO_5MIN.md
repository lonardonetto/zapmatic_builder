# 🚀 INICIO RÁPIDO - COMECE A TESTAR AGORA

**Data:** 12/06/2026 16:01 UTC  
**Tempo para começar:** 5 minutos  
**Pré-requisitos:** Número WhatsApp + Instância Zapmatic configurada

---

## ⚡ 5 PASSOS PARA TESTAR EM 5 MINUTOS

### Passo 1: Obter Dados da Sua Instância (1 min)

Execute no seu banco de dados:

```sql
SELECT 
    a.id as instance_id,
    a.token,
    a.phone as instance_phone,
    a.status,
    t.id as team_id
FROM sp_accounts a
LEFT JOIN sp_teams t ON a.team_id = t.id
WHERE a.status = 1 
AND a.token IS NOT NULL
LIMIT 1;
```

**Anote:**
- Instance ID: `_______________`
- Token: `_______________`
- Phone: `_______________`
- Team ID: `_______________`

---

### Passo 2: Criar Bot de Teste via SQL (1 min)

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
    'Bot Teste Rápido',
    'Teste rápido do Flow Builder',
    'teste,oi,start',
    'sair,parar',
    1, 1, 
    [SEU_TEAM_ID], 
    1, 
    NOW()
);

-- Anote o ID retornado:
-- Bot ID: _______________
```

---

### Passo 3: Conectar Bot à Instância (1 min)

```sql
INSERT INTO sp_bb_integrations (bot_id, instance_id, created_at)
VALUES (
    [BOT_ID], 
    [INSTANCE_ID], 
    NOW()
);
```

---

### Passo 4: Criar Fluxo Mínimo (1 min)

Execute para criar blocos básicos:

```sql
-- Obter ID do bot
SET @bot_id = [BOT_ID];

-- Criar bloco START
INSERT INTO sp_bb_blocks (bot_id, type, label, data, pos_x, pos_y, created_at)
VALUES (@bot_id, 'start', 'Início', '{}', 0, 0, NOW());
SET @start_id = LAST_INSERT_ID();

-- Atualizar bot com start_block_id
UPDATE sp_bot_builders SET start_block_id = @start_id WHERE id = @bot_id;

-- Criar bloco TEXT
INSERT INTO sp_bb_blocks (bot_id, type, label, data, pos_x, pos_y, created_at)
VALUES (@bot_id, 'text', 'Mensagem', '{"text":"Olá! Bem-vindo ao teste 🎉"}', 100, 0, NOW());
SET @text_id = LAST_INSERT_ID();

-- Criar bloco INPUT
INSERT INTO sp_bb_blocks (bot_id, type, label, data, pos_x, pos_y, created_at)
VALUES (@bot_id, 'input_text', 'Nome', '{"question":"Qual seu nome?","variable":"name"}', 200, 0, NOW());
SET @input_id = LAST_INSERT_ID();

-- Criar bloco END
INSERT INTO sp_bb_blocks (bot_id, type, label, data, pos_x, pos_y, created_at)
VALUES (@bot_id, 'end', 'Fim', '{}', 300, 0, NOW());
SET @end_id = LAST_INSERT_ID();

-- Criar arestas
INSERT INTO sp_bb_edges (bot_id, from_block_id, to_block_id, created_at) 
VALUES (@bot_id, @start_id, @text_id, NOW());

INSERT INTO sp_bb_edges (bot_id, from_block_id, to_block_id, created_at) 
VALUES (@bot_id, @text_id, @input_id, NOW());

INSERT INTO sp_bb_edges (bot_id, from_block_id, to_block_id, created_at) 
VALUES (@bot_id, @input_id, @end_id, NOW());
```

---

### Passo 5: Testar via WhatsApp (1 min)

1. Abra WhatsApp
2. Envie mensagem para seu número Zapmatic:
   ```
   teste
   ```
3. Bot deve responder:
   ```
   Olá! Bem-vindo ao teste 🎉
   Qual seu nome?
   ```
4. Responda com seu nome
5. Bot finaliza

---

## ✅ CHECKLIST RÁPIDO

- [ ] Dados da instância obtidos
- [ ] Bot criado no banco
- [ ] Bot conectado à instância
- [ ] Fluxo mínimo criado (4 blocos)
- [ ] Primeira mensagem recebida no WhatsApp
- [ ] Fluxo completou com sucesso

---

## 🔍 VERIFICAR LOGS

Se algo der errado, verifique:

```bash
# Terminal SSH
tail -f /www/wwwroot/app_zapmatic_app/writable/bot_builder_webhook.log

# Procure por:
# ✅ "✅ Keyword matched!" = sucesso
# ❌ "No keyword match" = trigger keyword errado
# ❌ "Not found" = instância não encontrada
```

---

## 📊 ENTENDER O RESULTADO

### Mensagem Recebida
```
2026-06-12 16:01:00 | Phone: 5585987654321 | Text: teste | Type: text
2026-06-12 16:01:00 | ✅ Keyword matched! Bot #123 (Bot Teste Rápido)
2026-06-12 16:01:01 | 📤 Sending text to 5585987654321
2026-06-12 16:01:02 | ✅ Flow completed successfully
```

**O que significa:**
1. Mensagem "teste" recebida
2. Keyword encontrado (✅)
3. Mensagem enviada
4. Fluxo completou

---

## 🎯 PRÓXIMO: TESTE COM 56 NODES

Depois de confirmar que funciona:

1. Leia [GUIA_PRATICO_TESTE_FLOW_BUILDER.md](file:///www/wwwroot/app_zapmatic_app/GUIA_PRATICO_TESTE_FLOW_BUILDER.md)
2. Construa fluxo com 56 nodes no editor visual
3. Execute todos os cenários de teste
4. Gere relatório de desempenho

---

## 🆘 PROBLEMAS FREQUENTES

### Bot não responde
```
1. Verificar: Bot está vinculado?
   SELECT * FROM sp_bb_integrations WHERE bot_id = [ID];
   
2. Verificar: Instância está ativa?
   SELECT status FROM sp_accounts WHERE id = [ID];
   
3. Verificar logs
   tail -f writable/bot_builder_webhook.log
```

### Keyword não bate
```
1. Reenviar com keyword exato: "teste"
2. Verificar maiúsculas/minúsculas
3. Verificar se tem espaços extras
4. Atualizar trigger_keywords no banco
```

### Fluxo não avança
```
1. Verificar se arestas estão conectadas
   SELECT * FROM sp_bb_edges WHERE bot_id = [ID];
   
2. Verificar se start_block_id está correto
   SELECT start_block_id FROM sp_bot_builders WHERE id = [ID];
```

---

## 📞 COMANDOS ÚTEIS

```bash
# Ver bot criado
mysql -e "SELECT id, name FROM sp_bot_builders WHERE name LIKE 'Bot Teste%';"

# Ver blocos do bot
mysql -e "SELECT id, type, label FROM sp_bb_blocks WHERE bot_id = 123;"

# Ver arestas
mysql -e "SELECT * FROM sp_bb_edges WHERE bot_id = 123;"

# Ver última sessão
mysql -e "SELECT * FROM sp_bb_sessions ORDER BY created_at DESC LIMIT 1;"

# Ver logs em tempo real
tail -f /www/wwwroot/app_zapmatic_app/writable/bot_builder_webhook.log
```

---

## 📈 MÉTRICA DE SUCESSO

✅ **Teste passou se:**
- Bot recebe mensagem "teste"
- Responde com "Olá! Bem-vindo ao teste 🎉"
- Pede "Qual seu nome?"
- Recebe resposta do usuário
- Encerra sem erros

**Tempo total:** ~5 minutos  
**Blocos criados:** 4  
**Mensagens trocadas:** 2  
**Status:** 🟢 FUNCIONANDO

---

## 🎓 PRÓXIMAS FASES

### Após 5 minutos (Teste Básico Passou)
→ Construa fluxo com 10-15 blocos no editor visual

### Após 30 minutos (Testes Intermediários)
→ Adicione validações (email, telefone)
→ Adicione botões e ramificações

### Após 2 horas (Testes Avançados)
→ Integre webhook/API externa
→ Adicione IA (OpenAI/Gemini)
→ Teste com 56 nodes

### Após 1 dia (Teste Completo)
→ Gere relatório de desempenho
→ Implemente ajustes necessários
→ Deploy em produção

---

## 📚 REFERÊNCIA

| Arquivo | Propósito |
|---------|-----------|
| SUMARIO_EXECUTIVO_FLOW_BUILDER.md | Visão geral completa |
| RELATORIO_FLOW_BUILDER.md | Análise técnica detalhada |
| GUIA_PRATICO_TESTE_FLOW_BUILDER.md | Instruções práticas completas |
| TESTE_FLOW_BUILDER_56NODES.md | Teste com 56 nodes |
| INICIO_RAPIDO_5MIN.md | Este arquivo - comece aqui! |

---

**Status:** 🟢 PRONTO PARA COMEÇAR  
**Tempo estimado:** 5-10 minutos  
**Dificuldade:** ⭐ Muito Fácil

🚀 **Vá para a Seção "5 PASSOS" acima e comece agora!**

