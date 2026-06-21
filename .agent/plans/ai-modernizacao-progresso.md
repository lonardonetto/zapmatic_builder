# Progresso da Operação - Modernização da IA

> Documento oficial de acompanhamento da operação  
> Regra: toda etapa concluída deve ser registrada aqui antes do avanço

---

## 1. Status geral

| Item | Status |
| ---- | ------ |
| Operação iniciada | Sim |
| Documento de funcionamento atual | Concluído |
| Plano de ação | Concluído |
| Documento de cenário de testes | Concluído |
| Implementação técnica | Não iniciada |
| Mudanças de banco | Não iniciadas |
| Gate de testes de código | Baseline interna concluída |

---

## 2. Resumo executivo

### Estado atual da operação

- A fase documental foi concluída.
- A baseline interna da Fase 1 foi concluída.
- A homologação interna sequencial de chatbot, autoresponder e bulk foi concluída sem tráfego real.
- A baseline local foi atualizada novamente em **2026-04-13** após novas mudanças no código e no banco.
- O sistema ainda não foi alterado funcionalmente nesta operação.
- O próximo passo recomendado é a **homologação real da Fase 1** com conta viva.

### Decisão estrutural já tomada

- A modernização será feita por fases.
- O sistema não será migrado em big bang.
- O contexto persistente em banco foi considerado viável, mas ficará para fase posterior.

---

## 3. Registro por fase

## Fase 0 - Governança, baseline e documentação

### Status

**Concluída**

### Objetivo

Criar a base documental e operacional da modernização da IA.

### Entregas

- Documento do funcionamento atual criado.
- Plano de ação criado.
- Documento de progresso criado.

### Arquivos gerados

- [ai-modernizacao-funcionamento-atual.md](/www/wwwroot/app_zapmatic_app/.agent/plans/ai-modernizacao-funcionamento-atual.md)
- [ai-modernizacao-plano-de-acao.md](/www/wwwroot/app_zapmatic_app/.agent/plans/ai-modernizacao-plano-de-acao.md)
- [ai-modernizacao-progresso.md](/www/wwwroot/app_zapmatic_app/.agent/plans/ai-modernizacao-progresso.md)

### Banco

- Nenhuma alteração.

### Código

- Nenhuma alteração funcional.

### Testes executados

- Verificação manual do mapeamento técnico do fluxo atual.
- Verificação da existência e integridade dos arquivos de documentação.

### Parecer técnico

- Como não houve alteração de código nesta fase, não existe risco funcional introduzido pela etapa.
- O sistema permanece no estado original.
- A base documental da operação está pronta.

### Decisão

**Apto para seguir para a Fase 1**

---

## Fase 1 - Observabilidade e testes de segurança operacional

### Status

**Concluída internamente / homologação real pendente**

### Objetivo

Criar base de logs, checklist repetível e baseline de smoke tests.

### Entregas

- Snapshot interno do estado atual do motor.
- Cenário oficial de testes criado.
- Critério de homologação real definido.

### Arquivos gerados

- [ai-modernizacao-cenario-de-testes.md](/www/wwwroot/app_zapmatic_app/.agent/plans/ai-modernizacao-cenario-de-testes.md)

### Banco

- Nenhuma alteração.

### Código

- Nenhuma alteração funcional.

### Testes internos executados

- `node --check app_zapmatic_api/waziper/extend.js`
- `node --check app_zapmatic_api/waziper/waziper.js`
- `php -l inc/core/Openai/Helpers/Openai_helper.php`
- `php -l inc/core/Openai/Controllers/Openai.php`
- `php -l inc/core/Whatsapp_chatbot/Controllers/Whatsapp_chatbot.php`
- `php -l inc/core/Whatsapp_chatbot/Views/ai_settings.php`
- Snapshot de baseline no banco:
  - `sp_whatsapp_ai`
  - `sp_whatsapp_chatbot`
  - `sp_whatsapp_autoresponder`
  - `sp_whatsapp_schedules`
  - `sp_accounts`
- Preparação do cenário real nas contas ativas:
  - chatbot de homologação criado para Baileys
  - chatbot de homologação criado para Cloud API
  - autoresponder de homologação preparado e mantido desativado
  - bulk de homologação montado em rascunho para Baileys
  - bulk de homologação montado em rascunho para Cloud API

