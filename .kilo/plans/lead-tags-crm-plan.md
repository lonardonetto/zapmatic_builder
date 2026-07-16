# Plano Completo: Sistema de CRM, Tags, Pipeline Kanban e Sequências

> **Objetivo:** Elevar o Zapmatic ao nível ManyChat/BotConversa em gestão de leads, categorização e follow-up.
> **Data base:** 09/07/2026
> **Versão atual:** v7.9.3

---

## ANÁLISE COMPETITIVA: ManyChat × BotConversa × Zapmatic

### 1. SISTEMA DE TAGS / ETIQUETAS

| Funcionalidade | ManyChat | BotConversa | Zapmatic HOJE |
|---|---|---|---|
| Tags no perfil do contato | ✅ Persistentes | ✅ Persistentes (Cartão do Usuário) | ❌ Coluna `sp_whatsapp_subscriber.tags` existe mas sempre vazia |
| Bloco "Adicionar Tag" no fluxo | ✅ | ✅ "Ações > Adicionar/Remover Etiqueta" | ❌ Não tem |
| Bloco "Remover Tag" no fluxo | ✅ | ✅ | ❌ Não tem |
| Usar tag em condição (if/else) | ✅ | ✅ | ❌ Não tem |
| Tags automáticas por evento | ✅ (ex: "clicou no botão X") | ✅ | ❌ |
| Filtro por tag na listagem | ✅ Segments | ✅ Audiência | ❌ |
| API para tags | ✅ | ✅ | ❌ |

### 2. CAMPOS PERSONALIZADOS (User Fields)

| Funcionalidade | ManyChat | BotConversa | Zapmatic HOJE |
|---|---|---|---|
| Campos persistentes (sobrevivem entre fluxos) | ✅ Custom Fields | ✅ "Campo Individual" | ❌ Só `set_variable` (volátil, morre com a sessão) |
| Campo do robô (volátil, só no fluxo atual) | - | ✅ "Campo do Robô" | ✅ `set_variable` no context da sessão |
| Bloco "Definir Campo do Usuário" | ✅ | ✅ | ❌ (precisa criar) |
| Usar campo em condições | ✅ | ✅ | ❌ |
| API para campos | ✅ | ✅ | ❌ |

### 3. CRM KANBAN / PIPELINE

| Funcionalidade | ManyChat | BotConversa | Zapmatic HOJE |
|---|---|---|---|
| Pipeline visual (Kanban drag-and-drop) | ❌ Não tem | ✅ CRM Kanban | ❌ Colunas `kanban_group` e `kanban_order` existem mas zeradas |
| Mover card entre estágios | ❌ | ✅ Arrastar | ❌ |
| Bloco "Mover para Estágio" no fluxo | ❌ | ✅ | ❌ |
| Atribuir responsável | ❌ Parcial | ✅ | ❌ |
| Anotações no card | ❌ | ✅ | ❌ |
| Histórico de movimentações | ❌ | ✅ | ❌ |

### 4. SEQUÊNCIAS DE FOLLOW-UP

| Funcionalidade | ManyChat | BotConversa | Zapmatic HOJE |
|---|---|---|---|
| Sequência multi-step | ✅ Sequences | ✅ Sequências (delay dias/horas/min) | ❌ Só bulk de uma vez (`sp_whatsapp_schedules`) |
| Delay configurável | ✅ | ✅ "tempo entre fluxos" | ❌ |
| Gatilho por tag | ✅ | ✅ (entra na sequência ao receber tag) | ❌ |
| Gatilho por evento (clicou botão X) | ✅ | ✅ | ❌ |
| Cancelar sequência | ✅ | ✅ | ❌ |
| Pausar/retomar | ✅ | - | ❌ |

### 5. BASE DE CONTATOS / AUDIÊNCIA

| Funcionalidade | ManyChat | BotConversa | Zapmatic HOJE |
|---|---|---|---|
| Lista de contatos com filtros | ✅ | ✅ Audiência | ✅ `Whatsapp_leads` (básico) |
| Filtro por tag/segmento | ✅ | ✅ | ❌ |
| Cartão do Usuário (perfil completo) | ✅ Contact Profile | ✅ Cartão do Usuário | ❌ |
| Importar/Exportar contatos | ✅ | ✅ | ✅ Export apenas |
| Contatos por instância | ✅ | ✅ | ✅ |
| Métricas por contato (última msg, status) | ✅ | ✅ | ✅ Parcial |

