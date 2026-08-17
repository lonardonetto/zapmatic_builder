# Integracao de Modulos ao Sistema de Planos SaaS

> **Status:** aprovado para implementacao  
> **Data:** 2026-08-17  
> **Escopo:** Apenas modulos user-facing (nao admin)  
> **Excluidos:** Baileys (legado), Page_builder (admin-only), admin modules (Social_pages, Blog_internal, Notification, Other, Mail, Settings, etc.)

---

## 1. Contexto

### 1.1 Como o Sistema de Planos Funciona

```
sp_plans.permissions (JSON)
    ↓
permission("chave") no runtime
    ↓
libera (true) ou bloqueia (false) para o usuario
```

- O admin configura planos em **Memberships → Plans**
- Cada plano tem um JSON `permissions` com toggles (0/1) e limites numericos
- Na sidebar do WhatsApp, `permission($module_id)` decide se o modulo aparece
- `block_plans()` no Model registra o toggle na UI do plano (aba "Limits")
- `block_permissions()` no Model registra checkboxes na aba "Permissions"
- `permission("chave")` no Controller/View faz a verificacao em runtime

### 1.2 Modulos que JA ESTAO nos Planos (23 modulos)

WhatsApp (report, bulk, autoresponder, callresponder, history, chatbot, profile, api, contacts, leads, send_message, button_template, list_message_template, poll_template, carousel_template, flow, campaign_analytics, link_generator), File_manager, Account_manager, Proxies, Shortlink, Teams, Watermark, Criptografia_copy, Extended_Modules.

### 1.3 Modulos Admin-Only (NAO entrarao nos planos)

Users, Payments, Subscriptions, Coupons, Plans, Settings, Language, Mail, Social_network_settings, Proxy_system, Plugins, Blog_manager, Faqs_manager, Page_builder, Social_pages, Blog_internal, Notification, Other.

---

## 2. Modulos para Integrar aos Planos

### 2.1 Resumo

| # | Modulo | Config ID | Chave de Permissao | O que faz | Prioridade |
|---|--------|-----------|-------------------|-----------|------------|
| 1 | Bot_builder | `bot_builder` | `bot_builder` | Construtor de fluxos/bots visuais | ALTA |
| 2 | Whatsapp_call_campaign | `whatsapp_call_campaign` | `whatsapp_call_campaign` | Campanhas de ligacao WhatsApp via Go (audio + auto-hangup) | ALTA |
| 3 | Gm_scraper | `gm_scraper` | `gm_scraper` | Extrator de leads do Google Maps | ALTA |
| 4 | Whatsapp_export_participants | `whatsapp_export_participants` | `whatsapp_export_participants` | Exportar participantes de grupos + Clone de grupos | MEDIA |
| 5 | Group_manager | `group_manager` | `group_manager` | Gerenciamento de grupos WhatsApp | MEDIA |
| 6 | Caption | `caption` | `caption` | Templates de texto/caption | MEDIA |
| 7 | Whatsapp_official_template | `whatsapp_official_template` | `whatsapp_official_template` | Templates oficiais WhatsApp (Cloud API) | BAIXA |

### 2.2 Detalhe de Cada Modulo

---

#### Modulo 1: Bot_builder (Construtor de Bots)

| Item | Valor |
|---|---|
| Config ID | `bot_builder` |
| Chave | `bot_builder` |
| Status atual | Checkbox ja existe em `Whatsapp/Views/permissions.php` (linha 48), mas Model nao tem `block_plans()` e Controller nao faz `permission()` check |
| O que precisa | 1. Adicionar `block_plans()` no Model para registrar toggle na UI do plano. 2. Adicionar verificacao `permission("bot_builder")` no Controller (ou na sidebar) |
| Impacto | Sem isso, qualquer usuario pode criar fluxos independentemente do plano |
| Chaves relacionadas que ja existem | `whatsapp_chatbot_item_limit` (limite de itens por chatbot) |

---

#### Modulo 2: Whatsapp_call_campaign (Campanhas de Ligacao)

