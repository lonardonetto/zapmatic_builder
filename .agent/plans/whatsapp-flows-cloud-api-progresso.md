# Progresso da Operação - WhatsApp Flows

> Documento oficial de acompanhamento da operação  
> Estratégia aprovada: Cloud API primeiro, Baileys depois

---

## 1. Status geral

| Item | Status |
| ---- | ------ |
| Operação iniciada | Sim |
| Documento de funcionamento atual | Concluído |
| Plano de ação | Concluído |
| Implementação técnica | Fases 1, 2, 2.2 e 3 concluídas |
| Modelagem de banco | Concluída |
| Frontend administrativo | Builder rico concluído |
| Endpoint criptografado | Concluído |
| Envio Cloud Flow | Single Message, endpoint e retorno iniciados |
| Adaptação Baileys | Não iniciada |

---

## 2. Resumo executivo

### Estado atual da operação

- A análise técnica foi concluída.
- A decisão arquitetural foi tomada.
- O canal prioritário será a Cloud API.
- O Baileys ficará para a segunda etapa da operação.
- A Fase 1 de modelagem interna foi concluída nesta base.
- A Fase 2 do módulo administrativo local foi concluída nesta base.
- A Fase 2.1 do builder visual foi concluída nesta base.
- A Fase 2.2 do builder rico foi concluída nesta base.
- A Fase 3 de sincronização com a Meta foi concluída nesta base.
- O `Single Message` já recebeu a primeira camada de envio `interactive flow`.
- O endpoint criptografado já foi validado de ponta a ponta.
- O `Single Message` já consegue abrir o Flow em `navigate` ou `data_exchange`.
- O retorno `nfm_reply` da Cloud já começa a ser interpretado e registrado.

### Decisão estrutural já tomada

- Flow será tratado como frente separada.
- Cloud API será a fonte da verdade.
- O endpoint criptografado será implementado no Node.
- A adaptação Baileys só começa depois da trilha Cloud estar estável.

---

## 3. Registro por fase

## Fase 0 - Análise e governança

### Status

**Concluída**

### Objetivo

Mapear a capacidade atual do sistema e definir a arquitetura da operação.

### Entregas

- análise completa do estado atual
- documento de funcionamento atual
- plano de ação por fases
- documento de progresso

### Arquivos gerados

- [whatsapp-flows-funcionamento-atual.md](/www/wwwroot/app_zapmatic_app/.agent/plans/whatsapp-flows-funcionamento-atual.md)
- [whatsapp-flows-cloud-api-plano-de-acao.md](/www/wwwroot/app_zapmatic_app/.agent/plans/whatsapp-flows-cloud-api-plano-de-acao.md)
- [whatsapp-flows-cloud-api-progresso.md](/www/wwwroot/app_zapmatic_app/.agent/plans/whatsapp-flows-cloud-api-progresso.md)

### Banco

- nenhuma alteração

### Código

- nenhuma alteração funcional

### Testes e verificações executadas

- leitura estrutural dos módulos:
  - `Whatsapp_send_message`
  - `Whatsapp_bulk`
  - `Whatsapp_autoresponder`
  - `Whatsapp_chatbot`
  - `Whatsapp_button_template`
  - `Whatsapp_official_template`
- leitura do runtime Node:
  - `waziper.js`
  - `extend.js`
- leitura do suporte Baileys instalado:
  - `nativeFlowMessage`
  - `nativeFlowResponseMessage`
- leitura de helpers Cloud:
  - `send_cloud_template`
  - `send_cloud_interactive`
- análise de documentação oficial e exemplos oficiais da Meta

### Parecer técnico

- o sistema já possui base forte para implantação de Flow na Cloud
- não existe implementação de Flow no app hoje
- o Baileys já possui suporte de transporte, mas não deve ser a primeira fase
- a arquitetura recomendada é Cloud-first

### Decisão

**Apto para iniciar a Fase 1**

---

## Fase 1 - Modelagem interna de Flow

### Status

**Concluída**

### Objetivo