### 6. CAMPANHAS / TRANSMISSÃO

| Funcionalidade | ManyChat | BotConversa | Zapmatic HOJE |
|---|---|---|---|
| Transmissão em massa | ✅ Broadcast | ✅ Transmissão | ✅ `sp_whatsapp_schedules` (bulk) |
| Segmentação por tag | ✅ | ✅ | ❌ |
| Link/QR Code de campanha | ✅ Growth Tools | ✅ Campanhas | ❌ |
| Janela de 24h (Cloud API) | ✅ | ✅ | ❌ |
| Agendamento | ✅ | ✅ | ✅ |

---

## PLANO DE IMPLEMENTAÇÃO (3 FASES)

### FASE 1 — TAGS + CAMPOS PERSISTENTES (PRIORIDADE MÁXIMA)

**Tempo estimado:** 4-6 horas de desenvolvimento

#### 1.1 Banco de Dados
- **REUSAR** colunas existentes: `sp_whatsapp_subscriber.tags` (TEXT, comma-separated)
- **Tabela nova:** `sp_bb_contact_fields` — campos persistentes do contato
  ```sql
  CREATE TABLE sp_bb_contact_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    phone VARCHAR(100) NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_value TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE KEY idx_phone_field (team_id, phone, field_name)
  );
  ```
- **Tabela nova:** `sp_bb_tags` — catálogo de tags do time
  ```sql
  CREATE TABLE sp_bb_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    created_at DATETIME
  );
  ```

#### 1.2 Flow Builder — Blocos Novos
| Bloco | Descrição |
|-------|-----------|
| `add_tag` | Adiciona tag ao `sp_whatsapp_subscriber.tags` + inscreve em sequências |
| `remove_tag` | Remove tag do subscriber |
| `set_contact_field` | Escreve campo persistente no contato (sobrevive entre fluxos) |
| `condition` (extensão) | Adicionar operador "tem tag X" e "campo Y = Z" |

#### 1.3 Contexto Automático
| Tag | Conteúdo |
|-----|----------|
| `{{wa_tags}}` | "comprador, sp, quente" |
| `{{contact.nome}}` | Campo persistente nome |
| `{{contact.email}}` | Campo persistente email |
| `{{contact.*}}` | Qualquer campo salvo via `set_contact_field` |

#### 1.4 PHP — Bot_builder.php
- `case 'add_tag'`: escrever em `sp_whatsapp_subscriber.tags`
- `case 'remove_tag'`: remover do campo tags
- `case 'set_contact_field'`: INSERT/UPDATE em `sp_bb_contact_fields`
- `case 'condition'`: adicionar operadores `has_tag` e `contact_field`
- `process_webhook()`: injetar `wa_tags` e `contact.*` no `$init_ctx`
- `get_contact_tags()`: helper que lê do DB

#### 1.5 GO Gateway (whatsmeow)
- **Endpoint novo:** `POST /contact/set-tag` — escreve via MySQL direto
- **Endpoint novo:** `GET /contact/tags?phone=5511...` — retorna tags do contato
- **Endpoint novo:** `POST /contact/field` — set/update campo persistente

#### 1.6 Node.js (waziper.js)
- Estender `extend.js` com:
  - `addSubscriberTag(team_id, phone, tag)` 
  - `removeSubscriberTag(team_id, phone, tag)`
  - `getSubscriberTags(phone)`
  - Já tem `hydrateSubscriberRow()` que lê `tags` — é só escrever de volta

#### 1.7 Frontend — node-defs.js
```javascript
add_tag: { icon:'fad fa-tag', label:'Adicionar etiqueta', defaults:{tag:'', variable:''} },
remove_tag: { icon:'fad fa-tag-slash', label:'Remover etiqueta', defaults:{tag:''} },
set_contact_field: { icon:'fad fa-address-card', label:'Campo do contato', defaults:{field:'',value:''} },
```

