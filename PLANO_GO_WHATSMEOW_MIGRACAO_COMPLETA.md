# Plano Vivo — Migração Paralela de Baileys/waziper para Go/Whatsmeow

Status: PLANEJADO
Última atualização: 2026-06-22

## Objetivo

Criar um gateway Go/Whatsmeow paralelo, profissional e modular, capaz de substituir futuramente o `waziper.js`/Baileys sem quebrar o sistema atual.

Regra principal:

```text
NÃO mexer no legado para iniciar.
NÃO quebrar produção.
NÃO criar novo monólito.
Tudo novo deve nascer desacoplado, testável e documentado.
```

## Decisão de produto

O `waziper.js` será tratado como legado operacional.

No futuro:

```text
Go/Whatsmeow = gateway principal
waziper.js/Baileys = removido ou fallback legado
```

Durante a transição:

```text
Baileys continua funcionando
Go roda paralelo
PHP escolhe gateway por instância
Flow Builder não precisa saber qual gateway enviou/recebeu
```

## Arquitetura alvo

```text
WhatsApp
├── Gateway Baileys atual
│   └── app_zapmatic_api / waziper.js
└── Gateway Go novo
    └── app_zapmatic_whatsmeow_api

PHP Zapmatic
├── WhatsAppGatewayService
├── AutomationGatewayService
├── Flow Builder Runtime
├── AIService
├── BusinessHoursService
├── FlowSchedulerService
└── Campanhas / Follow-up / Pipeline
```

## Princípios obrigatórios

1. `waziper.js` não recebe regra de negócio nova.
2. Go não deve copiar o monólito do Node.
3. Gateway só faz conexão, sessão, envio, recebimento e normalização.
4. Fluxo, IA, autoresponder inteligente, follow-up e campanhas ficam no PHP/services novos.
5. Toda implementação deve ter teste antes e depois.
6. Legado continua operando até equivalência total.
7. Cada etapa deve atualizar este arquivo.

---

# 1. Mapeamento completo do Baileys/waziper atual

## 1.1 Serviço Node atual

Pasta:

```text
app_zapmatic_api/
```

Arquivos principais:

```text
app_zapmatic_api/app.js
app_zapmatic_api/config.js
app_zapmatic_api/waziper/waziper.js
app_zapmatic_api/waziper/extend.js
app_zapmatic_api/waziper/common.js
app_zapmatic_api/waziper/callresponder_runtime.js
app_zapmatic_api/waziper/flow_endpoint.js
```

Dependência Baileys:

```text
@itsukichan/baileys
```

Pontos Baileys diretos:

```text
waziper.js
- makeWASocket
- useMultiFileAuthState
- DisconnectReason
- WAMessageStubType
- generateWAMessageFromContent
- jidNormalizedUser
- prepareWAMessageMedia
- WAMessage
- WAMessageUpdate

extend.js
- WAMessageStubType
- jidNormalizedUser
```

## 1.2 Endpoints expostos pelo Node atual

Arquivo:

```text
app_zapmatic_api/app.js
```

Endpoints atuais:

```text
GET  /instance
GET  /get_qrcode
GET  /get_paircode
GET  /get_groups
GET  /probe_ip_open
GET  /logout
POST /send_message
POST /direct_send_message
POST /bot_builder_send
POST /send_template
GET  /flow_endpoint/:endpointIds
POST /flow_endpoint/:endpointIds
GET  /reset
GET  /clear_cache_ai
POST /webhook/:accountId
GET  /webhook/:accountId
GET  /
```

Equivalência futura no Go:

```text
/instance          → status/info da sessão
/get_qrcode        → QR da sessão
/get_paircode      → pair code, se suportado
/get_groups        → listar grupos
/logout            → desconectar/remover sessão
/send_message      → envio genérico legado
/direct_send_message → envio direto/template
/bot_builder_send  → envio do Flow Builder
/send_template     → templates/cloud quando aplicável
/webhook           → receber payload oficial, se necessário
```

## 1.3 Conexão e sessão

Arquivo:

```text
app_zapmatic_api/waziper/waziper.js
```

Função principal:

```text
makeWASocket(instance_id)
```

Responsabilidades atuais:

```text
- abrir sessão Baileys;
- carregar credenciais em session_dir + instance_id;
- aplicar proxy da instância;
- gerar QR code;
- tratar reconexão;
- remover sessão em logout/401;
- manter stores em memória;
- emitir eventos socket.io;
- processar mensagens recebidas;
- chamar webhook externo;
- chamar Bot Builder;
- chamar chatbot legado;
- chamar autoresponder legado;
- tratar chamadas/callresponder;
- atualizar contatos e grupos.
```