Criar a base estrutural de banco e os contratos internos para WhatsApp Flows sem mexer no motor atual de envio.

### Entregas

- constantes internas para Flow adicionadas
- migration SQL criada
- modelagem de banco aplicada nesta base
- documento técnico da fase criado

### Arquivos gerados/alterados

- [Constants.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Config/Constants.php)
- [20260414_whatsapp_flows_phase1.sql](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Migrations/20260414_whatsapp_flows_phase1.sql)
- [whatsapp-flows-fase-1-modelagem.md](/www/wwwroot/app_zapmatic_app/.agent/plans/whatsapp-flows-fase-1-modelagem.md)

### Banco

Tabelas criadas:

- `sp_whatsapp_flow_endpoints`
- `sp_whatsapp_flows`
- `sp_whatsapp_flow_assets`
- `sp_whatsapp_flow_events`

### Código

- sem alteração no motor de envio
- sem alteração em chatbot
- sem alteração em autoresponder
- sem alteração em bulk

### Testes executados

- `php -l inc/core/Whatsapp/Config/Constants.php`
- `node --check app_zapmatic_api/waziper/waziper.js`
- `node --check app_zapmatic_api/waziper/extend.js`
- aplicação da migration SQL em `db_zapmatic_sql`
- `SHOW TABLES LIKE 'sp_whatsapp_flow%'`
- `DESCRIBE` das 4 tabelas criadas

### Parecer técnico

- a modelagem foi aplicada com sucesso
- a fase é aditiva e não encosta no fluxo atual de mensagens
- o sistema permanece apto para seguir para a fase administrativa

### Decisão

**Apto para iniciar a Fase 2**

---

## Fase 2 - Módulo administrativo de Flow

### Status

**Concluída**

### Objetivo

Criar o módulo administrativo local de Flow para Cloud API sem iniciar ainda a publicação/envio.

### Entregas

- novo módulo `Whatsapp_flow`
- CRUD local de `sp_whatsapp_flows`
- vínculo com conta Cloud API
- referência opcional de endpoint local
- validação de `flow_json` e `preview_data`
- integração de permissão no painel de planos
- documento técnico da fase criado
- builder visual inicial para usuário leigo

### Arquivos gerados/alterados

- [Config.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Config.php)
- [Config/Routes.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Config/Routes.php)
- [Whatsapp_flow.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php)
- [Whatsapp_flowModel.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Models/Whatsapp_flowModel.php)
- [content.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/content.php)
- [ajax_list.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/ajax_list.php)
- [update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/update.php)
- [permissions.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Views/permissions.php)
- [whatsapp-flows-fase-2-modulo-administrativo.md](/www/wwwroot/app_zapmatic_app/.agent/plans/whatsapp-flows-fase-2-modulo-administrativo.md)
- [whatsapp-flows-fase-2-1-builder-visual.md](/www/wwwroot/app_zapmatic_app/.agent/plans/whatsapp-flows-fase-2-1-builder-visual.md)

### Banco

- nenhuma alteração adicional

### Código

- módulo novo isolado para Flow
- construtor visual inicial sobre o módulo Flow
- sem alteração no motor de envio
- sem alteração em chatbot
- sem alteração em autoresponder
- sem alteração em bulk

### Parecer técnico

- a base administrativa local do Flow foi aberta com sucesso
- o sistema atual permanece isolado das mudanças desta fase
- a próxima etapa passa a ser a integração Meta de create/assets/publish

### Decisão

**Apto para iniciar a Fase 2.2**

---

## Fase 3 - Cloud API Meta sync, publish e Single Message

### Status

**Concluída**

### Objetivo

Fazer o Flow sair do estado “apenas local” e passar a operar com a Meta:

- criação do draft remoto
- upload do `flow.json`
- publicação
- sincronização de status, preview e assets
- primeira integração real com `Single Message`

### Entregas

- aba `Flow` no `Single Message`
- envio `interactive.type=flow` para Cloud API
- ações Meta no módulo:
  - `meta_push_draft`
  - `meta_publish`
  - `meta_sync`
