# Relatorio de testes WhatsApp - 2026-06-04

## Escopo

- Validacao de `Cloud API` para `autoresponder` e `chatbot`
- Validacao de `Baileys` para `autoresponder` e `chatbot`
- Regressao de disparo em massa nas 3 conexoes
- Diagnostico e correcao da funcao de ligacao (`bulk_call`)

## Resumo executivo

O ambiente ficou estavel nos quatro pontos validados nesta rodada:

- `Cloud API`: `autoresponder` e `chatbot` funcionando nas duas conexoes testadas
- `Baileys`: `autoresponder` e `chatbot` funcionando na conexao final `2529`
- `Bulk de mensagens`: funcionamento confirmado nas 3 conexoes testadas
- `Ligacao`: falha real identificada no codigo, corrigida, e campanha de ligacao voltou a executar

Nao houve mudanca de rota publica. O ajuste de codigo ficou restrito ao Node.

## Correcao aplicada na ligacao

Arquivo alterado:

- [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:501)

Correcao aplicada:

- Inclusao de `get_session_user_ids` e `is_same_jid_user` no destructuring de `callResponderRuntime`

Motivo:

- A campanha de ligacao quebrava em runtime com `ReferenceError: get_session_user_ids is not defined` antes mesmo de enviar a oferta de chamada.

## Evidencia do bug original

O erro ficou registrado em runtime no processamento da schedule `116`:

- [app_zapmatic_api_runtime.log](/www/wwwroot/app_zapmatic_app/writable/logs/app_zapmatic_api_runtime.log:3846)

Trecho observado:

- `bulk process 116 next account null`
- `Processing bulk item 116, account 69FB46BE69E37, to 5521970402529`
- `Calling auto_call_campaign for item 116`
- `ReferenceError: get_session_user_ids is not defined`

## Evidencia da correcao

O helper ausente agora esta importado e usado no fluxo da campanha:

- [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:501)
- [waziper.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js:2299)
- [callresponder_runtime.js](/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/callresponder_runtime.js:408)

Tambem foi feita checagem estatica de sintaxe:

- `node --check app_zapmatic_api/waziper/waziper.js` -> sem erro

## Resultado dos testes

### 1. Cloud API

Status final:

- `OK` para `autoresponder`
- `OK` para `chatbot`

Observacao:

- Nesta rodada, o problema de `Cloud API` que antes ficava "digitando" sem enviar foi tratado e voltou a responder corretamente nas duas conexoes testadas.

### 2. Baileys

Status final:

- `OK` para `autoresponder`
- `OK` para `chatbot`

Observacao:

- O ajuste foi feito sem regressao no que ja tinha sido estabilizado em `Cloud API`.

### 3. Disparo em massa de mensagens

Status final:

- `OK` nas 3 conexoes testadas

Observacao:

- Os envios observados durante a validacao de massa passaram a executar sem quebrar os ajustes anteriores de `single`, `autoresponder` e `chatbot`.

### 4. Ligacao / bulk call

Status final:

- `OK` apos correcao de runtime

Evidencia persistida em historico:

- [sp_whatsapp_history] validado por consulta direta no banco
- Eventos confirmados:
  - `885` -> `5521970402529` -> `bulk_call` -> `Cannot start a call to the same connected WhatsApp account`
  - `886` -> `557791586374` -> `bulk_call` -> `CALL OFFER SENT`
  - `887` -> `5521968666544` -> `bulk_call` -> `CALL OFFER SENT`
  - `888` -> `5511964157039` -> `bulk_call` -> `CALL OFFER SENT`
  - `891` -> `558588940623` -> `bulk_call` -> `CALL OFFER SENT`
  - `892` -> `556294221380` -> `bulk_call` -> `CALL OFFER SENT`
  - `894` -> `558588940623` -> `bulk_call_event` -> `CALL ACCEPT`
  - `895` -> `5511964157039` -> `bulk_call_event` -> `CALL ACCEPT`
  - `898` -> `556294221380` -> `bulk_call_event` -> `CALL TIMEOUT`
  - `899` -> `5521968666544` -> `bulk_call_event` -> `CALL TIMEOUT`

Leitura tecnica:

- A funcao de ligacao voltou a executar normalmente para numeros terceiros.
- O proprio numero conectado (`5521970402529`) agora falha de forma controlada, com mensagem explicita, em vez de derrubar a campanha.

## Estado do processo

- Processo `zapmatic` confirmado como `online` no PM2 durante a validacao final.

## Risco residual

Nao existe bug critico em aberto no fluxo validado, mas ha um ajuste preventivo recomendado:

- impedir na origem que uma campanha de ligacao inclua o proprio numero conectado

Isso nao bloqueia o uso atual, porque o backend ja rejeita esse caso com seguranca, mas melhora a experiencia e evita tentativa inutil.

## Conclusao

O ambiente ficou funcional para:

- `Cloud API` mensagens automatizadas
- `Baileys` mensagens automatizadas
- disparo em massa de mensagens
- campanha de ligacao

O unico defeito confirmado na etapa de ligacao era um erro de codigo em runtime, e ele foi corrigido sem alterar as rotas publicas nem mexer nos fluxos que ja estavam funcionando.
