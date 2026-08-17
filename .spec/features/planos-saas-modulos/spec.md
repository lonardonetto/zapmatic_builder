# Integracao de Modulos ao Sistema de Planos SaaS — Historia de Implementacao

> **Status:** aprovado para implementacao  
> **Data:** 2026-08-17  
> **Escopo:** Apenas ajustar funcionalidade de planos (block_plans, block_permissions, permission checks)  
> **NAO modificar:** funcionalidade dos modulos, layouts, rotas, controllers (exceto adicionar permission check)  
> **NAO commitar:** sem aprovacao do usuario  
> **Referencia de padrao:** `Whatsapp_apiModel.php`, `ShortlinkModel.php`, `Whatsapp_flowModel.php`, `Gm_scraperModel.php`

---

## 1. Como o Sistema de Planos Funciona

### 1.1 Arquitetura

```
Admin edita plano → UI carrega modulos com block_plans()/block_permissions()
                  → Salva JSON em sp_plans.permissions
                  
Usuario loga → sidebar carrega modulos
             → permission("chave") verifica JSON do plano do team
             → true = modulo aparece / false = modulo oculto
```

### 1.2 Padroes de Implementacao

**Padrao A — tab inteiro (modulos WhatsApp):**
```php
// No Model:
public function block_plans(){
    return [
        "tab" => 15,                    // 15 = "Whatsapp tool"
        "position" => <numero>,
        "label" => __("Whatsapp tool"),
        "items" => [
            ["id" => $this->config['id'], "name" => __("Nome do modulo")]
        ]
    ];
}
```

**Padrao B — tab string (modulos Features):**
```php
// No Model:
public function block_plans(){
    return [
        "tab" => __('Features'),
        "position" => <numero>,
        "permission" => true,
        "label" => __('Nome da secao'),
        "items" => [
            ["id" => 'chave_permissao', "name" => __('Label do toggle')]
        ]
    ];
}
```

**Verificacao no Controller:**
```php
if (!permission('chave') && !is_admin()) {
    return redirect()->to('/whatsapp');
}
```

**Checkbox em permissions.php (opcional, para aba Permissions):**
```php
$permissions[] = ['id' => 'chave', 'label' => __('Label'), 'default' => 0];
```

### 1.3 Tabs Existentes

| Tab ID | Nome | Modulos |
|---|---|---|
| `15` | Whatsapp tool | Whatsapp_api, Whatsapp_flow, Whatsapp_leads, Whatsapp_button_template, etc. |
| `30` | Advanced features | Shortlink |
| `__('Features')` | Features | Gm_scraper |
| `__('Extended_Modules')` | Extended Modules | Extended_Modules |

---

## 2. Modulos para Integrar — Historias

---

### Historia 1: Whatsapp_call_campaign (Campanhas de Ligacao)

**Como:** usuario com plano que inclui `whatsapp_call_campaign` pode criar campanhas de ligacao WhatsApp com audio e auto-hangup via Go gateway.

**Arquivos a modificar:**

**1.1 — Config.php** (`inc/core/Whatsapp_call_campaign/Config.php`):
```php
// ANTES:
'show_plan' => false,

// DEPOIS:
'show_plan' => true,
```

**1.2 — Model** (`inc/core/Whatsapp_call_campaign/Models/Whatsapp_call_campaignModel.php`):
```php
// ADICIONAR metodo block_plans():

public function block_plans()
{
    return [
        "tab" => 15,
        "position" => 410,
        "label" => __("Whatsapp tool"),
        "items" => [
            [
                "id" => $this->config['id'],
                "name" => __("Campanhas de Chamada WhatsApp"),
            ],
        ]
    ];
}
```

**1.3 — Controller** (`inc/core/Whatsapp_call_campaign/Controllers/Whatsapp_call_campaign.php`):
```php
// ADICIONAR no inicio de cada metodo publico (index, create, start, etc.):

if (!permission('whatsapp_call_campaign') && !is_admin()) {
    return redirect()->to('/whatsapp');
}
```

