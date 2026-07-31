# Plano de Ação - WhatsApp Flows na Cloud API

> Operação: implantação de WhatsApp Flows no sistema  
> Estratégia: Cloud API completa primeiro, Baileys depois  
> Princípio: nenhuma fase pode quebrar envio atual, templates atuais, bulk, chatbot ou autoresponder

---

## 1. Objetivo

Implantar suporte completo a WhatsApp Flows na Cloud API com:

- criação e gestão local do Flow;
- integração com a Meta;
- publicação e assets;
- endpoint criptografado;
- envio manual;
- resposta de webhook;
- suporte futuro a template com botão Flow;
- trilha de adaptação posterior para Baileys.

---

## 2. Decisão arquitetural

### Decisão principal

Trabalhar em duas linhas:

1. **Cloud API como fonte da verdade**
2. **Baileys como camada de compatibilidade posterior**

### Justificativa

Cloud API é o canal oficial de Flow da Meta e cobre:

- Flow creation
- Flow JSON
- assets
- publish
- endpoint encryption
- webhook oficial
- template message com botão Flow

Baileys pode ser adaptado depois para envio/recebimento compatível, mas não deve definir a arquitetura inicial.

---

## 3. Escopo da Fase Cloud

### Dentro do escopo

- módulo local de Flow
- persistência local
- create/list/publish/deprecate na Meta
- assets do Flow
- configuração de endpoint
- criptografia/chaves
- envio manual Cloud
- webhook de resposta
- visualização de status
- documentação e progresso operacional

### Fora do escopo inicial

- chatbot com Flow
- autoresponder com Flow
- bulk com Flow
- Baileys
- analytics avançado de conversão

---

## 4. Fases recomendadas

## Fase 0 - Governança e baseline

### Objetivo

Abrir a operação com documentação e trilha própria.

### Entrega

- documento de funcionamento atual
- plano de ação
- documento de progresso

### Gate

- baseline criada
- sem mudança funcional

---

## Fase 1 - Modelagem interna de Flow

### Objetivo

Criar o contrato local do Flow sem ainda enviar nada.

### Entregas

- definição de estrutura de banco
- definição de status locais
- definição da relação entre conta Cloud e Flow
- definição de tipos de envio

### Recomendação de dados

Criar tabela própria para Flows, em vez de forçar tudo em `sp_whatsapp_template`.

Entidades sugeridas:

- `sp_whatsapp_flow_endpoints`
- `sp_whatsapp_flows`
- `sp_whatsapp_flow_assets`
- `sp_whatsapp_flow_events`

Campos mínimos em `sp_whatsapp_flows`:

- `ids`
- `team_id`
- `account_ids`
- `waba_id`
- `meta_flow_id`
- `name`
- `status_local`
- `status_meta`
- `flow_json`
- `json_version`
- `data_api_version`
- `endpoint_uri`
- `public_key_uploaded`
- `last_sync_at`
- `created`
- `changed`

Campos mínimos em `sp_whatsapp_flow_endpoints`:

- `team_id`
- `account_id`
- `account_ids`
- `waba_id`
- `phone_number_id`
- `endpoint_uri`
- `endpoint_status`
- `public_key_fingerprint`
- `public_key_uploaded`
- `last_sync_at`
- `changed`
- `created`

### Gate

- migration validada
- nenhuma regressão no sistema atual

---

## Fase 2 - Módulo administrativo de Flow

### Objetivo

Construir o painel de gestão local do Flow.

### Frontend previsto

- lista de Flows
- criar/editar
- importação/edição de Flow JSON
- status de publicação
- assets
- ações de sync/publicar/depreciar

### Módulos que devem ser afetados

- novo módulo `Whatsapp_flow`
- menu do WhatsApp
- integração com seletor de contas Cloud

### Estratégia de interface

- começar com editor JSON e campos estruturais
- deixar builder visual para etapa futura

### Gate

- CRUD local funcionando
- sem impacto no envio atual

---

## Fase 3 - Integração Meta: create, assets e publish

### Objetivo

Ligar o Flow local ao ciclo oficial da Meta.

### Entregas

- criar Flow na Meta
- clonar quando necessário
- subir assets
- publicar Flow
- depreciar Flow
- ler status e erros

### Base de reaproveitamento

Usar como referência o pipeline existente em:

- [Whatsapp_button_template.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_button_template/Controllers/Whatsapp_button_template.php:461)

### Gate

- Flow criado e sincronizado na Meta
- publicação concluída
- status refletido localmente

