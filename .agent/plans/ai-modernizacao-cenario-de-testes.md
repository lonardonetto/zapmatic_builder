# Cenário de Testes - Modernização da IA

> Documento operacional de baseline interna e homologação real  
> Uso: apoiar cada etapa da modernização sem quebrar chatbot, autoresponder, bulk e OpenAI admin

---

## 1. Objetivo

Este documento define:

- a baseline interna do estado atual;
- os cenários mínimos de teste por módulo;
- os campos que devem ser preparados para homologação real;
- os critérios de aprovação antes de avançar de fase.

---

## 2. Baseline interna registrada em 2026-04-12

### 2.1. Verificações de sintaxe

Executado com sucesso:

- `node --check app_zapmatic_api/waziper/extend.js`
- `node --check app_zapmatic_api/waziper/waziper.js`
- `php -l inc/core/Openai/Helpers/Openai_helper.php`
- `php -l inc/core/Openai/Controllers/Openai.php`
- `php -l inc/core/Whatsapp_chatbot/Controllers/Whatsapp_chatbot.php`
- `php -l inc/core/Whatsapp_chatbot/Views/ai_settings.php`

### 2.2. Snapshot do banco

Estado encontrado:

- `sp_whatsapp_ai`: `7` configs, `5` habilitadas
- modelos em uso:
  - `gpt-4-turbo`: `4`
  - `gpt-3.5-turbo`: `3`
- `sp_whatsapp_chatbot`: `128` itens
  - `use_ai = 1`: `1`
  - `status = 1`: `126`
  - `run = 1`: `101`
- bots default ativos: `0`
- `sp_whatsapp_autoresponder`: `14`
  - ativos: `7`
- `sp_whatsapp_schedules`: `19`
  - `status = 1`: `0`
  - `run = 1`: `0`
- `sp_accounts` WhatsApp:
  - total: `5`
  - ativos: `3`
  - Cloud API: `3`
  - Baileys: `2`

### 2.3. Leitura técnica da baseline

Conclusões:

- o motor está íntegro do ponto de vista estrutural;
- a superfície ativa de IA ainda é pequena, o que reduz risco de rollout;
- não há campanha em execução neste momento;
- a homologação real vai depender da conexão viva que você preparar.

### 2.4. Homologação interna sequencial registrada em 2026-04-12

Status desta rodada:

| Módulo | Status | Observação |
| ------ | ------ | ---------- |
| Chatbot clássico | Homologado internamente | cenário montado nas contas reais e regra de match validada por simulação determinística |
| Chatbot com IA | Pendente | time `245` ainda não possui configuração em `sp_whatsapp_ai` |
| Autoresponder | Homologado internamente por regra | itens preparados, porém mantidos desativados para não responder contato real por engano |
| Bulk | Homologado internamente como rascunho seguro | campanhas criadas e validadas sem ativação |
| OpenAI admin | Pendente | sem chamada funcional à API nesta rodada |

Evidências objetivas:

- conta Baileys homologada: `69DB7FEEA20FE`
- conta Cloud homologada: `CLD69D911C170A0B`
- simulação da seleção do chatbot confirmou:
  - `hmlg menu 1204` -> `[HOMOLOG][BAILEYS] Exato`
  - `quero hmlg pix 1204 agora` -> `[HOMOLOG][BAILEYS] Parcial`
  - `hmlg fluxo 1204` -> `[HOMOLOG][BAILEYS] Fluxo 1`
  - `hmlg proximo 1204` -> `[HOMOLOG][BAILEYS] Fluxo 2`
- autoresponder validado internamente para:
  - enviar em conversa de usuário sem histórico
  - bloquear grupos
  - bloquear nova resposta dentro do `delay`
  - liberar resposta após expirar o `delay`
- bulk validado internamente para:
  - não processar enquanto os rascunhos estiverem com `status = 0`
  - tornar-se elegível quando ativado conscientemente no painel

Importante:

- esta homologação é interna e não substitui o teste vivo no WhatsApp;
- nenhuma mensagem real foi disparada nesta rodada;
- o cenário foi mantido seguro para evitar impacto em contatos reais.

### 2.5. Refresh local registrado em 2026-04-13