**1.4 — permissions.php** (`inc/core/Whatsapp/Views/permissions.php`):
```php
// ADICIONAR na secao Features, apos whatsapp_leads:
if (find_modules("whatsapp_call_campaign")) {
    $permissions[] = ['id' => 'whatsapp_call_campaign', 'label' => __('Campanhas de Chamada WhatsApp'), 'default' => 0];
}
```

**Chave de permissao:** `whatsapp_call_campaign`  
**Tipo:** toggle (0/1)  
**Tab:** 15 (Whatsapp tool)  
**Position:** 410 (apos callresponder que e 380)

---

### Historia 2: Bot_builder (Construtor de Bots)

**Como:** usuario com plano que inclui `bot_builder` pode criar fluxos visuais de atendimento.

**Arquivos a modificar:**

**2.1 — Config.php** (`inc/core/Bot_builder/Config.php`):
```php
// ANTES:
'show_plan' => false

// DEPOIS:
'show_plan' => true
```

**2.2 — Model** (`inc/core/Bot_builder/Models/Bot_builderModel.php`):
```php
// ADICIONAR metodo block_plans():

public function block_plans()
{
    return [
        "tab" => 15,
        "position" => 100,
        "label" => __("Whatsapp tool"),
        "items" => [
            [
                "id" => $this->config['id'],
                "name" => __("Construtor de Bots"),
            ],
        ]
    ];
}
```

**2.3 — permissions.php** (`inc/core/Whatsapp/Views/permissions.php`):
```php
// JA EXISTE (linha 48) — nao precisa adicionar, apenas confirmar que esta la:
$permissions[] = ['id' => 'bot_builder', 'label' => __('Construtor de Bots'), 'default' => 0];
```

**Chave de permissao:** `bot_builder`  
**Tipo:** toggle (0/1)  
**Tab:** 15 (Whatsapp tool)  
**Position:** 100 (antes de todos os outros)

---

### Historia 3: Gm_scraper (Extrator de Leads Google Maps)

**Como:** usuario com plano que inclui `gm_scraper` pode extrair leads do Google Maps.

**Arquivos a modificar:**

**3.1 — Config.php** (`inc/core/Gm_scraper/Config.php`):
```php
// ANTES:
'show_plan' => false,

// DEPOIS:
'show_plan' => true,
```

**3.2 — Model** (`inc/core/Gm_scraper/Models/Gm_scraperModel.php`):
```php
// JA TEM block_plans() — apenas confirmar que esta correto
// O metodo ja existe com 'tab' => __('Features'), 'permission' => true
// NAO PRECISA ALTERAR
```

**3.3 — Controller** (`inc/core/Gm_scraper/Controllers/Gm_scraper.php`):
```php
// ADICIONAR no inicio de cada metodo publico:
if (!permission('gm_scraper') && !is_admin()) {
    return redirect()->to('/whatsapp');
}
```

**3.4 — permissions.php** (`inc/core/Whatsapp/Views/permissions.php`):
```php
// ADICIONAR na secao Features:
if (find_modules("gm_scraper")) {
    $permissions[] = ['id' => 'gm_scraper', 'label' => __('Extrator de Leads Google Maps'), 'default' => 0];
}
```

**Chave de permissao:** `gm_scraper`  
**Tipo:** toggle (0/1)  
**Tab:** Features  
**Position:** 1000

---

### Historia 4: Whatsapp_export_participants (Exportar Participantes + Clone de Grupos)

**Como:** usuario com plano que inclui `whatsapp_export_participants` pode exportar listas de participantes e clonar grupos.

**Arquivos a modificar:**

**4.1 — Config.php** (`inc/core/Whatsapp_export_participants/Config.php`):
```php
// VERIFICAR se tem show_plan — adicionar se nao tiver:
'show_plan' => true,
```

