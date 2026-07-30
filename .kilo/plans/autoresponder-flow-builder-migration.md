# Plano: Autoresponder via Flow Builder (v2 - Simplificado)

## Conceito

Adicionar um **toggle "Responder qualquer palavra"** no settings do Flow Builder. Quando ativado, o bot responde a **qualquer mensagem recebida** (não apenas keywords específicas). Isso transforma qualquer fluxo do Builder num Autoresponder.

Único a mais: um campo de **delay** para evitar responder a mesma pessoa repetidamente em pouco tempo.

---

## O que o Autoresponder legado faz hoje

- Responde a qualquer mensagem com uma resposta fixa
- Tem delay entre respostas ao mesmo contato
- Tem lista de números excluídos
- Funciona por conta WhatsApp (1:1)

## O que o toggle "Responder qualquer palavra" faz

- Responde a qualquer mensagem com o **fluxo inteiro do Builder**
- Pode ter delay configurável
- Funciona por bot (vinculado a contas via integração)
- Pode ter delay, botões, AI, listas, carrossel — tudo nativo

---

## Alterações Necessárias

### 1. Banco de Dados - 2 colunas novas em `sp_bot_builders`

```sql
ALTER TABLE sp_bot_builders 
ADD COLUMN autorespond TINYINT(1) DEFAULT 0 AFTER chat_type,
ADD COLUMN autorespond_delay INT(11) DEFAULT 60 AFTER autorespond;
```

| Campo | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| `autorespond` | TINYINT(1) | 0 | 1=responde qualquer palavra |
| `autorespond_delay` | INT(11) | 60 | Segundos entre respostas ao mesmo contato |

### 2. PHP - Hook no `Bot_builder.php` (~20 linhas)

No método `process_webhook`, após falhar keyword, command e reply, adicionar:

```php
// 5. Try autorespond bots
$auto_bot = $this->find_autorespond_bot($instance_id_for_lookup, $phone);
if ($auto_bot) {
    // Verificar delay
    // Criar sessão e executar fluxo
    $handled_count++;
    continue;
}
```

### 3. PHP - Método `find_autorespond_bot()` (~30 linhas)

Busca no `sp_bot_builders` um bot com `autorespond = 1` vinculado à instância.
Verifica delay (última resposta ao mesmo phone foi há X segundos).

### 4. PHP - Helper de delay (~40 linhas)

Usa a tabela `sp_bb_sessions` para rastrear última resposta.
```php
function check_autorespond_delay($bot_id, $phone, $delay_seconds) {
    // SELECT last_response FROM sp_bb_sessions WHERE bot_id = ? AND phone = ?
    // Se now - last_response < delay_seconds → return false
}
```

### 5. Frontend - Toggle na UI do Flow Builder (~30 linhas)

No painel de configurações do bot (onde já tem name, trigger_keywords, etc):
- Checkbox: "⚡ Responder qualquer palavra"
- Campo number: "Delay entre respostas (segundos)" (aparece só quando checkbox marcado)

### 6. JS - Salvar toggle no `bot_builder.js` (~10 linhas)

Adicionar `autorespond` e `autorespond_delay` ao payload de save.

---

## Fluxo de Execução

```
Mensagem recebida
    │
    ├── 1. Sessão ativa? → Continua fluxo
    │
    ├── 2. Keyword match? → Inicia flow
    │
    ├── 3. Command match? → Inicia flow
    │
    ├── 4. Reply match? → Inicia flow
    │
    ├── 5. Bot com "Responder qualquer palavra"? → Verifica delay → Inicia flow ← NOVO
    │
    └── 6. Nada? → Sem resposta (ou legado)
```

---

## Arquivos a Criar/Modificar

| Arquivo | Alteração | Linhas |
|---------|-----------|--------|
| SQL migration | 2 colunas novas em `sp_bot_builders` | 3 |
| `Bot_builder.php` | +`find_autorespond_bot()` + hook no `process_webhook` | ~50 |
| `Bot_builder.php` | +`check_autorespond_delay()` | ~30 |
| `Bot_builder.php` | +`touch_autorespond_delay()` | ~15 |
| `bot_builder.js` (frontend) | Toggle + campo delay no settings | ~30 |
| `bot_builder_ui.js` (frontend) | Renderizar toggle | ~20 |

**Total: ~148 linhas novas. NENHUM arquivo novo. NENHUM módulo novo.**

---

## Ordem de Implementação

1. SQL migration (2 colunas)
2. `find_autorespond_bot()` no Bot_builder.php
3. `check_autorespond_delay()` no Bot_builder.php  
4. Hook no `process_webhook` (step 5)
5. Frontend: toggle no settings do bot
6. Teste end-to-end