---

## Fase 4 - Endpoint criptografado de Flow

### Objetivo

Implementar o endpoint exigido pela Meta para data exchange.

### Decisão técnica

Implementar no Node em `app_zapmatic_api`, não no PHP.

### Motivos

- exemplos oficiais estão em Node
- criptografia RSA/AES encaixa melhor no runtime Node
- facilita integração com webhook e futuro uso em automações

### Entregas

- geração/configuração de chaves
- endpoint de validação de assinatura
- decrypt request
- encrypt response
- retorno de telas
- tratamento de erros de endpoint

### Gate

- endpoint responde corretamente
- chave pública aceita pela Meta
- request/response criptografados validados

---

## Fase 5 - Envio manual de Flow pela Cloud API

### Objetivo

Permitir disparar Flow direto do sistema em conta Cloud.

### Tipos a suportar

1. Flow interativo direto
2. Flow template message

### Pontos que devem ser alterados

- helper Cloud em [Common_helper.php](/www/wwwroot/app_zapmatic_app/app/Helpers/Common_helper.php:3459)
- runtime Node em [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2530)
- tela de envio manual em [info.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_send_message/Views/info.php:1)

### Gate

- envio manual Flow direto funcionando
- resposta básica chegando por webhook
- nenhum envio atual quebrado

---

## Fase 6 - Recebimento e interpretação da resposta do Flow

### Objetivo

Entender e persistir o retorno do usuário ao completar/interagir com o Flow.

### Entregas

- parser de `nfm_reply` na Cloud
- persistência do retorno
- log técnico da resposta
- base para acionar próximos passos

### Pontos que devem ser alterados

- [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2502)
- [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js:1379)

### Gate

- resposta recebida corretamente
- dados persistidos
- chat continua íntegro

---

## Fase 7 - Template com botão Flow

### Objetivo

Permitir envio outbound usando template aprovado com botão Flow.

### Estratégia

Reaproveitar o padrão existente de template oficial, mas com um novo tipo/blueprint específico para Flow.

### Entregas

- builder de template Flow
- submissão para review na Meta
- sync de status
- envio via template com `sub_type = flow`

### Gate

- template aprovado
- envio fora da janela funcionando
- variáveis e `flow_token` consistentes

---

## Fase 8 - Expansão para automações Cloud

### Objetivo

Levar Flow para:

- chatbot
- autoresponder
- bulk

### Regra de segurança

Esta fase só começa depois que:

- envio manual estiver estável
- webhook estiver estável
- template com botão Flow estiver homologado

---

## Fase 9 - Adaptação Baileys

### Objetivo

Adaptar o suporte para envio/recebimento compatível no canal Baileys.

### Observação crítica

Baileys entra como compatibilidade, não como fonte primária da arquitetura.

### Entregas esperadas

- envio de `nativeFlowMessage`
- leitura de `nativeFlowResponseMessage`
- integração com o mesmo modelo local de Flow

### Gate

- envio Baileys validado
- parser de resposta validado
- nenhuma regressão no envio atual

---

## 5. Impacto por camada

### Backend PHP

- menu
- CRUD administrativo
- sync/status
- telas de seleção

### Node

- endpoint criptografado
- envio Cloud Flow
- webhook Cloud Flow
- futura adaptação Baileys

### Banco

- nova modelagem para Flows
- eventos e assets

### Frontend

- módulo administrativo
- envio manual
- status e operação

---

## 6. Ordem recomendada de implementação

1. documentação e baseline
2. banco/modelagem
3. módulo administrativo
4. integração create/assets/publish
5. endpoint criptografado
6. envio manual Cloud
7. webhook de resposta
8. template com botão Flow
9. automações Cloud
10. Baileys

---

## 7. Regras de segurança da operação

- nenhuma fase avança sem teste interno
- nenhuma fase avança sem atualização do progresso
- nenhuma fase pode quebrar envio comum
- nenhuma fase pode quebrar templates já existentes
- nenhuma fase pode quebrar bulk, chatbot ou autoresponder atuais
- todo rollout deve começar pela Cloud API

---

## 8. Parecer final

Sim, existe um plano seguro para iniciar.

A melhor estratégia é:

- fazer **Cloud API completa primeiro**
- usar o padrão de template oficial que o sistema já tem
- tratar Flow como módulo novo e operação separada
- só depois adaptar Baileys

Esse é o caminho com melhor equilíbrio entre:

- velocidade
- compatibilidade
- governança
- menor risco de regressão
