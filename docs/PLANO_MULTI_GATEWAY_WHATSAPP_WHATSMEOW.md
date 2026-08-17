# Plano — Multi-Gateway WhatsApp com Whatsmeow

## Decisão de arquitetura

`waziper.js` deve virar apenas um gateway de conexão/API WhatsApp.

A regra de negócio nova fica fora dele:

```text
WhatsApp Gateway
├── Baileys atual / waziper
└── Whatsmeow futuro / Go

Backend Zapmatic
├── AutomationGatewayService
├── Flow Builder Runtime
├── AIService
├── BusinessHoursService
├── FlowSchedulerService
└── Follow-up/Pipeline
```

## Viabilidade

É possível incluir `whatsmeow` como API secundária, mas não deve ser colocado dentro do `waziper.js`.

O correto é criar um serviço separado em Go:

```text
app_zapmatic_whatsmeow_api/
├── main.go
├── internal/http
├── internal/session
├── internal/send
├── internal/webhook
└── storage
```

Esse serviço expõe endpoints compatíveis com a camada PHP.

## Estado atual do sistema

Hoje o sistema usa:

```text
app_zapmatic_api/app.js
app_zapmatic_api/waziper/waziper.js
Baileys
```

Endpoints atuais importantes:

```text
GET /instance
GET /get_qrcode
GET /get_paircode
GET /get_groups
POST /bot_builder_send
POST /direct_send_message
```

O Flow Builder recebe mensagens por:

```text
inc/core/Bot_builder/Controllers/Bot_builder.php::webhook()
```

E envia mensagens por:

```text
send_whatsapp()
→ wa_post_curl('bot_builder_send')
```

## Problema se misturar tudo no waziper

Não recomendado:

```text
waziper.js
├── Baileys
├── Whatsmeow
├── Autoresponder
├── Flow Builder
├── Follow-up
└── IA
```

Isso vira monólito difícil de manter.

## Arquitetura recomendada

Criar uma camada PHP de abstração:

```text
app/Services/WhatsAppGatewayService.php
```

Responsabilidade:

```text
- descobrir qual gateway a instância usa;
- enviar mensagens;
- buscar QR code;
- desconectar;
- consultar status;
- normalizar payload de entrada;
- manter interface única para Flow Builder.
```

Exemplo:

```php
WhatsAppGatewayService::send($instanceId, $phone, $type, $payload);
WhatsAppGatewayService::qr($instanceId);
WhatsAppGatewayService::status($instanceId);
```

Tabela sugerida:

```text
sp_whatsapp_gateways
- id
- team_id
- instance_id
- provider          baileys|whatsmeow|official
- base_url
- api_key
- status
- capabilities_json
- created
- changed
```

Ou adicionar campos em `sp_accounts`:

```text
gateway_provider
 gateway_base_url
 gateway_status
```

Preferência: tabela separada para não quebrar legado.

## API mínima do Whatsmeow

O serviço Go deve expor endpoints equivalentes:

```text
GET /health
GET /instance?instance_id=
GET /qrcode?instance_id=
POST /logout
POST /send/text
POST /send/media
POST /send/buttons
POST /send/list
POST /presence
POST /webhook/config
```

Payload de entrada para o PHP deve ser normalizado no mesmo formato do Bot Builder atual:

```json
{
  "instance_id": "token_da_instancia",
  "gateway": "whatsmeow",
  "data": {
    "messages": []
  }
}
```

Assim o Flow Builder continua usando o mesmo endpoint:

```text
/bot_builder/webhook
```

## Capabilities por gateway

Nem todo gateway suporta tudo igual.

Criar mapa:

```json
{
  "text": true,
  "image": true,
  "audio": true,
  "document": true,
  "buttons": true,
  "list": true,
  "carousel": false,
  "poll": true,
  "presence": true,
  "groups": true
}
```

O Flow Builder deve consultar capabilities antes de enviar recursos avançados.

## Benefícios do Whatsmeow

- serviço em Go, geralmente leve e rápido;
- boa base para múltiplas sessões;
- separação clara de gateway;
- pode rodar como API secundária paralela;
- não precisa substituir Baileys imediatamente;
- permite testes A/B por instância.

## Riscos

- WhatsApp Web não oficial continua sujeito a bloqueios/mudanças;
- suporte a botões/listas pode variar;
- migração de sessão Baileys para Whatsmeow não é direta;
- precisa novo QR por instância Whatsmeow;
- mídia, LID e eventos precisam normalização cuidadosa;
- requer deploy Go/process manager separado.

## Estratégia de migração

### Fase 1 — Abstração PHP

1. Criar `WhatsAppGatewayService`.
2. Fazer `send_whatsapp()` usar service.
3. Gateway padrão continua `baileys`.
4. Nenhuma mudança de comportamento para instâncias atuais.

### Fase 2 — Serviço Whatsmeow experimental

1. Criar `app_zapmatic_whatsmeow_api`.
2. Implementar health/status/QR/send text.
3. Receber mensagens e enviar ao webhook do Bot Builder.
4. Testar com uma instância isolada.

### Fase 3 — Recursos essenciais

1. Envio de imagem/documento/audio.
2. Presence/typing.
3. Groups.
4. Normalização de contato e JID/LID.
5. Logs por mensagem.

### Fase 4 — UI multi-gateway

1. Na tela de conectar WhatsApp, escolher:
   - Baileys atual
   - Whatsmeow experimental
   - Oficial futura
2. Mostrar status por gateway.
3. QR code por gateway.
4. Indicador de capacidade.

### Fase 5 — Produção controlada

1. Permitir Whatsmeow por equipe/instância.
2. Rate limit por gateway.
3. Fallback manual para Baileys.
4. Métricas de falha/latência.

## Testes automatizados necessários

```text
tests/whatsapp_gateway_service_test.php
- gateway padrão baileys
- roteia envio para base_url correta
- normaliza resposta de sucesso
- normaliza erro
- respeita capabilities
```

```text
tests/whatsmeow_payload_normalizer_test.php
- texto recebido vira payload Bot Builder
- botão/lista normaliza button_id
- fromMe é ignorado
- grupo é identificado
- LID é resolvido quando disponível
```

```text
tests/flow_runtime_gateway_test.php
- Flow Builder envia texto via gateway configurado
- Flow Builder envia mídia via gateway configurado
- erro do gateway é logado
- gateway sem capability bloqueia recurso avançado
```

## Decisão recomendada

Sim, é possível adicionar Whatsmeow como API secundária.

Mas a ordem correta é:

```text
1. criar WhatsAppGatewayService
2. manter Baileys atual funcionando
3. criar Whatsmeow como serviço separado
4. testar uma instância isolada
5. só depois liberar na UI
```

Não substituir o gateway atual de uma vez.
