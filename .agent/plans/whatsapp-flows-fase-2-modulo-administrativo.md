# Fase 2 - Módulo Administrativo de Flow

> Operação: WhatsApp Flows na Cloud API  
> Escopo desta fase: painel local de cadastro e gestão  
> Data: 14 de abril de 2026

---

## 1. Objetivo

Abrir o primeiro módulo administrativo de Flow sem tocar ainda em:

- criação/publicação na Meta;
- assets remotos;
- endpoint criptografado;
- envio Cloud em produção.

Nesta fase, o foco foi:

- CRUD local de `sp_whatsapp_flows`;
- vínculo do Flow com conta Cloud;
- referência opcional para `sp_whatsapp_flow_endpoints`;
- edição segura de `flow_json` e `preview_data`;
- leitura de estado local e espelho de status Meta.

---

## 2. Arquivos criados/alterados

### Novo módulo

- [Config.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Config.php)
- [Config/Routes.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Config/Routes.php)
- [Controllers/Whatsapp_flow.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php)
- [Models/Whatsapp_flowModel.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Models/Whatsapp_flowModel.php)
- [Views/content.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/content.php)
- [Views/ajax_list.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/ajax_list.php)
- [Views/update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/update.php)

### Integração com permissões

- [permissions.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Views/permissions.php)

---

## 3. O que o módulo já faz

- lista Flows locais por time;
- cria e edita Flow local;
- escolhe a conta Cloud API vinculada;
- resolve `account_id`, `account_ids`, `waba_id` e `phone_number_id` a partir da conta escolhida;
- liga o Flow a um endpoint local já existente quando disponível;
- valida JSON de `flow_json`;
- valida JSON de `preview_data`;
- exibe `meta_flow_id`, `status_meta`, `published_at`, `last_sync_at` e total local de assets quando existir.

---

## 4. O que ficou intencionalmente para a próxima fase

- create Flow na Meta;
- publish/deprecate;
- upload de assets remotos;
- sync de saúde/status remoto;
- endpoint criptografado;
- envio manual de Flow.

---

## 5. Testes previstos desta fase

- `php -l` em todos os arquivos novos do módulo
- `php -l inc/core/Whatsapp/Views/permissions.php`
- checagem de sintaxe do runtime atual:
  - `node --check app_zapmatic_api/waziper/waziper.js`
  - `node --check app_zapmatic_api/waziper/extend.js`
- checagem de sintaxe dos controladores críticos atuais:
  - `php -l inc/core/Whatsapp_bulk/Controllers/Whatsapp_bulk.php`
  - `php -l inc/core/Whatsapp_chatbot/Controllers/Whatsapp_chatbot.php`
  - `php -l inc/core/Whatsapp_autoresponder/Controllers/Whatsapp_autoresponder.php`

---

## 6. Parecer técnico

Esta fase continua **aditiva**:

- não altera o motor Node;
- não altera a lógica atual de chatbot;
- não altera autoresponder;
- não altera bulk;
- não altera envio comum ou templates atuais.

Ela abre apenas a camada administrativa necessária para a trilha Cloud-first.