- rotas explícitas do módulo
- sincronização de preview e assets
- nova migration de persistência Meta
- documento técnico da fase criado

### Arquivos gerados/alterados

- [Whatsapp_send_message.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php)
- [info.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_send_message/Views/info.php)
- [Whatsapp_flow.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php)
- [Config/Routes.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Config/Routes.php)
- [update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/update.php)
- [content.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/content.php)
- [ajax_list.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/ajax_list.php)
- [menu.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/widget/menu.php)
- [content.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/widget/content.php)
- [20260414_whatsapp_flows_phase3_meta_sync.sql](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Migrations/20260414_whatsapp_flows_phase3_meta_sync.sql)
- [whatsapp-flows-fase-3-cloud-meta-sync.md](/www/wwwroot/app_zapmatic_app/.agent/plans/whatsapp-flows-fase-3-cloud-meta-sync.md)

### Banco

Colunas adicionadas em `sp_whatsapp_flows`:

- `categories_json`
- `preview_url`
- `preview_expires_at`

### Código

- Flow no `Single Message` agora forma payload oficial da Meta
- o módulo de Flow já cria/sincroniza/publica drafts na Meta
- o sistema passa a guardar preview oficial e assets do `flow.json`
- bulk, chatbot e autoresponder permanecem sem alteração nesta fase

### Testes executados

- `php -l` nos arquivos do módulo e do `Single Message`
- aplicação da migration de fase 3
- `DESCRIBE sp_whatsapp_flows`
- `php spark routes | rg 'whatsapp_flow/(ajax_list|save|meta_push_draft|meta_publish|meta_sync)'`
- teste interno de payload para:
  - categorias Meta
  - form-data de create/update
  - upload `FLOW_JSON`
  - payload `interactive.type=flow`

### Parecer técnico

- o builder local agora conversa com a Meta
- a operação de draft/publicação está pronta para ser usada no painel
- a trilha Cloud segue isolada e estável
- o próximo passo estrutural passa a ser o endpoint criptografado e depois o outbound com template+Flow

### Decisão

**Apto para iniciar a fase de endpoint criptografado**

---

## Fase 2.2 - Builder rico de jornada

### Status

**Concluída**

### Objetivo

Levar o editor visual de Flow para um nível realmente utilizável por usuário leigo, cobrindo capa com imagem, menu principal, submenu e captura final de dados.

### Entregas

- coluna `builder_state` adicionada para persistência do estado visual
- builder guiado com tela de boas-vindas e imagem
- menu principal com `NavigationList`
- múltiplas seções com imagem, badge, tags e descrição
- submenu por seção
- imagem por opção de submenu
- telas finais com formulário compartilhado
- geração automática de `routing_model`
- manutenção do modo `Advanced JSON`
- documento técnico da fase criado

### Arquivos gerados/alterados

- [Whatsapp_flow.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php)
- [update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/update.php)
- [20260414_whatsapp_flows_phase2_builder_state.sql](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Migrations/20260414_whatsapp_flows_phase2_builder_state.sql)
- [whatsapp-flows-fase-2-2-builder-rico.md](/www/wwwroot/app_zapmatic_app/.agent/plans/whatsapp-flows-fase-2-2-builder-rico.md)

### Banco

- coluna nova em `sp_whatsapp_flows`:
  - `builder_state`

### Código

- builder visual expandido para jornada rica
- persistência separada do estado visual
- sem alteração no runtime de envio atual
- sem alteração em chatbot
- sem alteração em autoresponder
- sem alteração em bulk

### Testes executados

- `php -l inc/core/Whatsapp_flow/Views/update.php`
- `php -l inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php`
- `php -l inc/core/Whatsapp_flow/Views/content.php`
- aplicação da migration `builder_state`
- `SHOW COLUMNS FROM sp_whatsapp_flows LIKE 'builder_state'`

### Parecer técnico

