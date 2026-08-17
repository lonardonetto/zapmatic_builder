# Planos SaaS — Limites Quantitativos por Modulo

> **Status:** aprovado para implementacao  
> **Data:** 2026-08-17  
> **Problema:** Modulos novos tem apenas toggle on/off, faltam limites de uso  
> **Padrao:** Seguir o modelo de Whatsapp_bulk (toggle + limites numericos)

---

## 1. Como o Sistema de Limites Funciona

### 1.1 Arquitetura

```
Plan editor (admin)
├── Tab "Limits" → block_plans() no Model → toggle on/off por modulo
└── Tab "Permissions" → block_permissions() no Whatsapp → toggles + inputs numericos
    └── permissions.php → <input type="number" name="permissions[chave]">
    
Runtime (usuario)
└── permission("chave") → retorna valor armazenado (1 para toggle, 50 para limite)
    └── Controller: (int)permission("chave") → compara com contagem atual no DB
```

### 1.2 Padrao de Limites (ex: Whatsapp_bulk)

**No permissions.php:**
```php
<!-- Toggle (ja vem do block_plans, mas aparece aqui tambem para organizacao) -->
<input type="checkbox" name="permissions[whatsapp_bulk]" value="1" checked>

<!-- Limite numerico -->
<label>Max campanhas simultaneas</label>
<input type="number" name="permissions[whatsapp_bulk_max_run]" value="10">

<label>Max grupos de contatos</label>
<input type="number" name="permissions[whatsapp_bulk_max_contact_group]" value="10">

<label>Max numeros por grupo</label>
<input type="number" name="permissions[whatsapp_bulk_max_phone_numbers]" value="200">
```

**No Controller (enforcement):**
```php
$campaign_running = db_get("count(*) as count", TB_SCHEDULES, ["status" => 1, "team_id" => $team_id])->count;
if ($campaign_running >= (int)permission("whatsapp_bulk_max_run")) {
    ms(["status" => "error", "message" => "Limite atingido: " . permission("whatsapp_bulk_max_run") . " campanhas simultaneas"]);
}
```

---

## 2. Limites por Modulo

### 2.1 Bot_builder — Construtor de Bots

| Chave | Tipo | Label | Default | Enforcement |
|---|---|---|---|---|
| `bot_builder` | toggle | Ativar Construtor de Bots | 0 | Sidebar gate |
| `bot_builder_max_flows` | number | Max fluxos/bots criados | 10 | `COUNT(sp_bot_builders WHERE team_id)` |
| `bot_builder_max_nodes` | number | Max nodes por fluxo | 50 | `COUNT(sp_bb_blocks WHERE bot_id)` |

**Enforcement no Controller** (`Bot_builder/Controllers/Bot_builder.php`):
```php
// Ao criar novo fluxo:
$count = db_get("count(*) as c", "sp_bot_builders", ["team_id" => $team_id])->c;
if ($count >= (int)permission("bot_builder_max_flows")) {
    ms(["status" => "error", "message" => sprintf("Limite: max %s fluxos", permission("bot_builder_max_flows"))]);
}
```

---

### 2.2 Whatsapp_call_campaign — Campanhas de Ligacao

| Chave | Tipo | Label | Default | Enforcement |
|---|---|---|---|---|
| `whatsapp_call_campaign` | toggle | Ativar Campanhas de Chamada | 0 | Sidebar gate |
| `whatsapp_call_campaign_max_calls` | number | Max ligacoes por mes | 100 | `COUNT(sp_call_leads WHERE status IN answered,no_answer AND MONTH)` |
| `whatsapp_call_campaign_max_concurrent` | number | Max ligacoes simultaneas | 1 | `COUNT(sp_call_leads WHERE status=ringing)` |
| `whatsapp_call_campaign_max_audio_duration` | number | Max duracao do audio (segundos) | 30 | Comparar com `audio_duration` do payload |

