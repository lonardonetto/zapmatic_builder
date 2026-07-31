# 📋 PLANO: Tracking de Status Cloud API (Meta) - Disparo em Massa

> **Contexto:** Implementar sistema de tracking de status (`sent`, `delivered`, `read`, `failed`) da Cloud API Meta para campanhas de disparo em massa, com relatório visual por campanha.

---

## 🎯 OBJETIVO

Capturar e exibir status de entrega de mensagens enviadas via **Cloud API oficial** em campanhas de **Bulk Message**, permitindo que o cliente veja:
- **Resumo agregado** por campanha (total enviado, entregue, lido, falhas)
- **Detalhamento por contato** (drill-down quando necessário)
- **Códigos de erro completos** da Meta quando houver falhas

---

## 🔴 DECISÕES CRÍTICAS (Blocking - Não pode prosseguir sem resposta)

### 1. **Estratégia de Armazenamento de Logs**

**Question:** Como vamos armazenar os logs de status? Criar tabela nova ou reutilizar estrutura existente?

**Why This Matters:**
- **Tabela nova** (`sp_whatsapp_message_status`): Isolamento claro, fácil de limpar por campanha, não impacta outras funcionalidades
- **Reutilizar estrutura existente**: Se já existe tabela de logs, pode evitar duplicação, mas precisa garantir compatibilidade

**Options:**

| Option | Pros | Cons | Best For |
|--------|------|------|----------|
| **A) Nova tabela dedicada** | Isolamento, fácil manutenção, performance otimizada | Mais uma tabela no banco | ✅ **Recomendado** - Escalabilidade |
| **B) Reutilizar logs existentes** | Menos tabelas | Acoplamento, risco de quebrar funcionalidades existentes | Se já existe estrutura compatível |

**If Not Specified:** Criar nova tabela `sp_whatsapp_message_status` (isolamento e segurança)

---

### 2. **Ponto de Captura do `wa_message_id`**

**Question:** Onde vamos capturar o `wa_message_id` retornado pela Meta após envio?

**Why This Matters:**
- **No PHP (helpers)**: Mais controle, fácil integrar com Bulk, mas precisa passar `schedule_id` pelos helpers
- **No Node.js (waziper.js)**: Já processa bulk, mas precisa comunicação PHP ↔ Node para associar `schedule_id`

**Options:**

| Option | Pros | Cons | Best For |
|--------|------|------|----------|
| **A) Capturar no PHP (helpers Cloud)** | Controle direto, fácil associar `schedule_id` | Precisa modificar assinatura dos helpers | ✅ **Recomendado** - Simplicidade |
| **B) Capturar no Node.js** | Menos mudanças no PHP | Precisa criar API PHP↔Node para status | Se bulk já roda 100% no Node |

**If Not Specified:** Capturar no PHP (`send_cloud_message`, `send_cloud_template`, `send_cloud_interactive`) após `http_code == 200`

---

### 3. **Tratamento de Webhook de Status**

**Question:** Onde processar os webhooks de `statuses` da Meta? PHP direto ou manter fluxo PHP → Node?

**Why This Matters:**
- **PHP direto**: Processa status imediatamente, atualiza banco direto, sem dependência do Node
- **PHP → Node**: Mantém arquitetura atual, mas adiciona latência e ponto de falha

**Options:**

| Option | Pros | Cons | Best For |
|--------|------|------|----------|
| **A) Processar no PHP (webhook)** | Latência zero, controle total, não depende Node | Precisa implementar lógica de status | ✅ **Recomendado** - Performance |
| **B) Repassar para Node.js** | Mantém arquitetura atual | Latência, Node pode estar offline | Se Node já processa tudo |

**If Not Specified:** Processar `statuses` diretamente no PHP (`Whatsapp_webhook` controller), mantendo apenas `messages` indo para Node

---

## 🟡 DECISÕES DE ALTA LEVERAGEM (Afetam >30% da implementação)

### 4. **Estrutura da Tabela de Status**

**Question:** Quais campos são essenciais vs. opcionais na tabela de logs?

**Why This Matters:**
- Campos mínimos: Performance, queries rápidas
- Campos completos: Debugging, auditoria, mas pode impactar performance em campanhas grandes

**Proposta de Schema (Mínimo Viável):**