- o editor local agora cobre os cenários visuais mais comuns de Flow sem JSON manual
- a integração com a Meta continua isolada para a próxima fase
- a experiência atual já suporta imagem, menu, submenu e cadastro final

### Decisão

**Apto para iniciar a Fase 3**

---

## 4. Próximo passo oficial

### Fase 3 - Integração Meta: create, assets e publish

Objetivo da próxima fase:

- criar o Flow na Meta;
- sincronizar status e erros;
- subir assets;
- publicar e depreciar com segurança.

---

## 5. Observações operacionais

- esta operação é independente da modernização de IA;
- esta operação é independente dos ajustes recentes de bulk e placeholders;
- qualquer implementação futura deverá preservar:
  - envio comum;
  - templates existentes;
  - bulk atual;
  - chatbot atual;
  - autoresponder atual.

---

## 6. Validação real na Meta

Em `14/04/2026`, o Flow `TESTING` foi validado de ponta a ponta na Meta Cloud:

- o draft já existente foi reenviado com `flow.json` saneado
- o builder passou a gerar IDs compatíveis com as regras da Meta
- o backend também passou a corrigir IDs antigos antes do upload
- resultado oficial:
  - `validation_errors = []`
  - `status = DRAFT`
  - `health_status.can_send_message = AVAILABLE`

Parecer:

- a formação do Flow está correta para a Meta
- o draft está pronto para teste via `Single Message`
- a publicação pode ser feita depois de uma última conferência funcional no WhatsApp

### Atualização

Ainda em `14/04/2026`, o Flow `TESTING` foi efetivamente publicado:

- `status_meta = PUBLISHED`
- `validation_errors = []`
- `health_status.can_send_message = AVAILABLE`

Com isso, a trilha Cloud já cobre:

- criação local
- builder visual
- sync draft na Meta
- upload/validação de `flow.json`
- publicação real na Meta

Próximo passo recomendado:

- teste funcional via `Single Message` dentro da janela
- depois endpoint criptografado
- depois template com botão Flow para outbound/bulk

### Nova continuação

Ainda em `14/04/2026`, a operação Cloud passou a cobrir também a importação completa da WABA para o módulo local:

- ação para puxar todos os Flows já existentes na Meta pela conta Cloud vinculada;
- sincronização local em lote por `meta_flow_id`;
- atualização do estado oficial, preview, health status e `data_channel_uri`;
- persistência e exibição das `categories` oficiais da Meta no painel.

Com isso, o módulo não depende apenas de Flows criados localmente: ele também consegue puxar e alinhar os Flows já existentes na WABA, preservando a leitura completa do estado oficial.

### Endpoint criptografado

Ainda em `14/04/2026`, a trilha Cloud foi ampliada com o endpoint criptografado completo:

- registro local de endpoint por conta Cloud;
- geração de RSA 2048 bits no `writable`;
- upload da chave pública para `PHONE_NUMBER_ID/whatsapp_business_encryption`;
- refresh do estado de criptografia no painel;
- rota pública Node em `/flow_endpoint/:endpointIds`;
- verificação de assinatura HMAC;
- decrypt/encrypt do payload no padrão oficial da Meta;
- logging dos requests em `sp_whatsapp_flow_events`.

Validação real executada:

- endpoint criado para a conta `ELITEZAP`;
- `endpoint_uri = https://serverzapmatic.zapmatic.tec.br/flow_endpoint/69ded91c8bd99`;
- chave pública aceita pela Meta;
- retorno oficial:
  - `business_public_key_signature_status = VALID`;
- processo PM2 `zapmatic` reiniciado;
- teste público `GET` e `POST` criptografado concluídos com sucesso;
- evento `flow_endpoint_init` persistido no banco.

Próximo passo recomendado:

- evoluir a lógica de `data_exchange` por Flow;
- depois implementar `template + Flow button` para outbound e bulk.

### Runtime e retorno do Flow

Em `15/04/2026`, a trilha Cloud ganhou a primeira camada completa de runtime e retorno:

- a aba `Flow` do `Single Message` passou a permitir:
  - abertura por `navigate`
  - abertura por `data_exchange`