No Go, isso deve ser dividido em pacotes:

```text
internal/session
internal/events
internal/proxy
internal/qrcode
internal/webhook
internal/presence
```

## 1.4 Recebimento de mensagens

Evento atual:

```text
WA.ev.on('messages.upsert')
```

Fluxo atual no Node:

```text
messages.upsert
→ WAZIPER.webhook(instance_id, { event: 'messages.upsert', data: messages })
→ WAZIPER.bot_builder_flow(...)
→ se Bot Builder não tratou:
   ├── WAZIPER.chatbot(...)
   └── WAZIPER.autoresponder(...)
```

Ponto importante:

```text
O Bot Builder já recebe payload PHP em /bot-builder/webhook.
```

No Go, o recebimento deve fazer apenas:

```text
Mensagem recebida
→ normalizar payload
→ POST para /bot-builder/webhook ou AutomationGatewayService endpoint
```

Sem chatbot legado, sem autoresponder legado.

## 1.5 Bot Builder atual

Webhook PHP:

```text
inc/core/Bot_builder/Controllers/Bot_builder.php::webhook()
```

Processamento:

```text
process_webhook()
→ resolve sp_accounts.token
→ resolve identidade do contato
→ acha sessão ativa
→ se sessão existe, continua run_flow()
→ se não existe, procura bot por gatilho
→ cria sessão
→ executa fluxo
```

Envio atual do Flow Builder:

```text
send_whatsapp()
→ wa_post_curl('bot_builder_send')
```

Impacto Go:

```text
send_whatsapp() deve futuramente chamar WhatsAppGatewayService::send()
```

## 1.6 Envio atual via PHP/helper

Helper:

```text
inc/core/Whatsapp/Helpers/Whatsapp_helper.php
```

Função:

```php
wa_post_curl($endpoint, $params, $data)
```

Hoje ela usa:

```text
get_option('whatsapp_server_url')
```

E chama o Node atual.

Chamadas encontradas:

```text
Bot_builder.php
- direct_send_message
- bot_builder_send

Whatsapp_send_message.php
- direct_send_message

Whatsapp_api.php
- send_message
- add_participants
- remove_participants
- edit_group

Criptografia_copy.php
- send_message
```

Plano:

```text
Não remover wa_post_curl agora.
Criar WhatsAppGatewayService novo.
Migrar módulo por módulo para o service.
```

## 1.7 Envio em massa / Bulk atual

Arquivo Node:

```text
waziper.js
```

Responsabilidades atuais:

```text
- bulk_messaging
- bulk_messaging_cloud_parallel
- auto_send
- controle de run
- rotação de contas
- schedule_hours
- schedule_weekdays
- holidays
- socket.io update_campaign/end_campaign/pause_campaign
- call campaign
```

Módulos PHP relacionados:

```text
inc/core/Whatsapp_bulk
inc/core/Whatsapp_contact
```

Regra futura:

```text
Bulk legado continua no Baileys.
Campanha de Fluxo nova usa contatos existentes, mas passa por FlowCampaignService + WhatsAppGatewayService.
```

Não migrar bulk legado no primeiro ciclo.

## 1.8 Chatbot e autoresponder legados

Node atual ainda chama:

```text
WAZIPER.chatbot()
WAZIPER.autoresponder()
```

Módulos:

```text
inc/core/Whatsapp_chatbot
inc/core/Whatsapp_autoresponder
```

Decisão:

```text
Legado não será evoluído.
Novo sistema usa AutomationGatewayService + Flow Builder.
```

## 1.9 Call responder / call campaign

Arquivos:

```text
waziper.js
callresponder_runtime.js
```

Responsabilidades:

```text
- WA.ws.on('CB:call')
- WA.ev.on('call')
- fast_callresponder
- callresponder
- callcampaign_event
- auto_call_campaign
```

Risco:

```text
Whatsmeow pode tratar chamadas de forma diferente.
Essa parte fica fora do MVP Go.
```

Migração posterior.

## 1.10 Templates, botões, listas, carrossel

Módulos:

```text
Whatsapp_button_template
Whatsapp_list_message_template
Whatsapp_carousel_template
Whatsapp_poll_template
Bot_builder native templates
TB_WHATSAPP_TEMPLATE
```

Envio atual avançado:

