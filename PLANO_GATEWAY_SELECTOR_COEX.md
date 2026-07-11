# Plano: Seletor de Gateway Coexistence (Cloud API + Baileys/Go)

**Versão:** v7.10.0+
**Data:** Julho 2026
**Objetivo:** Permitir que o mesmo número (via Coex) envie mensagens por diferentes gateways, escolhidos por campanha ou mensagem.

---

## 1. Arquitetura Atual

```
                    ┌──────────────────────┐
                    │   WhatsAppGateway    │
                    │   Service.php        │
                    │   (roteador central) │
                    └──────────┬───────────┘
                               │
                    gatewayForInstance()
                               │
              ┌────────────────┼────────────────┐
              │                │                │
        provider=baileys  provider=whatsmeow   (não existe)
              │                │                │
              ▼                ▼                ▼
        Node.js (8000)    Go (8090)      Cloud API (Meta)
        sendViaBaileys()  sendViaWhatsmeow()  ???
```

**Problema:** Não existe o caminho `cloud_api`. O sistema hoje só conhece Baileys e Go.

---

## 2. Arquitetura Proposta

```
                    ┌──────────────────────┐
                    │   WhatsAppGateway    │
                    │   Service.php        │
                    └──────────┬───────────┘
                               │
                    gatewayForInstance() + override por campanha
                               │
         ┌─────────────────────┼─────────────────────┐
         │                     │                     │
   provider=baileys     provider=whatsmeow    provider=cloud_api
         │                     │                     │
         ▼                     ▼                     ▼
   Node.js (8000)        Go (8090)          Meta Graph API
   /send/buttons         /send/buttons      /messages (template)
   /send/carousel        /send/carousel
   /send/list            /send/list
   /send/text            /send/text
```

---

## 3. O que muda em cada camada

### 3.1 Banco de Dados

#### Tabela `sp_whatsapp_gateways` (já existe)
Adicionar provider `cloud_api` como valor válido:

| instance_id | provider | base_url | api_key | status |
|-------------|----------|----------|---------|--------|
| WMEOW_001 | whatsmeow | http://127.0.0.1:8090 | xxx | 1 |
| COEX_001 | cloud_api | https://graph.facebook.com/v21.0 | EAAxxxx | 1 |
| ABC123 | baileys | NULL | xxx | 1 |

#### Nova tabela `sp_whatsapp_cloud_api_config`
Para armazenar credenciais da Cloud API por número:

```sql
CREATE TABLE sp_whatsapp_cloud_api_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    instance_id VARCHAR(100) NOT NULL,
    phone_number_id VARCHAR(50) NOT NULL,
    waba_id VARCHAR(50) NOT NULL,
    access_token TEXT NOT NULL,
    business_id VARCHAR(50) NOT NULL,
    verify_token VARCHAR(255),
    is_coexistence TINYINT(1) DEFAULT 0,
    created INT,
    changed INT,
    INDEX idx_instance (instance_id),
    INDEX idx_team (team_id)
);
```

#### Tabela `sp_whatsapp_campaigns` (bulk)
Adicionar coluna `gateway_override`:

```sql
ALTER TABLE sp_whatsapp_schedules
    ADD COLUMN gateway_override VARCHAR(30) DEFAULT NULL;
    -- Valores: NULL (usa o padrão da conta), 'baileys', 'whatsmeow', 'cloud_api'
```

### 3.2 PHP — WhatsAppGatewayService.php

Adicionar o terceiro caminho no roteador:

```php
public static function send($instanceId, string $chatId, string $type, array $payload): array
{
    self::ensureTables();

    // Se a campanha tem override de gateway, usa ele
    $override = $payload['_gateway_override'] ?? null;

    if ($override === 'cloud_api') {
        return self::sendViaCloudAPI($instanceId, $chatId, $type, $payload);
    }

    $gateway = self::gatewayForInstance($instanceId);
    $provider = $override ?? ($gateway['provider'] ?? 'baileys');

    if ($provider === 'whatsmeow') {
        return self::sendViaWhatsmeow($gateway, $instanceId, $chatId, $type, $payload);
    }

    if ($provider === 'cloud_api') {
        return self::sendViaCloudAPI($instanceId, $chatId, $type, $payload);
    }

    return self::sendViaBaileys($instanceId, $chatId, $type, $payload);
}
```

Novo método `sendViaCloudAPI()`:

```php
private static function sendViaCloudAPI($instanceId, string $chatId, string $type, array $payload): array
{
    $config = self::getCloudAPIConfig($instanceId);
    if (!$config) {
        return ['status' => 'error', 'provider' => 'cloud_api', 'message' => 'Cloud API config not found'];
    }

    $url = "https://graph.facebook.com/v21.0/{$config['phone_number_id']}/messages";
    $headers = [
        'Authorization: Bearer ' . $config['access_token'],
        'Content-Type: application/json',
    ];

    // Cloud API só suporta templates aprovados para mensagens proativas
    // Para 1:1 (customer-initiated), pode enviar texto livre
    $body = self::buildCloudAPIPayload($chatId, $type, $payload);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    return [
        'status' => $httpCode >= 200 && $httpCode < 300 ? 'success' : 'error',
        'provider' => 'cloud_api',
        'http_code' => $httpCode,
        'response' => $decoded,
    ];
}

private static function buildCloudAPIPayload(string $chatId, string $type, array $payload): array
{
    $phone = preg_replace('/@.*/', '', $chatId); // remove @s.whatsapp.net

    if ($type === 'text') {
        return [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $payload['message'] ?? $payload['caption'] ?? ''],
        ];
    }

    // Para buttons, list, carousel — Cloud API usa INTERACTIVE messages
    // ou TEMPLATES aprovados
    if ($type === 'buttons') {
        return [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $payload['body'] ?? 'Escolha:'],
                'action' => [
                    'buttons' => array_map(function($btn) {
                        return [
                            'type' => 'reply',
                            'reply' => [
                                'id' => $btn['id'] ?? uniqid(),
                                'title' => substr($btn['text'] ?? 'Opção', 0, 20),
                            ],
                        ];
                    }, array_slice($payload['buttons'] ?? [], 0, 3)), // Cloud API limita a 3
                ],
            ],
        ];
    }

    // ... list, carousel, media adaptados para formato Cloud API
    // (detalhes na implementação)
}
```

### 3.3 PHP — Controller de Campanha

Na tela de criação de campanha (bulk), adicionar seletor:

```php
// No controller que recebe a campanha:
$gateway_override = post('gateway_override') ?? null;
// Valores permitidos: null, 'baileys', 'whatsmeow', 'cloud_api'

// Salvar na tabela sp_whatsapp_schedules:
db_update('sp_whatsapp_schedules', [
    'gateway_override' => $gateway_override,
], ['id' => $campaign_id]);
```

### 3.4 Go — processor.go

O bulk processor do Go precisa respeitar o override:

```go
func (p *Processor) sendMessage(campaign Campaign, contact Contact) error {
    // Determinar qual gateway usar
    gateway := campaign.GatewayOverride // 'cloud_api', 'whatsmeow', 'baileys', ou ""

    if gateway == "" {
        gateway = p.detectGateway(campaign.InstanceID)
    }

    switch gateway {
    case "cloud_api":
        return p.sendViaCloudAPI(campaign, contact)
    case "whatsmeow":
        return p.snd.SendText(context.Background(), sender.TextRequest{
            InstanceID: campaign.InstanceID,
            ChatID:     contact.ChatID,
            Message:    contact.Message,
        })
    case "baileys":
        return p.sendViaBaileys(campaign, contact)
    default:
        // fallback: tenta whatsmeow (que é o gateway do Go)
        return p.snd.SendText(...)
    }
}
```

### 3.5 Frontend — Interface de Campanha

Adicionar dropdown na criação de campanha:

```html
<div class="form-group">
    <label>Gateway de Envio</label>
    <select name="gateway_override" class="form-control">
        <option value="">Padrão (conexão atual)</option>
        <option value="cloud_api">☁️ Cloud API (oficial, pago, seguro)</option>
        <option value="whatsmeow">🟢 Whatsmeow/Go (não-oficial, grátis)</option>
        <option value="baileys">🟡 Baileys/Node (não-oficial, grátis)</option>
    </select>
    <small class="text-muted">
        Cloud API: usa templates aprovados, cobrado por conversa.<br>
        Whatsmeow/Baileys: envio livre com botões/carousel, sem custo.
    </small>
</div>
```

### 3.6 Frontend — Seletor por Mensagem Individual (Opcional)

Na tela de envio avulso (`/send`), adicionar toggle:

```html
<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" id="useCloudAPI">
    <label class="form-check-label" for="useCloudAPI">
        Enviar via Cloud API (oficial)
    </label>
</div>
```

