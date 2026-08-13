# Plano completo de implementação — Builder nativo, disparo/autoresponder e contexto interativo

## Objetivo geral

Integrar o Bot Builder aos templates nativos globais do Zapmatic sem quebrar o funcionamento atual do sistema.

A implementação deve respeitar os pontos decididos:

1. Botões no Builder podem continuar usando o modo manual antigo, pois já funciona bem.
2. Botões também devem poder usar templates nativos globais `type = 2`.
3. Lista/Menu no Builder deve usar template nativo global `type = 1`.
4. Carrossel/Cards no Builder deve usar template nativo global `type = 5`.
5. Carrossel global já foi preparado para upload de mídia por card.
6. Disparo em massa e Autoresponder devem poder atrelar resposta/click de template interativo a um fluxo do Builder.
7. O reconhecimento de fluxo deve respeitar `instance_id`/número conectado.
8. Palavra-chave duplicada só pode ser aceita em números/instâncias diferentes; na mesma instância deve ser bloqueada ou alertada.
9. Campanha e Autoresponder não devem depender apenas de palavra-chave para iniciar fluxo; devem usar contexto interativo direto.

---

## Contexto técnico atual

### Bot Builder

Arquivos principais:

```text
/inc/core/Bot_builder/Assets/js/bot_builder.js
/inc/core/Bot_builder/Views/editor.php
/inc/core/Bot_builder/Controllers/Bot_builder.php
/inc/core/Bot_builder/Models/Bot_builderModel.php
/inc/core/Bot_builder/Config/Routes.php
```

Hoje o Builder processa mensagens recebidas assim:

```text
1. Procura sessão ativa do lead na instância.
2. Se houver sessão, continua o fluxo.
3. Se não houver sessão, procura keyword do bot.
4. Depois procura command trigger.
5. Depois procura reply trigger.
```

Ponto de entrada importante:

```text
Bot_builder.php → webhook/processamento de mensagens
```

O Builder já extrai:

```text
button_reply
list_reply
interactiveResponseMessage
templateButtonReplyMessage
buttonId
rowId
displayText
```

Funções relevantes:

```text
extract_text()
extract_button_id()
get_message_type()
run_flow()
```

### Node/Baileys

Arquivo principal:

```text
/app_zapmatic_api/waziper/waziper.js
```

Ordem atual quando chega mensagem Baileys:

```text
1. Bot Builder tenta processar.
2. Se Bot Builder não processar, chama Chatbot antigo.
3. Depois chama Autoresponder.
```

Essa ordem deve ser preservada.

### Templates globais nativos

Tabela:

```text
TB_WHATSAPP_TEMPLATE
```

Tipos usados:

```text
1 = Lista/Menu
2 = Botões
5 = Carrossel
```

### Disparo em massa

Arquivo principal:

```text
/inc/core/Whatsapp_bulk/Controllers/Whatsapp_bulk.php
```

Hoje a campanha salva:

```text
type
template
accounts
contact_id
caption
media
schedule_time
intervalos
```

Para templates interativos:

```text
type = 2 → botão
type = 3 → lista
type = 5 → carrossel
```

### Autoresponder

Arquivo principal:

```text
/inc/core/Whatsapp_autoresponder/Controllers/Whatsapp_autoresponder.php
```

Hoje aceita:

```text
type = 2 → botão
type = 3 → lista
type = 5 → carrossel
```

---

# Parte 1 — Builder com templates nativos

## Regra de UX definida

### Node Botões

O node de botões deve ter dois modos:

```text
1. Botões rápidos do Builder
2. Template nativo global
```

Motivo:

- o modo antigo funciona bem;
- muitos bots podem depender dele;
- botões rápidos são úteis para fluxos simples;
- o modo nativo fica disponível para casos mais robustos.

### Node Lista/Menu

Deve usar template nativo global.

```text
TB_WHATSAPP_TEMPLATE.type = 1
```

O modo antigo de lista não deve ser a experiência principal, porque atualmente a lista antiga não envia lista real; ela envia texto simulando menu.

Fallback antigo pode ficar no runtime apenas para fluxos legados.

### Node Carrossel/Cards

Deve usar template nativo global.