**Enforcement no Controller** (`Whatsapp_call_campaign/Controllers/Whatsapp_call_campaign.php`):
```php
// Ao iniciar campanha:
$ringing = db_get("count(*) as c", "sp_call_leads", ["status" => "ringing", "team_id" => $team_id])->c;
if ($ringing >= (int)permission("whatsapp_call_campaign_max_concurrent")) {
    ms(["status" => "error", "message" => "Limite de ligacoes simultaneas atingido"]);
}

$monthly = db_get("count(*) as c", "sp_call_leads", 
    ["team_id" => $team_id, "MONTH(created_at)" => date('m')])->c;
if ($monthly >= (int)permission("whatsapp_call_campaign_max_calls")) {
    ms(["status" => "error", "message" => "Limite mensal de ligacoes atingido"]);
}
```

---

### 2.3 Gm_scraper — Extrator de Leads Google Maps

| Chave | Tipo | Label | Default | Enforcement |
|---|---|---|---|---|
| `gm_scraper` | toggle | Ativar Extrator de Leads | 0 | Sidebar gate |
| `gm_scraper_max_jobs` | number | Max buscas por mes | 10 | `COUNT(sp_gmscraper_jobs WHERE MONTH)` |
| `gm_scraper_max_leads` | number | Max leads extraidos por mes | 5000 | `SUM(total) FROM sp_gmscraper_jobs WHERE MONTH` |

**Enforcement no Controller** (`Gm_scraper/Controllers/Gm_scraper.php`):
```php
// Ao criar nova busca:
$jobs_month = db_get("count(*) as c", "sp_gmscraper_jobs", 
    ["team_id" => $team_id, "MONTH(created)" => date('m')])->c;
if ($jobs_month >= (int)permission("gm_scraper_max_jobs")) {
    ms(["status" => "error", "message" => "Limite de buscas mensais atingido"]);
}
```

---

### 2.4 Whatsapp_export_participants — Export + Clone de Grupos

| Chave | Tipo | Label | Default | Enforcement |
|---|---|---|---|---|
| `whatsapp_export_participants` | toggle | Ativar Export/Clone | 0 | Sidebar gate |
| `whatsapp_export_max_exports` | number | Max exportacoes por mes | 20 | `COUNT(sp_export_participants_queue WHERE MONTH)` |
| `whatsapp_export_max_clones` | number | Max clonagens por mes | 5 | `COUNT(sp_clone_group_queue WHERE MONTH)` |
| `whatsapp_export_max_participants` | number | Max participantes por exportacao | 10000 | Comparar com `total` da queue |

**Enforcement no Controller** (`Whatsapp_export_participants/Controllers/Whatsapp_export_participants.php`):
```php
// Ao exportar:
$exports_month = db_get("count(*) as c", "sp_export_participants_queue", 
    ["team_id" => $team_id])->c; // filtrar por mes
if ($exports_month >= (int)permission("whatsapp_export_max_exports")) {
    ms(["status" => "error", "message" => "Limite de exportacoes atingido"]);
}

// Ao clonar:
$clones_month = db_get("count(*) as c", "sp_clone_group_queue", 
    ["team_id" => $team_id])->c;
if ($clones_month >= (int)permission("whatsapp_export_max_clones")) {
    ms(["status" => "error", "message" => "Limite de clonagens atingido"]);
}
```

---

### 2.5 Group_manager — Gerenciamento de Grupos

| Chave | Tipo | Label | Default | Enforcement |
|---|---|---|---|---|
| `group_manager` | toggle | Ativar Gerenciamento de Grupos | 0 | Menu gate |
| `group_manager_max_groups` | number | Max grupos gerenciados | 10 | `COUNT(sp_groups WHERE team_id)` |

---

### 2.6 Caption — Templates de Texto

| Chave | Tipo | Label | Default | Enforcement |
|---|---|---|---|---|
| `caption` | toggle | Ativar Templates de Texto | 0 | Menu gate |
| `caption_max_templates` | number | Max templates criados | 50 | `COUNT(sp_captions WHERE team_id)` |

---

### 2.7 Whatsapp_official_template — Templates Oficiais