| Item | Valor |
|---|---|
| Config ID | `whatsapp_call_campaign` |
| Chave | `whatsapp_call_campaign` |
| Status atual | ZERO integracao com planos. Nao tem `block_plans()`, nao tem `block_permissions()`, nao faz `permission()`. Qualquer usuario pode criar campanhas de ligacao |
| O que precisa | 1. Adicionar `block_plans()` no Model. 2. Adicionar `block_permissions()` no Model. 3. Adicionar `permission("whatsapp_call_campaign")` no Controller. 4. Adicionar checkbox em `permissions.php` |
| Impacto | Funcionalidade premium — ligacoes WhatsApp com audio e auto-hangup. Sem controle, usuarios do plano mais barato podem usar ilimitadamente |
| Chaves sugeridas para limites | `whatsapp_call_campaign_max_calls` (max ligacoes por mes), `whatsapp_call_campaign_max_audio_duration` (max duracao do audio em segundos) |

---

#### Modulo 3: Gm_scraper (Extrator de Leads Google Maps)

| Item | Valor |
|---|---|
| Config ID | `gm_scraper` |
| Chave | `gm_scraper` |
| Status atual | Tem `block_plans()` no Model (registra toggle), mas Config tem `show_plan => false`. Controller nao faz `permission()` check. Sidebar nao tem gate |
| O que precisa | 1. Mudar `show_plan` para `true` no Config.php. 2. Adicionar `permission("gm_scraper")` no Controller. 3. Adicionar gate na sidebar |
| Impacto | Extracao de leads do Google Maps e recurso que consome recursos (Playwright browser). Sem controle, pode ser abusado |

---

#### Modulo 4: Whatsapp_export_participants (Exportar + Clone de Grupos)

| Item | Valor |
|---|---|
| Config ID | `whatsapp_export_participants` |
| Chave | `whatsapp_export_participants` |
| Status atual | Checkbox ja existe em `permissions.php` (linha 55), mas Model nao tem `block_plans()` e Controller nao faz `permission()` check. Clone de grupos esta embutido neste modulo (metodo `clone_group()`) |
| O que precisa | 1. Adicionar `block_plans()` no Model. 2. Adicionar `permission("whatsapp_export_participants")` no Controller (cobrindo tanto export quanto clone) |
| Impacto | Exportacao de participantes e clone de grupos sao recursos avancados. Clone pode criar multiplas copias de grupos |

---

#### Modulo 5: Group_manager (Gerenciamento de Grupos)

| Item | Valor |
|---|---|
| Config ID | `group_manager` (submenu de `tools`) |
| Chave | `group_manager` |
| Status atual | Menu standalone (Tools tab), bypassa verificacao de sidebar. ZERO integracao com planos |
| O que precisa | 1. Adicionar verificacao de permissao no menu (sidebar ou template). 2. Adicionar toggle no sistema de planos |
| Impacto | Gerenciamento de grupos (adicionar/remover membros, etc.) |

---

#### Modulo 6: Caption (Templates de Texto)

| Item | Valor |
|---|---|
| Config ID | `caption` (submenu de `tools`) |
| Chave | `caption` |
| Status atual | Menu standalone (Tools tab), bypassa verificacao de sidebar. ZERO integracao com planos |
| O que precisa | 1. Adicionar verificacao de permissao no menu. 2. Adicionar toggle no sistema de planos |
| Impacto | Templates de texto reutilizaveis. Menor criticidade |

---

#### Modulo 7: Whatsapp_official_template (Templates Oficiais)

| Item | Valor |
|---|---|
| Config ID | `whatsapp_official_template` |
| Chave | `whatsapp_official_template` |
| Status atual | Widget dentro de Whatsapp_profiles. Sem Model proprio, sem plan integration, sem permission checks |
| O que precisa | 1. Adicionar verificacao de permissao no widget. 2. Registrar no sistema de planos |
| Impacto | Templates oficiais do WhatsApp Cloud API. Depende de Cloud API estar habilitado |

---

## 3. Implementacao Sugerida

### 3.1 Padrao de Implementacao

Para cada modulo, seguir o padrao dos modulos que ja funcionam (ex: `Whatsapp_api`, `Shortlink`):

