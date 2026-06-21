# Avanço completo — Upload de mídia no Carrossel e preparação para Bot Builder

## Status geral

Implementação concluída e validada por sintaxe.

Este documento consolida o estado atual para permitir backup do projeto antes de avançar para a integração do Bot Builder com os templates globais nativos.

## Objetivo da etapa concluída

Corrigir a limitação do template global de carrossel, que antes dependia apenas de URL manual para mídia dos cards.

Agora cada card do carrossel permite:

- upload direto de mídia;
- preenchimento automático da URL pública gerada;
- uso manual de URL externa, preservando compatibilidade;
- preview visual da mídia selecionada;
- envio compatível por Cloud API e Baileys/Node.

Essa etapa era necessária antes de avançar para o Builder, porque o Bot Builder deverá consumir o carrossel nativo/global já funcionando corretamente.

---

## Arquivos modificados nesta etapa

### 1. View do template de carrossel

Arquivo:

```text
/inc/core/Whatsapp_carousel_template/Views/update.php
```

Alterações realizadas:

- Substituído o campo simples `Media URL` por um bloco de mídia por card.
- Adicionado botão `Upload` por card.
- Adicionado input file oculto por card.
- Adicionado preview visual da mídia.
- Mantido o campo URL manual `card_media[index]`.
- Adicionado upload AJAX com `FormData` para o endpoint nativo do File Manager.
- Ajustada função `renumberCards()` para atualizar corretamente:
  - `data-card` do bloco de mídia;
  - botão de upload;
  - input file;
  - campos `card_media[index]` após adicionar/remover cards.

Endpoint usado:

```text
file_manager/upload_files
```

Formato da URL gerada após upload:

```text
{PATH}/writable/{result.file}
```

Exemplo:

```text
https://dominio.com/writable/uploads/arquivo.jpg
```

---

### 2. Envio Cloud API via PHP

Arquivo:

```text
/inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php
```

Alterações realizadas:

- Normalizada a leitura da mídia do card antes de montar o header da Cloud API.
- Agora o envio aceita:

```text
card.media
card.media.url
card.image
card.image.url
```

Formato final enviado para Cloud API:

```json
{
  "header": {
    "type": "image",
    "image": {
      "link": "https://dominio.com/writable/uploads/arquivo.jpg"
    }
  }
}
```

Essa alteração evita que templates antigos ou novos fiquem sem imagem por diferença de estrutura no JSON.

---

### 3. Envio Node/Baileys

Arquivo:

```text
/app_zapmatic_api/waziper/waziper.js
```

Alterações realizadas:

- O carrossel no Node agora também reconhece `card.media` como string.
- O envio Baileys agora prepara mídia usando:

```text
card.media
card.media.url
card.image.url
card.image
```

- Os logs de debug do carrossel Baileys também passam a exibir corretamente mídia salva como string.

Pontos ajustados:

- montagem de card para Cloud/Node;
- debug `[DBG_CAROUSEL_BAILEYS] card_input`;
- preparo de mídia com `prepareWAMessageMedia`.

---

## Compatibilidade preservada

A alteração foi feita sem remover comportamento antigo.

Continua funcionando:

```json
"media": "https://site.com/imagem.jpg"
```

Também passa a funcionar:

```json
"media": {
  "url": "https://site.com/imagem.jpg"
}
```

E também:

```json
"image": {
  "url": "https://site.com/imagem.jpg"
}
```

A URL manual continua disponível no formulário, então templates existentes não foram invalidados.

---

## Validações executadas

Foram executadas validações de sintaxe nos arquivos afetados.

Comandos:

```bash
php -l '/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_carousel_template/Views/update.php'
php -l '/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php'
php -l '/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php'
node --check '/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js'
```

Resultado:

```text
No syntax errors detected
node --check OK
```

Observação: ainda falta teste funcional real pelo painel e envio real para WhatsApp.

---

## Teste funcional recomendado antes do backup final

Antes de avançar para o Builder, testar:

1. Acessar o módulo de template de carrossel.
2. Criar um novo carrossel.
3. Adicionar pelo menos 2 cards.
4. Fazer upload de imagem em cada card.
5. Confirmar se o campo de mídia recebe URL `/writable/uploads/...`.
6. Salvar o template.
7. Reabrir o template.
8. Confirmar se as URLs e previews continuam salvos.
9. Enviar por uma conta Cloud API.
10. Enviar por uma conta Baileys.
11. Confirmar renderização da mídia no WhatsApp.

Logs úteis:

```text
/writable/logs/cloud_interactive.log
/writable/logs/cloud_send.log
/writable/bot_builder_send.log
PM2/Node logs com [DBG_CAROUSEL_BAILEYS]
```

---

## Risco conhecido

O upload gera URL em:

```text
/writable/uploads/...
```

A Meta/WhatsApp precisa conseguir baixar essa URL publicamente.