```text
TB_WHATSAPP_TEMPLATE.type = 5
```

O modo antigo de carrossel/cards não deve ser a experiência principal, porque carrossel é sensível, especialmente com iOS, Cloud API e mídia por card.

Fallback antigo pode ficar no runtime apenas para fluxos legados.

---

## Campos novos no node

### Botões modo nativo

```json
{
  "type": "buttons",
  "button_mode": "native",
  "template_ids": "abc123",
  "template_type": 2,
  "variable": "last_selection"
}
```

### Botões modo rápido/manual

```json
{
  "type": "buttons",
  "button_mode": "quick",
  "text": "Escolha uma opção",
  "options": "Sim,Não",
  "variable": "last_selection"
}
```

### Lista nativa

```json
{
  "type": "list",
  "template_mode": "native",
  "template_ids": "abc123",
  "template_type": 1,
  "variable": "last_selection"
}
```

### Carrossel nativo

```json
{
  "type": "cards",
  "template_mode": "native",
  "template_ids": "abc123",
  "template_type": 5,
  "variable": "last_selection"
}
```

---

## Inspector no Builder

### Botões

Mostrar:

```text
Modo:
    Botões rápidos
    Template nativo

Se modo rápido:
    texto
    opções
    variável

Se modo nativo:
    selecionar template type 2
    criar novo template
    editar template selecionado
    preview
    variável
```

### Lista

Mostrar:

```text
Selecionar lista nativa type 1
Criar nova lista
Editar lista selecionada
Preview
Variável
```

### Carrossel

Mostrar:

```text
Selecionar carrossel nativo type 5
Criar novo carrossel
Editar carrossel selecionado
Preview
Variável
```

---

## Criar/editar template nativo a partir do Builder

O Builder deve abrir a tela original do módulo nativo.

URLs devem incluir retorno para o Builder:

```text
wa_return = URL do editor + node de origem
```

Fluxo:

```text
Builder → Criar template nativo → Salvar template → Voltar ao Builder → Recarregar node/preview
```

Para editar:

```text
Builder → Editar template selecionado → Salvar → Voltar ao Builder → Atualizar preview
```

---

## Preview nativo

O preview no Builder deve ser montado com base no JSON real do template global.

### Preview Botões

Exibir:

```text
título/texto/caption
imagem, se houver
footer
botões
```

### Preview Lista

Exibir:

```text
título
texto
footer
botão da lista
seções
linhas
```

### Preview Carrossel

Exibir:

```text
mensagem principal
cards
imagem/mídia por card
título/body/footer do card
botões do card
```

---

## Handles/conexões no Builder

O Builder deve gerar saídas a partir dos IDs reais do template.

### Botões nativos type 2

Usar:

```text
quickReplyButton.id
quickReplyButton.displayText
```

Fallback:

```text
displayText
label
índice
```

### Lista type 1

Usar:

```text
sections[].rows[].rowId
sections[].rows[].title
```

### Carrossel type 5

Usar:

```text
cards[].buttons[].buttonParamsJson.id
cards[].buttons[].buttonParamsJson.display_text
```

Sem isso, a mensagem pode ser enviada, mas o fluxo não saberá qual próximo bloco seguir.

---

## Runtime do Builder

Arquivo:

```text
/inc/core/Bot_builder/Controllers/Bot_builder.php
```

Regra:

```text
Se node tem template_ids:
    buscar template global
    enviar usando tipo nativo
Senão:
    usar fallback antigo quando necessário
```

### Botões

```text
Se button_mode = native:
    carregar template type 2
    enviar botões nativos
Se button_mode = quick ou sem modo:
    manter envio antigo
```

### Lista

```text
Se template_ids existe:
    carregar template type 1
    enviar lista nativa
Senão:
    fallback legado invisível
```

### Carrossel

```text
Se template_ids existe:
    carregar template type 5
    enviar carrossel nativo
Senão:
    fallback legado invisível
```

---

# Parte 2 — Contexto interativo para Disparo em Massa e Autoresponder

## Problema que será resolvido

Hoje, se uma campanha dispara um carrossel e o lead clica, o sistema sabe:

```text
lead clicou em botão X
```

Mas não sabe automaticamente:

```text
esse botão veio da campanha Y
deve iniciar o fluxo Z
deve entrar no bloco W
```

A solução é criar um contexto interativo persistido.

---

## Nova tabela proposta

Tabela:

```text
sp_wa_interactive_contexts
```

Campos sugeridos:

```sql
id BIGINT AUTO_INCREMENT PRIMARY KEY,
ids VARCHAR(32),
team_id BIGINT NOT NULL,
instance_id VARCHAR(191) NOT NULL,
account_id BIGINT NULL,
phone VARCHAR(191) NOT NULL,
source_type VARCHAR(50) NOT NULL,
source_id BIGINT NULL,
source_ids VARCHAR(64) NULL,
template_id BIGINT NULL,
template_ids VARCHAR(64) NULL,
template_type TINYINT NULL,
bot_id BIGINT NOT NULL,
default_block_id VARCHAR(191) NULL,
option_map LONGTEXT NULL,
status TINYINT DEFAULT 1,
used_at INT NULL,
expires_at INT NULL,
created INT NOT NULL,
updated INT NOT NULL,
INDEX idx_lookup (team_id, instance_id, phone, status),
INDEX idx_source (source_type, source_id),
INDEX idx_template (template_ids, template_type),
INDEX idx_expires (expires_at)
);
```

### `source_type`

Valores:

```text
bulk
autoresponder
api
builder
```

### `option_map`

JSON com mapeamento de opções para blocos:

```json
{
  "produto_a": "block_produto_a",
  "produto_b": "block_produto_b",
  "falar_vendedor": "block_humano"
}
```

Também pode guardar aliases:

```json
{
  "produto_a": "block_produto_a",
  "Produto A": "block_produto_a",
  "Quero Produto A": "block_produto_a"
}
```

---

## Ordem nova do webhook do Builder

Hoje:

```text
sessão ativa
keyword
command
reply
```

Novo fluxo:

```text
1. Sessão ativa do lead naquela instância.
2. Contexto interativo pendente naquela instância/lead.
3. Keyword do bot filtrada por instância.
4. Command trigger filtrado por instância.
5. Reply trigger filtrado por instância.
6. Se nada resolver, libera para Chatbot/Autoresponder antigo.
```

Importante:

```text
contexto interativo vem antes de keyword
```

Motivo:

- campanha/autoresponder precisa iniciar o fluxo correto sem depender de palavra-chave;
- evita conflito com múltiplos bots usando mesma palavra.

---

## Como resolver o contexto no clique

Quando mensagem chega, extrair:

```text
phone canônico
reply_phone
instance_id
text/displayText
button_id/rowId
message_type
```

Buscar contexto:

```text
team_id + instance_id + phone + status ativo + não expirado
```

Depois resolver bloco:

```text
1. tenta button_id no option_map
2. tenta text/displayText no option_map
3. tenta normalização lowercase/trim
4. se não encontrar, usa default_block_id
5. se não tiver default, usa start_block_id do bot
```

Criar sessão:

```text
bot_id
phone
instance_id
current_block_id = bloco resolvido
context = informações da origem/campanha/template/opção
```

Contexto inicial recomendado:

```json
{
  "source_type": "bulk",
  "source_id": 123,
  "template_ids": "abc123",
  "template_type": 5,
  "button_id": "produto_a",
  "last_selection": "Produto A"
}
```

---

# Parte 3 — Disparo em Massa integrado ao Builder

## Campos novos na campanha

Adicionar opções na tela de disparo em massa:

```text
Ação quando lead clicar/responder:
    Não fazer nada
    Iniciar fluxo do Builder
```

Se escolher Builder:

```text
Bot Builder
Bloco padrão
Mapeamento por botão/lista/card
Expiração do contexto
```

Campos a salvar em `sp_whatsapp_schedules` ou tabela auxiliar:

```text
reply_action = none|bot_builder
reply_bot_id
reply_default_block_id
reply_option_map
reply_context_ttl
```

Se preferir evitar alterar muito `sp_whatsapp_schedules`, criar tabela auxiliar:

```text
sp_whatsapp_schedule_reply_actions
```

Mas a solução mais simples é adicionar colunas no schedule.

---

## Momento de criação do contexto