**No Model do modulo:**
```php
public function block_plans()
{
    return [
        'id' => '{config_id}',
        'tab' => 'Features',
        'position' => {posicao},
        'items' => [
            ['id' => '{chave}', 'type' => 'checkbox', 'default' => 0, 'label' => '{Descricao}'],
        ]
    ];
}
```

**No Controller do modulo (metodo index ou construtor):**
```php
if (!permission('{chave}') && !is_admin()) {
    return redirect()->to('/whatsapp');
}
```

**No permissions.php (Whatsapp/Views/permissions.php):**
```php
$permissions[] = ['id' => '{chave}', 'label' => '{Descricao}', 'default' => 0];
```

### 3.2 Ordem de Implementacao

| Prioridade | Modulo | Esforco |
|---|---|---|
| 1 | Whatsapp_call_campaign | Baixo — Model ja existe, so adicionar methods |
| 2 | Bot_builder | Baixo — checkbox ja existe em permissions.php |
| 3 | Gm_scraper | Baixo — ja tem block_plans, so falta enforcement |
| 4 | Whatsapp_export_participants | Baixo — checkbox ja existe em permissions.php |
| 5 | Group_manager | Medio — menu standalone, precisa adaptar sidebar |
| 6 | Caption | Medio — menu standalone, precisa adaptar sidebar |
| 7 | Whatsapp_official_template | Medio — widget, precisa criar Model |

### 3.3 Chaves de Permissao (JSON no plano)

```json
{
    "bot_builder": 1,
    "whatsapp_call_campaign": 1,
    "whatsapp_call_campaign_max_calls": 500,
    "gm_scraper": 1,
    "whatsapp_export_participants": 1,
    "group_manager": 1,
    "caption": 1,
    "whatsapp_official_template": 1
}
```

**Toggle (0/1):** Ativa/desativa o modulo para o plano  
**Numerico:** Limite (ex: max ligacoes por mes)

---

## 4. Checklist de Cada Modulo

| Item | call_campaign | bot_builder | gm_scraper | export_part | group_mgr | caption | official_tpl |
|---|---|---|---|---|---|---|---|
| block_plans() no Model | adicionar | adicionar | ja existe | adicionar | adicionar | adicionar | adicionar |
| block_permissions() no Model | adicionar | — | — | — | — | — | — |
| permission() no Controller | adicionar | adicionar | adicionar | adicionar | adicionar | adicionar | adicionar |
| Checkbox em permissions.php | adicionar | ja existe | adicionar | ja existe | adicionar | adicionar | adicionar |
| Gate na sidebar/menu | verificar | verificar | verificar | verificar | adicionar | adicionar | — |
| show_plan no Config | adicionar | manter false | mudar p/ true | adicionar | adicionar | adicionar | adicionar |
| Testar com plano sem permissao | sim | sim | sim | sim | sim | sim | sim |
| Testar com plano com permissao | sim | sim | sim | sim | sim | sim | sim |

---

## 5. Cenarios de Teste

| Cenario | Esperado |
|---|---|
| Plano BASICO sem `whatsapp_call_campaign` | Usuario NAO ve "Campanhas de Chamada" no menu |
| Plano PREMIUM com `whatsapp_call_campaign: 1` | Usuario VE o modulo e pode criar campanhas |
| Plano BASICO sem `bot_builder` | Usuario NAO ve "Construtor de Bots" no menu |
| Plano PREMIUM com `bot_builder: 1` | Usuario VE o modulo e pode criar fluxos |
| Plano sem `gm_scraper` | Usuario NAO ve "Extrator de Leads" no menu |
| Plano com `gm_scraper: 1` | Usuario VE e pode extrair leads do Google Maps |
| Plano sem `whatsapp_export_participants` | Usuario NAO ve "Exportar Participantes" nem "Clone de Grupos" |
| Plano com `whatsapp_export_participants: 1` | Usuario VE ambos (export + clone) |
| Plano sem `group_manager` | Usuario NAO ve "Gerenciamento de Grupos" em Tools |
| Plano sem `caption` | Usuario NAO ve "Caption" em Tools |
| Admin (is_admin=1) | SEMPRE ve tudo, independente do plano |