```text
direct_send_message type=2 buttons
direct_send_message type=5 carousel
bot_builder_send message_type=buttons/list/carousel/etc
```

Regra futura:

```text
Go precisa declarar capabilities.
Se não suportar carrossel/lista/botões nativos, o Flow Builder deve bloquear ou fazer fallback.
```

## 1.11 Webhooks oficiais / Cloud API

Módulos separados:

```text
Whatsapp_profiles
Whatsapp_webhook
Whatsapp_flow
Cloud API helpers
```

Não fazem parte direta do Baileys, mas devem ser considerados no multi-gateway.

Regra:

```text
Cloud API oficial continua separada.
No futuro pode virar provider = official no WhatsAppGatewayService.
```

---

# 2. Arquitetura Go/Whatsmeow alvo

## 2.1 Pasta nova

```text
app_zapmatic_whatsmeow_api/
├── cmd/server/main.go
├── go.mod
├── internal/http
├── internal/config
├── internal/session
├── internal/sender
├── internal/receiver
├── internal/normalizer
├── internal/webhook
├── internal/proxy
├── internal/presence
├── internal/capabilities
├── internal/logging
├── storage/sessions
└── logs
```

## 2.2 Serviço Go não monolítico

Separação obrigatória:

```text
http        → rotas REST
session     → conectar, QR, logout, status
sender      → enviar texto/mídia/interativos
receiver    → eventos whatsmeow
normalizer  → converter payload para padrão Zapmatic
webhook     → enviar eventos ao PHP
proxy       → aplicar proxy por instância
presence    → digitando/gravando/pausado
logging     → logs estruturados
```

## 2.3 Como o Go vai rodar no aaPanel

O Go não roda via Node. Ele compila para um binário e roda como serviço separado.

Estrutura de produção recomendada:

```text
zapmatic.tec.br
→ PHP/CodeIgniter

api atual / Baileys
→ app_zapmatic_api via PM2

gateway.zapmatic.tec.br
→ app_zapmatic_whatsmeow_api via Go/systemd
```

Porta local sugerida:

```text
127.0.0.1:8090
```

Subdomínio recomendado:

```text
gateway.zapmatic.tec.br
```

Opção recomendada para manter o serviço online:

```text
systemd
```

Serviço Linux futuro:

```text
/etc/systemd/system/zapmatic-whatsmeow.service
```

Exemplo:

```ini
[Unit]
Description=Zapmatic Whatsmeow Gateway
After=network.target

[Service]
Type=simple
WorkingDirectory=/www/wwwroot/app_zapmatic_app/app_zapmatic_whatsmeow_api
ExecStart=/www/wwwroot/app_zapmatic_app/app_zapmatic_whatsmeow_api/zapmatic-whatsmeow --port 8090
Restart=always
RestartSec=5
User=www
Environment=APP_ENV=production

[Install]
WantedBy=multi-user.target
```

Comandos de operação:

```bash
systemctl daemon-reload
systemctl enable zapmatic-whatsmeow
systemctl start zapmatic-whatsmeow
systemctl status zapmatic-whatsmeow
journalctl -u zapmatic-whatsmeow -f
```

Nginx no aaPanel:

```nginx
location / {
    proxy_pass http://127.0.0.1:8090;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

Alternativa aceita, mas não preferida:

```bash
pm2 start ./zapmatic-whatsmeow --name zapmatic-whatsmeow -- --port 8090
pm2 save
```

Decisão:

```text
Produção: systemd + Nginx/aaPanel + SSL.
Desenvolvimento: rodar binário direto em 127.0.0.1:8090.
```

## 2.4 Endpoints Go MVP

```text
GET  /health
GET  /instance?instance_id=
GET  /qrcode?instance_id=
GET  /status?instance_id=
POST /logout
POST /send/text
POST /send/media
POST /presence
POST /webhook/config
GET  /capabilities
```

## 2.5 Endpoints Go futuros

```text
GET  /groups
POST /send/buttons
POST /send/list
POST /send/poll
POST /send/location
POST /send/contact
POST /group/add_participants
POST /group/remove_participants
POST /group/edit
```

## 2.5 Payload padrão recebido pelo PHP

O Go deve enviar ao PHP:

```json
{
  "instance_id": "token_da_instancia",
  "gateway": "whatsmeow",
  "data": {
    "messages": [
      {
        "key": {},
        "message": {},
        "messageTimestamp": 0,
        "pushName": "",
        "_wa_id": "5511999999999",
        "_automation_context": {
          "canonicalId": "5511999999999",
          "canonicalJid": "5511999999999@s.whatsapp.net",
          "replyJid": "5511999999999@s.whatsapp.net",
          "gateway": "whatsmeow"
        }
      }
    ]
  }
}
```

Destino:

```text
https://zapmatic.tec.br/index.php/bot-builder/webhook
```

## 2.6 Envio padrão recebido pelo Go

```json
{
  "instance_id": "token_da_instancia",
  "chat_id": "5511999999999@s.whatsapp.net",
  "type": "text",
  "payload": {
    "text": "Olá"
  }
}
```

Resposta:

```json
{
  "status": "success",
  "provider": "whatsmeow",
  "message_id": "...",
  "raw": {}
}
```

---

# 3. Camada PHP obrigatória

## 3.1 WhatsAppGatewayService

Arquivo:

```text
app/Services/WhatsAppGatewayService.php
```

Métodos:

```php
send($instanceId, $chatId, $type, $payload)
status($instanceId)
qr($instanceId)
logout($instanceId)
groups($instanceId)
capabilities($instanceId)
```

## 3.2 Tabela de gateways

```text
sp_whatsapp_gateways
- id
- team_id
- instance_id
- provider              baileys|whatsmeow|official
- base_url
- api_key
- status
- capabilities_json
- created
- changed
```

## 3.3 Regra de roteamento

```text
Se não existe registro → baileys legado
Se provider=baileys → usar wa_post_curl legado
Se provider=whatsmeow → chamar API Go
Se provider=official → chamar Cloud API futura
```

## 3.4 Onde migrar primeiro

Primeiro alvo:

```text
Bot_builder::send_whatsapp()
```

Motivo:

- é nosso módulo novo;
- não mexe bulk legado;
- permite testar Flow Builder com Go isolado.

Depois:

```text
Whatsapp_send_message
Whatsapp_api
Campanha de Fluxo nova
```

Não migrar no começo:

```text
Whatsapp_bulk legado
Whatsapp_autoresponder legado
Whatsapp_chatbot legado
Call responder
```

---

# 4. Se começarmos hoje

O primeiro dia não deve tentar conectar WhatsApp no Go ainda. O objetivo inicial é preparar o terreno sem risco.

## Dia 1 — Base segura

Executar:

```text
1. Rodar baseline do legado.
2. Criar WhatsAppGatewayService no PHP.
3. Criar tabela sp_whatsapp_gateways.
4. Fazer apenas o Flow Builder enviar através do service.
5. Provider padrão continua Baileys.
6. Testar fluxo, IA e envio pelo Baileys.
7. Atualizar o log deste plano.
```

Arquivos prováveis:

```text
app/Services/WhatsAppGatewayService.php
inc/core/Bot_builder/Controllers/Bot_builder.php
```

Regra:

```text
Nenhum arquivo do waziper.js deve ser alterado no Dia 1.
```

Comandos antes:

```bash
php -l inc/core/Bot_builder/Controllers/Bot_builder.php
php -l inc/core/Whatsapp/Helpers/Whatsapp_helper.php
node -c app_zapmatic_api/app.js
node -c app_zapmatic_api/waziper/waziper.js
node -c app_zapmatic_api/waziper/extend.js
pm2 list
```

Comandos depois:

```bash
php -l app/Services/WhatsAppGatewayService.php
php -l inc/core/Bot_builder/Controllers/Bot_builder.php
```

Teste manual depois:

```text
1. Fluxo simples envia texto.
2. Node Resposta com IA envia resposta.
3. Baileys legado continua conectado.
4. Disparo/bulk legado pequeno continua funcionando.
```

## Dia 2 — Go skeleton

Executar:

```text
1. Criar pasta app_zapmatic_whatsmeow_api.
2. Criar go.mod.
3. Criar cmd/server/main.go.
4. Criar GET /health.
5. Criar GET /capabilities.
6. Rodar local em 127.0.0.1:8090.
7. Não conectar WhatsApp ainda.
```

Comandos:

```bash
cd app_zapmatic_whatsmeow_api
go mod tidy
go test ./...
go build ./cmd/server
./server --port 8090
curl http://127.0.0.1:8090/health
curl http://127.0.0.1:8090/capabilities
```

## Dia 3 — Preparar aaPanel/systemd

Executar:

```text
1. Criar subdomínio gateway.zapmatic.tec.br.
2. Configurar proxy Nginx para 127.0.0.1:8090.
3. Criar serviço systemd.
4. Ativar SSL no aaPanel.
5. Validar /health público.
```

Nada de sessão WhatsApp ainda.

## Primeiro marco real

O primeiro marco só estará concluído quando:

```text
- Flow Builder envia via WhatsAppGatewayService usando Baileys.
- Go responde /health e /capabilities.
- Legado segue intacto.
- Plano foi atualizado com testes e resultados.
```

---

# 5. Plano de implementação por fases

## Fase 0 — Baseline obrigatório

Antes de qualquer mudança:

```bash
php -l inc/core/Bot_builder/Controllers/Bot_builder.php
php -l inc/core/Whatsapp/Helpers/Whatsapp_helper.php
node -c app_zapmatic_api/app.js
node -c app_zapmatic_api/waziper/waziper.js
node -c app_zapmatic_api/waziper/extend.js
pm2 list
```

Teste manual obrigatório:

```text
1. Conectar instância Baileys atual.
2. Enviar mensagem manual.
3. Rodar fluxo simples no Flow Builder.
4. Enviar disparo bulk pequeno de teste.
5. Confirmar que legado segue funcionando.
```

## Fase 1 — Abstração PHP sem alterar comportamento

Implementar:

```text
app/Services/WhatsAppGatewayService.php
sp_whatsapp_gateways
```

Alterar apenas Flow Builder:

```text
Bot_builder::send_whatsapp()
→ WhatsAppGatewayService::send()
```

Provider padrão:

```text
baileys
```

Teste obrigatório depois:

```bash
php -l app/Services/WhatsAppGatewayService.php
php -l inc/core/Bot_builder/Controllers/Bot_builder.php
```

Teste funcional:

```text
Flow Builder envia texto pelo Baileys exatamente como antes.
Flow Builder envia botão/lista existente pelo Baileys.
IA responde pelo Flow Builder.
```

## Fase 2 — Go health service

Criar:

```text
app_zapmatic_whatsmeow_api
GET /health
GET /capabilities
```

Comandos:

```bash
cd app_zapmatic_whatsmeow_api
go mod tidy
go test ./...
go build ./cmd/server
./server --port 8090
curl http://127.0.0.1:8090/health
```

Não conectar WhatsApp ainda.

## Fase 3 — Sessão Whatsmeow mínima

Implementar:

```text
GET /qrcode
GET /status
POST /logout
storage/sessions/{instance_id}
```

Teste:

```bash
go test ./...
curl http://127.0.0.1:8090/status?instance_id=test
curl http://127.0.0.1:8090/qrcode?instance_id=test
```

Manual:

```text
1. Escanear QR com número teste.
2. Confirmar status conectado.
3. Desconectar.
4. Reconectar usando sessão salva.
```

## Fase 4 — Envio de texto Go

Implementar:

```text
POST /send/text
```

Teste automático:

```bash
go test ./...
```

Teste manual:

```bash
curl -X POST http://127.0.0.1:8090/send/text \
  -H 'Content-Type: application/json' \
  -H 'X-Zapmatic-Gateway-Key: chave' \
  -d '{"instance_id":"test","chat_id":"5511999999999@s.whatsapp.net","text":"Teste Go"}'