---

## 4. Fluxo Completo: Campanha com Seletor

```
Usuário cria campanha
    │
    ├── Seleciona conta: +55 11 99999-0000 (Coex)
    ├── Seleciona gateway: "Baileys/Go (grátis)"
    ├── Seleciona template: "Promoção Verão" (com botões)
    ├── Seleciona contatos: 500 números
    └── Clica "Iniciar"
          │
          ▼
    Processor.processDue()
          │
          ├── Para cada contato:
          │   ├── gateway_override = 'baileys'
          │   ├── WhatsAppGatewayService::send(instanceId, chatId, 'buttons', payload)
          │   ├── $override === 'baileys' → sendViaBaileys()
          │   ├── Baileys envia pelo companion device (app do celular)
          │   ├── Mensagem sai GRÁTIS com 10 botões nativos
          │   └── Destinatário recebe da mesma identidade
          │
          └── Se usuário tivesse escolhido "Cloud API":
              ├── gateway_override = 'cloud_api'
              ├── sendViaCloudAPI()
              ├── Meta Graph API envia template aprovado
              ├── Mensagem é COBRADA por conversa
              └── Destinatário recebe da mesma identidade
```

---

## 5. Rotação Híbrida (Avançado)

Para quem quer alternar entre gateways automaticamente:

```php
// Na campanha, opção "Rotação Híbrida":
$rotation_mode = 'hybrid'; // 'single', 'round_robin', 'hybrid'

// Hybrid = alterna entre Cloud API e Baileys/Go a cada N mensagens
// Ex: 70% Baileys (grátis) + 30% Cloud API (oficial, esquenta)
```

No processor:

```go
func (p *Processor) nextGateway(campaign Campaign) string {
    if campaign.RotationMode == "hybrid" {
        // 70% não-oficial, 30% oficial
        if rand.Float64() < 0.7 {
            return "baileys" // ou "whatsmeow"
        }
        return "cloud_api"
    }
    return campaign.GatewayOverride
}
```

---

## 6. Limitações e Cuidados

| Item | Detalhe |
|------|---------|
| Cloud API: botões | Máximo 3 botões (vs 10 no nativo) |
| Cloud API: proatividade | Só com templates aprovados pela Meta |
| Cloud API: custo | Tarifa por conversa (varia por país/tipo) |
| Coex: heartbeat | Abrir o Business App 1x a cada 13 dias |
| Coex: throughput | Limitado a 20 msgs/segundo |
| Baileys/Go: risco | Pode ser detectado pela Meta como client não-oficial |
| Mesmo número | O destinatário vê a mesma identidade em ambos os caminhos |
| Webhooks | Mensagens enviadas pelo Baileys aparecem no Cloud API via `smb_message_echoes` |

---

## 7. Cronograma Sugerido

| Fase | O que | Esforço |
|------|-------|---------|
| 1 | Adicionar `sendViaCloudAPI()` no `WhatsAppGatewayService.php` | 1 dia |
| 2 | Criar tabela `sp_whatsapp_cloud_api_config` e tela de config | 1 dia |
| 3 | Adicionar seletor de gateway na tela de campanha | 0.5 dia |
| 4 | Modificar `processor.go` para respeitar gateway override | 0.5 dia |
| 5 | Adaptar payload Cloud API (templates, interactive, media) | 2 dias |
| 6 | Testes end-to-end com número Coex real | 1 dia |
| 7 | (Opcional) Rotação híbrida automática | 1 dia |

**Total estimado: 6-7 dias de desenvolvimento**

---

## 8. Pré-requisitos

- [ ] Conta WhatsApp Business App ativa com número real
- [ ] Meta Business Manager verificado
- [ ] Embedded Signup configurado para Coexistence
- [ ] Cloud API access token com permissões de messaging
- [ ] Pelo menos 1 template aprovado pela Meta (para mensagens proativas)
- [ ] Whatsmeow/Go conectado como companion device no mesmo número

---

## 9. Referências

- Meta Coexistence Docs: https://developers.facebook.com/docs/whatsapp/embedded-signup/custom-flows/onboarding-business-app-users
- Cloud API Reference: https://developers.facebook.com/docs/whatsapp/cloud-api/reference
- Conversation Pricing: https://developers.facebook.com/docs/whatsapp/pricing
- PLANO_PASSKEY_WHATSAPP.md: Plano de suporte a Passkey (complementar)
