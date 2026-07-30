# Plano: Seletor de Gateway por Perfil (Cloud API + Baileys/Go)

**Versão:** v7.10.1  
**Data:** Julho 2026  
**Status:** Aprovação pendente  

---

## 1. O QUE É (Requisito do Usuário)

Quando o usuário cria uma campanha de disparo em massa, ele seleciona os perfis (contas WhatsApp). O sistema deve:

1. **Detectar** onde cada perfil está conectado (Baileys, Go/Whatsmeow, Cloud API)
2. **Mostrar** apenas as opções de gateway disponíveis para os perfis selecionados
3. **Roteamento automático**: quando a vez é de um perfil no Baileys → dispara via Baileys. Quando é a vez de um perfil no Go → dispara via Go. Quando é a vez de um perfil no Cloud → dispara via Cloud API.
4. **Rotação round-robin**: o rotador já existe (`rotator.go`), mas precisa saber qual gateway usar para cada conta.

---

## 2. FLUXO ATUAL (Como funciona HOJE)

```
Usuário seleciona perfis na campanha
    ↓
Go processor.resolveBestInstance(campaign)
    ↓
Round-robin: rotator.Next() → account_id
    ↓
SELECT token FROM sp_accounts WHERE id = account_id
    ↓
Procura sessão whatsmeow conectada com esse token
    ↓
SE encontrou → envia via whatsmeow (SEMPRE)
SE não encontrou → tenta próximo perfil
```

**Problema**: O sistema SEMPRE envia via whatsmeow. Se o perfil está no Baileys ou Cloud API, ele não consegue enviar.

---

## 3. FLUXO PROPOSTO (Como deve funcionar)

```
Usuário seleciona perfis na campanha
    ↓
Go processor.resolveBestInstance(campaign)
    ↓
Round-robin: rotator.Next() → account_id
    ↓
SELECT token, login_type FROM sp_accounts WHERE id = account_id
    ↓
┌─────────────────────────────────────────────────────────┐
│ login_type = 3 (Go/Whatsmeow)                          │
│   → Procura sessão whatsmeow conectada                 │
│   → SE conectada → envia via whatsmeow (atual)         │
│                                                         │
│ login_type = 2 (Baileys)                                │
│   → HTTP POST para Node.js (localhost:8000)             │
│   → /send_message com chat_id + payload                 │
│                                                         │
│ login_type = 1 (Cloud API / CoEx)                       │
│   → HTTP POST para Meta Graph API                      │
│   → /messages com template aprovado                     │
└─────────────────────────────────────────────────────────┘
```

---

## 4. MUDANÇAS NECESSÁRIAS

### 4.1 Banco de Dados

#### Nova coluna em `sp_whatsapp_schedules`:
```sql
ALTER TABLE sp_whatsapp_schedules 
ADD COLUMN gateway_mode VARCHAR(20) DEFAULT 'auto';
```

Valores:
- `auto` (padrão) → usa o gateway nativo de cada conta (Baileys→Baileys, Go→Go, Cloud→Cloud)
- `whatsmeow` → força envio via Go para TODAS as contas (ignora login_type)
- `baileys` → força envio via Baileys para TODAS as contas
- `cloud_api` → força envio via Cloud API para TODAS as contas

#### Nova tabela `sp_whatsapp_cloud_api_config`:
```sql
CREATE TABLE IF NOT EXISTS sp_whatsapp_cloud_api_config (
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

### 4.2 PHP — WhatsAppGatewayService.php

Adicionar método `sendViaCloudAPI()`:

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
```

Método `buildCloudAPIPayload()`:
```php
private static function buildCloudAPIPayload(string $chatId, string $type, array $payload): array
{
    $phone = preg_replace('/@.*/', '', $chatId);

    if ($type === 'text') {
        return [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $payload['message'] ?? $payload['caption'] ?? ''],
        ];
    }

    if ($type === 'buttons') {
        $buttons = array_map(function($btn) {
            return [
                'type' => 'reply',
                'reply' => [
                    'id' => $btn['id'] ?? uniqid(),
                    'title' => substr($btn['text'] ?? 'Opção', 0, 20),
                ],
            ];
        }, array_slice($payload['buttons'] ?? [], 0, 3));

        return [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $payload['body'] ?? 'Escolha:'],
                'action' => ['buttons' => $buttons],
            ],
        ];
    }

    // ... list, carousel, media adaptados para formato Cloud API
}
```