**4.2 — Model** (`inc/core/Whatsapp_export_participants/Models/Whatsapp_export_participantsModel.php`):
```php
// ADICIONAR metodo block_plans():

public function block_plans()
{
    return [
        "tab" => 15,
        "position" => 800,
        "label" => __("Whatsapp tool"),
        "items" => [
            [
                "id" => $this->config['id'],
                "name" => __("Exportar Participantes e Clone de Grupos"),
            ],
        ]
    ];
}
```

**4.3 — Controller** (`inc/core/Whatsapp_export_participants/Controllers/Whatsapp_export_participants.php`):
```php
// ADICIONAR no inicio de cada metodo publico (index, export, clone_group, etc.):
if (!permission('whatsapp_export_participants') && !is_admin()) {
    return redirect()->to('/whatsapp');
}
```

**4.4 — permissions.php** (`inc/core/Whatsapp/Views/permissions.php`):
```php
// JA EXISTE (linha 55) — nao precisa adicionar, apenas confirmar que esta la:
$permissions[] = ['id' => 'whatsapp_export_participants', 'label' => __('Export participants'), 'default' => 0];
```

**Chave de permissao:** `whatsapp_export_participants`  
**Tipo:** toggle (0/1)  
**Tab:** 15 (Whatsapp tool)  
**Position:** 800

---

### Historia 5: Group_manager (Gerenciamento de Grupos)

**Como:** usuario com plano que inclui `group_manager` pode gerenciar grupos WhatsApp (adicionar/remover membros, etc.).

**Arquivos a modificar:**

**5.1 — Model** (`inc/core/Group_manager/Models/Group_managerModel.php`):
```php
// ADICIONAR metodo block_plans():

public function block_plans()
{
    return [
        "tab" => 30,
        "position" => 100,
        "label" => __("Advanced features"),
        "items" => [
            [
                "id" => "group_manager",
                "name" => __("Gerenciamento de Grupos"),
            ],
        ]
    ];
}
```

**5.2 — View/Menu** (verificar onde o menu de Tools e renderizado):
```php
// ADICIONAR verificacao de permissao antes de renderizar o submenu group_manager:
if (permission('group_manager')) {
    // renderizar menu
}
```

**5.3 — permissions.php** (`inc/core/Whatsapp/Views/permissions.php`):
```php
// ADICIONAR na secao Features:
$permissions[] = ['id' => 'group_manager', 'label' => __('Gerenciamento de Grupos'), 'default' => 0];
```

**Chave de permissao:** `group_manager`  
**Tipo:** toggle (0/1)  
**Tab:** 30 (Advanced features)  
**Position:** 100

---

### Historia 6: Caption (Templates de Texto)

**Como:** usuario com plano que inclui `caption` pode criar e usar templates de texto.

**Arquivos a modificar:**

**6.1 — Model** (`inc/core/Caption/Models/CaptionModel.php`):
```php
// ADICIONAR metodo block_plans():

public function block_plans()
{
    return [
        "tab" => 30,
        "position" => 200,
        "label" => __("Advanced features"),
        "items" => [
            [
                "id" => "caption",
                "name" => __("Templates de Texto (Caption)"),
            ],
        ]
    ];
}
```

**6.2 — View/Menu** (verificar onde o menu de Tools e renderizado):
```php
// ADICIONAR verificacao de permissao antes de renderizar o submenu caption:
if (permission('caption')) {
    // renderizar menu
}
```

**6.3 — permissions.php** (`inc/core/Whatsapp/Views/permissions.php`):
```php
// ADICIONAR na secao Features:
$permissions[] = ['id' => 'caption', 'label' => __('Templates de Texto (Caption)'), 'default' => 0];
```

**Chave de permissao:** `caption`  
**Tipo:** toggle (0/1)  
**Tab:** 30 (Advanced features)  
**Position:** 200

---

### Historia 7: Whatsapp_official_template (Templates Oficiais WhatsApp)

**Como:** usuario com plano que inclui `whatsapp_official_template` pode criar e gerenciar templates oficiais do WhatsApp Cloud API.

