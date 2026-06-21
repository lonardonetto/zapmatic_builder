# Fase 1 - Modelagem Interna de WhatsApp Flows

> Etapa operacional da implantação de Flows  
> Estratégia aprovada: Cloud API primeiro

---

## 1. Objetivo da fase

Criar a base estrutural de banco e contrato interno para WhatsApp Flows sem alterar o motor de envio atual.

Esta fase foi desenhada para:

- não quebrar o sistema;
- permitir rollout progressivo;
- separar Flow de `sp_whatsapp_template`;
- acomodar Cloud API agora e Baileys depois.

---

## 2. Decisão de modelagem

Flow não deve ser modelado apenas dentro de `sp_whatsapp_template`.

Motivos:

- Flow tem lifecycle próprio;
- Flow possui assets próprios;
- Flow possui endpoint e chaves;
- Flow gera eventos e respostas próprias;
- o template com botão Flow é só uma camada de envio, não o Flow em si.

Por isso a fase 1 criou estas entidades:

- `sp_whatsapp_flow_endpoints`
- `sp_whatsapp_flows`
- `sp_whatsapp_flow_assets`
- `sp_whatsapp_flow_events`

---

## 3. Papel de cada tabela

## `sp_whatsapp_flow_endpoints`

Guarda a configuração operacional do endpoint por conta Cloud/phone number:

- endpoint URI
- status do endpoint
- fingerprint da chave pública
- metadata de verificação
- vínculo com conta/WABA/phone_number_id

## `sp_whatsapp_flows`

É a entidade principal do Flow:

- nome
- vínculo com conta Cloud
- vínculo com endpoint
- `meta_flow_id`
- JSON do Flow
- versões do protocolo
- status local e status Meta

## `sp_whatsapp_flow_assets`

Guarda os arquivos e handles do Flow:

- assets locais
- assets enviados para Meta
- tipo de asset
- handles/ids remotos

## `sp_whatsapp_flow_events`

Guarda trilha operacional:

- criação
- sync
- publish
- webhook
- endpoint request/response
- erros
- respostas do usuário

---

## 4. Contratos principais

### Canal padrão

- `channel = cloud_api`

### Status local inicial

- `draft`
- `ready`
- `archived`

### Status Meta

Ficam abertos em string porque a Meta pode evoluir o ciclo operacional.

---

## 5. Arquivos criados/alterados nesta fase

### Alterados

- [Constants.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Config/Constants.php)

### Criados

- [20260414_whatsapp_flows_phase1.sql](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Migrations/20260414_whatsapp_flows_phase1.sql)
- [whatsapp-flows-fase-1-modelagem.md](/www/wwwroot/app_zapmatic_app/.agent/plans/whatsapp-flows-fase-1-modelagem.md)

---

## 6. Observação importante

Nesta fase:

- não foi alterado o motor de envio;
- não foi alterado o webhook existente;
- não foi exposto módulo novo no frontend;
- não foi mexido em chatbot, bulk ou autoresponder.

A intenção aqui é apenas preparar a base para as próximas fases.