### Resultado dos testes internos

- Sintaxe dos arquivos-chave: ok
- Configurações de IA encontradas: `7`
- Configurações de IA habilitadas: `5`
- Chatbots usando IA: `1`
- Autoresponders ativos: `7`
- Campanhas em execução no momento: `0`
- Contas WhatsApp ativas: `3`
- Cenário real preparado nas contas do time `245`:
  - Baileys: `69DB7FEEA20FE`
  - Cloud API: `CLD69D911C170A0B`
- Configuração de IA no time `245`: `0`
- Bulk de homologação criado:
  - `hmlg_bulk_ba_20260412`
  - `hmlg_bulk_cl_20260412`

### Homologação interna sequencial registrada em 2026-04-12

| Módulo | Status interno | Evidência |
| ------ | -------------- | --------- |
| Chatbot clássico | Homologado internamente | regras criadas nas contas reais, seleção validada pela lógica atual para match exato, parcial e `nextBot` |
| Autoresponder | Homologado internamente por regra | contas corretas, item preparado, lógica de `send_to`, `delay` e dedupe revisada e simulada |
| Bulk | Homologado internamente como rascunho seguro | campanhas criadas, grupo de teste vinculado, consulta de processamento mostra que rascunhos inativos não disparam |
| Chatbot com IA | Bloqueado nesta rodada | time `245` continua com `0` registros em `sp_whatsapp_ai` |
| OpenAI admin | Pendente de homologação funcional | apenas checagem de sintaxe nesta etapa |

### Evidências objetivas desta homologação interna

- Contas de homologação confirmadas:
  - Baileys `69DB7FEEA20FE` (`sp_accounts.id = 28`)
  - Cloud API `CLD69D911C170A0B` (`sp_accounts.id = 41`)
- Chatbots de homologação presentes e ativos nas duas contas:
  - exato
  - parcial
  - fluxo com `nextBot`
- Bots default de homologação presentes, porém desativados de propósito para não capturar mensagens reais por engano.
- Simulação determinística da regra atual confirmou:
  - `hmlg menu 1204` -> bot exato
  - `quero hmlg pix 1204 agora` -> bot parcial
  - `hmlg fluxo 1204` -> etapa 1
  - `hmlg proximo 1204` -> etapa 2
- Autoresponder preparado nas duas contas, ambos `status = 0`, com validação interna de:
  - envio em conversa de usuário
  - bloqueio para grupo pelo `send_to = 2`
  - bloqueio por `delay`
  - liberação após expirar o `delay`
- Bulk preparado em dois rascunhos inativos:
  - `hmlg_bulk_ba_20260412`
  - `hmlg_bulk_cl_20260412`
- Consulta operacional atual do bulk:
  - `0` campanhas elegíveis para execução automática no momento
- IA nas contas de homologação:
  - `0` configurações em `sp_whatsapp_ai` para o time `245`

### Refresh local registrado em 2026-04-13

Interpretação operacional adotada nesta atualização:

- não existe um vhost explícito chamado `localhost` neste aaPanel;
- a base local usada como referência foi:
  - painel/web: `/www/wwwroot/app_zapmatic_app`
  - domínio web: `zapmatic.tec.br`
  - domínio API: `serverzapmatic.zapmatic.tec.br`

Novas validações executadas:

- `node --check app_zapmatic_api/waziper/extend.js`
- `node --check app_zapmatic_api/waziper/waziper.js`
- `php -l inc/core/Openai/Helpers/Openai_helper.php`
- `php -l inc/core/Openai/Controllers/Openai.php`
- `php -l inc/core/Whatsapp_chatbot/Controllers/Whatsapp_chatbot.php`
- `php -l inc/core/Whatsapp_chatbot/Views/ai_settings.php`
- novo snapshot estrutural do banco
- leitura de arquivos modificados recentemente
- validação do mapeamento atual de vhost da base local

Snapshot atualizado do banco local:

- `sp_whatsapp_ai`: `7`
- `sp_whatsapp_ai` habilitadas: `5`
- `sp_whatsapp_chatbot`: `138`
- `sp_whatsapp_chatbot use_ai = 1`: `1`
- `sp_whatsapp_chatbot status = 1`: `134`
- `sp_whatsapp_chatbot run = 1`: `111`
- `sp_whatsapp_autoresponder`: `15`
- `sp_whatsapp_autoresponder ativos`: `7`
- `sp_whatsapp_schedules`: `21`
- `sp_whatsapp_schedules status = 1`: `0`
- `sp_whatsapp_schedules run = 1`: `0`
- `sp_accounts` ativas: `4`
- `sp_accounts` Cloud API: `3`
- `sp_accounts` Baileys: `2`

Diferenças relevantes em relação ao snapshot anterior:

- chatbots totais: `128 -> 138`
- chatbots ativos: `126 -> 134`
- chatbots em execução: `101 -> 111`
- autoresponder total: `14 -> 15`
- schedules totais: `19 -> 21`
- contas ativas: `3 -> 4`

Mudança de código visível no refresh:

- arquivo recente identificado: `inc/core/Whatsapp/Config.php` em `2026-04-13 16:51`

Parecer do refresh local:

- a base local continua íntegra em nível estrutural;
- a documentação anterior precisava mesmo de refresh porque o banco já mudou;
- o cenário de homologação montado para o time `245` continua preservado;
- a pendência central permanece igual: o time `245` segue sem configuração em `sp_whatsapp_ai`.

### Parecer técnico

- O sistema está estruturalmente íntegro para iniciar a operação.
- Não há sinal de quebra sintática nos pontos mais sensíveis.
- A superfície ativa de IA ainda é pequena, o que favorece uma migração controlada.
- O cenário de homologação real já foi materializado no banco sem tocar no código.
- O cenário cobre chatbot, autoresponder preparado e bulk em rascunho controlado.
- A homologação interna sequencial confirmou que os cenários clássicos estão coerentes com a lógica atual do motor.
- A homologação real continua obrigatória antes de abrir a Fase 2.
- O teste específico de IA nessas contas permanece pendente até existir configuração em `sp_whatsapp_ai` para o time `245`.

### Testes obrigatórios ao concluir

- Chatbot texto
- Chatbot `nextBot`
- Chatbot com IA atual
- Autoresponder
- Bulk
- OpenAI administrativo texto/imagem

### Parecer

- Baseline interna aprovada. Homologação real pendente.

### Decisão

- **Não abrir a Fase 2 antes do teste real com conexão viva**

---

## Fase 2 - Adapter de provedor

### Status

**Não iniciada**

### Parecer

- Pendente

---

## Fase 3 - Normalização de schema e configuração

### Status

**Não iniciada**

### Parecer

- Pendente

---

## Fase 4 - Contexto persistente em banco

### Status

**Não iniciada**

### Decisão preliminar

- Viável
- Recomendado em modelo híbrido

### Parecer

- Pendente

---

## Fase 5 - Modernização do OpenAI

### Status

**Não iniciada**

### Parecer

- Pendente

---

## Fase 6 - Gemini direto

### Status

**Não iniciada**

### Parecer

- Pendente

---

## Fase 7 - OpenRouter opcional

### Status

**Não iniciada**

### Parecer

- Pendente

---

## Fase 8 - Multimodal imagem e áudio

### Status

**Não iniciada**

### Parecer

- Pendente

---

## Fase 9 - IA no autoresponder

### Status

**Não iniciada**

### Parecer

- Pendente

---

## Fase 10 - IA no bulk

### Status

**Não iniciada**

### Parecer

- Pendente

---

## 4. Template obrigatório para atualização futura

Sempre que uma fase for executada, registrar neste formato:

### Nome da fase

- Status:
- Objetivo:
- Arquivos alterados:
- Mudanças de banco:
- Mudanças de configuração:
- Testes executados:
- Resultado dos testes:
- Riscos encontrados:
- Pendências:
- Parecer técnico:
- Decisão: avançar / não avançar

---

## 5. Regras de governança desta operação

- Não avançar fase sem atualizar este documento.
- Não avançar fase sem parecer técnico.
- Não avançar fase sem testes mínimos da etapa.
- Qualquer regressão crítica interrompe o avanço.
- Toda alteração estrutural deve ser rastreada aqui.

---

## 6. Próximo passo recomendado

Próxima fase operacional:

- **Fase 1 - Observabilidade e testes de segurança operacional**

Objetivo imediato:

- estabelecer checklist repetível de validação antes de tocar no adapter e no banco.