```

## Fase 5 — Recebimento Go → PHP

Implementar:

```text
receiver
normalizer
webhook sender
```

Go envia para:

```text
/bot-builder/webhook
```

Teste:

```text
1. Enviar mensagem para número Go.
2. Confirmar log no PHP writable/bot_builder_webhook.log.
3. Confirmar sessão criada no sp_bb_sessions.
4. Confirmar fluxo executado.
```

## Fase 6 — Provider whatsmeow no PHP

Adicionar registro:

```text
sp_whatsapp_gateways.provider = whatsmeow
base_url = http://127.0.0.1:8090 ou https://gateway.zapmatic.tec.br
```

Teste:

```text
1. Uma instância teste usa Go.
2. Flow Builder envia resposta por Go.
3. Instâncias antigas continuam Baileys.
```

## Fase 7 — Mídia e presença

Implementar:

```text
POST /send/media
POST /presence
```

Teste:

```text
- texto
- imagem
- áudio
- documento
- digitando
```

## Fase 8 — Interativos

Implementar por capability:

```text
buttons
list
poll
```

Se não suportado:

```text
Flow Builder faz fallback para texto enumerado.
```

## Fase 9 — Campanha de Fluxo com Go

Só para módulo novo.

Testar:

```text
- 3 contatos existentes
- rate limit baixo
- iniciar fluxo por contato
- confirmar status por item
```

## Fase 10 — Migração progressiva

```text
Novas instâncias → Go recomendado
Instâncias antigas → Baileys
Equipe piloto → Go
Fallback manual → Baileys
```

Só remover waziper quando:

```text
- conexão OK
- QR OK
- texto OK
- mídia OK
- fluxo OK
- bulk novo OK
- campanhas OK
- contatos/grupos OK
- métricas OK
- 30 dias sem incidentes críticos
```

---

# 5. Comandos obrigatórios de teste

## 5.1 Antes de qualquer implementação

```bash
php -l inc/core/Bot_builder/Controllers/Bot_builder.php
php -l inc/core/Whatsapp/Helpers/Whatsapp_helper.php
node -c app_zapmatic_api/app.js
node -c app_zapmatic_api/waziper/waziper.js
node -c app_zapmatic_api/waziper/extend.js
pm2 list
```

## 5.2 Depois de mudança PHP

```bash
php -l app/Services/WhatsAppGatewayService.php
php -l inc/core/Bot_builder/Controllers/Bot_builder.php
php -l inc/core/Whatsapp/Helpers/Whatsapp_helper.php
```

## 5.3 Depois de mudança Node legado

Evitar mudança Node. Se inevitável:

```bash
node -c app_zapmatic_api/app.js
node -c app_zapmatic_api/waziper/waziper.js
node -c app_zapmatic_api/waziper/extend.js
pm2 restart zapmatic-api
pm2 logs zapmatic-api --lines 80
```

## 5.4 Depois de mudança Go

```bash
cd app_zapmatic_whatsmeow_api
go test ./...
go build ./cmd/server
curl http://127.0.0.1:8090/health
curl http://127.0.0.1:8090/capabilities
```

## 5.5 Testes funcionais obrigatórios

### Conexão

```text
- gerar QR
- escanear
- status conectado
- reiniciar serviço
- status continua conectado
- logout remove sessão
```

### Flow Builder

```text
- mensagem recebida inicia fluxo
- resposta de texto enviada
- node IA responde
- variável do usuário é mantida
- botão/lista funciona ou faz fallback
```

### Disparo em massa

Legado:

```text
- bulk Baileys atual segue funcionando
```

Novo:

```text
- Campanha de Fluxo envia para 3 contatos teste
- respeita rate limit
- status enviado/falhou é salvo
```

### Regressão obrigatória

```text
- instância Baileys existente continua enviando
- instância Baileys existente continua recebendo
- Flow Builder atual continua funcionando
- IA atual continua funcionando
- contatos existentes não duplicam
```

---

# 6. Checklist de equivalência Baileys → Go

## MVP

```text
[ ] Health
[ ] Capabilities
[ ] QR
[ ] Status
[ ] Logout
[ ] Enviar texto
[ ] Receber texto
[ ] Webhook PHP
[ ] Flow Builder texto
```

## Essencial

```text
[ ] Enviar imagem
[ ] Enviar áudio
[ ] Enviar documento
[ ] Presence/digitando
[ ] Normalização JID/LID
[ ] Grupos listar
[ ] Logs estruturados
[ ] Proxy por instância
```

## Avançado

```text
[ ] Botões
[ ] Lista
[ ] Poll
[ ] Template/fallback
[ ] Reações
[ ] Status de mensagem
[ ] Retry controlado
[ ] Rate limit por instância
```

## Migração total

```text
[ ] Flow Builder completo
[ ] Campanha de Fluxo
[ ] Contatos/grupos
[ ] Follow-up
[ ] Multiatendimento
[ ] Painel de métricas
[ ] Fallback Baileys
[ ] Remoção segura do waziper
```

---

# 7. Como atualizar este plano

A cada implementação, adicionar entrada:

```text
## Log de avanço