Se a mídia não renderizar, validar primeiro:

1. Abrir a URL em aba anônima.
2. Confirmar HTTPS válido.
3. Confirmar que não há bloqueio por firewall, permissão ou hotlink.
4. Confirmar que o arquivo é imagem suportada.

---

# Plano de backup antes de avançar para o Builder

## Arquivos críticos para backup

Backup obrigatório dos arquivos alterados:

```text
/inc/core/Whatsapp_carousel_template/Views/update.php
/inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php
/app_zapmatic_api/waziper/waziper.js
```

Backup recomendado também do controller do carrossel, mesmo sem alteração final relevante nesta etapa:

```text
/inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php
```

## Dados/tabelas importantes

Backup recomendado das tabelas relacionadas:

```text
TB_WHATSAPP_TEMPLATE
TB_FILES
```

Motivo:

- `TB_WHATSAPP_TEMPLATE` guarda o JSON dos templates globais, incluindo carrossel type `5`.
- `TB_FILES` guarda os arquivos enviados pelo File Manager.

## Diretório de uploads

Backup recomendado:

```text
/writable/uploads
```

Motivo:

- As URLs salvas nos cards apontam para arquivos desse diretório.

---

# Contexto para próxima fase — Bot Builder usando templates globais

## Objetivo da próxima fase

Fazer o Bot Builder parar de construir botões/listas/carrossel manualmente dentro do node e passar a consumir os templates nativos globais do sistema.

Fonte global:

```text
TB_WHATSAPP_TEMPLATE
```

Tipos:

```text
type = 1 → Lista
type = 2 → Botões
type = 5 → Carrossel
```

## Arquivos principais da próxima fase

### Builder visual

```text
/inc/core/Bot_builder/Assets/js/bot_builder.js
/inc/core/Bot_builder/Views/editor.php
```

Responsável por:

- adicionar seletor de template global nos nodes;
- carregar templates disponíveis por tipo;
- gerar handles/conexões com base nos botões/rows/cards do template;
- preservar modo manual antigo como fallback.

### Runtime PHP do bot

```text
/inc/core/Bot_builder/Controllers/Bot_builder.php
```

Responsável por:

- detectar se o node usa template global;
- buscar `TB_WHATSAPP_TEMPLATE` por `ids`, `team_id` e `type`;
- enviar usando o payload nativo;
- preservar sessões e roteamento;
- manter fallback para nodes antigos manuais.

### Node/Baileys

```text
/app_zapmatic_api/waziper/waziper.js
```

Responsável por:

- receber `bot_builder_send`;
- enviar botões/listas/carrossel;
- garantir que carrossel renderize corretamente, inclusive iOS;
- registrar logs de envio.

## Regra técnica mais importante

Não migrar removendo o modo antigo.

Estratégia segura:

```text
Se node tiver template_ids:
    usar template global nativo
Senão:
    manter comportamento manual antigo
```

Isso evita quebrar bots já existentes.

## Roteamento obrigatório

O Builder não pode apenas enviar o template. Ele precisa gerar as saídas do node a partir dos IDs reais do template.

Mapeamento esperado:

### Botões type 2

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

Sem isso, a mensagem pode ser enviada, mas o fluxo pode não saber para qual próximo node seguir.

---

# Ordem recomendada para avançar no Builder

## Fase 1 — Seletor global nos nodes

Adicionar seleção de template nos nodes:

```text
Botões → type 2
Lista → type 1
Carrossel/Cards → type 5
```

## Fase 2 — Preview e handles

Ao selecionar um template:

- carregar o JSON do template;
- exibir preview resumido;
- criar handles/conexões com IDs reais;
- salvar `template_ids` no node.

## Fase 3 — Runtime PHP

Em `Bot_builder.php`:

- se `template_ids` existir, buscar template global;
- normalizar payload;
- chamar envio nativo;
- manter fallback antigo.

## Fase 4 — Testes

Testar por ordem:

1. Botões type 2.
2. Lista type 1.
3. Carrossel type 5.

Carrossel deve ser testado por último por ser o mais sensível.

## Fase 5 — Logs

Registrar nos logs:

```text
bot_id
node_id
template_ids
template_type
message_type
buttonId/rowId clicado
edge encontrada
resultado do envio
```

Logs principais:

```text
/writable/bot_builder_send.log
/writable/bot_builder_webhook.log
/writable/logs/cloud_interactive.log
PM2/Node logs
```

---

# Estado final desta etapa

A etapa de preparação do carrossel está pronta para backup e teste.

Resumo:

- Upload por card implementado.
- URL manual preservada.
- Envio Cloud API normalizado.
- Envio Baileys normalizado.
- Documentação de contexto criada.
- Documentação de avanço atualizada.
- Próximo avanço técnico: integração do Bot Builder com templates globais nativos.