**Arquivos a modificar:**

**7.1 — Model** (CRIAR se nao existir: `inc/core/Whatsapp_official_template/Models/Whatsapp_official_templateModel.php`):
```php
<?php
namespace Core\Whatsapp_official_template\Models;
use CodeIgniter\Model;

class Whatsapp_official_templateModel extends Model
{
    protected $config;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
    }

    public function block_plans()
    {
        return [
            "tab" => 15,
            "position" => 360,
            "label" => __("Whatsapp tool"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("Templates Oficiais WhatsApp"),
                ],
            ]
        ];
    }
}
```

**7.2 — Config.php** (`inc/core/Whatsapp_official_template/Config.php`):
```php
// ADICIONAR se nao tiver:
'show_plan' => true,
```

**7.3 — Widget/View** (verificar onde o widget e renderizado):
```php
// ADICIONAR verificacao de permissao:
if (permission('whatsapp_official_template') || is_admin()) {
    // renderizar widget
}
```

**7.4 — permissions.php** (`inc/core/Whatsapp/Views/permissions.php`):
```php
// ADICIONAR na secao Features:
if (find_modules("whatsapp_official_template")) {
    $permissions[] = ['id' => 'whatsapp_official_template', 'label' => __('Templates Oficiais WhatsApp'), 'default' => 0];
}
```

**Chave de permissao:** `whatsapp_official_template`  
**Tipo:** toggle (0/1)  
**Tab:** 15 (Whatsapp tool)  
**Position:** 360

---

## 3. Mapa de Posicoes (Tab 15 — Whatsapp tool)

| Position | Modulo | Status |
|---|---|---|
| 100 | Bot_builder | **NOVO** |
| 200 | Whatsapp_profile | ja existe |
| 250 | Whatsapp_autoresponder | ja existe |
| 280 | Whatsapp_callresponder | ja existe |
| 300 | Whatsapp_chatbot | ja existe |
| 320 | Whatsapp_send_message | ja existe |
| 340 | Whatsapp_flow | ja existe |
| 350 | Whatsapp_leads | ja existe |
| 360 | Whatsapp_official_template | **NOVO** |
| 370 | Criptografia_copy | ja existe |
| 380 | Whatsapp_api | ja existe |
| 400 | Whatsapp_bulk | ja existe |
| 410 | Whatsapp_call_campaign | **NOVO** |
| 500 | Whatsapp_button_template | ja existe |
| 520 | Whatsapp_list_message_template | ja existe |
| 540 | Whatsapp_poll_template | ja existe |
| 560 | Whatsapp_carousel_template | ja existe |
| 600 | Whatsapp_link_generator | ja existe |
| 700 | Whatsapp_export_participants | **NOVO** |
| 800 | Whatsapp_campaign_analytics | ja existe |
| 900 | Gm_scraper | ja existe (show_plan=false → mudar) |

## 4. Mapa de Posicoes (Tab 30 — Advanced features)

| Position | Modulo | Status |
|---|---|---|
| 100 | Group_manager | **NOVO** |
| 200 | Caption | **NOVO** |
| 500 | Shortlink | ja existe |

---

## 5. JSON de Permissoes Atualizado

Apos implementacao, o JSON de permissoes de um plano completo deve conter:

```json
{
    "bot_builder": 1,
    "whatsapp_call_campaign": 1,
    "gm_scraper": 1,
    "whatsapp_export_participants": 1,
    "group_manager": 1,
    "caption": 1,
    "whatsapp_official_template": 1
}
```

**Planos sugeridos (configuracao manual pelo admin apos implementacao):**

| Plano | bot_builder | call_campaign | gm_scraper | export_part | group_mgr | caption | official_tpl |
|---|---|---|---|---|---|---|---|
| Teste Gratis | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Start | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Advanced | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ | ❌ |
| Advanced Plus | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| STAND | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| Administrador | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 6. Checklist de Seguranca