Para evitar ambiguidade nesta operação, o termo `localhost` foi interpretado como a base local atual desta máquina:

- aplicação web: `/www/wwwroot/app_zapmatic_app`
- vhost web: `zapmatic.tec.br`
- vhost API: `serverzapmatic.zapmatic.tec.br`

Novo snapshot coletado em 2026-04-13:

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
- contas WhatsApp ativas: `4`
- contas Cloud API: `3`
- contas Baileys cadastradas: `2`

Diferenças percebidas desde o snapshot anterior:

- aumento de `10` chatbots no total
- aumento de `8` chatbots ativos
- aumento de `10` chatbots em execução
- aumento de `1` autoresponder cadastrado
- aumento de `2` schedules
- aumento de `1` conta ativa

Leituras adicionais desta atualização:

- sintaxe dos arquivos críticos permaneceu íntegra;
- o arquivo de código mais claramente alterado na leitura rápida foi `inc/core/Whatsapp/Config.php`;
- o cenário de homologação do time `245` segue intacto;
- o bloqueio do teste de IA continua o mesmo: `0` registros em `sp_whatsapp_ai` para o time `245`.

---

## 3. Massa mínima de teste recomendada

Para homologação real, preparar:

### 3.1. Contas

- `1` conta Baileys ativa
- `1` conta Cloud API ativa

### 3.1.1. Contas preparadas nesta operação

Assumindo o time ativo `245`, foram preparados os cenários nestas conexões:

- **Baileys**
  - nome: `5521970402529@s.whatsapp.net`
  - token: `69DB7FEEA20FE`
- **Cloud API**
  - nome: `Zapmatic Tech`
  - token: `CLD69D911C170A0B`

Observação:

- existe outra conta Cloud ativa no time, mas o cenário desta operação foi isolado apenas na conta acima para reduzir risco de interferência.

### 3.2. Contatos

- `1` número de teste principal
- `1` número secundário opcional
- `1` grupo opcional para validar `send_to`

### 3.3. Itens de chatbot

Criar ou reservar estes itens:

1. `BOT_EXATO`
   - keyword: `menu teste`
   - tipo: exato
   - resposta: texto simples

2. `BOT_PARCIAL`
   - keyword: `pix`
   - tipo: parcial
   - resposta: texto simples

3. `BOT_NEXT_1`
   - keyword: `fluxo venda`
   - resposta: texto simples
   - `nextBot`: `BOT_NEXT_2`

4. `BOT_NEXT_2`
   - sem keyword obrigatória
   - resposta: texto simples

5. `BOT_IA`
   - keyword: `falar com ia`
   - `use_ai = 1`
   - usar config atual da conta

6. `BOT_DEFAULT`
   - bot default ativo
   - resposta padrão de fallback

### 3.3.1. Itens realmente preparados no banco

Foram criados os seguintes itens ativos e isolados:

- **Baileys**
  - `[HOMOLOG][BAILEYS] Exato`
    - keyword: `hmlg menu 1204`
  - `[HOMOLOG][BAILEYS] Parcial`
    - keyword: `hmlg pix 1204`
  - `[HOMOLOG][BAILEYS] Fluxo 1`
    - keyword: `hmlg fluxo 1204`
    - `nextBot`: `hmlg proximo 1204`
  - `[HOMOLOG][BAILEYS] Fluxo 2`
    - keyword: `hmlg proximo 1204`
  - `[HOMOLOG][BAILEYS] Default`
    - keyword auxiliar: `hmlg default 1204`
    - criado **desativado** para não capturar mensagens reais por engano

- **Cloud API**
  - `[HOMOLOG][CLOUD] Exato`
    - keyword: `hmlg menu 1204`
  - `[HOMOLOG][CLOUD] Parcial`
    - keyword: `hmlg pix 1204`
  - `[HOMOLOG][CLOUD] Fluxo 1`
    - keyword: `hmlg fluxo 1204`
    - `nextBot`: `hmlg proximo 1204`
  - `[HOMOLOG][CLOUD] Fluxo 2`
    - keyword: `hmlg proximo 1204`
  - `[HOMOLOG][CLOUD] Default`
    - keyword auxiliar: `hmlg default 1204`
    - criado **desativado** para não capturar mensagens reais por engano