- o payload `Initial data JSON` passou a ser enviado como objeto real para a Meta;
- o endpoint passou a interpretar o `flow.json` salvo no sistema:
  - `screens`
  - `routing_model`
  - `NavigationList`
  - `builder_state` do menu guiado
- a conclusão do Flow agora devolve `SUCCESS` com:
  - token do Flow
  - referências do Flow
  - categoria e opção escolhidas
  - campos do formulário final
- o parser da Cloud agora reconhece `nfm_reply`;
- o retorno final do cliente ao Flow passou a ser gravado em `sp_whatsapp_flow_events` com `event_type = flow_reply`.

Validação interna executada:

- `INIT -> WELCOME`
- seleção da categoria principal -> tela de submenu
- seleção da opção -> tela final
- conclusão -> `SUCCESS` com `flow_token`, `category_id`, `option_id` e dados preenchidos
- parsing de `nfm_reply` com resumo textual e JSON estruturado

Parecer:

- o ciclo funcional do Flow na Cloud ficou mais próximo do comportamento final esperado;
- a próxima lacuna principal agora é outbound:
  - `template + Flow button`
  - integração com bulk

### Template + Flow Button

Em `15/04/2026`, a trilha Cloud passou a cobrir a base de outbound com `template + Flow button`:

- o helper oficial de template passou a montar `button/sub_type=flow`;
- o `Single Message` passou a encaminhar os metadados do botão Flow quando o template interno aprovado for usado;
- o runtime Node do bulk passou a gerar o mesmo componente oficial da Meta;
- o módulo `Modelo de botão` ganhou um novo tipo visual:
  - `Flow Button`
  - seleção de Flow publicado
  - JSON inicial opcional por botão
- a submissão oficial na Meta passou a aceitar `FLOW` como tipo suportado, validando se o Flow:
  - existe
  - está publicado
  - pertence à mesma conta Cloud da submissão

Validação interna executada:

- `php -l` dos arquivos PHP alterados;
- `node --check` do runtime `waziper.js`;
- teste interno de payload com:
  - `BODY` parametrizado
  - `button/sub_type=flow`
  - `flow_token`
  - `flow_action_data`

Resultado:

- o sistema já forma corretamente o payload oficial esperado pela Meta para `template + Flow button`;
- o próximo passo prático é o teste funcional pela interface:
  - salvar um `Modelo de botão` com `Flow Button`
  - submeter para análise
  - sincronizar quando `APPROVED`
  - testar no `Single Message`
  - depois validar no `Bulk`

### Correção do Single e testes reais de template oficial

Ainda em `15/04/2026`, foi corrigido um conflito importante no `Single Message`:

- a aba de `Template Oficial` havia deixado de aparecer porque o `type=6` foi reaproveitado para `Flow`;
- a aba oficial voltou a ser renderizada separadamente;
- `Flow` e `Template Oficial` agora seguem tipos independentes no envio manual;
- o backend voltou a aceitar templates aprovados da Meta sem conflitar com o módulo de Flow.

Testes backend executados:

- conta `ELITEZAP`, template aprovado `review_demo_apr2026_2_260407_094445`, destino `5521970402529`
  - requisição aceita pela Meta
  - retorno final do webhook: `failed`
  - erro externo da Meta: `131049` (`healthy ecosystem engagement`)
- conta `BM_ELIAS_WA-2013`, template aprovado `hello_world`, destino `5521970402529`
  - requisição aceita pela Meta
  - retorno final do webhook: `failed`
  - erro externo da Meta: `131031` (`Business Account locked`)
- conta `ELITEZAP`, template aprovado `review_demo_apr2026_260407_093043`, destino `551231993269`
  - requisição aceita pela Meta
  - webhook final: `sent` e `delivered`

Parecer final desta rodada:

- o módulo de `Template Oficial` voltou a funcionar no backend;
- os bloqueios remanescentes observados não são falha do sistema, e sim respostas externas da Meta por conta/destinatário/política.