Ao enviar campanha para cada lead, depois de sucesso ou antes do envio, registrar contexto:

```text
source_type = bulk
source_id = schedule_id
team_id
instance_id
phone
template_ids
template_type
bot_id
default_block_id
option_map
expires_at
```

Recomendação:

```text
Registrar somente após envio com sucesso.
```

Motivo:

- evita contexto pendente para lead que não recebeu a mensagem.

---

## Campanhas com múltiplas contas

Regra:

```text
O contexto deve ser salvo com a instância que realmente enviou a mensagem.
```

Se a campanha usa várias contas:

```text
lead A recebeu pelo número X → contexto instance_id X
lead B recebeu pelo número Y → contexto instance_id Y
```

Assim, se a mesma palavra/botão existir em vários fluxos, o sistema resolve pelo número correto.

---

# Parte 4 — Autoresponder integrado ao Builder

## Campos novos no Autoresponder

Adicionar opções:

```text
Quando lead clicar/responder no template enviado:
    Não fazer nada
    Iniciar fluxo do Builder
```

Se escolher Builder:

```text
Bot Builder
Bloco padrão
Mapeamento por opção
Expiração
```

Campos sugeridos em `TB_WHATSAPP_AUTORESPONDER`:

```text
reply_action
reply_bot_id
reply_default_block_id
reply_option_map
reply_context_ttl
```

---

## Momento de criação do contexto

Quando o Autoresponder enviar uma resposta interativa com sucesso, gravar:

```text
source_type = autoresponder
source_id = autoresponder.id
instance_id = conta que enviou
phone = lead
bot_id = fluxo atrelado
option_map
```

---

# Parte 5 — Regra de instância e palavra-chave

## Regra principal

Palavra-chave deve ser filtrada por instância/número conectado.

```text
keyword + instance_id
```

Nunca apenas:

```text
keyword
```

## Mesma keyword em números diferentes

Permitido.

Exemplo:

```text
Bot A → número 5511 → keyword start
Bot B → número 5521 → keyword start
```

Sem conflito.

## Mesma keyword no mesmo número

Não recomendado.

Regra ideal:

```text
bloquear ao salvar ou mostrar alerta forte
```

Mensagem sugerida:

```text
A palavra-chave "start" já está ativa no bot "X" para esta mesma instância. Escolha outra palavra-chave ou desative o outro fluxo.
```

## Campanha/autoresponder não dependem de keyword

Quando for clique/resposta de campanha ou autoresponder:

```text
usar contexto interativo
```

Não usar keyword como decisão principal.

---

# Parte 6 — Ordem segura de implementação

## Fase 1 — Backend base de templates para Builder

Criar endpoints no `Bot_builder.php`:

```text
GET/POST bot-builder/native-templates?type=1|2|5
GET/POST bot-builder/native-template/{ids}
```

Retorno deve conter:

```text
ids
name
type
data
edit_url
create_url
```

## Fase 2 — UI do Builder

Alterar:

```text
bot_builder.js
editor.php
```

Implementar:

- modo híbrido nos botões;
- lista nativa;
- carrossel nativo;
- preview;
- criar/editar com retorno;
- salvar `template_ids` no node.

## Fase 3 — Handles nativos

Gerar saídas com base no template real.

Validar:

```text
buttonId
rowId
card button id
```

## Fase 4 — Runtime Builder nativo

Alterar `Bot_builder.php`:

- carregar template global;
- enviar botão/lista/carrossel nativo;
- manter fallback antigo invisível.

## Fase 5 — Tabela de contexto interativo

Criar migration/SQL para:

```text
sp_wa_interactive_contexts
```

Criar métodos no model:

```text
create_interactive_context()
find_interactive_context()
mark_interactive_context_used()
cleanup_expired_contexts()
```

## Fase 6 — Resolver contexto no webhook

Inserir busca do contexto entre:

```text
sessão ativa
```

e

```text
keyword
```

## Fase 7 — Disparo em massa

Alterar:

```text
Whatsapp_bulk.php
views do bulk
waziper.js auto_send/callback
```

Adicionar:

- campos de ação pós-resposta;
- seleção de bot;
- bloco padrão;
- mapeamento por opção;
- gravação de contexto após envio bem-sucedido.