### 3.3.2. IA nesta conta

Estado atual do time `245`:

- `0` configurações em `sp_whatsapp_ai`

Impacto:

- o teste de chatbot com IA (`T04`) **não pode ser homologado ainda** nessas contas;
- os testes de chatbot clássico, fluxo, autoresponder e parte de mídia já podem ser executados;
- a homologação de IA ficará pendente até existir configuração da IA para esse time.

### 3.4. Itens de mídia

Separar estes arquivos para envio manual:

- `1` imagem com legenda
- `1` imagem sem legenda
- `1` vídeo com legenda
- `1` áudio curto

### 3.5. Autoresponder

Configuração mínima:

- ativo
- `delay` curto para teste
- sem contatos em `except`
- resposta simples

### 3.5.1. Autoresponder preparado

Foi deixado preparado, porém desativado, para evitar resposta indevida em contato real:

- Baileys:
  - caption: `[HOMOLOG BAILEYS] Autoresponder pronto para teste.`
  - status: `0`
- Cloud API:
  - caption: `[HOMOLOG CLOUD] Autoresponder pronto para teste.`
  - status: `0`

Orientação:

- só ativar durante a janela do teste real;
- desativar novamente após concluir os casos `T10` e `T11`.

### 3.6. Bulk

Preparar:

- `1` campanha simples
- `2` contatos válidos
- `1` mensagem curta

### 3.6.1. Bulk realmente preparado no banco

Foram criados dois rascunhos de campanha, ambos **inativos** (`status=0`), para evitar disparo automático:

- **Baileys**
  - campanha: `[HOMOLOG][BAILEYS] Bulk Draft`
  - `ids`: `hmlg_bulk_ba_20260412`
  - conta: `["28"]`
  - grupo: `TESTING` (`id=97`)

- **Cloud API**
  - campanha: `[HOMOLOG][CLOUD] Bulk Draft`
  - `ids`: `hmlg_bulk_cl_20260412`
  - conta: `["41"]`
  - grupo: `TESTING` (`id=97`)

Grupo de teste associado:

- `5521970402529`
- `551231993269`
- `5521968666544`

Observação crítica:

- os rascunhos estão prontos para revisão no painel;
- eles **não foram ativados**;
- a execução deve ser manual e consciente, porque o grupo `TESTING` contém números reais cadastrados.

---

## 4. Cenários mínimos obrigatórios

## T01 - Chatbot texto exato

- Canal: Baileys e Cloud API
- Entrada: `menu teste`
- Esperado: dispara `BOT_EXATO`
- Aprovação: resposta correta e sem duplicidade

## T02 - Chatbot texto parcial

- Canal: Baileys e Cloud API
- Entrada: `quero pagar no pix`
- Esperado: dispara `BOT_PARCIAL`
- Aprovação: match parcial funcionando

## T03 - Chatbot com nextBot

- Canal: Baileys e Cloud API
- Entrada: `fluxo venda`
- Esperado:
  - primeiro responde `BOT_NEXT_1`
  - depois dispara `BOT_NEXT_2`
- Aprovação: cadeia automática completa

## T04 - Chatbot com IA

- Canal: Baileys e Cloud API
- Entrada: `falar com ia`
- Pré-requisito: existir configuração em `sp_whatsapp_ai` para a conta/time
- Estado atual: **pendente por ausência de configuração de IA no time 245**
- Aprovação futura: resposta gerada e sem travar o fluxo

## T05 - Default bot

- Canal: Baileys e Cloud API
- Entrada: frase sem keyword conhecida
- Esperado: resposta do bot default
- Aprovação: fallback funcionando

## T06 - Imagem com legenda

- Canal: Baileys e Cloud API
- Entrada: imagem + legenda de teste
- Esperado atual:
  - o sistema considera a legenda como conteúdo
  - chatbot/autoresponder continuam operando
- Aprovação: sem erro de processamento

## T07 - Imagem sem legenda

- Canal: Baileys e Cloud API
- Entrada: imagem sem texto
- Esperado atual:
  - conteúdo cai como placeholder visual
  - sistema não quebra