### 4.3 PHP — Controller de Campanha (save method)

```php
// No Whatsapp_bulk::save()
$gateway_mode = post('gateway_mode') ?? 'auto';
// Validar: auto, whatsmeow, baileys, cloud_api
if (!in_array($gateway_mode, ['auto', 'whatsmeow', 'baileys', 'cloud_api'])) {
    $gateway_mode = 'auto';
}

// Salvar no banco
db_update('sp_whatsapp_schedules', [
    'gateway_mode' => $gateway_mode,
], ['id' => $campaign_id]);
```

### 4.4 PHP — API para detectar gateways disponíveis

Novo endpoint ou função para o frontend consultar:

```php
// No Whatsapp_bulk controller
public function detect_gateways()
{
    $account_ids = post('account_ids') ?? [];
    $team_id = get_team("id");
    
    $gateways = [];
    $accounts = db_fetch("*", TB_ACCOUNTS, [
        "id" => $account_ids,
        "social_network" => "whatsapp",
        "status" => 1,
        "team_id" => $team_id
    ]);
    
    foreach ($accounts as $acc) {
        $provider = 'baileys'; // padrão
        if ($acc->login_type == 3) {
            // Verificar se está conectado no whatsmeow
            $gw = \App\Services\WhatsAppGatewayService::gatewayForInstance($acc->token);
            $provider = $gw['provider'] ?? 'whatsmeow';
        } elseif ($acc->login_type == 1) {
            $provider = 'cloud_api';
        }
        $gateways[$acc->id] = [
            'id' => $acc->id,
            'name' => $acc->name,
            'provider' => $provider,
            'token' => $acc->token,
        ];
    }
    
    return $this->respond([
        'status' => 'success',
        'gateways' => $gateways,
        'available' => array_unique(array_column($gateways, 'provider')),
    ]);
}
```

### 4.5 Go — campaign.go

Adicionar campo `GatewayMode`:

```go
type Campaign struct {
    // ... campos existentes ...
    GatewayMode string `json:"gateway_mode"` // 'auto', 'whatsmeow', 'baileys', 'cloud_api'
}
```

Atualizar `scanCampaign()` e `campaignCols` para incluir `gateway_mode`.

### 4.6 Go — processor.go

Atualizar `resolveBestInstance()` para retornar também o provider:

```go
type ResolvedInstance struct {
    InstanceID string
    Provider   string // 'whatsmeow', 'baileys', 'cloud_api'
    AccountID  int
    Token      string
}

func (p *Processor) resolveBestInstance(c *Campaign) *ResolvedInstance {
    // ... lógica existente de round-robin ...
    
    // Após encontrar o account_id:
    var loginType int
    mysqlDB.QueryRow("SELECT login_type FROM sp_accounts WHERE id=?", accID).Scan(&loginType)
    
    provider := "whatsmeow" // padrão
    if c.GatewayMode != "auto" && c.GatewayMode != "" {
        provider = c.GatewayMode // override da campanha
    } else {
        switch loginType {
        case 1: provider = "cloud_api"
        case 2: provider = "baileys"
        case 3: provider = "whatsmeow"
        }
    }
    
    // Se provider é whatsmeow, verificar sessão conectada
    if provider == "whatsmeow" {
        for _, s := range p.sm.ListInstances() {
            if s.ID == token && s.State == "connected" {
                return &ResolvedInstance{InstanceID: s.ID, Provider: provider, AccountID: accID, Token: token}
            }
        }
    } else {
        // Para baileys/cloud_api, não precisa de sessão whatsmeow
        return &ResolvedInstance{InstanceID: token, Provider: provider, AccountID: accID, Token: token}
    }
}
```

Atualizar `processCampaign()` para usar o provider:

```go
resolved := p.resolveBestInstance(c)
if resolved == nil {
    updateCampaignField(c.ID, "time_post", fmt.Sprintf("%d", time.Now().Unix()+30))
    UnlockCampaign(c.ID); return
}

// Enviar conforme o provider
var msgResult sender.SendResponse
switch resolved.Provider {
case "whatsmeow":
    // Lógica atual (via sender.Sender do whatsmeow)
    msgResult = p.sendViaWhatsmeow(c, resolved.InstanceID, chatID, params, pushName)
case "baileys":
    msgResult = p.sendViaBaileysHTTP(c, resolved, chatID, params, pushName)
case "cloud_api":
    msgResult = p.sendViaCloudAPIHTTP(c, resolved, chatID, params, pushName)
}
```