| Chave | Tipo | Label | Default | Enforcement |
|---|---|---|---|---|
| `whatsapp_official_template` | toggle | Ativar Templates Oficiais | 0 | Widget gate |
| `whatsapp_official_template_max` | number | Max templates oficiais | 10 | `COUNT(sp_whatsapp_template WHERE team_id)` |

---

## 3. Implementacao — permissions.php

### 3.1 Remover Redundancia

Os checkboxes duplicados no permissions.php devem ser **removidos** para modulos que ja tem toggle via `block_plans()`. O toggle ja aparece na aba "Limits". No permissions.php devem ficar **apenas os inputs numericos**.

**Modulos com toggle via block_plans (remover checkbox do permissions.php):**
- `bot_builder` — ja tem toggle na aba Limits
- `whatsapp_call_campaign` — ja tem toggle na aba Limits
- `gm_scraper` — ja tem toggle na aba Limits
- `whatsapp_export_participants` — ja tem toggle na aba Limits
- `group_manager` — ja tem toggle na aba Limits
- `caption` — ja tem toggle na aba Limits
- `whatsapp_official_template` — ja tem toggle na aba Limits

**O que fica no permissions.php:** apenas os inputs numericos (limites).

### 3.2 Secao no permissions.php

Adicionar secoes de limites para cada modulo, no padrao existente:

```php
<!-- Bot Builder Limits -->
<?php if (find_modules("bot_builder")): ?>
<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Construtor de Bots")?></label>
    <div class="mb-3">
        <label class="form-label"><?php _e("Max fluxos/bots criados")?></label>
        <input type="number" class="form-control" name="permissions[bot_builder_max_flows]" 
               value="<?php _ec((int)plan_permission('text', "bot_builder_max_flows"))?>">
    </div>
    <div class="mb-3">
        <label class="form-label"><?php _e("Max nodes por fluxo")?></label>
        <input type="number" class="form-control" name="permissions[bot_builder_max_nodes]" 
               value="<?php _ec((int)plan_permission('text', "bot_builder_max_nodes"))?>">
    </div>
</div>
<?php endif ?>
```

---

## 4. Checklist de Seguranca

- [ ] Cada limite tem enforcement no Controller (nao basta ter no permissions.php)
- [ ] Enforcement usa `(int)permission("chave")` para comparar com contagem DB
- [ ] Default razoavel (nao 0, que bloquearia tudo)
- [ ] Admin bypassa todos os limites
- [ ] Mensagem de erro amigavel mostrando o limite
- [ ] Limites aparecem na UI de edicao de plano (permissions.php)
- [ ] Toggle aparece na aba Limits (block_plans no Model)
- [ ] Sem checkboxes duplicadas entre Limits e Permissions

---

## 5. Tabela de Chaves Consolidada

| Modulo | Toggle | Limite 1 | Limite 2 | Limite 3 |
|---|---|---|---|---|
| Bot_builder | `bot_builder` | `bot_builder_max_flows` (10) | `bot_builder_max_nodes` (50) | — |
| Call_campaign | `whatsapp_call_campaign` | `whatsapp_call_campaign_max_calls` (100/mes) | `whatsapp_call_campaign_max_concurrent` (1) | `whatsapp_call_campaign_max_audio_duration` (30s) |
| Gm_scraper | `gm_scraper` | `gm_scraper_max_jobs` (10/mes) | `gm_scraper_max_leads` (5000/mes) | — |
| Export_participants | `whatsapp_export_participants` | `whatsapp_export_max_exports` (20/mes) | `whatsapp_export_max_clones` (5/mes) | `whatsapp_export_max_participants` (10000) |
| Group_manager | `group_manager` | `group_manager_max_groups` (10) | — | — |
| Caption | `caption` | `caption_max_templates` (50) | — | — |
| Official_template | `whatsapp_official_template` | `whatsapp_official_template_max` (10) | — | — |

**Total: 7 toggles + 14 limites numericos = 21 chaves de permissao**