- Aprovação: mensagem processada sem crash

## T08 - Vídeo com legenda

- Canal: Baileys e Cloud API
- Entrada: vídeo com legenda
- Esperado atual:
  - legenda entra no motor
  - fluxo continua
- Aprovação: sem erro no inbound

## T09 - Áudio

- Canal: Baileys e Cloud API
- Entrada: áudio curto
- Esperado atual:
  - sem transcrição nativa
  - sistema processa sem travar
- Aprovação: sem erro estrutural

## T10 - Autoresponder primeira mensagem

- Canal: Baileys e Cloud API
- Entrada: primeira mensagem do contato
- Esperado: autoresponder responde uma vez
- Aprovação: sem duplicidade

## T11 - Autoresponder delay/dedupe

- Canal: Baileys e Cloud API
- Entrada: repetir mensagem dentro da janela
- Esperado: não duplicar resposta indevidamente
- Aprovação: dedupe e delay respeitados

## T12 - Bulk simples

- Canal: Baileys e Cloud API, conforme disponibilidade
- Entrada: campanha com 2 contatos
- Esperado:
  - fila processa
  - contadores atualizam
  - campanha não trava
- Aprovação: `sent/failed` atualizados corretamente

## T13 - OpenAI admin texto

- Canal: painel administrativo
- Entrada: prompt simples
- Esperado: geração de texto
- Aprovação: resposta retornada sem erro

## T14 - OpenAI admin imagem

- Canal: painel administrativo
- Entrada: prompt simples de imagem
- Esperado: URL/retorno de imagem
- Aprovação: resposta retornada sem erro

---

## 5. Ficha de execução da homologação real

Preencher durante o teste vivo:

### Identificação

- Data:
- Ambiente:
- Operador:
- Conta Baileys:
- Conta Cloud API:
- Número de teste:

### Resultado por caso

| Caso | Canal | Status | Evidência | Observação |
| ---- | ----- | ------ | --------- | ---------- |
| T01 | Baileys | Pendente |  |  |
| T01 | Cloud | Pendente |  |  |
| T02 | Baileys | Pendente |  |  |
| T02 | Cloud | Pendente |  |  |
| T03 | Baileys | Pendente |  |  |
| T03 | Cloud | Pendente |  |  |
| T04 | Baileys | Pendente |  |  |
| T04 | Cloud | Pendente |  |  |
| T05 | Baileys | Pendente |  |  |
| T05 | Cloud | Pendente |  |  |
| T06 | Baileys | Pendente |  |  |
| T06 | Cloud | Pendente |  |  |
| T07 | Baileys | Pendente |  |  |
| T07 | Cloud | Pendente |  |  |
| T08 | Baileys | Pendente |  |  |
| T08 | Cloud | Pendente |  |  |
| T09 | Baileys | Pendente |  |  |
| T09 | Cloud | Pendente |  |  |
| T10 | Baileys | Pendente |  |  |
| T10 | Cloud | Pendente |  |  |
| T11 | Baileys | Pendente |  |  |
| T11 | Cloud | Pendente |  |  |
| T12 | Baileys | Pendente |  |  |
| T12 | Cloud | Pendente |  |  |
| T13 | Admin | Pendente |  |  |
| T14 | Admin | Pendente |  |  |

---

## 6. Critério para seguir de fase

Antes de abrir a Fase 2:

- chatbot deve passar nos cenários base;
- autoresponder deve responder sem duplicidade;
- bulk simples deve processar sem travar;
- OpenAI admin deve continuar funcional;
- não pode existir erro crítico novo de sintaxe ou bootstrap.

Se falhar:

- registrar a falha;
- não avançar de fase;
- corrigir primeiro a baseline.

---

## 7. Parecer operacional atual

Status neste momento:

- baseline interna concluída;
- homologação interna sequencial concluída para chatbot clássico, autoresponder e bulk;
- baseline local atualizada novamente em `2026-04-13`;
- IA no chatbot dessas contas segue pendente por ausência de configuração em `sp_whatsapp_ai`;
- cenário de homologação real pronto;
- aguardando conexão real do usuário para executar a bateria viva.