#### 1.8 Frontend — inspector.js
- UI para selecionar tag existente OU digitar nova
- UI para `set_contact_field`: escolher campo existente ou novo

#### 1.9 Frontend — simulator.js
- Simular `add_tag` no `sim.context.wa_tags`
- Simular `set_contact_field` no `sim.context.contact`

#### 1.10 Leads — WhatsApp_leads
- Adicionar coluna "Etiquetas" na listagem
- Adicionar filtro por tag no formulário de busca
- Exibir tags coloridas na linha de cada lead (estilo ManyChat)

#### 1.11 Tags — Admin CRUD
- Página de gerenciamento de tags (`Whatsapp_tags`)
- Criar/editar/excluir tags com nome e cor
- Lista de tags do time
- Quantidade de contatos por tag

---

### FASE 2 — CRM KANBAN (PIPELINE VISUAL)

**Tempo estimado:** 8-10 horas de desenvolvimento

#### 2.1 Banco de Dados
- **REUSAR** colunas: `sp_whatsapp_subscriber.kanban_group` e `kanban_order`
- **Tabela nova:** `sp_bb_pipelines`
  ```sql
  CREATE TABLE sp_bb_pipelines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    name VARCHAR(100),
    stages JSON, -- ['Novo Lead', 'Qualificado', 'Negociação', 'Fechado', 'Perdido']
    created_at DATETIME
  );
  ```

#### 2.2 Bloco no Flow Builder
| Bloco | Descrição |
|-------|-----------|
| `move_stage` | Move o lead para um estágio do pipeline |

#### 2.3 UI Kanban
- Nova página "Pipeline" no menu lateral (dentro de Leads)
- Colunas arrastáveis (drag-and-drop) — cada coluna = um estágio
- Cards com: nome, telefone, última mensagem, tags
- Arrastar card entre colunas atualiza `kanban_group` e `kanban_order`
- Ao abrir card: Cartão do Usuário (tags, campos, histórico, notas)

#### 2.4 Cartão do Usuário (Contact Profile)
- Side panel com:
  - Foto de perfil (se disponível)
  - Nome, telefone
  - Etiquetas (tags coloridas, adicionar/remover inline)
  - Campos personalizados (editar inline)
  - Estágio atual do pipeline
  - Histórico de mensagens (últimas 20)
  - Notas internas (atendentes)
  - Sequências ativas

---

### FASE 3 — SEQUÊNCIAS DE FOLLOW-UP

**Tempo estimado:** 10-12 horas de desenvolvimento

#### 3.1 Banco de Dados
```sql
CREATE TABLE sp_bb_sequences (
  id INT AUTO_INCREMENT PRIMARY KEY,
  team_id INT NOT NULL,
  name VARCHAR(200),
  trigger_type ENUM('tag_added','tag_removed','field_changed','manual'),
  trigger_value VARCHAR(200), -- nome da tag ou campo
  steps JSON, -- [{flow_id: 5, delay_minutes: 1440}, {flow_id: 8, delay_minutes: 2880}]
  status TINYINT DEFAULT 1,
  created_at DATETIME
);

CREATE TABLE sp_bb_sequence_subscribers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sequence_id INT NOT NULL,
  phone VARCHAR(100) NOT NULL,
  current_step INT DEFAULT 0,
  next_run_at DATETIME,
  status ENUM('active','paused','completed','cancelled'),
  created_at DATETIME
);
```

#### 3.2 Mecanismo de Execução (CRON no PHP)
- Script PHP executado a cada 1 minuto via cron
- `SequenceRunner.php`: 
  1. Busca `sp_bb_sequence_subscribers` com `next_run_at <= NOW()` e `status='active'`
  2. Pega o fluxo do `current_step` via `steps[current_step].flow_id`
  3. Dispara o webhook `process_webhook()` com o phone e mensagem inicial do fluxo
  4. Incrementa `current_step`, atualiza `next_run_at = NOW() + steps[current_step].delay_minutes`
  5. Se último step, marca `status='completed'`