```sql
CREATE TABLE sp_whatsapp_message_status (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    team_id INT NOT NULL,
    schedule_id INT NOT NULL,              -- FK para TB_WHATSAPP_SCHEDULES
    account_id INT NOT NULL,               -- FK para sp_accounts
    to_number VARCHAR(20) NOT NULL,         -- Número destino (normalizado)
    wa_message_id VARCHAR(255) NOT NULL,   -- ID retornado pela Meta (chave para webhook)
    status ENUM('sent', 'delivered', 'read', 'failed', 'deleted') NOT NULL DEFAULT 'sent',
    last_status_at INT NOT NULL,           -- Timestamp Unix
    meta_error_code INT NULL,              -- Código de erro da Meta (ex: 131026)
    meta_error_title VARCHAR(255) NULL,    -- Título do erro
    meta_error_details TEXT NULL,          -- Detalhes completos do erro
    created INT NOT NULL,
    INDEX idx_schedule (schedule_id, team_id),
    INDEX idx_wa_message_id (wa_message_id),
    INDEX idx_status (status, last_status_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**If Not Specified:** Usar schema acima (balance entre performance e informação)

---

### 5. **Associação `schedule_id` no Envio**

**Question:** Como passar o `schedule_id` da campanha para os helpers Cloud durante o envio?

**Why This Matters:**
- **Parâmetro adicional**: Simples, mas precisa modificar todas as chamadas
- **Contexto global**: Complexo, pode causar bugs se não limpar
- **Buscar por `wa_message_id` depois**: Não precisa modificar helpers, mas adiciona latência

**Options:**

| Option | Pros | Cons | Best For |
|--------|------|------|----------|
| **A) Parâmetro opcional `$schedule_id`** | Explícito, seguro | Precisa modificar chamadas no Bulk | ✅ **Recomendado** - Clareza |
| **B) Buscar depois por `wa_message_id`** | Não modifica helpers | Precisa tabela temporária, complexo | Se não quer tocar nos helpers |

**If Not Specified:** Adicionar parâmetro opcional `$schedule_id = null` nos helpers, passar quando chamado do Bulk

---

### 6. **Limpeza Automática de Logs**

**Question:** Como implementar a limpeza "até exclusão da campanha"?

**Why This Matters:**
- **Cascade DELETE**: Automático, mas precisa FK no MySQL
- **Hook no `delete()` do Bulk**: Manual, mas mais controle
- **Cron job**: Pode limpar antes do usuário querer ver histórico

**Options:**

| Option | Pros | Cons | Best For |
|--------|------|------|----------|
| **A) Hook no `delete()` do Bulk** | Controle total, limpeza imediata | Precisa lembrar de chamar | ✅ **Recomendado** - Simplicidade |
| **B) FK CASCADE no MySQL** | Automático | Pode falhar se FK não existir | Se banco suporta |
| **C) Cron job diário** | Não depende ação do usuário | Pode limpar antes do esperado | Se quer histórico limitado |

**If Not Specified:** Adicionar `DELETE FROM sp_whatsapp_message_status WHERE schedule_id = ?` no método `delete()` do Bulk controller

---

## 🟢 DECISÕES NICE-TO-HAVE (Edge Cases / Otimizações)

### 7. **Relatório Visual - Nível de Detalhe**

**Question:** O relatório deve mostrar apenas resumo ou também permitir exportação?

**Why This Matters:**
- **Apenas visual**: Simples, rápido de implementar
- **Exportação CSV/PDF**: Útil para clientes, mas adiciona complexidade

**If Not Specified:** Implementar apenas visual na tela de detalhes da campanha (exportação pode ser v2)

---

### 8. **Tratamento de Status Duplicados**

**Question:** Como lidar se a Meta enviar múltiplos eventos para o mesmo `wa_message_id`?

**Why This Matters:**
- **UPDATE sempre**: Simples, sempre mantém status mais recente
- **INSERT se não existe, UPDATE se existe**: Idempotente, mas precisa verificar

**If Not Specified:** Usar `INSERT ... ON DUPLICATE KEY UPDATE` ou `UPDATE ... WHERE wa_message_id = ?` (idempotente)

---

### 9. **Performance em Campanhas Grandes**

**Question:** Precisa otimizar para campanhas com >10.000 mensagens?

**Why This Matters:**
- **Queries simples**: Funciona até ~5k mensagens
- **Agregação pré-calculada**: Necessário para >10k, mas adiciona complexidade

**If Not Specified:** Implementar queries simples primeiro, otimizar depois se necessário (YAGNI)

---

## 📐 ARQUITETURA PROPOSTA

### Fluxo de Dados

```
1. BULK MESSAGE (PHP)
   └─> Chama helper Cloud (send_cloud_*)
       └─> Meta retorna { messages: [{ id: "wamid.xxx" }] }
           └─> Grava em sp_whatsapp_message_status (status='sent', schedule_id, wa_message_id)

