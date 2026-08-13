# Melhoria: `[wa_name]` no Disparo em Massa

## Objetivo

Garantir que o placeholder `[wa_name]` funcione no disparo em massa sem quebrar:

- placeholders de planilha no formato `%campo%`
- valores locais/fixos em templates
- envio comum de bulk
- envio de template oficial pela Meta Cloud

## Problema anterior

No bulk, o motor chamava `auto_send(..., false, false, ...)`, então não existia `message.pushName`.

Como o placeholder `[wa_name]` dependia de `message?.pushName`, o nome do perfil vinha vazio durante o disparo em massa, inclusive no fluxo de template da Meta Cloud.

## Solução aplicada

1. O motor agora cria um contexto sintético de mensagem para o bulk.
2. Esse contexto busca o nome mais recente do contato em `sp_whatsapp_subscriber`.
3. O nome é lido prioritariamente de `contact_data.name`.
4. Se não houver nome utilizável, o sistema mantém `[wa_name]` vazio sem afetar `%campo%`.
5. O contexto sintético também é repassado ao caminho de template oficial da Meta Cloud, para que `body_example_values` resolva `[wa_name]` e `%campo%` no mesmo envio.

## Arquivos alterados

- `app_zapmatic_api/waziper/extend.js`
- `app_zapmatic_api/waziper/waziper.js`

## Escopo da mudança

- sem migration
- sem alteração de banco
- sem alteração em PHP
- sem mudança na lógica de `%planilha%`

## Testes internos esperados

1. Bulk com `%nome%` deve continuar funcionando.
2. Bulk com valor local fixo deve continuar funcionando.
3. Bulk com `[wa_name]` deve usar o último nome conhecido do subscriber.
4. Meta Cloud com `body_example = [wa_name]|%nome%|ABC123` deve resolver:
   - `[wa_name]` pelo subscriber
   - `%nome%` pela planilha
   - `ABC123` como valor fixo

## Observação

Se o contato nunca interagiu antes e não existir registro correspondente em `sp_whatsapp_subscriber`, o placeholder `[wa_name]` continuará vazio. Isso é intencional para não inventar nome nem misturar o nome da planilha com o nome real do perfil do WhatsApp.