## Fase 8 — Autoresponder

Alterar:

```text
Whatsapp_autoresponder.php
views do autoresponder
waziper.js autoresponder send/callback
```

Adicionar a mesma lógica de ação pós-resposta.

## Fase 9 — Validação de duplicidade de keyword por instância

Ao salvar bot/conexões:

```text
verificar bots ativos conectados à mesma instância
comparar keywords normalizadas
bloquear ou avisar conflito
```

---

# Parte 7 — Validações obrigatórias

## Testes Builder

1. Botão rápido antigo continua enviando e roteando.
2. Botão nativo envia e roteia por ID.
3. Lista nativa envia e roteia por rowId.
4. Carrossel nativo envia com upload e roteia por botão do card.
5. Preview respeita template real.
6. Criar novo template volta ao node correto.
7. Editar template volta ao node correto.

## Testes Disparo em Massa

1. Campanha com botão nativo e fluxo atrelado.
2. Campanha com lista nativa e fluxo atrelado.
3. Campanha com carrossel nativo e fluxo atrelado.
4. Clique cria sessão no bot correto.
5. Clique entra no bloco correto.
6. Campanha com várias contas salva contexto na instância correta.

## Testes Autoresponder

1. Autoresponder envia botão/lista/carrossel.
2. Clique inicia fluxo correto.
3. Autoresponder normal continua funcionando quando não há contexto.

## Testes de conflito

1. Mesma keyword em instâncias diferentes deve funcionar.
2. Mesma keyword na mesma instância deve alertar/bloquear.
3. Contexto interativo deve ter prioridade sobre keyword.

## Testes Cloud API e Baileys

1. Cloud API botão/lista responde com ID reconhecido.
2. Baileys carrossel responde com `interactiveResponseMessage` reconhecido.
3. iOS renderiza carrossel corretamente.
4. URL de mídia `/writable/uploads` abre publicamente.

---

# Parte 8 — Logs recomendados

## Bot Builder webhook

Arquivo:

```text
/writable/bot_builder_webhook.log
```

Adicionar logs:

```text
interactive_context_lookup
context_found
context_not_found
context_button_id
context_text
context_bot_id
context_block_id
context_source_type
context_source_id
```

## Envio Builder

Arquivo:

```text
/writable/bot_builder_send.log
```

Adicionar:

```text
template_ids
template_type
node_id
message_type
send_result
```

## Disparo em massa

Logs úteis:

```text
schedule_id
phone
instance_id
template_ids
reply_action
context_created
```

## Autoresponder

Logs úteis:

```text
autoresponder_id
phone
instance_id
template_ids
context_created
```

---

# Decisão final consolidada

## Builder

```text
Botões:
    modo rápido antigo + modo nativo global

Lista:
    nativo global na interface
    fallback antigo invisível no runtime

Carrossel:
    nativo global na interface
    fallback antigo invisível no runtime
```

## Disparo em massa

```text
Pode atrelar template interativo a fluxo do Builder.
Clique/resposta cria/continua sessão pelo contexto interativo.
Não depende de keyword.
```

## Autoresponder

```text
Pode atrelar template interativo a fluxo do Builder.
Clique/resposta cria/continua sessão pelo contexto interativo.
Não depende de keyword.
```

## Keywords

```text
Filtradas por instância.
Duplicidade permitida em números diferentes.
Duplicidade na mesma instância deve ser bloqueada ou alertada.
```

## Proteção contra quebra

```text
Não remover runtime legado imediatamente.
Não alterar ordem Bot Builder → Chatbot → Autoresponder.
Adicionar contexto interativo antes da keyword.
Usar instance_id como filtro obrigatório.
Validar por Cloud API e Baileys.
```

---

# Próximo passo recomendado

Iniciar pela Fase 1 e Fase 2:

```text
1. Endpoints de templates nativos para o Builder.
2. UI do Builder com modo híbrido para botões e nativo para lista/carrossel.
```

Só depois avançar para:

```text
3. Runtime nativo do Builder.
4. Contexto interativo para campanha/autoresponder.
```

Essa ordem reduz risco porque primeiro melhora o Builder sem interferir diretamente no disparo em massa/autoresponder em produção.
