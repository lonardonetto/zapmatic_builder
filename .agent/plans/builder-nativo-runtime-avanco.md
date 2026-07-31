# Avanço — Runtime nativo do Bot Builder

## Status

Implementado e validado por sintaxe.

Esta etapa conclui a ligação inicial entre o Builder e o envio real dos templates nativos globais.

## Objetivo entregue

O `Bot_builder.php` agora consegue enviar templates nativos selecionados no Builder para:

- Botões nativos `TB_WHATSAPP_TEMPLATE.type = 2`;
- Lista/Menu nativo `TB_WHATSAPP_TEMPLATE.type = 1`;
- Carrossel nativo `TB_WHATSAPP_TEMPLATE.type = 5`.

O fallback antigo foi preservado para não quebrar fluxos existentes.

---

## Arquivos alterados

### Runtime PHP do Builder

```text
/inc/core/Bot_builder/Controllers/Bot_builder.php
```

Foram adicionados helpers:

```text
get_native_template_payload()
replace_vars_recursive()
normalize_native_buttons_payload()
normalize_native_list_payload()
normalize_native_carousel_payload()
```

Também foi ajustado `run_flow()` para obter o `team_id` do bot atual e buscar os templates globais corretamente.

### Node/Baileys

```text
/app_zapmatic_api/waziper/waziper.js
```

Ajustado o endpoint `bot_builder_send` para aceitar botões nativos vindos de `templateButtons` e `interactiveButtons`, além dos botões rápidos antigos.

---

## Comportamento implementado

### Node Botões

Regra:

```text
Se button_mode = native e template_ids existe:
    carrega template type 2
    normaliza payload
    envia message_type = buttons
Senão:
    mantém modo antigo com options manuais
```

### Node Lista

Regra:

```text
Se template_ids existe:
    carrega template type 1
    normaliza payload
    envia message_type = list
Senão:
    fallback antigo invisível com texto/menu manual
```

### Node Carrossel/Cards

Regra:

```text
Se template_ids existe:
    carrega template type 5
    normaliza payload
    envia message_type = carousel
Senão:
    fallback antigo invisível com cards_data manual
```

---

## Compatibilidade preservada

O envio antigo continua disponível para:

- botões rápidos do Builder;
- lista antiga sem `template_ids`;
- carrossel antigo sem `template_ids`.

Isso evita quebrar bots já criados.

---

## Validações executadas

Comandos:

```bash
php -l '/www/wwwroot/app_zapmatic_app/inc/core/Bot_builder/Controllers/Bot_builder.php'
node --check '/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js'
```

Resultado:

```text
Sem erros de sintaxe.
```

---

## Teste funcional recomendado agora

1. Reiniciar o processo Node/PM2 se necessário para carregar `waziper.js`.
2. Abrir o Builder.
3. Criar/editar node de Carrossel.
4. Selecionar template nativo de carrossel `type = 5`.
5. Salvar o fluxo.
6. Ativar o bot.
7. Enviar a palavra-chave de entrada.
8. Confirmar se o carrossel chega no WhatsApp.
9. Clicar em um botão do carrossel.
10. Confirmar se o fluxo segue para o próximo node conectado.

Logs úteis:

```text
/writable/bot_builder_send.log
/writable/bot_builder_webhook.log
PM2 logs do Node com [DBG_CAROUSEL_BAILEYS]
```

---

## Observações importantes

- O runtime nativo depende do `template_ids` estar salvo no node.
- O envio do carrossel usa o JSON do template global, incluindo mídia por card.
- Para carrossel via Baileys, o Node precisa estar executando a versão atualizada do `waziper.js`.
- Se o painel salvar o node sem `template_ids`, cairá no fallback antigo.

---

## Próxima etapa após teste

Se o envio do Builder estiver confirmado, avançar para:

```text
Contexto interativo para Disparo em Massa e Autoresponder
```

Com isso, campanhas e autoresponders poderão atrelar cliques/respostas a fluxos específicos do Builder.
