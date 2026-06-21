# Avanço — Builder nativo Fase 1 e Fase 2

## Status

Implementação inicial concluída e validada por sintaxe.

Esta etapa iniciou a integração do Bot Builder com templates nativos globais do sistema, sem alterar ainda o runtime de envio do Builder.

## Objetivo entregue nesta etapa

Foram implementadas as bases para o Builder carregar templates nativos globais:

- Botões nativos `type = 2`;
- Lista/Menu nativo `type = 1`;
- Carrossel nativo `type = 5`.

Também foi mantido o modo antigo de botões rápidos no Builder, conforme decisão final.

---

## Arquivos alterados

### Rotas do Bot Builder

```text
/inc/core/Bot_builder/Config/Routes.php
```

Novas rotas:

```text
GET bot-builder/native-templates
GET bot-builder/native-template/{ids}
```

### Controller do Bot Builder

```text
/inc/core/Bot_builder/Controllers/Bot_builder.php
```

Novos métodos adicionados:

```text
native_templates()
native_template($ids)
format_native_template_for_builder($item)
native_template_create_url($type)
native_template_edit_url($type, $ids)
```

Função dos endpoints:

- listar templates nativos por tipo;
- carregar template específico por `ids`;
- retornar JSON com `ids`, `name`, `type`, `data`, `edit_url` e `create_url`;
- respeitar `team_id` atual;
- aceitar apenas tipos `1`, `2` e `5`.

### Editor do Builder

```text
/inc/core/Bot_builder/Views/editor.php
```

Foram adicionadas URLs ao `window.bsConfig`:

```text
native_templates_url
native_template_url
native_return_url
```

Essas URLs são usadas pelo JavaScript do Builder para buscar templates e montar retorno após criar/editar template nativo.

### JavaScript do Builder

```text
/inc/core/Bot_builder/Assets/js/bot_builder.js
```

Alterações principais:

- defaults dos nodes atualizados;
- botões agora têm `button_mode` com `quick` ou `native`;
- lista usa `template_mode = native`;
- cards/carrossel usa `template_mode = native`;
- adicionados helpers para templates nativos;
- adicionada UI de seleção de template nativo;
- adicionados botões de criar, editar e atualizar templates;
- adicionado preview simples do template selecionado;
- handles/conexões passam a usar opções extraídas do template nativo quando carregado.

---

## Comportamento atual implementado

### Node Botões

Agora mostra seletor de modo:

```text
Botões rápidos do Builder
Template nativo global
```

Se escolher `Botões rápidos do Builder`, mantém a experiência antiga:

```text
Mensagem
Itens manuais
Variável
Obrigatório
```

Se escolher `Template nativo global`, mostra:

```text
Selecionar template type 2
Criar novo
Editar
Atualizar lista
Preview
Variável
Obrigatório
```

### Node Lista/Menu

Agora mostra seleção nativa:

```text
Selecionar template type 1
Criar novo
Editar
Atualizar lista
Preview
Variável
```

### Node Cards/Carrossel

Agora mostra seleção nativa:

```text
Selecionar template type 5
Criar novo
Editar
Atualizar lista
Preview
Variável
Obrigatório
```

---

## Preview implementado

O preview inicial exibe resumo do JSON do template:

### Botões

Mostra:

```text
nome do template
texto/caption/título
labels dos botões
```

### Lista

Mostra:

```text
nome do template
texto/título
linhas/opções detectadas
```

### Carrossel

Mostra:

```text
nome do template
texto/título
quantidade de cards
títulos dos cards
```

---

## Handles/conexões implementados nesta etapa

Quando um template está carregado no node, os handles são extraídos de:

### Botões type 2

```text
templateButtons[].quickReplyButton.id
templateButtons[].quickReplyButton.displayText
```

### Lista type 1

```text
sections[].rows[].rowId
sections[].rows[].title
```

### Carrossel type 5

```text
cards[].buttons[].buttonParamsJson.id
cards[].buttons[].buttonParamsJson.display_text
```

Observação: nesta etapa a UI já prepara os handles, mas o runtime de envio nativo no `Bot_builder.php` ainda será feito na próxima fase.

---

## Validações executadas

Comandos executados:

```bash
php -l '/www/wwwroot/app_zapmatic_app/inc/core/Bot_builder/Controllers/Bot_builder.php'
php -l '/www/wwwroot/app_zapmatic_app/inc/core/Bot_builder/Config/Routes.php'
php -l '/www/wwwroot/app_zapmatic_app/inc/core/Bot_builder/Views/editor.php'
node --check '/www/wwwroot/app_zapmatic_app/inc/core/Bot_builder/Assets/js/bot_builder.js'
```

Resultado:

```text
Sem erros de sintaxe.
```

---

## O que ainda falta

### Próxima fase obrigatória

Implementar o runtime nativo no Builder:

```text
/inc/core/Bot_builder/Controllers/Bot_builder.php
```

Regras:

```text
Botões:
    se button_mode = native → carregar template type 2 e enviar nativo
    se quick → manter envio antigo

Lista:
    se template_ids → carregar template type 1 e enviar nativo
    se não tiver → fallback legado invisível

Carrossel:
    se template_ids → carregar template type 5 e enviar nativo
    se não tiver → fallback legado invisível
```

### Depois

Implementar contexto interativo para:

```text
Disparo em massa
Autoresponder
```

---

## Pontos de atenção para teste manual

1. Abrir editor do Builder.
2. Adicionar node de Botões.
3. Alternar entre modo rápido e nativo.
4. Selecionar template de botão nativo.
5. Confirmar preview.
6. Confirmar handles do node.
7. Adicionar node de Lista e selecionar template type 1.
8. Adicionar node de Cards e selecionar carrossel type 5.
9. Testar botões Criar/Editar e retorno ao Builder.

---

## Observação importante

Esta etapa ainda não altera o envio real do Builder.

Ou seja:

- UI e endpoints nativos foram preparados;
- envio runtime nativo ainda será implementado na próxima fase;
- fallback antigo ainda permanece preservado.
