# WhatsApp Flows - Fase 3

## Objetivo

Entregar a primeira camada operacional real da Cloud API para WhatsApp Flows:

- sincronizar o rascunho local com a Meta
- criar o Flow remoto quando ainda não existir
- enviar o `flow.json` para validação
- publicar o Flow
- sincronizar status, preview e assets de volta para o banco local

---

## Escopo implementado

### Controller

No controller [Whatsapp_flow.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php):

- refatorado o `save()` para reaproveitar a persistência local
- adicionadas as ações:
  - `meta_push_draft`
  - `meta_publish`
  - `meta_sync`
- adicionados helpers internos para:
  - persistência local reutilizável
  - normalização de categorias Meta
  - criação de payloads de `create/update`
  - criação de payload do upload `FLOW_JSON`
  - request autenticada na Graph API
  - sincronização de status e preview
  - sincronização de assets `FLOW_JSON`
  - logging técnico em `sp_whatsapp_flow_events`
  - logging bruto em `writable/logs/whatsapp_flow_meta.log`

### Frontend

Na tela [update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/update.php):

- seleção persistida de categorias Meta
- exibição de preview oficial quando disponível
- bloco operacional com botões:
  - `Save + sync draft on Meta`
  - `Save + publish on Meta`
  - `Refresh Meta status`

Na listagem:

- [content.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/content.php) atualizado para refletir a fase atual
- [ajax_list.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/ajax_list.php) ganhou atalhos rápidos de:
  - sincronizar rascunho
  - publicar
  - atualizar status

### Rotas

Em [Config/Routes.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Config/Routes.php):

- rotas explícitas adicionadas para:
  - `save`
  - `ajax_list`
  - `meta_push_draft`
  - `meta_publish`
  - `meta_sync`

### Banco

Migration criada:

- [20260414_whatsapp_flows_phase3_meta_sync.sql](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Migrations/20260414_whatsapp_flows_phase3_meta_sync.sql)

Colunas adicionadas em `sp_whatsapp_flows`:

- `categories_json`
- `preview_url`
- `preview_expires_at`

---

## Fluxo operacional entregue

### Save + sync draft on Meta

1. salva ou atualiza o Flow local
2. cria o Flow na Meta se ainda não existir
3. atualiza nome/categorias/endpoint na Meta
4. envia `flow.json` para validação
5. sincroniza `status_meta`, `health_status`, preview e assets

### Save + publish on Meta

1. executa todo o fluxo de sincronização do rascunho
2. publica o Flow na Meta
3. atualiza o status local com o retorno oficial

### Refresh Meta status

1. busca o Flow oficial pelo `meta_flow_id`
2. atualiza:
  - `status_meta`
  - `json_version`
  - `data_api_version`
  - `health_status`
  - `preview_url`
  - `preview_expires_at`
  - `last_meta_error`
  - `last_sync_at`

---

## Testes executados

### Sintaxe

- `php -l inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php`
- `php -l inc/core/Whatsapp_flow/Views/update.php`
- `php -l inc/core/Whatsapp_flow/Views/content.php`
- `php -l inc/core/Whatsapp_flow/Views/ajax_list.php`
- `php -l inc/core/Whatsapp_flow/Config/Routes.php`

### Banco

- aplicação da migration `20260414_whatsapp_flows_phase3_meta_sync.sql`
- `DESCRIBE sp_whatsapp_flows`

### Rotas

- `php spark routes | rg 'whatsapp_flow/(ajax_list|save|meta_push_draft|meta_publish|meta_sync)'`

### Teste interno de payload

Teste de laboratório feito com o Flow real `TESTING`:

- categorias normalizadas:
  - `["customer_support","survey","invalid"]` -> `["CUSTOMER_SUPPORT","SURVEY"]`
  - `["lead_generation","other","OTHER"]` -> `["LEAD_GENERATION","OTHER"]`
- payload de create/update:
  - `name = TESTING`
  - `categories = ["CUSTOMER_SUPPORT","OTHER"]`
  - `endpoint_uri = https://example.com/flow-endpoint`
- payload de upload:
  - `asset_type = FLOW_JSON`
  - `name = flow.json`
  - `file = CURLFile(application/json)`

### Validação real na Meta

Teste real executado em `14/04/2026` no Flow `TESTING` (`meta_flow_id = 965899069303473`):

- draft existente localizado na Meta com `status = DRAFT`
- erro inicial reproduzido e diagnosticado:
  - IDs de tela gerados pelo builder continham números
  - a Meta rejeitava `FINANCEIRO_EMITIR_FATURA_1_1`
- correção aplicada em duas camadas:
  - builder visual agora gera IDs compatíveis com a Meta
  - upload do `flow.json` sanitiza IDs antigos antes de enviar
- novo upload executado com sucesso:
  - `validation_errors = []`
  - `health_status.can_send_message = AVAILABLE`
  - preview mantido ativo no draft

### Publicação real na Meta

Na sequência, o mesmo Flow foi publicado com sucesso:

- chamada `POST /965899069303473/publish`
- resposta oficial:
  - `success = true`
- consulta posterior do Flow:
  - `status = PUBLISHED`
  - `validation_errors = []`
  - `health_status.can_send_message = AVAILABLE`

O estado local também foi sincronizado:

- `status_meta = PUBLISHED`
- `published_at` preenchido
- `last_meta_error = null`
- evento `meta_publish` registrado em `sp_whatsapp_flow_events`

---

## Parecer técnico

- a fase Cloud de draft/publish/status já está operacional no módulo
- o `Single Message` continua isolado e intacto
- bulk, chatbot e autoresponder não foram alterados nesta etapa
- ainda falta a fase de endpoint criptografado e, depois, a trilha de template com botão Flow para outbound
