# WhatsApp Flows - Fase 4

## Endpoint criptografado na Cloud API

Data: `14/04/2026`

### Objetivo

Entregar a camada de endpoint criptografado do WhatsApp Flow na operação Cloud API, cobrindo:

- gestão local do endpoint por conta Cloud;
- geração do par de chaves RSA;
- upload da chave pública para a Meta;
- verificação do status da criptografia no `phone_number_id`;
- rota pública Node para requests criptografadas da Meta;
- logging interno de requests e respostas em `sp_whatsapp_flow_events`.

### Arquivos alterados

- [Whatsapp_flow.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php)
- [Routes.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Config/Routes.php)
- [update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/update.php)
- [ajax_list.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/ajax_list.php)
- [app.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/app.js)
- [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js)
- [flow_endpoint.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/flow_endpoint.js)

### Backend PHP

- criação automática do registro em `sp_whatsapp_flow_endpoints` por conta Cloud;
- derivação da `endpoint_uri` a partir de `whatsapp_server_url`;
- geração local de RSA 2048 bits no `writable/flow_endpoints/<endpoint_ids>/`;
- cálculo e persistência do `public_key_fingerprint`;
- action para:
  - preparar endpoint + subir chave pública na Meta;
  - refrescar status da criptografia;
- sincronização do `endpoint_id` no Flow local.

### Backend Node

- nova rota pública:
  - `GET /flow_endpoint/:endpointIds`
  - `POST /flow_endpoint/:endpointIds`
- validação opcional de assinatura com `x-hub-signature-256`;
- decrypt do payload usando RSA OAEP SHA-256 + AES-128-GCM;
- encrypt da resposta com IV invertido;
- resolução do Flow local a partir do `flow_token`;
- logging em `sp_whatsapp_flow_events`.

### Testes executados

- `php -l inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php`
- `php -l inc/core/Whatsapp_flow/Views/update.php`
- `php -l inc/core/Whatsapp_flow/Views/ajax_list.php`
- `php -l inc/core/Whatsapp_flow/Config/Routes.php`
- `node --check app_zapmatic_api/app.js`
- `node --check app_zapmatic_api/waziper/waziper.js`
- `node --check app_zapmatic_api/waziper/flow_endpoint.js`
- round-trip local do endpoint criptografado com RSA + AES-GCM
- restart do processo PM2 `zapmatic`
- teste público `GET https://serverzapmatic.zapmatic.tec.br/flow_endpoint/69ded91c8bd99`
- teste público `POST` criptografado ao endpoint com assinatura HMAC válida

### Validação real na Meta

Conta Cloud validada:

- conta: `ELITEZAP`
- `account_id = 40`
- `phone_number_id = 1017716114747963`
- Flow usado para amarração inicial: `TESTING`

Resultado real:

- endpoint local criado:
  - `endpoint_id = 1`
  - `endpoint_ids = 69ded91c8bd99`
  - `endpoint_uri = https://serverzapmatic.zapmatic.tec.br/flow_endpoint/69ded91c8bd99`
- chave pública enviada com sucesso para a Meta
- retorno oficial:
  - `business_public_key_signature_status = VALID`
- estado local final:
  - `endpoint_status = verified`
  - `public_key_uploaded = 1`
  - `app_secret_verified = 1`
  - `last_meta_error = null`

### Parecer técnico

- o endpoint criptografado da fase Cloud está funcional de ponta a ponta;
- a operação local, a sincronização com a Meta e a rota pública foram validadas;
- o sistema já está apto para evoluir a lógica de `data_exchange` por Flow e, em seguida, entrar na fase de template com botão Flow para outbound e bulk.

## Atualização - runtime e ciclo de resposta

Data: `15/04/2026`

### Entrega complementar

- `Single Message` agora permite escolher a estratégia de abertura do Flow:
  - `navigate`
  - `data_exchange`
- o payload de `flow_action_payload.data` passou a ser enviado como objeto real para a Meta;
- o endpoint Node agora entende o runtime do Flow publicado:
  - indexa `screens`
  - lê `routing_model`
  - interpreta `NavigationList`
  - reconhece `builder_state` guiado
  - resolve categoria e submenu selecionados
- a conclusão via endpoint responde com `screen = SUCCESS` e inclui:
  - `flow_token`
  - `flow_id`
  - `flow_name`
  - `flow_slug`
  - `meta_flow_id`
  - campos finais do formulário
  - `category_id`
  - `category_title`
  - `option_id`
  - `option_title`
- o parser da Cloud agora reconhece `interactive.nfm_reply`;
- a resposta final do cliente ao Flow passou a ser registrada em `sp_whatsapp_flow_events` com `event_type = flow_reply`.

### Arquivos alterados nesta continuação

- [content.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/widget/content.php)
- [Whatsapp_send_message.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php)
- [flow_endpoint.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/flow_endpoint.js)
- [extend.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/extend.js)
- [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js)

### Testes executados nesta continuação

- `php -l inc/core/Whatsapp_flow/Views/widget/content.php`
- `php -l inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php`
- `node --check app_zapmatic_api/waziper/flow_endpoint.js`
- `node --check app_zapmatic_api/waziper/extend.js`
- `node --check app_zapmatic_api/waziper/waziper.js`
- simulação interna do endpoint:
  - `INIT -> WELCOME`
  - seleção de categoria -> `FINANCEIRO`
  - seleção de opção -> `FINANCEIRO_EMITIR_FATURA`
  - conclusão -> `SUCCESS` com params completos
- simulação interna do parser `nfm_reply` com `flow_name`, categoria, opção e campos finais

### Parecer técnico da continuação

- a trilha Cloud agora cobre não só a abertura do Flow, mas também o retorno final do cliente;
- o endpoint passou a se comportar de forma alinhada ao Flow real salvo no sistema;
- o próximo passo estrutural continua sendo `template + Flow button` para outbound e bulk.