Novo método `sendViaBaileysHTTP()`:
```go
func (p *Processor) sendViaBaileysHTTP(c *Campaign, resolved *ResolvedInstance, chatID string, params map[string]string, pushName string) sender.SendResponse {
    // HTTP POST para Node.js Baileys (localhost:8000)
    // Endpoint: /send_message
    // Body: { instance_id, chat_id, message, type }
}
```

Novo método `sendViaCloudAPIHTTP()`:
```go
func (p *Processor) sendViaCloudAPIHTTP(c *Campaign, resolved *ResolvedInstance, chatID string, params map[string]string, pushName string) sender.SendResponse {
    // Ler config de sp_whatsapp_cloud_api_config
    // HTTP POST para Meta Graph API
    // Endpoint: /{phone_number_id}/messages
}
```

### 4.7 Frontend — update.php (Formulário de Campanha)

Adicionar seletor de gateway após a seleção de perfis:

```html
<div class="mb-3" id="gateway-mode-section">
    <label class="form-label">Modo de envio</label>
    <select class="form-select form-select-solid" name="gateway_mode" id="gateway_mode">
        <option value="auto">Automático (usa o gateway nativo de cada perfil)</option>
        <option value="whatsmeow">Forçar via Go/Whatsmeow</option>
        <option value="baileys">Forçar via Baileys</option>
        <option value="cloud_api">Forçar via Cloud API (Oficial)</option>
    </select>
    <div class="fs-12 text-gray-600 mt-2" id="gateway-mode-hint">
        No modo automático, cada perfil envia pelo gateway onde está conectado.
    </div>
    <div class="alert alert-info d-none mt-2" id="gateway-detected-info"></div>
</div>
```

JavaScript para detectar gateways quando perfis são selecionados:

```javascript
// Quando o usuário seleciona/deseleciona perfis
$(document).on('change', '.am-selected-item', function() {
    var selectedIds = getSelectedAccountIds();
    
    $.post('/whatsapp_bulk/detect_gateways', { account_ids: selectedIds }, function(data) {
        if (data.status === 'success') {
            var available = data.available;
            var info = [];
            
            if (available.includes('whatsmeow')) info.push('🟢 Go/Whatsmeow');
            if (available.includes('baileys')) info.push('🟡 Baileys');
            if (available.includes('cloud_api')) info.push('☁️ Cloud API');
            
            $('#gateway-detected-info')
                .removeClass('d-none')
                .html('Gateways detectados: ' + info.join(', '));
        }
    });
});
```

---

## 5. LIMITAÇÕES E CUIDADOS

| Item | Detalhe |
|------|---------|
| Cloud API: botões | Máximo 3 (vs 10 no nativo) |
| Cloud API: proatividade | Só com templates aprovados |
| Cloud API: custo | Tarifa por conversa da Meta |
| Baileys: sem sessão whatsmeow | O Go precisa fazer HTTP para Node.js |
| CoEx: heartbeat | Abrir o Business App 1x a cada 13 dias |
| CoEx: throughput | 20 msgs/segundo máximo |
| Mesmo número | O destinatário vê a mesma identidade |

---

## 6. CRONOGRAMA

| Fase | O que | Esforço |
|------|-------|---------|
| 1 | Criar tabela `sp_whatsapp_cloud_api_config` | 0.5h |
| 2 | Adicionar coluna `gateway_mode` em `sp_whatsapp_schedules` | 0.5h |
| 3 | Adicionar `sendViaCloudAPI()` no WhatsAppGatewayService.php | 2h |
| 4 | Adicionar `detect_gateways()` no controller | 1h |
| 5 | Atualizar `campaign.go` com campo `GatewayMode` | 0.5h |
| 6 | Atualizar `processor.go` com roteamento por provider | 3h |
| 7 | Adicionar seletor no formulário de campanha (update.php) | 1.5h |
| 8 | Testes end-to-end | 2h |
| **Total** | | **~11 horas** |

---

## 7. REFERÊNCIAS

- Meta Coexistence Docs: developers.facebook.com/docs/whatsapp/embedded-signup/custom-flows/onboarding-business-app-users
- Cloud API Reference: developers.facebook.com/docs/whatsapp/cloud-api/reference
- PLANO_PASSKEY_WHATSAPP.md: Suporte a Passkey (complementar)
