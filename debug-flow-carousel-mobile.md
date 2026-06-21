# Debug: Flow Carousel Mobile

Status: [RESOLVED]
Session ID: flow-carousel-mobile

## Problema
Carrossel enviado pelo Single Message chega no mobile/iOS, mas o carrossel chamado pelo Flow Builder chega apenas no WhatsApp Web.

## Hipóteses falsificáveis
1. O payload do Flow Builder chega ao Node diferente do payload do Single Message que funciona.
2. O Flow Builder envia para um JID diferente do Single Message no momento do carrossel (`@lid` vs `@s.whatsapp.net`).
3. O handler `bot_builder_send` monta `data.cards` com campos aceitos no Web, mas incompatíveis no mobile/iOS.
4. O Node retorna sucesso antes do WhatsApp confirmar entrega/renderização real do carrossel.
5. O processo original `zapmatic` ainda não carregou a última alteração do `waziper.js`.

## Instrumentação aplicada
Arquivo: `app_zapmatic_api/waziper/waziper.js`

Pontos:
- `before-send`: registra `chat_id`, `type_media`, `post_type`, `item`, chaves do payload final e primeiro card.
- `after-send`: registra `message.key`, `status`, timestamp e resumo da mensagem retornada pelo Baileys.

Log gerado em:
`writable/trae-debug-log-flow-carousel-mobile.ndjson`

Validação:
- `node -c app_zapmatic_api/waziper/waziper.js`: OK
- `php -l inc/core/Bot_builder/Controllers/Bot_builder.php`: OK

## Causa raiz confirmada
O Flow Builder enviava carrossel nativo pelo endpoint `bot_builder_send` com payload manual (`message_type=carousel` + JSON de cards). Esse caminho renderizava no WhatsApp Web, mas falhava no mobile/iOS.

Os módulos que funcionavam corretamente (Single Message, Bulk, Chatbot nativo e Autoresponder) não enviavam o payload manual. Eles usavam o caminho nativo do sistema via endpoint `direct_send_message`, passando apenas o `template` ID e `type=5`. Assim, o próprio `waziper.js` carregava o template original e montava o carrossel no fluxo já compatível com mobile/iOS.

## Correção definitiva aplicada
Arquivo: `inc/core/Bot_builder/Controllers/Bot_builder.php`

No método `send_whatsapp()`, quando o tipo é `carousel` e o payload contém `_template_id`, o Builder agora envia por:

```php
wa_post_curl('direct_send_message', [
    'instance_id' => $instance_id,
    'access_token' => $access_token,
    'type' => 5
], [
    'chat_id' => $phone,
    'template' => (int)$content['_template_id']
]);
```

Ou seja, o Flow Builder agora usa o mesmo caminho funcional do Single/Bulk/Chatbot/Autoresponder.

## Importante
Não usar novamente o bloco manual em `waziper.js` com `relayMessage`, `generateWAMessageFromContent` ou `viewOnceMessage` para carrossel do Flow Builder. Esse caminho foi a origem da instabilidade.

## Resultado
Usuário confirmou que o carrossel chamado pelo Flow Builder passou a chegar corretamente no mobile/iOS.

## Limpeza
A instrumentação temporária adicionada em `waziper.js` foi removida. A correção final ficou somente no PHP/Builder.