2. WEBHOOK META (PHP)
   └─> Recebe POST com statuses[]
       └─> Para cada status:
           ├─> Busca log por wa_message_id
           └─> UPDATE status, last_status_at, meta_error_*

3. RELATÓRIO BULK (PHP)
   └─> SELECT COUNT(*) GROUP BY status WHERE schedule_id = X
       └─> Exibe card na tela de detalhes da campanha
```

---

## 📋 CHECKLIST DE IMPLEMENTAÇÃO

### Fase 1: Infraestrutura (Backend)
- [ ] Criar tabela `sp_whatsapp_message_status` (schema acima)
- [ ] Adicionar constante `TB_WHATSAPP_MESSAGE_STATUS` em `Whatsapp/Config/Constants.php`
- [ ] Modificar helpers Cloud (`send_cloud_message`, `send_cloud_template`, `send_cloud_interactive`):
  - [ ] Adicionar parâmetro opcional `$schedule_id = null`
  - [ ] Após `http_code == 200`, extrair `wa_message_id` de `$result['messages'][0]['id']`
  - [ ] Se `$schedule_id` fornecido, inserir log com `status='sent'`
- [ ] Modificar `Whatsapp_webhook` controller:
  - [ ] Detectar `statuses[]` no payload (além de `messages[]`)
  - [ ] Para cada `status` em `statuses[]`:
    - [ ] Buscar log por `wa_message_id = status.id`
    - [ ] Se encontrado, UPDATE com novo status, timestamp, erros
- [ ] Modificar `Whatsapp_bulk::delete()`:
  - [ ] Adicionar `DELETE FROM sp_whatsapp_message_status WHERE schedule_id = ?` antes de deletar campanha

### Fase 2: Integração Bulk → Helpers
- [ ] Localizar onde Bulk chama helpers Cloud (provavelmente em `waziper.js` ou PHP)
- [ ] Passar `schedule_id` nas chamadas dos helpers durante envio de campanha
- [ ] Testar envio de campanha pequena e verificar logs sendo criados

### Fase 3: Relatório Visual
- [ ] Criar método `get_status_summary($schedule_id)` no Bulk controller:
  - [ ] `SELECT status, COUNT(*) FROM sp_whatsapp_message_status WHERE schedule_id = ? GROUP BY status`
  - [ ] Retornar array com contagens
- [ ] Modificar view `Whatsapp_bulk/Views/update.php`:
  - [ ] Adicionar card "Status dos Envios (Cloud API)" (visível apenas se campanha usa Cloud)
  - [ ] Exibir resumo: Total enviado, entregue (%), lido (%), falhas (%)
  - [ ] Botão "Ver detalhes por contato" → Modal/tabela paginada
- [ ] Criar endpoint `ajax_status_details($schedule_id)` para drill-down:
  - [ ] Retornar lista paginada de contatos com status, data, erros

### Fase 4: Testes e Validação
- [ ] Testar envio de campanha pequena (10 mensagens) via Cloud API
- [ ] Verificar logs sendo criados com `wa_message_id`
- [ ] Simular webhook de status (ou aguardar Meta enviar)
- [ ] Verificar UPDATE de status funcionando
- [ ] Testar exclusão de campanha e limpeza de logs
- [ ] Validar relatório visual na tela de detalhes

---

## ⚠️ RISCOS E MITIGAÇÕES

| Risco | Impacto | Mitigação |
|-------|---------|-----------|
| **Webhook não recebe statuses** | Alto | Validar estrutura do payload Meta, adicionar logs detalhados |
| **Performance em campanhas grandes** | Médio | Indexar `schedule_id` e `wa_message_id`, considerar agregação depois |
| **`wa_message_id` não encontrado no webhook** | Médio | Log de warning, não quebrar fluxo |
| **Helpers chamados sem `schedule_id`** | Baixo | Parâmetro opcional, não quebra funcionalidades existentes |

---

## 📊 ESTIMATIVA DE ESFORÇO

| Fase | Tempo Estimado | Complexidade |
|------|----------------|--------------|
| Fase 1: Infraestrutura | 2-3 horas | Média |
| Fase 2: Integração Bulk | 1-2 horas | Baixa |
| Fase 3: Relatório Visual | 2-3 horas | Média |
| Fase 4: Testes | 1-2 horas | Baixa |
| **TOTAL** | **6-10 horas** | **Média** |

---

## ✅ PRÓXIMOS PASSOS

1. **Aguardar aprovação** deste plano
2. **Confirmar decisões críticas** (se houver dúvidas)
3. **Iniciar Fase 1** (criação de tabela + modificação de helpers)
4. **Testar incrementalmente** após cada fase

---

**Status:** ⏳ Aguardando aprovação para iniciar implementação
