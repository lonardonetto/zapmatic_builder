# Funcionamento Atual - WhatsApp Flows e Templates

> Operação: implantação de WhatsApp Flows no sistema  
> Recorte desta análise: estado atual do projeto antes da implementação  
> Data-base da leitura: 2026-04-14

---

## 1. Objetivo deste documento

Registrar como o sistema trabalha hoje com:

- mensagens comuns;
- interativos locais;
- templates oficiais da Meta;
- envio Cloud API;
- envio Baileys;
- webhook e processamento de resposta.

Este documento serve como baseline para a operação de implantação de WhatsApp Flows.

---

## 2. Resumo executivo

Hoje o sistema:

- já suporta envio comum por Baileys e Cloud API;
- já suporta interativos locais como botão, lista, enquete e carrossel;
- já possui um pipeline de template oficial da Meta com submissão, espelho de status e sincronização;
- ainda não possui módulo de WhatsApp Flow implementado;
- ainda não trata respostas de Flow no runtime;
- já possui base técnica suficiente para implantar Flows na Cloud API sem reescrever o sistema inteiro.

---

## 3. Arquitetura atual de templates

### 3.1. Tabela central

O sistema concentra templates em:

- `sp_whatsapp_template`

Constantes atuais em [Constants.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Config/Constants.php:1):

- `type = 6`: template oficial aprovado da Meta
- `type = 66`: espelho de status da Meta
- `type = 67`: draft/blueprint local

Além desses tipos, o sistema também usa tipos locais para:

- botão
- lista
- enquete
- carrossel

### 3.2. Módulos locais existentes

Hoje existem estes módulos principais:

- `Whatsapp_button_template`
- `Whatsapp_list_message_template`
- `Whatsapp_poll_template`
- `Whatsapp_carousel_template`
- `Whatsapp_official_template`
- `Whatsapp_send_message`
- `Whatsapp_bulk`
- `Whatsapp_autoresponder`
- `Whatsapp_chatbot`

---

## 4. Frontend atual

### 4.1. Envio manual

A tela de envio manual em [info.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_send_message/Views/info.php:1) hoje expõe:

- texto e mídia
- botão
- lista
- enquete
- carrossel

Apesar de existir um widget de template oficial em:

- [menu.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_official_template/Views/widget/menu.php:1)
- [content.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_official_template/Views/widget/content.php:1)

ele não está plugado atualmente na tela de envio manual.

### 4.2. Chatbot, autoresponder e bulk

As telas de:

- [update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_chatbot/Views/update.php:395)
- [info.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_autoresponder/Views/info.php:47)
- [update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_bulk/Views/update.php:143)

continuam expondo apenas os templates locais.

---

## 5. Pipeline oficial da Meta já existente

O ponto mais importante para a operação de Flows é que o sistema já possui um padrão maduro para a Meta.

### 5.1. Submissão para análise

O módulo de botão possui um fluxo de submissão em:

- [Whatsapp_button_template.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_button_template/Controllers/Whatsapp_button_template.php:461)

Esse fluxo já faz:

- validação local;
- seleção da conta Cloud;
- montagem do payload Meta;
- upload de mídia quando necessário;
- criação do template na Meta;
- gravação do status local;
- associação do template Meta com o template interno.

### 5.2. Widget de template oficial

O módulo oficial em:

- [Whatsapp_official_template.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_official_template/Controllers/Whatsapp_official_template.php:1)

já lista templates aprovados por conta Cloud API/WABA.

Esse módulo hoje é mais um seletor/sincronizador do que um builder completo.

### 5.3. Helpers Cloud

Os helpers existentes em [Common_helper.php](/www/wwwroot/app_zapmatic_app/app/Helpers/Common_helper.php:3385) já suportam:

- `send_cloud_template`
- `send_cloud_interactive`
- `get_cloud_templates`

Isso é relevante porque o Flow direto na Cloud usa justamente `interactive`.

---

## 6. Motor Node atual

### 6.1. Cloud API

O runtime principal está em:

- [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2530)

Hoje o motor Cloud já monta e envia:

- texto
- imagem
- vídeo
- áudio
- documento
- botão
- lista
- template
- carrossel

Ainda não existe branch explícita para:

- `interactive.type = flow`

### 6.2. Templates oficiais

O runtime já entende template oficial da Meta:

- [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:3752)

E também força envio oficial aprovado em cenários de bulk quando necessário.

### 6.3. Webhook oficial

O webhook Cloud entra em:

- [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2502)

e transforma mensagens recebidas com:

- [process_official_message](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:1379)

Hoje o parser trata bem:

- texto
- mídia
- botões
- listas

Mas não trata especificamente:

- `interactive.type = nfm_reply`

que é justamente a resposta oficial de Flow da Cloud.

### 6.4. Entrada Baileys

No Baileys, a extração principal de respostas em [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:727) trata:

- `buttonsResponseMessage`
- `templateButtonReplyMessage`
- `listResponseMessage`

Não existe hoje tratamento explícito para:

- `interactiveResponseMessage.nativeFlowResponseMessage`

---

## 7. Suporte do Baileys instalado

O pacote instalado em:

- [README.md](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/node_modules/@itsukichan/baileys/README.md:1286)

já mostra suporte para:

- `flow_message_version`
- `flow_token`
- `flow_id`
- `flow_cta`
- `flow_action`
- `flow_action_payload`

Além disso, os arquivos internos do pacote suportam:

- `interactiveMessage.nativeFlowMessage`
- `interactiveResponseMessage.nativeFlowResponseMessage`

Conclusão:

- o Baileys instalado é tecnicamente capaz de transportar payloads de Flow;
- o aplicativo local ainda não usa esse suporte.

---

## 8. Diferença estrutural entre Cloud API e Baileys

### 8.1. Cloud API

Na Cloud API, Flow é um produto oficial da Meta com:

- criação do Flow;
- upload de assets;
- publicação;
- endpoint criptografado;
- webhook de resposta;
- template com botão Flow;
- análise/aprovação para cenários outbound.

### 8.2. Baileys

No Baileys, o cenário é diferente:

- há suporte de transporte para mensagens nativas de Flow;
- mas não existe governança local de review/publicação como a Meta oferece;
- o modelo oficial de endpoint, chaves e lifecycle continua sendo Meta-first.

Conclusão prática:

- Cloud API pode ser tratada como plataforma completa de Flow;
- Baileys deve ser tratado como camada de compatibilidade posterior.

---

## 9. Limitações atuais do sistema

Antes de implantar Flows, estas lacunas precisam ser consideradas:

- não há módulo de Flow builder;
- não há UI de Flow JSON/assets/publicação;
- não há endpoint criptografado para Flow Data Exchange;
- não há parser de `nfm_reply` no webhook Cloud;
- não há parser de `nativeFlowResponseMessage` no runtime Baileys;
- não há frente visual integrada de Flow no envio manual;
- chatbot, bulk e autoresponder hoje bloqueiam o tipo oficial `6`.

---

## 10. Parecer técnico

O sistema atual está pronto para receber WhatsApp Flows, desde que a implantação respeite a arquitetura que já existe.

A base mais reaproveitável é:

- pipeline Meta do módulo de botão;
- separação `draft/status/approved`;
- helpers Cloud de interactive/template;
- runtime Node centralizado.

Decisão recomendada para a operação:

- implantar primeiro a versão completa na Cloud API;
- validar o fluxo ponta a ponta;
- depois adaptar o Baileys para compatibilidade operacional.
