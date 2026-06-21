# WhatsApp Flows - Fase 2.2 - Builder Rico

> Data: 14/04/2026  
> Estratégia: Cloud API first

---

## Objetivo

Evoluir o builder visual inicial para um construtor realmente utilizável por usuário leigo, sem exigir edição manual de JSON para os casos mais comuns de atendimento com menu, submenu, imagem e cadastro final.

---

## Escopo entregue

### 1. Persistência do estado visual

Foi adicionada a coluna `builder_state` em `sp_whatsapp_flows` para armazenar o estado do builder visual separadamente de `flow_json`.

Isso permite:

- reconstruir a experiência visual ao reabrir um Flow;
- preservar o JSON final gerado;
- manter compatibilidade com o modo avançado.

Migration aplicada:

- [20260414_whatsapp_flows_phase2_builder_state.sql](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Migrations/20260414_whatsapp_flows_phase2_builder_state.sql)

---

### 2. Builder guiado completo para leigos

O editor de Flow em [update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/update.php) passou a ter dois modos:

- `Guided Flow`
- `Simple form`

O modo `Guided Flow` agora cobre:

- tela inicial de boas-vindas;
- imagem de capa em base64;
- título, caption e texto introdutório;
- botão de entrada para o menu;
- menu principal com `NavigationList`;
- múltiplas seções principais;
- imagem por item de menu;
- subtítulo, descrição, metadata, badge e tags por item;
- submenu para cada seção;
- imagem por opção de submenu;
- tela final própria para cada opção;
- formulário final compartilhado;
- variáveis visuais `{{categoria}}` e `{{opcao}}` no texto final.

O modo `Simple form` continua disponível para:

- cadastro simples;
- pesquisa rápida;
- formulários de contato;
- qualificação direta sem menus.

---

### 3. Geração automática de JSON

O usuário não precisa escrever JSON manualmente para os cenários suportados pelo builder.

O sistema gera automaticamente:

- `Image`
- `TextHeading`
- `TextCaption`
- `TextBody`
- `NavigationList`
- `Form`
- `Footer`
- `routing_model`

Também foi preservado o modo `Advanced JSON` para casos especiais ou estruturas que ainda não podem ser reconstruídas visualmente.

---

## Estrutura funcional atual

### Guided Flow

Fluxo gerado:

1. `WELCOME`
2. `MAIN_MENU`
3. uma tela de submenu para cada categoria
4. uma tela terminal para cada opção do submenu

### Simple form

Fluxo gerado:

1. uma única tela terminal com formulário

---

## Arquivos impactados

- [Whatsapp_flow.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php)
- [update.php](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp_flow/Views/update.php)
- [20260414_whatsapp_flows_phase2_builder_state.sql](/www/wwwroot/app_zapmatic_app/inc/core/Whatsapp/Migrations/20260414_whatsapp_flows_phase2_builder_state.sql)

---

## Testes executados

- `php -l inc/core/Whatsapp_flow/Views/update.php`
- `php -l inc/core/Whatsapp_flow/Controllers/Whatsapp_flow.php`
- `php -l inc/core/Whatsapp_flow/Views/content.php`
- aplicação da migration `builder_state`
- `SHOW COLUMNS FROM sp_whatsapp_flows LIKE 'builder_state'`

---

## Parecer técnico

Esta fase fecha a experiência visual local do construtor para os casos mais comuns de uso comercial:

- capa com imagem;
- menu principal;
- submenu;
- coleta final de dados.

O que ainda não faz parte desta fase:

- create do Flow na Meta;
- upload de assets na Meta;
- publish/deprecate;
- endpoint criptografado;
- envio de Flow pela Cloud;
- adaptação Baileys.

---

## Decisão

**Fase 2.2 concluída.**  
**Apto para iniciar a Fase 3 de integração com a Meta.**