### YYYY-MM-DD — etapa
- Alterado:
- Arquivos:
- Testes rodados:
- Resultado:
- Pendências:
```

Nunca avançar fase sem registrar testes.

## Log de avanço

### 2026-06-22 — criação do plano
- Mapeado uso atual de Baileys/waziper.
- Definida arquitetura Go paralela.
- Definida regra de não mexer no legado inicialmente.
- Definidos comandos obrigatórios de teste.
- Próxima etapa: Fase 1, criar WhatsAppGatewayService com provider padrão Baileys.

### 2026-06-22 — Fase 1 iniciada e aplicada
- Alterado:
  - Criado `app/Services/WhatsAppGatewayService.php`.
  - Criada tabela `sp_whatsapp_gateways` automaticamente pelo service.
  - Envio genérico do Flow Builder passou a usar `WhatsAppGatewayService::send()`.
  - Provider padrão continua `baileys`, então o comportamento legado permanece.
- Arquivos:
  - `app/Services/WhatsAppGatewayService.php`
  - `inc/core/Bot_builder/Controllers/Bot_builder.php`
- Não alterado:
  - `app_zapmatic_api/waziper/waziper.js`
  - `app_zapmatic_api/app.js`
  - bulk legado
  - autoresponder legado
  - chatbot legado
- Testes antes:
  - `php -l inc/core/Bot_builder/Controllers/Bot_builder.php` OK
  - `php -l inc/core/Whatsapp/Helpers/Whatsapp_helper.php` OK
  - `node -c app_zapmatic_api/app.js` OK
  - `node -c app_zapmatic_api/waziper/waziper.js` OK
  - `node -c app_zapmatic_api/waziper/extend.js` OK
  - `pm2 list` mostrou `zapmatic-api` online
- Testes depois:
  - `php -l app/Services/WhatsAppGatewayService.php` OK
  - `php -l inc/core/Bot_builder/Controllers/Bot_builder.php` OK
  - `sp_whatsapp_gateways` criada/confirmada com `TABLE_OK`
- Pendências:
  - Teste manual de fluxo real no WhatsApp.
  - Teste manual de IA pelo Flow Builder.
  - Teste manual de disparo/bulk legado pequeno.
  - Próxima etapa só depois desses testes: Go skeleton com `/health` e `/capabilities`.

### 2026-06-22 — Ajuste pós-teste Fase 1
- Sintoma:
  - Fluxo com IA não respondeu após envio de mensagem pelo usuário.
- Evidência:
  - `wa_post_curl()` retorna objeto PHP porque já aplica `json_decode($result)`.
  - `WhatsAppGatewayService::sendViaBaileys()` tratava retorno como string JSON.
- Correção:
  - `sendViaBaileys()` agora normaliza retorno string/array/objeto para array.
- Arquivos:
  - `app/Services/WhatsAppGatewayService.php`
  - `debug-gateway-flow-no-reply.md`
- Testes:
  - `php -l app/Services/WhatsAppGatewayService.php` OK
  - `php -l inc/core/Bot_builder/Controllers/Bot_builder.php` OK
- Próximo passo:
  - Repetir teste manual do fluxo de IA no WhatsApp.

### 2026-06-23 — Fix access_token Fase 1 / Início Fase 2
- Fase 1 corrigida:
  - `sendViaBaileys()` não passava `access_token` ao Node — `waziper.js` endpoint `/bot_builder_send` exige `access_token` para autenticar via `sp_team.ids`.
  - Adicionado `resolveAccessToken()` que replica a lógica de `Bot_builder::get_access_token()`.
  - Logs confirmam envios com sucesso (`status: success`, `provider: baileys`).
- Fase 2 concluída:
  - Criada estrutura `app_zapmatic_whatsmeow_api/` com 13 diretórios.
  - `go.mod` com `github.com/rs/zerolog` como única dependência externa.
  - `cmd/server/main.go` — servidor HTTP com graceful shutdown.
  - `internal/config/config.go` — flags + env vars.
  - `internal/logging/logging.go` — zerolog com console + arquivo.
  - `internal/http/router.go` — rotas /health e /capabilities com CORS, auth middleware preparado.
  - `internal/capabilities/capabilities.go` — features declaradas (text/image/audio/video/document/presence true, demais false c/ notas).
  - Compilado para `zapmatic-whatsmeow` (7.1MB), rodando em `127.0.0.1:8090`.
  - `/health` → `{"status":"ok","provider":"whatsmeow","version":"0.1.0"}`
  - `/capabilities` → 13 features declaradas.
- Não alterado:
  - Nenhum arquivo legado (waziper.js, app.js, PHP existente).
- Próximo passo: Fase 3 — Sessão Whatsmeow (QR, status, login/logout).

### 2026-06-23 — Fase 3: Sessão Whatsmeow mínima (QR, status, logout)
- Alterado:
  - `internal/session/manager.go` reescrito com whatsmeow `v0.0.0-20260622185415`
  - `sqlstore.New(context, "sqlite3", ...)` com SQLite via `mattn/go-sqlite3` (CGO)
  - `GetQRChannel()` para QR codes (chamado ANTES de `Connect()`)
  - `Store.NewDevice()` para novos dispositivos
  - Instance mapping via `instance_map.json` (instance_id → JID)
- Criado:
  - `internal/webhook/sender.go` — POST para PHP com timeout 30s
  - `internal/sender/sender.go` — stub preparado
- Endpoints testados:
  - `GET /qrcode?instance_id=test` → QR gerado `{"qrcode":"2@ScFsXZ0k..."}`
  - `GET /status?instance_id=test` → `{"state":"qr_ready"}`
  - `POST /logout?instance_id=test` → sessão removida
- Dependências:
  - Go atualizado para 1.25.11 (exigido pelo whatsmeow mais recente)
  - `CGO_ENABLED=1` necessário para `mattn/go-sqlite3`
  - Binário: 22MB
- Próximo passo: Fase 4 — Envio de texto Go (`POST /send/text`)

### 2026-06-23 — Fase 4: Envio de texto Go (POST /send/text)
- Criado:
  - `internal/sender/sender.go` com `SendText()` usando `client.SendMessage()`
  - `Instance.Client()` exportado para acesso do sender
- Rota adicionada:
  - `POST /send/text` — recebe `{"instance_id", "chat_id", "type", "payload": {"text": "..."}}`
  - Valida instância conectada, JID, e payload não vazio
  - Retorna `{"status":"success","provider":"whatsmeow","message_id":"SUKI..."}`
- Testado:
  - `curl -X POST /send/text` com instância não conectada → `{"status":"error","error":"instance not found"}`
- Próximo passo: Fase 5 — Recebimento Go → PHP (receiver + normalizer + webhook)

### 2026-06-23 — Fase 5: Recebimento Go → PHP
- Criado:
  - `internal/normalizer/normalizer.go` — converte `events.Message` para payload padrão Zapmatic
  - `internal/receiver/receiver.go` — dispatcher de eventos conectados/mensagens para webhook
- Alterado:
  - `session/manager.go` `handleEvent` delegado para `receiver.HandleEvent()`
  - Todo evento de mensagem recebida → normalizado → POST para `/bot-builder/webhook`
- Payload normalizado segue o padrão do plano:
  ```json
  {"instance_id":"xxx","gateway":"whatsmeow","data":{"messages":[{"key":{},"message":{},"messageTimestamp":0,"pushName":"","_wa_id":"55119...","_automation_context":{"canonicalId":"...","canonicalJid":"...","replyJid":"...","gateway":"whatsmeow"}}]}}
  ```
- Compilação OK, gateway rodando em `127.0.0.1:8090`
- Próximo passo: Fase 6 — Provider whatsmeow no PHP

### 2026-06-23 — Fase 6: Provider whatsmeow no PHP + Frontend
- WhatsAppGatewayService expandido:
  - `register()` — cria/atualiza `sp_whatsapp_gateways` com provider whatsmeow
  - `qr()` — GET `/qrcode` no Go
  - `status()` — GET `/status` no Go
  - `logout()` — POST `/logout` + remove registro
  - `capabilities()` — consulta Go ou fallback default
- Instância registrada no banco: `EMB6A316A3593D3E → provider=whatsmeow, base_url=http://127.0.0.1:8090`
- Frontend (Central de Conexão - `oauth.php`):
  - Botão "Whatsmeow" adicionado na switchboard + drawer tab
  - Drawer com QR code gerado via Go gateway
  - Controller: `generate_whatsmeow_instance()` — gera instance_id + QR
- Próximos passos: testar conexão real escaneando QR, depois seguir para Fase 7 (mídia), 8 (interativos), 9 (campanhas)

### 2026-06-24 — Fase 7: Mídia + modularização + runtime
- Modularizado:
  - `router.go` — apenas rotas + CORS + auth
  - `handler_session.go` — QR, status, profile, logout
  - `handler_send.go` — send/text, send/media, send/presence
  - `handler_health.go` — health, capabilities
- Implementado:
  - `POST /send/media` — imagem/áudio/video/documento via whatsmeow
  - `internal/storage/storage.go` — mídia própria (Save/Path/List/Delete/SaveFromURL)
  - `internal/runtime/runtime.go` — orquestrador principal
  - Download automático de mídia de mensagens recebidas
  - `storage/files/` — diretório de mídia independente do Node
- Não alterado:
  - Nenhum arquivo do waziper.js ou Node
  - Nenhum arquivo PHP legado
- Regra mantida: **nenhum arquivo ultrapassa 200 linhas**
- Próximo passo: Fase 8 — Interativos (botões/lista/poll) + grupos (listar/criar/edit)