- [ ] NAO modificar funcionalidade dos modulos (apenas adicionar permission checks)
- [ ] NAO alterar rotas, layouts ou views (exceto adicionar permission gate)
- [ ] NAO remover permissoes existentes dos planos atuais
- [ ] NAO quebrar acesso de admin (is_admin sempre bypassa)
- [ ] Testar cada modulo com plano sem permissao (deve redirecionar ou ocultar)
- [ ] Testar cada modulo com plano com permissao (deve funcionar normalmente)
- [ ] Testar admin (deve ver tudo independente do plano)
- [ ] Confirmar que todos os metodos publicos do Controller tem o check
- [ ] Confirmar que a sidebar/menu oculta o modulo quando sem permissao

---

## 7. Cenarios de Teste

| # | Cenario | Acao | Esperado |
|---|---|---|---|
| 1 | Plano Start (sem call_campaign) | Acessar /whatsapp_call_campaign | Redireciona para /whatsapp |
| 2 | Plano Advanced Plus (com call_campaign) | Acessar /whatsapp_call_campaign | Ve o modulo normalmente |
| 3 | Admin (qualquer plano) | Acessar qualquer modulo | Ve tudo |
| 4 | Plano Start (sem bot_builder) | Verificar sidebar | "Construtor de Bots" nao aparece |
| 5 | Plano Advanced (com bot_builder) | Verificar sidebar | "Construtor de Bots" aparece |
| 6 | Plano sem gm_scraper | Acessar /gm_scraper | Redireciona |
| 7 | Plano sem export_participants | Acessar clone de grupo | Redireciona |
| 8 | Plano sem group_manager | Verificar menu Tools | "Group manager" nao aparece |
| 9 | Plano sem caption | Verificar menu Tools | "Caption" nao aparece |
| 10 | Admin edita plano | Verificar UI de edicao | Todos os 7 modulos aparecem como toggle |

---

## 8. Resumo de Alteracoes por Arquivo

| Arquivo | Alteracao | Modulos afetados |
|---|---|---|
| `inc/core/Whatsapp_call_campaign/Config.php` | `show_plan: false → true` | call_campaign |
| `inc/core/Whatsapp_call_campaign/Models/...Model.php` | Adicionar `block_plans()` | call_campaign |
| `inc/core/Whatsapp_call_campaign/Controllers/...php` | Adicionar `permission()` check | call_campaign |
| `inc/core/Bot_builder/Config.php` | `show_plan: false → true` | bot_builder |
| `inc/core/Bot_builder/Models/...Model.php` | Adicionar `block_plans()` | bot_builder |
| `inc/core/Gm_scraper/Config.php` | `show_plan: false → true` | gm_scraper |
| `inc/core/Gm_scraper/Controllers/...php` | Adicionar `permission()` check | gm_scraper |
| `inc/core/Whatsapp_export_participants/Models/...Model.php` | Adicionar `block_plans()` | export_participants |
| `inc/core/Whatsapp_export_participants/Controllers/...php` | Adicionar `permission()` check | export_participants |
| `inc/core/Group_manager/Models/...Model.php` | Adicionar `block_plans()` | group_manager |
| `inc/core/Group_manager/Views/` | Adicionar permission gate no menu | group_manager |
| `inc/core/Caption/Models/...Model.php` | Adicionar `block_plans()` | caption |
| `inc/core/Caption/Views/` | Adicionar permission gate no menu | caption |
| `inc/core/Whatsapp_official_template/Models/...Model.php` | CRIAR arquivo com `block_plans()` | official_template |
| `inc/core/Whatsapp_official_template/Config.php` | Adicionar `show_plan: true` | official_template |
| `inc/core/Whatsapp_official_template/Views/` | Adicionar permission gate no widget | official_template |
| `inc/core/Whatsapp/Views/permissions.php` | Adicionar 4 novas chaves (call_campaign, gm_scraper, group_manager, caption, official_template) | todos |