#### 3.3 Gatilhos de Sequência
- **Tag adicionada:** `add_tag("comprador")` → inscreve em sequências com `trigger_type='tag_added' trigger_value='comprador'`
- **Tag removida:** remove da sequência também
- **Campo alterado:** `set_contact_field("interesse","casa")` → inscreve na sequência de "interessados em casa"
- **Manual:** via painel admin

#### 3.4 UI de Sequências
- Página "Sequências" no menu lateral
- Lista de sequências com: nome, trigger, número de inscritos ativos, status
- Criar/Editar sequência:
  - Nome
  - Tipo de trigger + valor
  - Steps: para cada step, selecionar fluxo + delay (minutos/horas/dias)
  - Ordenar steps (arrastar)
- Visualizar inscritos de uma sequência com progresso

#### 3.5 GO + Node.js — Suporte
- Tanto GO quanto Node.js precisam expor endpoints ou funções para:
  - `addToSequence(team_id, phone, sequence_id)`
  - `removeFromSequence(team_id, phone, sequence_id)`
  - `getActiveSequences(phone)` — retorna sequências ativas do contato
- O cron PHP é o executor central (roda a cada 1 min)

---

## ARQUITETURA GERAL

```
┌─────────────────────────────────────────────────────────┐
│                     PAINEL ZAPMATIC                       │
│  ┌──────────┐  ┌──────────┐  ┌────────┐  ┌───────────┐ │
│  │Flow      │  │Leads     │  │Tags    │  │Sequências │ │
│  │Builder   │  │(filtros) │  │(CRUD)  │  │(CRUD)     │ │
│  └────┬─────┘  └────┬─────┘  └────┬───┘  └─────┬─────┘ │
│       │             │             │             │        │
│       ▼             ▼             ▼             ▼        │
│  ┌──────────────────────────────────────────────────┐   │
│  │              PHP Bot_builder.php                  │   │
│  │  add_tag / remove_tag / set_contact_field        │   │
│  │  move_stage / wa_tags / contact.*                │   │
│  │  SequenceRunner (cron)                           │   │
│  └──────┬──────────────┬────────────────┬───────────┘   │
│         │              │                │                │
│         ▼              ▼                ▼                │
│  ┌──────────┐   ┌────────────┐  ┌──────────────┐       │
│  │ GO       │   │ Node.js    │  │ MySQL        │       │
│  │ whatsmeow│   │ waziper.js │  │ subscriber   │       │
│  │ /contact │   │ extend.js  │  │ tags, fields │       │
│  └──────────┘   └────────────┘  │ sequences    │       │
│                                  └──────────────┘       │
└─────────────────────────────────────────────────────────┘
```

---

## PRIORIZAÇÃO (ROADMAP)

| Semana | Fase | Entregável |
|--------|------|------------|
| **Semana 1** | Fase 1 | Tags (add_tag, remove_tag, wa_tags, filtro, catálogo) + Campos Persistentes (set_contact_field, contact.*) |
| **Semana 2** | Fase 2 | CRM Kanban (pipeline visual, move_stage, cartão do usuário) |
| **Semana 3-4** | Fase 3 | Sequências de Follow-up (delay, cron runner, UI de sequências) |

---

## OBSERVAÇÕES TÉCNICAS

1. **Dual Gateway (GO + Node.js):** Toda escrita em `sp_whatsapp_subscriber` deve funcionar via:
   - **GO:** endpoints REST `/contact/*` que escrevem no MySQL
   - **Node.js:** funções `extend.js` que escrevem no MySQL
   - **PHP:** query direta no MySQL (fallback quando gateway não acessível)

2. **BotConversa chama "Etiquetas", ManyChat chama "Tags"** — usar "Etiquetas" na interface BR.

3. **O CRM Kanban do BotConversa** é o diferencial deles vs ManyChat (que não tem kanban nativo). Nosso kanban pode ser melhor.

4. **Sequências rodam no SERVIDOR** (cron), não no cliente — necessário para delays longos (dias).

5. **Tags e campos precisam ser indexados** para performance nas buscas:
   - `INDEX idx_subscriber_tags (tags(255))` — mas como é TEXT comma-separated, considerar tabela pivot `sp_bb_contact_tags` no futuro
   - `UNIQUE KEY` já existe em `sp_bb_contact_fields`
