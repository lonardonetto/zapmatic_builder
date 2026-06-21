# Contexto — Upload de mídia por card no Carrossel

## Objetivo

Permitir que o template global de carrossel aceite upload/seleção de mídia por card, além da URL manual já existente.

Essa correção prepara o módulo nativo de carrossel para ser usado posteriormente pelo Bot Builder sem depender de URLs externas coladas manualmente.

## Arquivos alterados

- `/inc/core/Whatsapp_carousel_template/Views/update.php`
- `/inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php`
- `/app_zapmatic_api/waziper/waziper.js`

## Implementação realizada

### 1. Interface do carrossel

No formulário de edição/criação de carrossel, o campo antigo `Media URL` foi substituído por um bloco de mídia por card contendo:

- campo de URL manual;
- botão `Upload`;
- input file oculto;
- preview da mídia selecionada.

O campo manual continua usando `card_media[index]`, preservando compatibilidade com templates já salvos.

### 2. Upload pelo File Manager

O botão `Upload` envia o arquivo para o endpoint já existente:

```text
file_manager/upload_files
```

O retorno `result.file` é convertido em URL pública no formato:

```text
{PATH}/writable/{result.file}
```

Exemplo:

```text
https://dominio.com/writable/uploads/arquivo.jpg
```

Essa URL é preenchida no campo `card_media[index]` e salva no JSON do template.

### 3. Compatibilidade com cards existentes

A view agora aceita `media` como:

```json
"media": "https://..."
```

ou como objeto legado/futuro:

```json
"media": { "url": "https://..." }
```

### 4. Normalização no envio Cloud API via PHP

O envio do carrossel em `Whatsapp_send_message.php` agora resolve a mídia do card a partir de:

- `card.media` string;
- `card.media.url`;
- `card.image` string;
- `card.image.url`.

Depois monta o header Cloud API:

```json
{
  "type": "image",
  "image": {
    "link": "https://..."
  }
}
```

### 5. Normalização no Node/Baileys

No `waziper.js`, o envio de carrossel também passa a aceitar `card.media` como string.

Antes o Baileys lia principalmente:

```js
card.media.url
card.image.url
card.image
```

Agora também lê:

```js
card.media
```

Isso evita que templates salvos pelo novo upload fiquem sem mídia ao passar pelo Node.

## Validações executadas

Foram executadas validações de sintaxe:

```bash
php -l inc/core/Whatsapp_carousel_template/Views/update.php
php -l inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php
php -l inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php
node --check app_zapmatic_api/waziper/waziper.js
```

Resultado: sem erros de sintaxe.

## Pontos de teste manual

1. Abrir o módulo de template de carrossel.
2. Criar ou editar um carrossel.
3. Em um card, clicar em `Upload`.
4. Selecionar uma imagem.
5. Confirmar que o campo de mídia recebe URL `/writable/uploads/...`.
6. Salvar o template.
7. Reabrir o template e confirmar preview/mídia.
8. Enviar por uma conta Cloud API.
9. Enviar por uma conta Baileys.
10. Confirmar renderização da mídia no card do carrossel.

## Observações importantes

- A URL manual não foi removida.
- Templates antigos continuam compatíveis.
- O upload usa o File Manager existente e respeita permissões/tamanho/extensões já configurados no sistema.
- O próximo passo recomendado é testar envio real e depois integrar o Bot Builder aos templates globais nativos.
